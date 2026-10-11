# Deploying Prayer Journal to a live subdomain

## 1. Server requirements

- PHP 8.2+ with extensions: `intl`, `mbstring`, `mysqli` (or `pdo_mysql`), `curl`, `gd`, `json`
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

# Only needed if a browser build of the mobile app calls the API from another
# domain. Native iOS/Android builds do not need CORS.
CORS_ALLOWED_ORIGINS = https://app.yourdomain.com
```

## 4. Install, migrate, seed

```bash
composer install --no-dev --optimize-autoloader
php spark key:generate
php spark migrate --all
php spark bible:import                    # full KJV text (already cleaned of editorial braces)
php spark bible:plans                     # 5 reading plans (Bible in a year, Psalms & Proverbs, ...)
php spark import:prayer-journal           # the "My Prayer Journal" course, built from the PDF
php spark db:seed ShopStarterSeeder       # ZAR/VAT settings, shipping zones, the print + eBook products
php spark app:admin --email you@yourdomain.com --password 'a-long-password' --username you
```

- `import:prayer-journal` publishes the course. Add `--draft` to keep it hidden, `--author=email` to set the owner, or `--force` to rebuild it. Rebuilding deletes learners' progress for that course.
- `ShopStarterSeeder` prices (R250 print and R120 eBook) and stock are placeholders. Change them in **Studio → Products**.
- `bible:clean` re-runs the KJV clean-up if you ever import a different source file.

## 5. File permissions

`writable/` must be writable by the web server user:
```bash
chmod -R 755 writable
chown -R www-data:www-data writable   # or the appropriate PHP-FPM user
```

## 6. Payment gateways

Set these in `.env` once you have real credentials. Without them, checkout offers a **test payment** button that confirms orders without charging. It is available only when `CI_ENVIRONMENT` is not `production`, or when **Studio → Settings → Store → Allow test payments** is switched on. Use that switch on a staging subdomain only:

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

## 7. Admins and roles

`php spark app:admin` (step 4) creates or promotes a superadmin without prompting. Run it again with `--group=admin` for more admins.

After that, manage everyone from **Studio → Users & roles**:

| Role | What it unlocks |
| --- | --- |
| Instructor | Courses, grading and students |
| Vendor | Products and inventory |
| Admin | The whole shop, site settings and moderation |

Only a superadmin can grant superadmin.

## 8. Cron (optional, for later)

Nothing currently requires a scheduled job, but the `jobs` table exists for future background work (emails, subscription renewal checks). Add a cron entry once needed:
```
* * * * * php /var/www/prayer-journal/spark jobs:work >> /dev/null 2>&1
```

## Before going live

```bash
vendor/bin/phpunit    # 38 tests on in-memory SQLite: security, API, LMS, shop and the course import
```

- Do a real sandbox transaction on Stripe and on PayFast before accepting live payments. Amount verification and signatures are implemented but have not been run against real credentials.
- Replace the placeholder product prices, and review shipping zones and VAT under **Studio → Shipping & tax**.
- Upload your final logo in **Studio → Settings → Branding**. It is used on every page, in the Studio, on certificates and on printed journals. The mobile app ships with `mobile/assets/logo-mark.png`; replace that file too.
