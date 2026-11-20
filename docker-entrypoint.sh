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

# Cache configuration, routes, and views if APP_KEY is present
if [ -n "$APP_KEY" ]; then
    php artisan config:cache || true
    php artisan route:cache || true
    php artisan view:cache || true
fi

# Run database migrations
if [ "$RUN_MIGRATIONS" = "true" ] || [ -n "$DB_HOST" ]; then
    php artisan migrate --force || true
fi

exec "$@"
