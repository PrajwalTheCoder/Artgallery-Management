# PHP Multi-App Demo (Art Gallery + E‑Commerce)

This repository contains two small vanilla PHP demo applications sharing a lightweight environment loader (`env.php`).

## Apps

1. Art Gallery (`/art_gallery`)
   - Manage artists, artworks, sales records.
   - Uses MySQLi.

2. E‑Commerce (`/ecommerce`)
   - Simple product catalog, cart, checkout.
   - Uses PDO.

Both now load configuration values from environment variables via a root `.env` file (NOT committed). See `.env.example` for keys.

## Quick Start

1. Copy `.env.example` to `.env` and set credentials (do NOT commit `.env`).
2. Ensure MySQL databases exist:
   - `art_gallery_db`
   - `ecommerce_simple`
3. Start Apache & MySQL (XAMPP Control Panel).
4. Visit:
   - `http://localhost/art_gallery/`
   - `http://localhost/ecommerce/`

## Environment Variables (Core)
| Key | Purpose |
|-----|---------|
| DB_HOST / DB_PORT / DB_USER / DB_PASS | Shared fallbacks |
| ART_DB_NAME / HOST / PORT / USER / PASS | Art Gallery DB override |
| ECOM_DB_NAME / HOST / PORT / USER / PASS | E‑Commerce DB override |
| HERO_IMAGE | Optional hero image filename (in `ecommerce/images/`) |

## Repository Structure (Trimmed)
```
art_gallery/
  config.php
  ...
ecommerce/
  config.php
  db_connect.php
  ...
.env.example
env.php
```

## Security Notes
- No real secrets in repository.
- `.env` is ignored via `.gitignore`.
- Demo only: add validation, auth, CSRF protection, prepared statements review before production.

## GitHub Push
After you supply the remote URL (e.g. `https://github.com/<user>/<repo>.git`):
```
git remote add origin <REMOTE_URL>
git branch -M main
git push -u origin main
```

## Next Ideas
- Add migrations script.
- Switch Art Gallery to PDO for consistency.
- Add Docker Compose for one-command startup.

Enjoy experimenting!
