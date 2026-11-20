#!/bin/bash
set -e

# Ensure only single MPM (mpm_prefork) is loaded to prevent AH00534
a2dismod mpm_event mpm_worker 2>/dev/null || true
a2enmod mpm_prefork 2>/dev/null || true

# Bind Apache to Railway's dynamic PORT
if [ -n "$PORT" ] && [ "$PORT" != "80" ]; then
    sed -i "s/Listen 80/Listen $PORT/g" /etc/apache2/ports.conf 2>/dev/null || true
    sed -i "s/:80/:$PORT/g" /etc/apache2/sites-available/*.conf 2>/dev/null || true
fi

# Ensure storage directory structure and permissions
mkdir -p /var/www/html/storage/framework/{sessions,views,cache} /var/www/html/storage/logs
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Run database migrations and seeding
if [ "$RUN_MIGRATIONS" = "true" ] || [ -n "$DB_HOST" ] || [ -n "$MYSQLHOST" ]; then
    echo "Checking database connection and running migrations..."
    for i in 1 2 3 4 5; do
        if php artisan migrate --force; then
            echo "Migrations ran successfully."
            php artisan db:seed --force || true
            break
        else
            echo "Database connection or migration failed (attempt $i/5). Retrying in 2 seconds..."
            sleep 2
        fi
    done
fi

# Cache configuration, routes, and views if APP_KEY is present
if [ -n "$APP_KEY" ]; then
    php artisan config:cache || true
    php artisan route:cache || true
    php artisan view:cache || true
fi

exec "$@"
