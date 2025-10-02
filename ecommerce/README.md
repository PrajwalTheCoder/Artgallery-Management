# Simple PHP E‑Commerce Demo

A minimal session-based cart example (no frameworks).

## Requirements
- XAMPP (Apache + MySQL) running on Windows
- PHP PDO extension (included in XAMPP)

## 1. Create the database & sample data
Open phpMyAdmin (http://localhost/phpmyadmin), create the database by running the contents of `schema.sql` OR from a terminal:

```sql
-- In phpMyAdmin SQL tab paste:
SOURCE c:/xampp/htdocs/ecommerce/schema.sql;
```

If SOURCE path fails in phpMyAdmin, just paste the SQL from the file manually (omit the first 2 lines if DB already created and selected).

## 2. Configure DB credentials
Copy the root `.env.example` file to `.env` (in the repository root) and adjust values for your local MySQL setup:

```
cp .env.example .env   # On Windows manually copy the file via Explorer
```

Relevant keys for the e‑commerce module:

```
ECOM_DB_HOST=localhost
ECOM_DB_PORT=3306
ECOM_DB_USER=root
ECOM_DB_PASS=your_password_here
ECOM_DB_NAME=ecommerce_simple
```

The code now loads credentials from environment variables (see `env.php` + `db_connect.php`). `db.php` remains for legacy inclusion but no longer stores secrets.

## 3. Place sample product images (optional)
Add images under `images/` with the names used in `schema.sql` (`mouse.jpg`, `keyboard.jpg`, etc.) or replace with your own. If missing, browser will show broken image icon; you can also change `image` values in DB.

## 4. Run the site
Ensure Apache and MySQL are started in XAMPP Control Panel.

Visit: http://localhost/ecommerce/

## 5. Features
- Product listing (`index.php`)
- Product detail (`product.php?id=...`)
- Add to cart (session storage)
- View / remove cart items (`cart.php`)
- Simple checkout that clears cart (`checkout.php`)

## 6. Security / Demo Notes
This is intentionally simplified. For production you would need:
- CSRF protection on forms
- Input validation & escaping (partially demonstrated)
- Persistent orders & users tables
- Authentication, payments, inventory management

## 7. Troubleshooting
| Problem | Fix |
|---------|-----|
| DB connection failed | Check `db.php` credentials & MySQL running |
| Blank product list | Confirm `products` table has rows; re-run `schema.sql` |
| Images broken | Add correct files to `images/` or update `image` field values |
| Session not persisting | Ensure `session_start()` is first output; check php.ini session.save_path |

## 8. Next Improvements (Ideas)
- Admin CRUD for products (a start file `admin/add_product.php` exists?)
- Order persistence
- Login / registration
- Pagination & search

Enjoy hacking!
