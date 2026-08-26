#!/usr/bin/env bash
set -euo pipefail

OWNER="${1:-}"
REPO="${2:-}"

if [[ -z "${OWNER}" || -z "${REPO}" ]]; then
  echo "Usage: ./create_repo_with_gh.sh <owner-or-org> <repo-name>"
  exit 1
fi

if ! command -v gh >/dev/null 2>&1; then
  echo "gh CLI not found. Install GitHub CLI and run: gh auth login"
  exit 1
fi

echo "Creating repo: ${OWNER}/${REPO}"

gh repo create "${OWNER}/${REPO}" --private --source=. --remote=origin --push

echo "Done: https://github.com/${OWNER}/${REPO}"
