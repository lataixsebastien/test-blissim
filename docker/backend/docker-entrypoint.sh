#!/bin/sh
set -e

cd /app

if [ -f composer.json ] && [ ! -f vendor/autoload.php ]; then
  composer install --no-interaction --prefer-dist
fi

mkdir -p var/cache var/log
chown -R www-data:www-data var

php bin/console assets:install public --no-interaction
chown -R www-data:www-data var

exec "$@"
