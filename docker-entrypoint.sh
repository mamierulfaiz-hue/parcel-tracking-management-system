#!/bin/sh
set -e

# Copy .env from example if it is missing
if [ ! -f /var/www/html/.env ]; then
  cp /var/www/html/.env.example /var/www/html/.env
fi

# Wait for MySQL to be ready before running migrations
until php -r "try { new PDO('mysql:host=' . getenv('DB_HOST') . ';port=' . getenv('DB_PORT') . ';dbname=' . getenv('DB_DATABASE'), getenv('DB_USERNAME'), getenv('DB_PASSWORD')); } catch (Exception $e) { exit(1); }" >/dev/null 2>&1; do
  echo "Waiting for MySQL..."
  sleep 1
done

cd /var/www/html
php artisan key:generate --force
php artisan migrate --force
php artisan db:seed --force
exec /start.sh
