#!/bin/sh
set -e

# =============================================================================
# PHP-FPM Docker entrypoint for resort-web-qr
# Handles first-run setup: key generation, migrations, cache
# =============================================================================

# Ensure storage directories exist (may be empty host volume)
mkdir -p /var/www/html/storage/framework/{sessions,views,cache,testing}
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/bootstrap/cache
mkdir -p /var/www/html/public/uploads

# Generate app key if empty
if grep -q "^APP_KEY=$" /var/www/html/.env 2>/dev/null; then
    echo "[ENTRYPOINT] Generating application key..."
    php /var/www/html/artisan key:generate --force --quiet
fi

# Run pending migrations (non-blocking, continues on failure)
echo "[ENTRYPOINT] Running database migrations..."
php /var/www/html/artisan migrate --force --quiet 2>/dev/null || true

# Storage link
php /var/www/html/artisan storage:link --quiet 2>/dev/null || true

echo "[ENTRYPOINT] Starting PHP-FPM..."

exec php-fpm
