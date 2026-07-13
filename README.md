# Laravel Shopify Sync App

A Shopify embedded app that syncs **Products, Inventory, Orders, Customers, Collections, and Discounts** from any connected store into a local database via GraphQL backfill + webhooks, with a dashboard UI and database-driven (not `.env`-driven) Shopify credentials.


---

## 1. Requirements

- PHP 8.3+
- MySQL (or another Laravel-supported DB)
- Redis (queue backend)
- Composer, Node/npm
- A public HTTPS URL the app is reachable at (ngrok/similar for local dev, a real domain in production) - Shopify requires this for OAuth and webhooks

## 2. Installation

```bash
composer install
npm install
npm run build
```

Copy the environment file and generate a key:

```bash
cp .env.example .env
php artisan key:generate
```

Set your database, then run migrations:

```bash
php artisan migrate
```

This creates every table the app needs - the Shopify package's own shop/plan/charge tables, the sync cache tables (`products`, `product_variants`, `inventory_levels`, `orders`, `order_line_items`, `fulfillments`, `order_transactions`, `customers`, `collections`, `discounts`), the `app_settings` table, and the `admins` table.

Set the queue and cache drivers in `.env`:

```env
QUEUE_CONNECTION=redis
REDIS_CLIENT=predis
CACHE_STORE=database
```

(`predis` avoids needing the `phpredis` PHP extension. If Redis isn't available on your host, `QUEUE_CONNECTION=database` works too - see the deployment section.)

Set the session cookie options - **required** for the app to work embedded inside Shopify Admin's iframe:

```env
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=none
```

Without this, the session cookie gets silently dropped by the browser because it's a cross-site (embedded iframe) context, and nothing involving login will work.

## 3. Create your admin account

`/settings` (Shopify credentials + connect screen, see below) is protected by its own login - deliberately separate from the Shopify shop session (see [Architecture notes](#architecture-notes)). Create an account either via:

```bash
php artisan admin:create --name="Admin" --email="you@example.com" --password="a-real-password"
```

or by setting `ADMIN_NAME` / `ADMIN_EMAIL` / `ADMIN_PASSWORD` in `.env` and running:

```bash
php artisan db:seed --class=AdminSeeder
```

`db:seed` is safe to re-run - it updates the existing account rather than duplicating it. **If a password contains `#`, wrap it in quotes in `.env`** (`ADMIN_PASSWORD="my#pass"`) - unquoted, `.env` treats `#` as a comment marker and silently truncates everything after it.

## 4. Connect a Shopify store

Shopify API credentials (`api_key`, `api_secret`, `api_scopes`) and the app's public URL are **stored in the database**, not `.env` - editable any time via the UI, no redeploy needed.

1. Log in at `/login` with the admin account from step 3.
2. You'll land on `/settings`. Fill in:
   - **API Key** / **API Secret** - from your app's entry in the [Shopify Partner Dashboard](https://partners.shopify.com).
   - **API Scopes** - comma-separated, e.g. `read_products,write_products,read_orders,read_customers`.
   - **App URL** - the public HTTPS URL this app is reachable at right now (your ngrok tunnel or production domain). Whenever this URL changes (e.g. a new ngrok session), update it here.
   - **Connect a store** (optional) - a shop domain (`your-store.myshopify.com`). Filling this in and saving immediately starts that store's OAuth install.
3. If you skip the "Connect a store" field, install the app normally from the Partner Dashboard / an install link instead.
4. **Protected Customer Data**: Shopify gates access to Order and Customer objects behind a separate approval, in *two levels*, regardless of your API scopes:
   - Level 1 - basic object access.
   - Level 2 - the actually sensitive fields (name, email, phone, address).

   Request/enable both in the Partner Dashboard under **Protected customer data access** (self-serve on dev stores, no waiting for review). Without this, Products/Collections/Discounts sync fine but Orders/Customers/Fulfillments/Transactions will fail with `ACCESS_DENIED` errors.

Until credentials are configured, **every route redirects to `/settings`** (or returns a `401 Unauthorized` for API/JSON requests) - this is intentional, not a bug, so there's no confusing low-level failure on a fresh unconfigured deploy.

## 5. What happens automatically on install

No manual command needed per store - installing (or reinstalling) the app on any shop automatically:

1. Registers all Shopify webhooks for that shop (products, inventory, orders, customers, collections, discounts, fulfillments, transactions, app uninstall).
2. Shows a **live sync-progress screen** (animated progress bar + per-entity checklist) while the full catalog/order/customer history backfills via GraphQL - lands on the real dashboard automatically once done.
3. On uninstall, that shop's synced data is cleaned up automatically.

## 6. Run the queue worker

Webhooks and the install-time backfill are processed by a queue worker - **this must be running continuously**, and needs to run in Redis (or whichever `QUEUE_CONNECTION` you set):

```bash
php artisan queue:work redis --queue=default,webhooks --tries=3 --backoff=5
```

**Restart this worker after any code, `.env`, or database change.** A running worker keeps using whatever it loaded at startup - it does not hot-reload PHP classes or pick up new `.env`/database values. This is the single most common cause of "it looks like nothing is syncing" - the worker is silently still running old code/config.

To restart it:
```bash
# Find it
powershell -NoProfile -Command "Get-CimInstance Win32_Process -Filter \"Name='php.exe'\" | Where-Object { $_.CommandLine -like '*queue:work*' } | Select-Object ProcessId"
# Stop it (replace <PID>)
powershell -NoProfile -Command "Stop-Process -Id <PID> -Force"
# Clear config cache and start a fresh one
php artisan config:clear
php artisan queue:work redis --queue=default,webhooks --tries=3 --backoff=5
```

## 7. Scheduled / manual sync commands

| Command | What it does |
|---|---|
| `php artisan shopify:sync` | Full reconciliation of **all** entities (products, customers, collections, orders, discounts) for every installed shop, or `{shop}` for one specific domain. |
| `php artisan shopify:sync-products` | Products/inventory only, same `{shop?}` argument. |
| `php artisan admin:create` | Create an admin login for `/settings`. |
| `php artisan db:seed --class=AdminSeeder` | Create/update the admin login from `.env` values. |

Webhooks keep data live in real time, but delivery isn't 100% guaranteed by Shopify - schedule `shopify:sync` as a nightly safety-net cron:

```cron
0 3 * * * cd /path/to/project && php artisan shopify:sync >> /dev/null 2>&1
```

On shared hosting without a persistent process allowed, run the queue worker itself via cron instead of a long-lived process:

```cron
* * * * * cd /path/to/project && php artisan queue:work --stop-when-empty --max-time=55 >> /dev/null 2>&1
```

## 8. Pages

All Shopify-embedded pages share one sidebar layout and require an authenticated shop session (handled by the OAuth/session-token flow):

- **Dashboard** (`/`) - stat cards (Products/Orders/Customers/Collections/Discounts counts + revenue), or the live sync-progress screen right after install.
- **Products** (`/products`)
- **Orders** (`/orders`, `/orders/{id}`) - list + full detail (line items, customer, addresses, fulfillments, transactions).
- **Customers** (`/customers`)
- **Collections** (`/collections`)
- **Discounts** (`/discounts`)

`/settings` is a separate, plain (non-embedded) admin page behind its own login - not part of the Shopify-embedded nav.

## 9. Deployment (cPanel or similar)

- Document root → `public/`.
- `composer install --no-dev --optimize-autoloader`
- `php artisan migrate --force`
- `storage/` and `bootstrap/cache/` writable by the web server user.
- `.env`: `APP_ENV=production`, `APP_DEBUG=false`, plus the session cookie settings from step 2.
- Point the app's **App URL** (in `/settings`, not `.env`) at your real domain, and update the Partner Dashboard's App URL/redirect URL to match.
- If you get a VPS-tier host with root + SSH, Redis + a Supervisor-managed `queue:work` (or `laravel/horizon` outright) is the robust option - Linux has the `pcntl`/`posix` extensions Horizon needs, unlike Windows.

## Architecture notes

- **Shopify shop auth vs admin login are two separate systems.** The shop model (`App\Models\User`) doubles as the Shopify "shop" record (its `password` column stores the raw Shopify access token, not a hashed password) - it uses Laravel's default `web` guard. `/settings` uses a completely separate `Admin` model/table and `admin` guard (via Fortify). They never interact; logging into one has zero effect on the other.
- **Credentials are database-only.** `App\Services\ShopifySettingsResolver` is wired into the Shopify package's own `config_api_callback` extension point - `api_key`/`api_secret`/`api_scopes` resolve from the `app_settings` table with no `.env` fallback. The app's public URL (used to build webhook addresses) is similarly database-driven, substituted in at boot time (`AppServiceProvider`) since config files load before the database connection exists.
- **Webhook-driven sync + GraphQL backfill work together**: webhooks keep data live going forward; `Backfill*Job` classes (GraphQL, paginated) populate history on install and via the reconciliation commands. Both paths upsert into the same tables, so re-running either is always safe.
- A couple of vendor package quirks are patched via container-bound overrides (not vendor file edits, so they survive `composer update`): `IframeProtection` (avoids caching a full Eloquent model, which can corrupt on unserialize) and `VerifyThemeSupport` (widens a narrow `catch (Exception)` to `catch (Throwable)`, since a fragile vendor cache read can throw a plain `Error`).
