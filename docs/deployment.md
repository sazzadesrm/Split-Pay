# Deployment

## This environment (StackCP shared hosting)

| Setting | Value |
|---|---|
| App URL | `https://sazzad.co.financial` |
| Root directory | `public_html/sazzad.co.financial` |
| DB host | `sdb-89.hosting.stackcp.net` |
| DB name | `splitpay-353131377148` |
| DB user | `splitpay` |

Shared hosting like StackCP usually cannot point the web server's document
root at a `public/` subfolder, so this repo ships two `.htaccess` files:

- **Root `.htaccess`** (in `public_html/sazzad.co.financial/`) rewrites every
  request into `public/`, so `app/`, `config/`, `database/`, `storage/`,
  `vendor/` and `.env` stay unreachable from the web even though they live
  alongside `public/` in the same account directory.
- **`public/.htaccess`** does the actual front-controller routing to
  `index.php` and sets security headers.

If your host *can* set the document root directly to `public/`, do that
instead and delete the root `.htaccess` — it is only a compatibility shim.

### Steps

1. Upload the full repository into `public_html/sazzad.co.financial/`.
2. Create `.env` from `.env.example` (never commit `.env`) with:
   ```
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://sazzad.co.financial
   SESSION_SECURE_COOKIE=true
   DB_HOST=sdb-89.hosting.stackcp.net
   DB_DATABASE=splitpay-353131377148
   DB_USERNAME=splitpay
   DB_PASSWORD=<the database password>
   STORAGE_PATH=/home/<account>/public_html/sazzad.co.financial/storage
   RECEIPT_STORAGE_PATH=/home/<account>/public_html/sazzad.co.financial/storage/receipts
   AVATAR_STORAGE_PATH=/home/<account>/public_html/sazzad.co.financial/storage/avatars
   ```
   Use an absolute filesystem path for `STORAGE_PATH` (ask your host for the
   account's home directory path). `storage/` sits next to `public/`, not
   inside it, so it is never web-accessible even without the rewrite rule.
3. Run the migrations (via SSH if available, otherwise a one-off PHP CLI
   trigger your host provides):
   ```
   php database/migrate.php          # schema only
   php database/migrate.php --seed   # schema + demo data (non-production only)
   ```
   If shell access isn't available, import `database/schema.sql` through
   phpMyAdmin/Adminer instead.
4. Ensure `storage/receipts`, `storage/avatars`, `storage/logs`,
   `storage/exports` are writable by PHP (0750 recommended) and confirm they
   are **not** served by the web server (test `https://sazzad.co.financial/storage/`
   returns 403/404).
5. Set up a cron job (StackCP → Scheduled Tasks) to run hourly or daily:
   ```
   php /home/<account>/public_html/sazzad.co.financial/app/Console/Scheduler.php
   ```
6. Point DNS/SSL at `sazzad.co.financial` (Let's Encrypt via the host's SSL
   panel) and confirm `SESSION_SECURE_COOKIE=true` once HTTPS is active.

## Generic Ubuntu + Nginx/Apache stack

Ubuntu 22.04+, Nginx or Apache with PHP-FPM 8.2+, MySQL 8+, Composer 2+,
Let's Encrypt, cron. Web root → `public/` directly (no rewrite shim needed).

PHP settings: `upload_max_filesize=10M`, `post_max_size=12M`,
`max_file_uploads=10`. Required extensions: `pdo_mysql`, `fileinfo`,
`mbstring`, `openssl`, `json`, `session`, `ctype`, `filter`.

Database user should be least-privilege: `SELECT, INSERT, UPDATE, DELETE,
CREATE, ALTER, INDEX` — no `DROP`/`GRANT` needed for normal operation.
Import `database/schema.sql` only; import
`database/seeds/seed_demo_data.sql` for demo/staging environments only,
never production.
