# ComicFlats

Upload black-and-white comic line art → get professional flats PNGs in seconds.

One-time **$4.99** Stripe Checkout · up to **4** generation attempts · Next.js 16 on Vercel.

## Stack

- **Next.js 16** (App Router) + TypeScript + Tailwind + shadcn/ui
- **Stripe Checkout** (one-time payment) + webhooks
- **Neon / Vercel Postgres** via Drizzle ORM
- **Vercel Blob** for input/output images
- **Replicate** image-to-image flatting (local sharp posterize fallback)
- Signed **httpOnly cookie** session (no login required)
- Optional **Upstash** rate limiting

## Quick start

```bash
npm install
cp .env.example .env.local
# fill in keys, then:
npm run db:push   # or run drizzle/0000_init.sql against your DB
npm run dev
```

Open [http://localhost:3000](http://localhost:3000).

### Free vs paying mode

In `.env.local`:

```bash
FREE_MODE=true   # testing — no payment, studio unlocks freely
FREE_MODE=false  # paying mode — Stripe Checkout required
```

With `FREE_MODE=true`, CTAs skip Stripe and `/studio` auto-creates a free job session.

### Stripe CLI (local webhooks)

```bash
stripe listen --forward-to localhost:3000/api/webhook/stripe
```

Put the printed `whsec_...` into `STRIPE_WEBHOOK_SECRET`.

Success URL hits `/api/success`, which verifies payment, marks the job paid, sets the session cookie, and redirects to `/studio`.

## Scripts

| Script | Description |
| --- | --- |
| `npm run dev` | Dev server |
| `npm run build` | Production build |
| `npm run db:push` | Push Drizzle schema |
| `npm run db:studio` | Drizzle Studio |

## Environment

See [`.env.example`](.env.example). Minimum for a real paid flow:

- `DATABASE_URL`
- `STRIPE_SECRET_KEY` + `STRIPE_WEBHOOK_SECRET`
- `BLOB_READ_WRITE_TOKEN`
- `SESSION_SECRET` (≥ 32 chars)
- `NEXT_PUBLIC_APP_URL`
- `REPLICATE_API_TOKEN` (recommended; otherwise local posterize fallback)

## Deploy (Vercel)

1. Import the repo on Vercel.
2. Add env vars from `.env.example`.
3. Create a Neon Postgres database and run `drizzle/0000_init.sql` (or `npm run db:push`).
4. Create a Vercel Blob store and copy the token.
5. Point Stripe webhook to `https://YOUR_DOMAIN/api/webhook/stripe` for `checkout.session.completed`.

## Product flow

1. Landing → Checkout ($4.99)
2. Stripe webhook / success route marks job `paid` and sets cookie
3. `/studio` upload (PNG/JPG/TIFF/WEBP, validated as mostly B&W)
4. Process via Replicate (+ line composite) → downloadable PNG
5. Regenerate up to 4 attempts per payment
