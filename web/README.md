# Prayer Journal — Web

The Prayer Journal website, Studio admin panel and REST API. It is built with CodeIgniter 4.7 (PHP 8.2+) and Shield for authentication.

It contains:

- a learning platform, starting with the **My Prayer Journal** course built from the book
- a full KJV Bible reader with reading plans
- a private prayer journal
- a community prayer wall
- a shop (physical and digital products)
- the `/api/v1` API used by the Expo mobile app in `../mobile`

See [FEATURES.md](FEATURES.md) for the full feature list and [DEPLOYMENT.md](DEPLOYMENT.md) for going live.

## Quick start (local)

```bash
composer install
cp env .env             # set app.baseURL; SQLite is fine locally:
                        #   database.default.DBDriver = SQLite3
                        #   database.default.database = /absolute/path/to/writable/database.db
php spark key:generate
php spark migrate --all
php spark bible:import
php spark bible:plans
php spark import:prayer-journal
php spark db:seed ShopStarterSeeder
php spark app:admin --email admin@example.test --password 'Admin-Pass-2026!' --username admin
php spark serve
```

Open http://localhost:8080 and sign in. The admin panel is at **/studio**.

## Layout

```
app/
  Modules/
    Core/     dashboard, account, search, prayer wall, notifications
    Lms/      catalogue, course player, journal, quizzes, certificates
    Bible/    reader, search, highlights/bookmarks/notes, reading plans
    Shop/     storefront, cart, checkout (Stripe, PayFast), orders, wishlist
    Studio/   admin panel for courses, curriculum, grading, products, orders and settings
    Api/      /api/v1 for the mobile app (bearer tokens)
  Libraries/  Scripture (reference parser/linker), HtmlSanitizer, Uploads
  Helpers/    ui_helper (icons, money, avatars, settings, dates)
  Commands/   import:prayer-journal, bible:import, bible:clean, bible:plans, app:admin
  Database/   migrations, seeders, and Data/ (KJV, course JSON, product images)
public/assets/
  css/app.css      design system (tokens, light/dark themes, components)
  js/app.js        progressive enhancement (no framework): AJAX forms, autosave,
                   rich-text editor, drag-and-drop, modals, scripture popovers
  img/icons.svg    line-icon sprite, used through icon('name')
scripts/build_prayer_journal_course.py   regenerates the course JSON from the PDF
```

## Commands

| Command | Purpose |
| --- | --- |
| `php spark import:prayer-journal [--draft] [--force] [--author=email]` | Build the "My Prayer Journal" course: 8 modules, 131 lessons, 6 quizzes, journal prompts |
| `php spark bible:import` / `bible:clean` | Load the KJV; strip the source's editorial braces |
| `php spark bible:plans` | Create the 5 built-in reading plans |
| `php spark db:seed ShopStarterSeeder` | Store settings, shipping zones, VAT, and the print and eBook products |
| `php spark app:admin --email … [--group=admin]` | Create or promote an admin without prompts |

Everything the commands create can be edited afterwards in Studio.

## Security notes

- CSRF protection is on for every form. Only `webhooks/*` (signature-verified) and `api/*` (bearer tokens) are exempt.
- Rich text is cleaned by an allow-list sanitizer. The only embeds allowed are YouTube and Vimeo.
- Uploads are type-checked. Digital products live outside `public/` and are served only to buyers.
- Orders are visible only to their owner, the guest session that placed them, or admins. Gateway amounts are verified against the order.
- Test payments are disabled in production unless an admin explicitly enables them.

## Tests

```bash
vendor/bin/phpunit
```

There are 38 tests. Each one runs every migration on a fresh in-memory SQLite database. They cover:

- **Security:** CSRF, role filters and ownership
- **API:** auth, journal CRUD, enrolment and progress, the prayer wall, and that quiz answers never leak to the device
- **LMS:** preview, sequential and drip locks; certificates; grading every question type
- **Shop:** inclusive and exclusive VAT, shipping zones, free-shipping threshold, discount validation, stock reserve and restock
- **Course import:** building the full course from the PDF content
- **Libraries:** the scripture parser and the sanitizer
