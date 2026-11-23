# Diding's Midnight Snack — POS, Inventory & Online Ordering

A web-based point of sale, stock system and public ordering website for a small
fast-food store, built with Laravel 12, Livewire 4, Tailwind CSS 4 and
PostgreSQL (Supabase).

## What it does

- **Online storefront** — a public menu at `/` with a cart, delivery or pickup,
  GCash or cash payment, a per-order tracking link, and order lookup by
  reference + phone.
- **Order board** — staff see new orders on a live kanban at `/orders`, confirm
  them, move them through preparation, record payment, and close them into a
  normal sale. New orders beep and raise a browser notification, and can also
  reach a Telegram staff group.
- **Customer SMS** — when SMS is configured, the customer gets one message when
  the snack is ready, feedback questions after completion, and a nudge when a
  GCash payment is still pending. Plus repeat-customer product suggestions on
  checkout. See [SMS & feedback](#sms--feedback).
- **Till** — barcode scanning, instant local cart, cash / GCash / Maya / bank
  payment, change due, senior & PWD 20% discounts with ID capture, parked
  sales, thermal receipt printing (58 mm or 80 mm).
- **Catalogue** — categories, products, SKU + barcode, cost and selling price,
  low-stock thresholds, unlimited-stock items.
- **Inventory** — stock in, set-count adjustments, wastage, full movement
  history with who/when/why, stock value at cost.
- **Sales** — searchable history, reprint receipts, void with a reason (stock is
  returned automatically), CSV export.
- **Shifts** — cash drawer sessions: opening cash, expected cash, counted cash,
  variance.
- **Reports** — daily Z-reading, payment mix, top sellers, discounts, cash
  collected.
- **Users** — admin and cashier roles, username login, deactivation instead of
  deletion so history stays intact.

## Stack

| Layer | Choice |
|---|---|
| Backend | Laravel 12, PHP 8.2+ |
| UI | Blade + Livewire 4 + Alpine.js + Tailwind CSS 4 |
| Database | PostgreSQL 17 on Supabase (session pooler) |
| Build | Vite 6 |
| Tests | PHPUnit / Laravel feature tests on in-memory SQLite |

## Setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Fill in the Supabase connection values in `.env` (see *Database* below), then:

```bash
php artisan migrate --seed
php artisan storage:link
npm run build
php artisan serve
```

Online ordering sends Telegram alerts through the queue, so run a worker too:

```bash
php artisan queue:work
```

Seeded accounts (change the passwords after first login):

| Role | Username | Password |
|---|---|---|
| Admin | `admin` | `admin123` |
| Cashier | `cashier` | `cashier123` |

## Database

Use the **session pooler** connection string from Supabase (Project settings →
Database). Direct connections are IPv6-only on the free plan and will fail on
many hosts.

```dotenv
DB_CONNECTION=pgsql
DB_HOST=aws-0-<region>.pooler.supabase.com
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres.<project-ref>
DB_PASSWORD=...
DB_SSLMODE=require
```

> **Latency matters.** Every query is a network round trip. If the app server is
> far from the database region, a single page can take several seconds. Keep the
> Laravel host in the same region as Supabase, and prefer a local database while
> developing.

## Local development without Supabase

SQLite is enabled for development and for tests. It makes the dev loop instant:

```dotenv
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
```

```bash
php artisan migrate:fresh --seed
```

The production deployment should still point at Supabase.

## How the till works

- The catalogue is loaded once into an Alpine store, so scanning a barcode and
  changing quantities never touch the network.
- Only the final `checkout()` call reaches the server, which **re-prices every
  line from the database** — prices sent by the browser are display-only.
- Checkout runs in a transaction with `SELECT ... FOR UPDATE` on the products
  involved, writes the sale, its items and a `stock_movements` row per line, and
  returns the receipt number and change.
- Money is stored as `numeric(12,2)` but all arithmetic runs on integer centavos
  through `App\Support\Money`, so totals never drift.

## Online ordering

| URL | What it is |
|---|---|
| `/` | Public menu (no login) |
| `/order` | Cart checkout |
| `/track/{token}` | Private order status page |
| `/track` | Find an order by reference + phone |
| `/login` | Staff login |
| `/orders` | Staff order board (admin and cashier) |

The flow:

1. A guest builds a cart in the browser. Prices there are **display-only**.
2. `POST /orders` re-reads every price and locks the product rows, then reserves
   stock with a `stock_movements` row of type `online_order`. Nothing is deducted
   twice: when the staff completes the order, those movements are re-pointed at
   the resulting sale.
3. GCash orders start as *awaiting verification* when a reference is supplied;
   COD and cash-at-pickup start *unpaid*. Staff verify the money and mark the
   order paid.
4. Staff confirm → prepare → ready → (dispatch) → complete. Completing creates a
   normal `sales` row with `channel = online` and the delivery fee, and the
   receipt works exactly like a walk-in sale.
5. Cancelling returns the reserved stock; a cancelled paid order can be marked
   refunded.

Senior/PWD discounts are **never** self-applied online. Staff verify the
OSCA/PWD ID on the board before the total is locked by payment.

### Availability, fees and payment details

Settings → *Online ordering* controls whether ordering is open, a temporary
pause, delivery/pickup availability, the fixed delivery fee, an optional free
delivery threshold, a minimum order, and the store hours shown to customers.
Public timing estimates are not shown; we instead tell customers we'll call them
when the order is ready.
Product → *Show on the online menu* controls what appears in the menu; stock and
the sold-out badge come from the normal inventory.

### Alerts

The board polls every 15 seconds. When a new order appears it beeps and, if the
browser has permission, shows a notification. Telegram is optional: put a bot
token and the staff group chat ID in Settings → *Order alerts* (or in `.env`) and
run a queue worker. The Telegram message contains the reference, type, payment
status and a link to the board — not the customer's address.

### SMS & feedback

An SMS gateway gives the customer a phone-first experience. The customer types
their phone at checkout, and if that phone placed two or more orders before,
they get repeat-customer suggestions in their cart.

When SMS is enabled, the customer gets:

- **"Snack is ready"** — a text when the order is marked ready.
- **"Still waiting"** — a nudge for abandoned GCash payments after N minutes.
- **Feedback request** — after the order completes, a link to `/feedback/{token}`
  with 5 star ratings; replies are stored in `customer_feedbacks`.

Configure it in Settings → *SMS for customers* (gateway URL + API key, sender
name, reminder wait, feedback rate). The gateway must be a POST endpoint that
accepts `to`, `message`, `sender` JSON. SMS is disabled by default; the admin
phone can test it from the same page.

Repeat customers: after a customer returns, their usual items are suggested in
the checkout — `GET /suggestions?phone=09171234567`.

### Deployment

Public ordering needs a real hostname with HTTPS. `localhost` is not reachable
from a customer's phone, and GCash references/images should not travel over
plain HTTP. Set `APP_URL` to the public address, keep a queue worker running,
and run `php artisan storage:link` so menu photos and the GCash QR load.

## Receipts

Receipts are HTML rendered at a fixed 58 mm or 80 mm width and printed through
the browser (`window.print()` / a new window). Set the printer driver's paper
size to match. Auto-opening the cash drawer or silent printing would need a
small local print agent and is not included.

## Tests

```bash
php artisan test
```

Covers login and roles, checkout maths, discounts, overselling, voids, shift
cash expectations, the Livewire terminal actions, CSV export, online order
placement (stock reservation, delivery fees, GCash/COD, lookup), the staff order
board lifecycle and sale conversion, SMS gateway sends (ready, abandoned,
feedback, suggestions), repeat customers, the feedback page and route, the
abandoned-order scheduler, and a smoke test that renders every screen.

## Project layout

```
app/
  Enums/           Role, PaymentMethod, DiscountType, SaleStatus, SalesChannel,
                   OrderStatus, OrderType, OrderPaymentMethod, PaymentStatus
  Livewire/        Pos, Orders, Products, Categories, Inventory, Sales, Shifts,
                   Reports, Users, Settings
  Models/          User, Category, Product, StockMovement, Sale, SaleItem, Shift,
                   Setting, Order, OrderItem
  Services/        CheckoutService, SaleService, StockService, ShiftService,
                   OrderService, StorefrontService, OrderNotifier
  Jobs/            SendOrderTelegramAlert
  Support/         Money, PosCatalog
  Http/Middleware/ EnsureRole
resources/views/   layouts, storefront, orders, livewire components, receipts
resources/js/      pos.js, order.js (public cart), orders.js (staff alerts)
```

## Not included (next steps)

- Automatic GCash (PayMongo/Xendit) instead of manual reference verification.
- Partial refunds (voids currently reverse the whole sale).
- Rider assignment and live map tracking; riders are coordinated by phone.
- Recipe/ingredient-level inventory; online orders use product stock.
- Suppliers and purchase orders; stock-in is manual.
- Multi-branch support (`branch_id` would need to be added to products, sales,
  orders and shifts).
- Barcode label printing.
