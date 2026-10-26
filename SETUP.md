# Setup — ComicFlats on DreamHost Shared

## Checklist

| Variable | Required | Source |
| --- | --- | --- |
| `APP_URL` | Yes | Your HTTPS site URL |
| `APP_KEY` | Yes | `openssl rand -base64 32` |
| `DB_*` | Yes | DreamHost panel → MySQL Databases |
| `FREE_MODE` | Yes | `true` locally, `false` in production |
| `STRIPE_SECRET_KEY` | Paid mode | https://dashboard.stripe.com/apikeys |
| `STRIPE_WEBHOOK_SECRET` | Paid mode | Stripe webhook endpoint |
| `COMICFLATTER_PRICE_ID` | Paid mode | Stripe product `prod_...` or price `price_...` |
| `COMICBALLOON_POPPER_PRICE_ID` | Balloon checkout | Stripe product or price id |
| `SEGMENTER` | Optional | `flood` (default). `sam` is reserved and not wired |
| `MAIL_FROM` | Password reset | An address on your domain |

## DreamHost steps

1. Upload the project (Git or SFTP) outside or inside the domain folder.
2. Websites → your domain → **Web directory** must end in `/public` (example: `/home/USER/example.com/public`).
3. Set PHP to **8.4** or **8.5**.
4. Create a MySQL database and user. Allow the web server hostname. Copy host, database, user, and password into `.env`.
5. SSH in and run:

```bash
cd /home/USER/example.com
curl -sS https://getcomposer.org/installer | /usr/local/php84/bin/php
/usr/local/php84/bin/php composer.phar install --no-dev
cp .env.example .env
# edit .env
/usr/local/php84/bin/php bin/migrate.php
mkdir -p storage/uploads storage/logs
chmod -R u+rwX storage
```

6. Turn on Let’s Encrypt for the domain.
7. Stripe → Developers → Webhooks → add `https://YOUR_DOMAIN/webhook/stripe` for `checkout.session.completed`. Paste the signing secret into `STRIPE_WEBHOOK_SECRET`.
8. Set `FREE_MODE=false` when you want real checkout.

## Flatting pipeline

Generation runs in PHP with the **Imagick** extension (required). Confirm with `php -m | grep imagick`.

Each upload is turned into:

1. A PNG, oriented, with the long edge capped at **2400px**
2. A black-and-white threshold
3. A transparent line-art PNG
4. Hard-edged flat colors from a flood fill of closed regions (`SEGMENTER=flood`)
5. A 2px trap under the ink, then the black ink is removed from the flats file

The studio offers **Download flats** (no inks, for the magic wand) and **Download line art** (stack this above the flats).

`SEGMENTER=sam` selects `SamSegmenter`, which is only a hook for a future SAM 2 HTTP service. It is not connected to a GPU. Leave `SEGMENTER` unset or set it to `flood`.

`REPLICATE_API_TOKEN` is unused by this pipeline.

## Stripe test cards

Use test keys (`sk_test_`) and card `4242 4242 4242 4242` while `FREE_MODE=false`.

## Security notes

- Document root must be `public/`. The app, `.env`, and `storage/` stay outside the web root.
- Passwords are hashed with Argon2id. Sessions are HttpOnly and Secure on HTTPS.
- Every form posts a CSRF token. Login attempts are rate limited.
- Jobs are scoped to the logged-in user.
