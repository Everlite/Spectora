#!/bin/sh

# ---------------------------------------------------------------------
# Spectora Agency Edition - Docker Entrypoint Script
# ---------------------------------------------------------------------

set -e

# 1. Fix permissions on mounted volumes (MUST run before anything else)
echo "Setting permissions on storage and database volumes..."
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database

# Ensure required storage subdirectories exist
mkdir -p /var/www/html/storage/logs \
         /var/www/html/storage/framework/cache/data \
         /var/www/html/storage/framework/sessions \
         /var/www/html/storage/framework/views \
         /var/www/html/storage/app/public/logos
chown -R www-data:www-data /var/www/html/storage
chmod -R 775 /var/www/html/storage

# 2. Ensure .env exists
if [ ! -f .env ]; then
    echo "Creating .env from .env.example..."
    cp .env.example .env
fi

# 3. Check for APP_KEY
if [ -z "$(grep -E "^APP_KEY=" .env | cut -d'=' -f2)" ]; then
    echo "APP_KEY missing. Generating new key..."
    php artisan key:generate --force
fi

# 4. Ensure SQLite database exists
touch /var/www/html/database/database.sqlite
chown www-data:www-data /var/www/html/database/database.sqlite
chmod 775 /var/www/html/database/database.sqlite

# 5. Run migrations
echo "Running database migrations..."
php artisan migrate --force

# 6. Create storage link if missing
if [ ! -L public/storage ]; then
    echo "Creating storage link..."
    php artisan storage:link
fi

# 7. Start Supervisor (Apache + Cron + Queue Worker)
echo "Starting services via Supervisor..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
