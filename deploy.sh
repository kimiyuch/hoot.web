#!/bin/zsh
# Deploys the committed state of this repository to hoot.kimiyu.ch (Hostpoint) over SSH,
# using your own SSH key. Run from your Mac: ./deploy.sh
#
# Frontend assets are built locally and committed (npm run build). The server's .env,
# public/.htaccess (PHP version, set in the Hostpoint panel), storage and caches are
# never overwritten or deleted.
set -euo pipefail
cd "$(dirname "$0")"

HOST=pumutuxu@pumutuxu.myhostpoint.ch
DEST=/home/pumutuxu/www/hoot.kimiyu.ch

if [[ -n $(git status --porcelain) ]]; then
  echo "You have uncommitted changes. Commit them first; only committed code is deployed."
  exit 1
fi
if [[ ! -f public/build/manifest.json ]]; then
  echo "public/build is missing. Run 'npm run build' and commit the result."
  exit 1
fi

echo "Running tests…"
php artisan test

STAGE=$(mktemp -d)
trap 'rm -rf "$STAGE"' EXIT
# mktemp creates the folder as 700; rsync would copy that onto the site folder and lock out the web server.
chmod 755 "$STAGE"

echo "Preparing $(git rev-parse --short HEAD)…"
git archive HEAD | tar -x -C "$STAGE"
composer install --working-dir="$STAGE" --no-interaction --no-progress --no-dev --optimize-autoloader --quiet

echo "Uploading…"
rsync -az --delete \
  --exclude '/.github' --exclude '/node_modules' --exclude '/tests' \
  --exclude '/.env' --exclude '/public/hot' --exclude '/deploy.sh' \
  --filter 'protect /public/.htaccess' \
  --filter 'protect /storage/**' \
  --filter 'protect /bootstrap/cache/*.php' \
  --filter 'protect /database/database.sqlite' \
  "$STAGE/" "$HOST:$DEST/"

echo "Migrating and refreshing caches…"
ssh "$HOST" "cd '$DEST' && php artisan migrate --force >/dev/null && php artisan optimize:clear >/dev/null && php artisan optimize >/dev/null"

echo "Deployed $(git rev-parse --short HEAD) to https://hoot.kimiyu.ch"
