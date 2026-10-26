# ComicFlats

Upload black-and-white comic line art and get flat-color PNGs for Photoshop and Clip Studio.

**$4.99** one-time · up to **4** attempts · PHP 8.4+ on DreamHost Shared.

## Stack

- Vanilla PHP 8.4 (front controller in `public/`)
- MySQL 8 (PDO)
- Stripe Checkout
- Local disk storage (`storage/uploads`)
- Imagick flatting pipeline (binarize, flood-fill regions, trap, strip inks)
- Email/password accounts (Argon2id)

## Local

```bash
composer install
cp .env.example .env
# fill DB + secrets, then:
php bin/migrate.php
php -S localhost:8080 -t public
```

Open http://localhost:8080.

Set `FREE_MODE=true` to skip Stripe while testing.

## DreamHost

Point the domain **web directory** at this project’s `public/` folder, select PHP 8.4 or 8.5, create a MySQL database, and follow [SETUP.md](SETUP.md).
