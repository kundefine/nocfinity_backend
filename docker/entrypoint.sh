#!/bin/sh
set -e

# Configure Apache port based on Cloud Run's $PORT environment variable (default 8080)
PORT=${PORT:-8080}
sed -i "s/80/$PORT/g" /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf

# Ensure storage directories exist and have proper permissions
mkdir -p /var/www/html/storage/framework/cache/data \
         /var/www/html/storage/framework/sessions \
         /var/www/html/storage/framework/views \
         /var/www/html/storage/logs \
         /var/www/html/bootstrap/cache \
         /var/www/html/public/uploads/products

chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/public/uploads
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/public/uploads

# Create storage symlink if not already created
if [ ! -L /var/www/html/public/storage ]; then
    php artisan storage:link || true
fi

# Run database migrations if RUN_MIGRATIONS is not false
if [ "$RUN_MIGRATIONS" != "false" ]; then
    echo "Running database migrations..."
    php artisan migrate --force || echo "Migration failed or database not reachable yet"
    if [ "$RUN_SEEDERS" = "true" ]; then
        echo "Running database seeders..."
        php artisan db:seed --force || echo "Seeding skipped or failed"
    fi
fi

# Cache configuration and routes for production performance if APP_KEY is set
if [ -n "$APP_KEY" ]; then
    echo "Caching configuration and routes..."
    php artisan config:cache || true
    php artisan route:cache || true
    php artisan view:cache || true
fi

exec apache2-foreground
