# DE-PRINCE HUB

Academic-grade monorepo scaffold for cyber cafe management + vendor marketplace.

- Backend: Laravel 10 API + Sanctum
- Frontend: Vue 3 + Vite (SPA)
- Payments: Stripe + Paystack + Flutterwave (Nigeria)
- Dev infra: Docker Compose (Postgres, Redis, MailHog, Nginx)

## Quickstart (Docker)

1) Start services

```bash
docker compose up -d --build
```

2) Backend install + setup (inside the container)

```bash
docker compose exec app composer install
docker compose exec app cp .env.example .env
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
```

3) Frontend

```bash
cd frontend
npm install
npm run dev
```

- Frontend: http://localhost:5173
- Backend (Nginx): http://localhost:8080
- API ping: http://localhost:8080/api/ping
- MailHog: http://localhost:8025

## Webhooks (ngrok)

Expose port 8080, then register the webhook URLs with each provider:

- Paystack: `https://<ngrok>.ngrok.io/api/webhooks/paystack`
- Flutterwave: `https://<ngrok>.ngrok.io/api/webhooks/flutterwave`
- Stripe: `https://<ngrok>.ngrok.io/api/webhooks/stripe`

Use HTTPS for all production webhooks.

## Local Environment Notes

- Frontend calls `/api/...` in dev; Vite proxies those requests to `VITE_API_URL` (or `http://localhost:8080`).
- Backend auth endpoints:
  - `POST /api/auth/register`
  - `POST /api/auth/login`
  - `POST /api/auth/logout` (auth)
  - `GET /api/auth/me` (auth)

## GitHub Repo Helper Scripts

Create and push a repo using a GitHub token:

```bash
export GITHUB_TOKEN=ghp_xxx
./create_repo.sh your-org-or-username deprince-hub
```

Alternative using `gh` CLI:

```bash
gh auth login
./create_repo_with_gh.sh your-org-or-username deprince-hub
```

## Security

- Never commit real secrets/keys.
- Replace all placeholder payment keys in `.env` before deploying.

## Credits

- Author: (your name)
- Course/Department: (your details)
