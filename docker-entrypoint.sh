#!/bin/bash
set -e

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
