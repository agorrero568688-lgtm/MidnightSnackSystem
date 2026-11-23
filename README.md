# Diding's Midnight Snack — POS & Inventory

A web-based point of sale and stock system for a small snack store, built with
Laravel 12, Livewire 4, Tailwind CSS 4 and PostgreSQL (Supabase).

## What it does

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
npm run build
php artisan serve
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
cash expectations, the Livewire terminal actions, CSV export, and a smoke test
that renders every screen.

## Project layout

```
app/
  Enums/           Role, PaymentMethod, DiscountType, SaleStatus, stock types
  Livewire/        Pos, Products, Categories, Inventory, Sales, Shifts, Reports, Users, Settings
  Models/          User, Category, Product, StockMovement, Sale, SaleItem, Shift, Setting
  Services/        CheckoutService, SaleService, StockService, ShiftService
  Support/         Money, PosCatalog
  Http/Middleware/ EnsureRole
resources/views/   layouts, livewire components, receipts
```

## Not included (next steps)

- Partial refunds (voids currently reverse the whole sale).
- Suppliers and purchase orders; stock-in is manual.
- Multi-branch support (`branch_id` would need to be added to products, sales and
  shifts).
- Barcode label printing and product images.
