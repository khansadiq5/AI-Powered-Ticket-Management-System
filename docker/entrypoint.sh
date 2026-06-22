#!/bin/sh
set -e

# Clear caches if any exist
php artisan cache:clear || true

# Cache Laravel configurations and routes for production
echo "Caching configurations..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Create SQLite database file if it is configured and does not exist
if [ "${DB_CONNECTION}" = "sqlite" ]; then
    DB_DATABASE_PATH="/var/www/html/database/database.sqlite"
    if [ ! -f "$DB_DATABASE_PATH" ]; then
        echo "Creating SQLite database at $DB_DATABASE_PATH..."
        mkdir -p "$(dirname "$DB_DATABASE_PATH")"
        touch "$DB_DATABASE_PATH"
        chown www-data:www-data "$DB_DATABASE_PATH"
    fi
fi

# Run database migrations
echo "Running migrations..."
php artisan migrate --force

# Ensure correct permissions on storage and bootstrap/cache
echo "Setting permissions..."
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Start supervisor to run Nginx, PHP-FPM, and Laravel workers
echo "Starting Supervisor..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
