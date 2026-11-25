#!/usr/bin/env bash
# Production Release Deployment Script for St. Cecilia PYC (Spec Section 15.1)
set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/scc-pyc}"
cd "${APP_DIR}"

php artisan down --retry=60

git pull --ff-only origin main

composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
npm ci
npm run build

php artisan migrate --force
php artisan db:seed --class=RolesAndPermissionsSeeder --force
php artisan db:seed --class=NotificationTemplatesSeeder --force

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart

if command -v supervisorctl >/dev/null 2>&1; then
    sudo supervisorctl restart scc-pyc:*
fi

php artisan up
php artisan pyc:health-check
