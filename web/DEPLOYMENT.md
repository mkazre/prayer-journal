# Deploying Prayer Journal

## cPanel (main domain) — the quick way

You need cPanel with **Terminal** and **Git Version Control**. It takes about 10 minutes.

1. **Pick PHP 8.2 or newer.** In cPanel open **MultiPHP Manager** and set your domain to PHP 8.2+. Then open **Select PHP Version → Extensions** and make sure `intl`, `mbstring`, `mysqli`, `gd` and `curl` are ticked.
2. **Create the database.** Open **MySQL Database Wizard** and create a database and a user. Give the user **ALL PRIVILEGES**, and note the three values (the names start with your cPanel username, e.g. `cpuser_prayer`).
3. **Get the code.** In **Git Version Control → Create**, fill in:
   - Clone URL: `https://github.com/mkazre/prayer-journal.git`
   - Repository Path: `prayer-journal`. This must be *outside* `public_html`, so it ends up at `/home/<you>/prayer-journal`.
4. **Install.** Open **Terminal** and run:
   ```bash
   bash ~/prayer-journal/web/deploy.sh
   ```
   It asks for:
   - your website address
   - the database details from step 2
   - an admin email, username and password

   Then it installs everything: the Bible, reading plans, the Prayer Journal course and the shop starter products. At the end it asks to make the app your main website. Answer **y**. Your current `public_html` is renamed to `public_html.backup-…` (nothing is deleted) and replaced with a link to the app.
5. **Turn on HTTPS.** Make sure **SSL/TLS Status** shows a certificate for your domain. AutoSSL usually handles this automatically.
6. Open your site, sign in, and go to `/studio`.

7. **Emails.** Go to **Studio → Settings → Emails** and set the sender name and address. For the best inbox delivery:
   - create that mailbox in cPanel **Email Accounts** (e.g. `hello@timelesswisdomapp.com`)
   - enter its SMTP details: server `mail.yourdomain.com`, port `465`, SSL, the full email address as username, and its password
   - turn on **Email Deliverability** (SPF/DKIM) for your domain in cPanel
   - click **Send me a test email**
8. **Scheduler.** `deploy.sh` adds the cron job for you; the Emails tab shows "Running" once it has run. If your host blocks it, add this line yourself in cPanel **Cron Jobs** with "Once Per Five Minutes". The Emails tab shows the exact command for your account:
   ```
   cd ~/prayer-journal/web && php spark app:cron
   ```
   It sends emails, checks memberships (reminders, missed payments, automatic suspension) and sends the daily learning reminders.
9. **PayFast memberships:** see section 6 below.

**Updating later** (after new changes are pushed to GitHub), run one line in Terminal:

```bash
cd ~/prayer-journal && git pull && bash web/deploy.sh
```

It asks nothing, keeps your data, and only applies what changed. (Instead of `git pull` you can click **Update from Remote** in Git Version Control, then run `bash ~/prayer-journal/web/deploy.sh`.)

**If something goes wrong:**

| Symptom | Fix |
| --- | --- |
| "PHP 8.2 or newer was not found" | Redo step 1. If Terminal still uses old PHP, run `PHP_BIN=/opt/cpanel/ea-php83/root/usr/bin/php bash ~/prayer-journal/web/deploy.sh`. |
| Error 500 after linking `public_html` | Check `~/prayer-journal/web/writable/logs/`. If the host forbids a linked `public_html`, undo the link (`rm ~/public_html && mv ~/public_html.backup-* ~/public_html`). Then, in cPanel **Domains**, set the domain's document root to `prayer-journal/web/public`. |
| Payments | Add your Stripe/PayFast keys to `~/prayer-journal/web/.env` (see section 6 below). |

The rest of this guide covers servers you manage yourself (VPS, Nginx) and the settings behind the script.

---

## 1. Server requirements

- PHP 8.2+ with extensions: `intl`, `mbstring`, `mysqli` (or `pdo_mysql`), `curl`, `gd`, `json`
- MySQL 8 (InnoDB) — a real server, not SQLite (SQLite was local-dev-only)
- Composer available on the server, or upload `vendor/` from a local `composer install --no-dev`
- HTTPS certificate for the subdomain (required for Stripe/PayFast to work correctly and for secure cookies)

## 2. Point the web server at `public/`

The document root **must** be the project's `public/` folder, not the project root. That's where `index.php`, `.htaccess`, and all static assets live.

**Apache / cPanel:** `deploy.sh` links `public_html` to `web/public` for you. Alternatively, set the domain's document root to `.../prayer-journal/web/public` in cPanel **Domains**.

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

**PayFast memberships (monthly/yearly):**
1. In your PayFast dashboard, set a **Salt Passphrase** (Settings → Developer Settings). It is required for subscriptions and for cancelling them, so put the same value in `PAYFAST_PASSPHRASE`.
2. Make sure **Recurring Billing / Subscriptions** is enabled on your PayFast account.
3. Create your plans in **Studio → Membership plans** (e.g. "Monthly membership", R99/month).
4. On a membership course, set Access to "Membership". On a paid course, tick "Also included in membership plans" if it should be included.

PayFast then charges members automatically every month and notifies the site after each payment.
- **Missed payment:** the member gets a "payment failed" email with a link to update their card, then a final notice, and access is suspended automatically after the 5-day grace period.
- **Cancelling:** members cancel from **Account → Membership**, and admins from **Studio → Membership plans**.
- **Testing:** set `PAYFAST_SANDBOX = true` with PayFast's sandbox merchant details and run a full subscription before switching to live.

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
vendor/bin/phpunit    # 54 tests on in-memory SQLite: security, API, LMS, shop, memberships, emails, reminders, user management
```

- **Copy protection** is on for every course by default. Turn it off per course in **Studio → Course → Settings**.
  - **Website:** blocks right-click, copying, printing, saving and media downloads, and shows a faint name/email watermark.
  - **App:** blocks screenshots and screen recording on lessons.
  - No website can stop a determined person photographing a screen. The watermark makes leaked copies traceable.
- Do a real sandbox transaction on Stripe and on PayFast before accepting live payments. Amount verification and signatures are implemented but have not been run against real credentials.
- Replace the placeholder product prices, and review shipping zones and VAT under **Studio → Shipping & tax**.
- Upload your final logo in **Studio → Settings → Branding**. It is used on every page, in the Studio, on certificates and on printed journals. The mobile app ships with `mobile/assets/logo-mark.png`; replace that file too.
