#!/usr/bin/env bash
#
# One-command install and update for cPanel (or any Linux host with a shell).
#
#   First install:  bash ~/prayer-journal/web/deploy.sh
#   Every update:   cd ~/prayer-journal && git pull && bash web/deploy.sh
#
# On the first run it asks a few questions (site address, database, admin
# login) and writes web/.env. Later runs ask nothing: they install
# dependencies, apply database migrations and clear caches. Every step is
# safe to repeat.

set -euo pipefail

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$APP_DIR"

say()  { printf '\n\033[1;35m==> %s\033[0m\n' "$*"; }
warn() { printf '\033[1;33m%s\033[0m\n' "$*"; }
die()  { printf '\033[1;31mError: %s\033[0m\n' "$*" >&2; exit 1; }

# --- PHP 8.2+ ---------------------------------------------------------------
# cPanel's default "php" in Terminal can be older than the site's PHP, so look
# for EasyApache builds too.
PHP_BIN="${PHP_BIN:-}"
if [ -z "$PHP_BIN" ]; then
    for candidate in php /opt/cpanel/ea-php84/root/usr/bin/php /opt/cpanel/ea-php83/root/usr/bin/php /opt/cpanel/ea-php82/root/usr/bin/php /usr/local/bin/php; do
        if command -v "$candidate" >/dev/null 2>&1 && "$candidate" -r 'exit(PHP_VERSION_ID >= 80200 ? 0 : 1);' 2>/dev/null; then
            PHP_BIN="$(command -v "$candidate")"
            break
        fi
    done
fi
[ -n "$PHP_BIN" ] || die "PHP 8.2 or newer was not found. In cPanel open 'MultiPHP Manager', choose PHP 8.2+ for your domain, then run this again (or run: PHP_BIN=/path/to/php bash deploy.sh)."
say "Using $("$PHP_BIN" -r 'echo "PHP ", PHP_VERSION;') ($PHP_BIN)"

for ext in intl mbstring mysqli json curl gd; do
    "$PHP_BIN" -m | grep -qi "^$ext$" || warn "PHP extension '$ext' is not enabled. Turn it on in cPanel > Select PHP Version > Extensions."
done

# --- Composer dependencies --------------------------------------------------
say "Installing PHP dependencies"
if command -v composer >/dev/null 2>&1; then
    COMPOSER=("$PHP_BIN" "$(command -v composer)")
else
    if [ ! -f composer.phar ]; then
        "$PHP_BIN" -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
        "$PHP_BIN" composer-setup.php --quiet
        rm -f composer-setup.php
    fi
    COMPOSER=("$PHP_BIN" composer.phar)
fi
"${COMPOSER[@]}" install --no-dev --optimize-autoloader --no-interaction --no-progress

spark() { "$PHP_BIN" spark "$@"; }

# --- First run: write .env ----------------------------------------------------
FIRST_RUN=0
if [ ! -f .env ]; then
    FIRST_RUN=1
    [ -t 0 ] || die "web/.env is missing. Run this script once from cPanel Terminal so it can ask for your settings."
    say "First-time setup: a few questions"
    echo "Create the database first in cPanel > MySQL Database Wizard (give the user ALL PRIVILEGES)."
    read -rp "Website address (e.g. https://yourdomain.com): " SITE_URL
    SITE_URL="${SITE_URL%/}/"
    read -rp "Database name (e.g. cpuser_prayer): " DB_NAME
    read -rp "Database user (e.g. cpuser_prayer): " DB_USER
    read -rsp "Database password: " DB_PASS; echo
    read -rp "Database host [localhost]: " DB_HOST; DB_HOST="${DB_HOST:-localhost}"

    cat > .env <<EOF
CI_ENVIRONMENT = production
app.baseURL = '${SITE_URL}'
app.forceGlobalSecureRequests = $( [[ "$SITE_URL" == https://* ]] && echo true || echo false )

database.default.hostname = ${DB_HOST}
database.default.database = ${DB_NAME}
database.default.username = ${DB_USER}
database.default.password = '${DB_PASS//\'/\\\'}'
database.default.DBDriver = MySQLi
database.default.DBPrefix =
database.default.port = 3306

# Payment gateways: fill these in when you have live credentials.
# STRIPE_SECRET_KEY =
# STRIPE_WEBHOOK_SECRET =
# PAYFAST_MERCHANT_ID =
# PAYFAST_MERCHANT_KEY =
# PAYFAST_PASSPHRASE =
# PAYFAST_SANDBOX = false
EOF
    chmod 600 .env
    spark key:generate --force >/dev/null
    echo "Saved web/.env"
fi

# --- Folders the web server writes to -----------------------------------------
mkdir -p writable/cache writable/logs writable/session writable/uploads public/uploads
chmod -R u+rwX writable
# The web server (LiteSpeed/Apache) must be able to pass through the app
# folders and read web/public. 711 lets it through without listing them;
# .env stays private (600).
chmod 711 "$(dirname "$APP_DIR")" "$APP_DIR"
chmod -R a+rX public

# --- Database -------------------------------------------------------------------
say "Updating the database"
spark migrate --all
if [ "$FIRST_RUN" = 1 ]; then
    say "Create your admin login"
    read -rp "Admin email: " ADMIN_EMAIL
    read -rp "Admin username: " ADMIN_USER
    read -rsp "Admin password (8+ characters): " ADMIN_PASS; echo
    spark app:admin --email "$ADMIN_EMAIL" --username "$ADMIN_USER" --password "$ADMIN_PASS"
fi
say "Loading content (skipped automatically if it already exists)"
spark bible:import
spark bible:plans
spark import:prayer-journal
spark db:seed ShopStarterSeeder

spark cache:clear >/dev/null 2>&1 || true

# --- Scheduler: emails, membership billing checks, learning reminders ---------
CRON_LINE="*/5 * * * * cd $APP_DIR && $PHP_BIN spark app:cron >/dev/null 2>&1"
if command -v crontab >/dev/null 2>&1; then
    ( crontab -l 2>/dev/null | grep -v 'spark app:cron' ; echo "$CRON_LINE" ) | crontab - \
        && say "Scheduler installed (runs every 5 minutes)" \
        || warn "Could not add the cron job automatically. Add it in cPanel > Cron Jobs: $CRON_LINE"
else
    warn "Add this in cPanel > Cron Jobs (Once Per Five Minutes): $CRON_LINE"
fi
spark app:cron >/dev/null 2>&1 || true

# --- Point the main domain at web/public ---------------------------------------
PUBLIC_HTML="$HOME/public_html"
if [ "$FIRST_RUN" = 1 ] && [ -t 0 ] && [ ! -L "$PUBLIC_HTML" ]; then
    say "Make this the website for your main domain?"
    echo "This renames your current public_html to public_html.backup-$(date +%Y%m%d) (nothing is deleted)"
    echo "and replaces it with a link to $APP_DIR/public."
    read -rp "Do it now? [y/N]: " LINK
    if [[ "$LINK" =~ ^[Yy] ]]; then
        [ -e "$PUBLIC_HTML" ] && mv "$PUBLIC_HTML" "$PUBLIC_HTML.backup-$(date +%Y%m%d%H%M)"
        ln -s "$APP_DIR/public" "$PUBLIC_HTML"
        echo "Done: public_html -> $APP_DIR/public"
    fi
fi

say "All done."
if [ "$FIRST_RUN" = 1 ]; then
    echo "Open your site, sign in with the admin login, and visit /studio to manage everything."
fi
