# Railway Deployment

Deploy the backend and frontend as two Railway services from this repository. Railway should build each service from its own directory, so configure the root directory before the first deployment.

## Create the project

1. Push this repository to GitHub.
2. In Railway, create a new project with **Deploy from GitHub repo**.
3. Add a PostgreSQL database to the project.
4. Add two services from the same repository: one for `backend` and one for `frontend`.

## Backend service

1. Set the service **Root Directory** to `/backend`.
2. Set its **Dockerfile Path** to `Dockerfile.railway`.
3. Add these variables:

```text
APP_ENV=production
APP_DEBUG=false
APP_KEY=<generate with: php artisan key:generate --show>
APP_URL=https://<backend-domain>
DB_CONNECTION=pgsql
DATABASE_URL=${{Postgres.DATABASE_URL}}
DB_SSLMODE=require
LOG_CHANNEL=stderr
LOG_LEVEL=error
SESSION_DRIVER=file
FRONTEND_URL=https://<frontend-domain>
```

Generate a public domain for this service. Its health check URL is `/api/ping`.

## Frontend service

1. Set the service **Root Directory** to `/frontend`.
2. Set its **Dockerfile Path** to `Dockerfile.railway`.
3. Add this variable to the frontend service. It is used while Vite builds the browser bundle:

```text
VITE_API_URL=https://<backend-domain>
```

Generate a public domain for the frontend service and open that URL.

After generating both domains, replace `<backend-domain>` and `<frontend-domain>` with the actual Railway domains. Redeploy the frontend after setting `VITE_API_URL`, because Vite values are embedded during the image build.

## Verify deployment

1. Open `https://<backend-domain>/api/ping` and confirm it returns JSON.
2. Open the frontend domain and test registration or login.
3. Check the backend service logs if the health check fails. The most common causes are a missing `APP_KEY`, an unset PostgreSQL reference, or a failed migration.

## Payment and mail settings

Add the real production payment and mail variables to the backend service only. Do not commit secrets. Configure each payment provider webhook to use:

```text
https://<backend-domain>/api/webhooks/stripe
https://<backend-domain>/api/webhooks/paystack
https://<backend-domain>/api/webhooks/flutterwave
```

For production email, replace the local MailHog values with an SMTP provider. Railway's PostgreSQL service supplies `DATABASE_URL`; do not copy its password into source files.