# Deploying to Vercel

The app runs on Vercel as a **container service** (FrankenPHP + Caddy), not as a
static Vite site. `Dockerfile.vercel` makes Vercel pick the Container preset even
though the repo contains Vite, which is what fixes the
`STATIC_BUILD_NO_OUT_DIR` build error.

## 1. Vercel project settings

In **Settings → General**:

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

## 2. Supabase storage

Create two buckets in Supabase Storage:

| Bucket   | Public | Holds                                      |
| -------- | ------ | ------------------------------------------ |
| `media`  | yes    | product photos, GCash QR, store settings   |
| `proofs` | no     | customer GCash receipt screenshots         |

Then generate S3 access keys (**Storage → S3 Connection**) and copy the
endpoint, region, access key, and secret.

Upload the existing files that are not in Git:

- `storage/app/public/store-settings/*` → `media/store-settings/`
- product images referenced by the seeded menu → `media/products/`
- `storage/app/private/order-proofs/*` → `proofs/order-proofs/`

## 3. Environment variables

Set these in **Settings → Environment Variables** (Production and Preview):

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

PROOF_DISK_DRIVER=s3
PUBLIC_DISK_DRIVER=s3
AWS_ACCESS_KEY_ID=...
AWS_SECRET_ACCESS_KEY=...
AWS_DEFAULT_REGION=<region>
AWS_ENDPOINT=https://<project-ref>.storage.supabase.co/storage/v1/s3
AWS_PROOFS_BUCKET=proofs
AWS_MEDIA_BUCKET=media
AWS_MEDIA_URL=https://<project-ref>.storage.supabase.co/storage/v1/object/public/media

CRON_SECRET=<long random string>
```

Do **not** set `AWS_USE_PATH_STYLE_ENDPOINT`; the disks always use path-style
because Supabase rejects virtual-hosted requests.

## 4. Migrate the database

Run once from a machine that has the production env values:

```bash
php artisan migrate --force
```

Migrations are intentionally not run at container start (several instances can
start at the same time).

## 5. Deploy and verify

```bash
npx vercel@latest deploy --prod
```

Check:

1. `GET /up` returns 200.
2. Storefront loads and assets come from `/build/...`.
3. Staff login survives a page reload (database sessions).
4. GCash checkout uploads a screenshot → file appears in the `proofs` bucket.
5. Tracking page can re-upload a receipt.
6. Staff can open the receipt thumbnail (signed URL).
7. `GET /cron/abandoned-reminders` returns 403 without the bearer token.

## 6. Reminders

`vercel.json` registers a daily cron (`0 16 * * *` UTC = midnight in Manila).
The Hobby plan only allows daily crons. For 5-minute reminders, point an external
cron at:

```
GET https://your-app.vercel.app/cron/abandoned-reminders
Authorization: Bearer <CRON_SECRET>
```

## Notes

- Container files are ephemeral; uploads go to Supabase and sessions/cache go to
  Postgres. Local development still uses the filesystem (`*_DISK_DRIVER=local`).
- `config:cache` is deliberately not built into the image because Vercel injects
  environment variables at runtime.
- Vercel caps request bodies at 4.5 MB, so receipt uploads are limited to 4 MB.
  Menu photos accept up to 10 MB, and large photos are resized in the browser
  before upload so they stay under the platform cap.
