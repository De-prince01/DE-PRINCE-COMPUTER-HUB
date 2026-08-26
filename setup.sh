#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

echo "DE-PRINCE HUB setup"
echo
echo "Repo: ${ROOT_DIR}"
echo

if command -v docker >/dev/null 2>&1; then
  echo "Docker: OK"
else
  echo "Docker: not found (optional if you run services another way)"
fi

if command -v node >/dev/null 2>&1; then
  echo "Node: $(node -v)"
else
  echo "Node: not found (required for frontend)"
fi

if command -v npm >/dev/null 2>&1; then
  echo "npm: $(npm -v)"
else
  echo "npm: not found (required for frontend)"
fi

echo
echo "Next steps:"
echo "- Docker: docker compose up -d --build"
echo "- Backend: docker compose exec app composer install && docker compose exec app php artisan migrate --seed"
echo "- Frontend: cd frontend && npm install && npm run dev"
