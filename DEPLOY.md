# Deploying to Vercel

The app runs on Vercel as a **container service** (FrankenPHP + Caddy), not as a
static Vite site. `Dockerfile.vercel` makes Vercel pick the Container preset even
though the repo contains Vite, which is what fixes the
`STATIC_BUILD_NO_OUT_DIR` build error.

## 1. Vercel project settings

In **Settings - General**:

- **Framework Preset:** `Container`
- **Build Command:** empty
- **Output Directory:** empty
- **Install Command:** empty
- **Root Directory:** repository root

If the preset is still Vite, run:

```bash
npx vercel@latest link
npx vercel@latest project update --framework container --yes
```

## 2. Bundled menu art

Chicken Lomi (and other future Lomi art) is committed to Git under
`public/images/products/` and is part of the container image. Static asset
routes (`/images/*`, `/build/*`, favicons) are rewired explicitly in
`vercel.json` and served directly from `/app/public` by the Caddyfile, so the
menu never depends on a `public/storage` symlink or a remote object.

Staff-uploaded photos and the GCash QR go to Vercel Blob when
`MEDIA_DRIVER=blob`. New uploads store the absolute Blob URL, so product photos,
checkout, tracking, and `StorefrontService::gcashQrUrl()` never make another
storage request. Bundled art still ships in the container.

## 3. Vercel Blob storage

Create two Blob stores in the Vercel dashboard and connect both to the project:

| Store      | Access | Holds                           |
| ---------- | ------ | ------------------------------- |
| `Images`   | public | product photos, GCash QR        |
| `Receipts` | private| customer GCash receipt screenshots |

Copy each store's `BLOB_READ_WRITE_TOKEN` into `BLOB_PUBLIC_TOKEN` and
`BLOB_PRIVATE_TOKEN`. Static tokens are used instead of OIDC so no token refresh
is needed inside a request. The store ID is derived from the token; only set
`BLOB_PUBLIC_STORE_ID` / `BLOB_PRIVATE_STORE_ID` if you want to override it.

Re-upload the QR and staff-uploaded product photos once after deploying so the
database points at Blob URLs. Legacy Supabase paths keep working through
`LEGACY_MEDIA_URL` until replaced. Existing receipts are read through the
Supabase REST API with `LEGACY_SUPABASE_SERVICE_ROLE_KEY`; no S3 call is made.

## 4. Environment variables

Set these in **Settings - Environment Variables** (Production and Preview):

```dotenv
APP_NAME="Diding's Midnight Snack"
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:...            # php artisan key:generate --show
APP_URL=https://your-app.vercel.app
APP_TIMEZONE=Asia/Manila

DB_CONNECTION=pgsql
DB_HOST=aws-0-<region>.pooler.supabase.com
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres.<project-ref>
DB_PASSWORD=...
DB_SSLMODE=require

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
CACHE_STORE=database
QUEUE_CONNECTION=sync
LOG_CHANNEL=stderr

MEDIA_DRIVER=blob
PROOF_DRIVER=blob
BLOB_PUBLIC_TOKEN=vercel_blob_rw_...
BLOB_PRIVATE_TOKEN=vercel_blob_rw_...

# Keep only while legacy Supabase media paths still exist.
LEGACY_MEDIA_URL=https://<project-ref>.storage.supabase.co/storage/v1/object/public/products
LEGACY_SUPABASE_URL=https://<project-ref>.supabase.co
LEGACY_SUPABASE_SERVICE_ROLE_KEY=...
LEGACY_PROOFS_BUCKET=proofs

CRON_SECRET=<long random string>
```

Do **not** set `PUBLIC_DISK_DRIVER=s3` or `PROOF_DISK_DRIVER=s3` on Vercel.
Laravel disks are only used for local development and Livewire temporary
uploads. Blob uploads are verified: the database is updated only after the Blob
API confirms the URL, and old objects are deleted only afterward.

### Region and timeouts

Set the Vercel project/function region to the same region as Supabase (for the
current project, Tokyo / `hnd1`). Cross-region database calls cost ~450 ms each
and make settings saves time out.

`Dockerfile.vercel` sets `max_execution_time=120`. Settings are cached as a
single map and saved with one upsert, so a settings save no longer performs one
query per key. Storefront rendering never calls Blob, S3, or remote `exists()`.

## 5. Migrate the database

Run once from a machine that has the production env values:

```bash
php artisan migrate --force
```

Migrations are intentionally not run at container start (several instances can
start at the same time).

## 6. Deploy and verify

```bash
npx vercel@latest deploy --prod
```

Check:

1. `GET /up` returns 200.
2. Storefront loads and bundled assets come from `/build/...`.
3. Staff login survives a page reload (database sessions).
4. Settings QR preview shows the Blob URL and checkout/tracking show the same QR.
5. GCash checkout uploads a screenshot to the private `Receipts` store.
6. Tracking page can re-upload a receipt; old receipt is deleted only afterward.
7. Staff can open the receipt through `/orders/{order}/proof`; the Blob URL is
   never exposed to the browser.
8. `GET /cron/abandoned-reminders` returns 403 without the bearer token.

## 7. Reminders

`vercel.json` registers a daily cron (`0 16 * * *` UTC = midnight in Manila).
The Hobby plan only allows daily crons. For 5-minute reminders, point an external
cron at:

```text
GET https://your-app.vercel.app/cron/abandoned-reminders
Authorization: Bearer <CRON_SECRET>
```

## Notes

- Container files are ephemeral; final uploads go to Vercel Blob and sessions/cache
  go to Postgres. Local development still uses Laravel disks
  (`MEDIA_DRIVER=local`, `PROOF_DRIVER=local`).
- `config:cache` is deliberately not built into the image because Vercel injects
  environment variables at runtime.
- Vercel caps request bodies at 4.5 MB, so product photos and receipts are
  limited to 4 MB. Product photos are resized in the browser before upload.
- Receipt URLs are never signed for the browser. `OrderProofController` fetches
  private Blob objects with the server-side token and streams them inline.
