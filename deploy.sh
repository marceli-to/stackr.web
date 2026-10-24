#!/bin/zsh
# Deploys the committed state of this repository to stackr.kimiyu.ch (Hostpoint) over SSH,
# using your own SSH key. Run from your Mac: ./deploy.sh
#
# Frontend assets are built locally and committed (npm run build). public/.htaccess (PHP version,
# HTTPS, security headers, caching) is versioned and deployed like everything else. The server's
# .env, storage (attachments, backups) and the SQLite database are never overwritten or deleted.
# Migrations run after the upload.
set -euo pipefail
cd "$(dirname "$0")"

HOST=pumutuxu@pumutuxu.myhostpoint.ch
DEST=/home/pumutuxu/www/stackr.kimiyu.ch
PHP=/usr/local/php85/bin/php

if [[ -n $(git status --porcelain) ]]; then
	echo "You have uncommitted changes. Commit them first; only committed code is deployed."
	exit 1
fi
if [[ ! -f public/build/manifest.json ]]; then
	echo "public/build is missing. Run 'npm run build' and commit the result."
	exit 1
fi

echo "Running tests…"
php artisan test --compact

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
	--exclude '/.env' --exclude '/public/hot' --exclude '/deploy.sh' --exclude '/scripts' \
	--filter 'protect /storage/**' \
	--filter 'protect /database/database.sqlite*' \
	"$STAGE/" "$HOST:$DEST/"

echo "Migrating and refreshing caches…"
ssh "$HOST" "cd '$DEST' && $PHP artisan migrate --force && $PHP artisan app:make-thumbnails >/dev/null && $PHP artisan optimize:clear >/dev/null && $PHP artisan optimize >/dev/null && $PHP artisan queue:restart >/dev/null"

echo "Deployed $(git rev-parse --short HEAD) to https://stackr.kimiyu.ch"
