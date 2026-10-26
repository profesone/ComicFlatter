# Comic-Subs

Helping comic creators, create comics.

Comic-Subs is a studio suite for people making comics. It takes the slow production chores off the page so a creator can stay with the story: flatting a penciled page, placing word balloons, and finding other creators to hire.

- **Comic Flatter** turns black-and-white line art into flat-color PNGs for Photoshop and Clip Studio. **$4.99** per page, up to **4** attempts, and several pages can be bought at once.
- **Comic Balloon Popper** maps a script onto a page and draws a transparent balloon overlay.
- **Creator board** is a free listing board. Anyone can read posts. A logged-in user can publish one.

PHP 8.4+ on DreamHost Shared. Operated by 500 Lines Media LLC.

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
