#!/bin/bash
set -e

echo "PHP version: $(php -v | head -1)"
echo "PHP binary: $(which php)"
php -m | grep -i gd || echo "gd extension NOT loaded for this PHP binary"

composer install --no-dev --optimize-autoloader --no-interaction
php artisan package:discover --ansi

if ! grep -q "^APP_KEY=.\+" .env; then
    php artisan key:generate --force
fi

mkdir -p public/storage

php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
