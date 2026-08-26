
# Railway Deployment

Deploy the backend and frontend as two Railway services from this monorepo.

## ?? Railway Platform Constraint

**The Railway UI will NOT let you add a second "Deploy from GitHub repo" service from the same repository inside one project.** This is a Railway product decision — not a code bug. Use Option A, B, or C below.

## Prerequisites

1. Push this repository to GitHub.

2. Make sure the root `railway.json` (declares `services.backend` + `services.frontend`) is committed.

## Create the project

1. In Railway, create a new project with **Deploy from GitHub repo** ? pick this repo.
   - If the root `railway.json` monorepo declaration exists, Railway will prompt you to pick which service(s) to add — tick both.
   - If it only creates one, follow Option A or B below for the second.

2. Add a PostgreSQL database to the project named `Postgres` (so the `${{Postgres.DATABASE_URL}}` reference works).

3. Configure the backend then frontend (see per-service sections below).

### Option A — Railway CLI (RECOMMENDED, bypasses UI entirely)

Run from the repo root:

```powershell
npm install -g @railway/cli
railway login
railway link <project-id>
railway up --service backend  --service-name backend
railway up --service frontend --service-name frontend

```

### Option B — Empty-service UI workaround (no CLI, no 2nd repo)

1. In your existing Railway project ? **New Service** ? choose **Empty Service** (NOT "Deploy from GitHub repo").

2. Open the empty service ? **Settings** ? **Source** ? connect this same GitHub repo.

3. Set: **Root Directory** = `/frontend`, **Dockerfile Path** = `Dockerfile.railway`.

4. Set the `VITE_API_URL` variable (see Frontend section below) ? trigger a redeploy.

### Option C — Fresh project after adding root `railway.json`

Commit the root-level monorepo `railway.json`, delete the Railway project, and create a fresh project from the same repo. Railway will now offer both services up front.

## Backend service

1. **Root Directory** = `/backend`

2. **Dockerfile Path** = `Dockerfile.railway` (runs `php artisan serve` on `$PORT` and auto-runs migrations on boot)

3. Variables (set BEFORE the first boot — `APP_KEY` and `DATABASE_URL` are required):

```text
APP_ENV=production
APP_DEBUG=false
APP_KEY=<generate locally with: php artisan key:generate --show --env=production>
APP_URL=https://<backend-domain>
DB_CONNECTION=pgsql
DATABASE_URL=${{Postgres.DATABASE_URL}}
DB_SSLMODE=require
LOG_CHANNEL=stderr
LOG_LEVEL=error
SESSION_DRIVER=file
FRONTEND_URL=https://<frontend-domain>

```

Generate a public domain. Health check URL is `/api/ping`.

> **CORS note**: `FRONTEND_URL` must be the exact final public frontend origin (scheme + host, no trailing slash). If you change the frontend domain later, update `FRONTEND_URL` **and redeploy the backend** (read at Laravel boot).

## Frontend service

1. **Root Directory** = `/frontend`

2. **Dockerfile Path** = `Dockerfile.railway` (multi-stage: Vite build ? nginx on `$PORT`)

3. Variable — set BEFORE the first deploy because Vite bakes `import.meta.env.VITE_*` INTO the static bundle at BUILD time:

```text
VITE_API_URL=https://<backend-domain>

```

Generate a public domain and open that URL.

## Order of operations after BOTH domains generate

1. Backend domain known ? frontend `VITE_API_URL` ? **Redeploy frontend** (rebuilds the bundle).

2. Frontend domain known ? backend `FRONTEND_URL` ? **Redeploy backend** (CORS + Sanctum).

3. Test in an incognito window: sign-up, login, open an invoice pay page.

## Verify deployment

1. `https://<backend-domain>/api/ping` ? returns JSON with `"message":"DE-PRINCE HUB API v1"`

2. Frontend domain loads ? homepage renders, no CORS/OPTIONS failures on auth

3. Open `/invoices/INV-202601-000001/pay` ? `GET /api/invoices/INV-*` returns **200** (not 404 — confirms Invoice route-key fix)

4. Complete a Flutterwave test payment ? after redirect ? `POST /api/payments/verify/flutterwave` returns **200** (not 405/401 — confirms route method + auth fix)

5. If backend health check fails ? backend service logs (missing `APP_KEY`, unset DB reference, or migration failure)

## Payment and mail settings

Add these to the **backend service only** (never frontend — secrets would ship to browsers):

- Payment: `PAYSTACK_SECRET_KEY`, `FLW_SECRET_KEY`, `STRIPE_SECRET`

- Mail: `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`

Webhook URLs:

```text
https://<backend-domain>/api/webhooks/stripe
https://<backend-domain>/api/webhooks/paystack
https://<backend-domain>/api/webhooks/flutterwave

```

Railway PostgreSQL supplies `DATABASE_URL` automatically — never manually copy its password.