#!/bin/sh
set -e

cd /app

dependencies_hash="$(sha256sum package.json package-lock.json | sha256sum)"
installed_hash="$(cat node_modules/.dependencies.sha256 2>/dev/null || true)"

if [ "$dependencies_hash" != "$installed_hash" ] || [ ! -x node_modules/.bin/vite ]; then
  npm ci --no-audit --no-fund
  printf '%s\n' "$dependencies_hash" > node_modules/.dependencies.sha256
fi

exec "$@"
