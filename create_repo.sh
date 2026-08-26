#!/usr/bin/env bash
set -euo pipefail

OWNER="${1:-}"
REPO="${2:-}"

if [[ -z "${OWNER}" || -z "${REPO}" ]]; then
  echo "Usage: ./create_repo.sh <owner-or-org> <repo-name>"
  exit 1
fi

if [[ -z "${GITHUB_TOKEN:-}" ]]; then
  echo "GITHUB_TOKEN is not set"
  exit 1
fi

API="https://api.github.com"
AUTH_HEADER="Authorization: token ${GITHUB_TOKEN}"
ACCEPT_HEADER="Accept: application/vnd.github+json"

echo "Creating repo: ${OWNER}/${REPO}"

CREATE_PAYLOAD="$(cat <<JSON
{
  "name": "${REPO}",
  "private": true,
  "auto_init": false,
  "has_issues": true,
  "has_projects": true,
  "has_wiki": false
}
JSON
)"

HTTP_CODE="$(curl -sS -o /tmp/create_repo_resp.json -w "%{http_code}" \
  -H "${AUTH_HEADER}" -H "${ACCEPT_HEADER}" \
  -X POST "${API}/orgs/${OWNER}/repos" \
  -d "${CREATE_PAYLOAD}" || true)"

if [[ "${HTTP_CODE}" != "201" ]]; then
  HTTP_CODE_USER="$(curl -sS -o /tmp/create_repo_resp_user.json -w "%{http_code}" \
    -H "${AUTH_HEADER}" -H "${ACCEPT_HEADER}" \
    -X POST "${API}/user/repos" \
    -d "${CREATE_PAYLOAD}" || true)"

  if [[ "${HTTP_CODE_USER}" != "201" ]]; then
    echo "Failed to create repo (org: ${HTTP_CODE}, user: ${HTTP_CODE_USER})"
    echo "Org response:"
    cat /tmp/create_repo_resp.json || true
    echo
    echo "User response:"
    cat /tmp/create_repo_resp_user.json || true
    exit 1
  fi
fi

REMOTE="https://github.com/${OWNER}/${REPO}.git"

if [[ ! -d .git ]]; then
  git init
fi

git add -A
git commit -m "Initial scaffold" || true
git branch -M main
git remote remove origin >/dev/null 2>&1 || true
git remote add origin "${REMOTE}"
git push -u origin main

echo "Done: ${REMOTE}"
