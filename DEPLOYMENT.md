# Deploying Prayer Journal to a live subdomain

## 1. Server requirements

- PHP 8.1+ with extensions: `intl`, `mbstring`, `mysqli` (or `pdo_mysql`), `curl`, `gd`, `json`
- MySQL 8 (InnoDB) — a real server, not SQLite (SQLite was local-dev-only)
- Composer available on the server, or upload `vendor/` from a local `composer install --no-dev`
- HTTPS certificate for the subdomain (required for Stripe/PayFast to work correctly and for secure cookies)

## 2. Point the web server at `public/`

The document root **must** be the project's `public/` folder, not the project root. That's where `index.php`, `.htaccess`, and all static assets live.

**Apache** (most shared/cPanel hosts): if you can't change the vhost docroot, add this to the project root's `.htaccess` to redirect into `public/`, or better, ask your host to set the subdomain's docroot directly to `.../prayer-journal/public`.

**Nginx** example:
```nginx
server {
    listen 443 ssl http2;
    server_name app.yourdomain.com;
    root /var/www/prayer-journal/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

## 3. Create the database and switch the driver

```sql
CREATE DATABASE prayerjournal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'prayerjournal'@'localhost' IDENTIFIED BY 'a-strong-password';
GRANT ALL PRIVILEGES ON prayerjournal.* TO 'prayerjournal'@'localhost';
```

Edit `.env` on the server (copy from `env`, don't reuse the local dev one):

```
CI_ENVIRONMENT = production
app.baseURL = 'https://app.yourdomain.com/'

database.default.hostname = localhost
database.default.database = prayerjournal
database.default.username = prayerjournal
database.default.password = a-strong-password
database.default.DBDriver = MySQLi
database.default.DBPrefix =

encryption.key = <generate fresh — see step 4>

# Now that we're on MySQL, database sessions are supported and preferred
# over file sessions for a multi-worker PHP-FPM deployment.
session.driver = 'CodeIgniter\Session\Handlers\DatabaseHandler'
session.savePath = 'ci_sessions'
```

## 4. Install, migrate, seed

```bash
composer install --no-dev --optimize-autoloader
php spark key:generate
php spark migrate --all
php spark bible:import
php spark import:prayer-journal   # optional: re-imports the PDF course as a draft
```

## 5. File permissions

`writable/` must be writable by the web server user:
```bash
chmod -R 755 writable
chown -R www-data:www-data writable   # or the appropriate PHP-FPM user
```

## 6. Payment gateways

Set these in `.env` once you have real credentials — until then, checkout runs in "instant test mode" (orders/enrollments confirm immediately with no real charge, useful for the first round of testing on the subdomain):

```
STRIPE_SECRET_KEY = sk_live_...
STRIPE_WEBHOOK_SECRET = whsec_...

PAYFAST_MERCHANT_ID = ...
PAYFAST_MERCHANT_KEY = ...
PAYFAST_PASSPHRASE = ...
PAYFAST_SANDBOX = false
```

Webhook URLs to register with each gateway:
- Stripe: `https://app.yourdomain.com/webhooks/stripe`
- PayFast: `https://app.yourdomain.com/webhooks/payfast` (set automatically per-transaction, but PayFast also lets you set a default ITN URL in the merchant dashboard)

## 7. Promote your first admin user

Register a normal account on the site, then run:

```bash
php spark shield:user activate -e you@yourdomain.com
```

Then add the `admin` (or `superadmin`) group directly via SQL, since there's no UI for this yet:
```sql
INSERT INTO auth_groups_users (user_id, `group`, created_at) VALUES (<your_user_id>, 'superadmin', NOW());
```

## 8. Cron (optional, for later)

Nothing currently requires a scheduled job, but the `jobs` table exists for future background work (emails, subscription renewal checks). Add a cron entry once needed:
```
* * * * * php /var/www/prayer-journal/spark jobs:work >> /dev/null 2>&1
```

## What's already been tested locally

Registration/login, instructor course+lesson+quiz authoring, student enroll/study/quiz/grading with auto-issued certificates, Bible reading (full KJV) with bookmarks/highlights, cart → checkout → order (dev-mode instant pay), and the shop back-office (products/inventory/discounts/fulfillment/reports). All of this should carry over directly to MySQL — the only environment-specific things are the `.env` values above.

## Known gaps to revisit after the first live test

- Stripe/PayFast are wired up correctly but untested against real sandbox/live credentials — do a real test transaction on each before accepting live payments.
- No REST API yet (needed for the planned Expo mobile app) and no mobile app has been started.
- Course/lesson authoring UI is functional but plain; Shield's own auth views (login/register/forgot-password) haven't been re-themed with the brand palette yet.
- Tax rules and shipping rates have tables but no admin UI yet — shipping/tax currently default to $0.
