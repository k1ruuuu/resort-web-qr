#!/usr/bin/env bash
set -euo pipefail

# =============================================================================
# Ubuntu 22.04+ automated installer for resort-web-qr (Laravel 12)
# Installs: Nginx, Apache2, PHP 8.2 + extensions, MySQL, Redis, Composer
# Configures: php.ini, .env, DB, cron, opcache, production optimization
# =============================================================================

APP_PATH="${1:-$(pwd)}"
APP_PUBLIC_PATH="$APP_PATH/public"
PHP_VERSION="8.2"
APACHE_PORT="8080"
NGINX_SITE_NAME="resort"
APACHE_SITE_NAME="resort"
DB_NAME="resort_voucher"
DB_USER="resort_user"

# Colours for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

info()  { echo -e "${GREEN}[INFO]${NC} $*"; }
warn()  { echo -e "${YELLOW}[WARN]${NC} $*"; }
error() { echo -e "${RED}[ERROR]${NC} $*"; }

# ---- Pre-flight checks ------------------------------------------------------
if [[ ! -d "$APP_PATH" || ! -f "$APP_PUBLIC_PATH/index.php" || ! -f "$APP_PATH/artisan" ]]; then
  error "This script must be run from the Laravel project root, or with the project path as argument."
  exit 1
fi

if [[ "$EUID" -ne 0 ]]; then
  error "This script requires sudo privileges. Re-run with sudo."
  exit 1
fi

# ---- 1. Package installation -------------------------------------------------
info "Updating apt cache..."
apt update -y

info "Installing Nginx, Apache2, PHP $PHP_VERSION + extensions, MySQL, Redis..."
apt install -y \
  nginx apache2 \
  "php$PHP_VERSION" "php$PHP_VERSION-cli" "php$PHP_VERSION-fpm" \
  "php$PHP_VERSION-mbstring" "php$PHP_VERSION-xml" "php$PHP_VERSION-curl" \
  "php$PHP_VERSION-zip" "php$PHP_VERSION-mysql" "php$PHP_VERSION-gd" \
  "php$PHP_VERSION-bcmath" "php$PHP_VERSION-intl" "php$PHP_VERSION-redis" \
  "php$PHP_VERSION-soap" "php$PHP_VERSION-sockets" \
  "libapache2-mod-php$PHP_VERSION" \
  mysql-server redis-server unzip curl git

# ---- 2. php.ini tuning -------------------------------------------------------
PHP_INI_DIR="/etc/php/$PHP_VERSION"
PHP_INIS=(
  "$PHP_INI_DIR/cli/php.ini"
  "$PHP_INI_DIR/fpm/php.ini"
  "$PHP_INI_DIR/apache2/php.ini"
)

info "Tuning php.ini settings..."
for ini in "${PHP_INIS[@]}"; do
  if [[ -f "$ini" ]]; then
    sed -i \
      -e 's/^memory_limit = .*/memory_limit = 256M/' \
      -e 's/^upload_max_filesize = .*/upload_max_filesize = 20M/' \
      -e 's/^post_max_size = .*/post_max_size = 20M/' \
      -e 's/^max_execution_time = .*/max_execution_time = 300/' \
      -e 's/^max_input_time = .*/max_input_time = 300/' \
      -e 's/^max_input_vars = .*/max_input_vars = 3000/' \
      -e 's/^;date\.timezone =.*/date.timezone = UTC/' \
      -e 's/^;opcache\.enable=.*/opcache.enable=1/' \
      -e 's/^;opcache\.memory_consumption=.*/opcache.memory_consumption=128/' \
      -e 's/^;opcache\.max_accelerated_files=.*/opcache.max_accelerated_files=10000/' \
      -e 's/^;opcache\.revalidate_freq=.*/opcache.revalidate_freq=2/' \
      "$ini"
    info "  Tuned: $ini"
  fi
done

# ---- 3. MySQL database setup -------------------------------------------------
info "Securing MySQL and creating database..."
mysql <<EOF
ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY '';
FLUSH PRIVILEGES;
EOF

# Generate random password for app user
DB_PASSWORD=$(openssl rand -base64 18 | tr -dc 'a-zA-Z0-9_!@#$%^&*()' | head -c24)

mysql <<EOF
CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASSWORD}';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
EOF

info "MySQL database '${DB_NAME}' created with user '${DB_USER}'"

# ---- 4. Redis -----------------------------------------------------------------
info "Enabling and starting Redis..."
systemctl enable redis-server
systemctl restart redis-server

# Generate random Redis password
REDIS_PASSWORD=$(openssl rand -base64 18 | tr -dc 'a-zA-Z0-9_!@#$%^&*()' | head -c24)
sed -i "s/^# requirepass .*/requirepass ${REDIS_PASSWORD}/" /etc/redis/redis.conf
systemctl restart redis-server
info "Redis secured with password"

# ---- 5. Composer installation -------------------------------------------------
if ! command -v composer &>/dev/null; then
  info "Installing Composer..."
  EXPECTED_CHECKSUM="$(php -r 'copy("https://composer.github.io/installer.sig", "php://stdout");')"
  php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
  ACTUAL_CHECKSUM="$(php -r "echo hash_file('sha384', 'composer-setup.php');")"

  if [[ "$EXPECTED_CHECKSUM" != "$ACTUAL_CHECKSUM" ]]; then
    error "Composer installer checksum mismatch. Aborting."
    rm composer-setup.php
    exit 1
  fi

  php composer-setup.php --quiet --install-dir=/usr/local/bin --filename=composer
  rm composer-setup.php
  info "Composer installed globally"
else
  info "Composer already installed, updating..."
  composer self-update --quiet
fi

# ---- 6. Apache configuration --------------------------------------------------
info "Configuring Apache on port $APACHE_PORT..."
if ! grep -q "^Listen $APACHE_PORT\b" /etc/apache2/ports.conf; then
  echo "Listen $APACHE_PORT" >> /etc/apache2/ports.conf
fi

APACHE_SITE_PATH="/etc/apache2/sites-available/${APACHE_SITE_NAME}.conf"
cat > "$APACHE_SITE_PATH" <<EOF
<VirtualHost *:${APACHE_PORT}>
    ServerName localhost
    DocumentRoot ${APP_PUBLIC_PATH}

    <Directory ${APP_PUBLIC_PATH}>
        AllowOverride All
        Require all granted
    </Directory>

    DirectoryIndex index.php index.html
    ErrorLog "/var/log/apache2/${APACHE_SITE_NAME}_error.log"
    CustomLog "/var/log/apache2/${APACHE_SITE_NAME}_access.log" combined
</VirtualHost>
EOF

a2enmod rewrite headers ssl expires
a2dissite 000-default.conf || true
if [[ -e /etc/apache2/sites-enabled/${APACHE_SITE_NAME}.conf ]]; then
  rm -f /etc/apache2/sites-enabled/${APACHE_SITE_NAME}.conf
fi
ln -sf "$APACHE_SITE_PATH" "/etc/apache2/sites-enabled/${APACHE_SITE_NAME}.conf"

# ---- 7. Nginx configuration ---------------------------------------------------
info "Configuring Nginx..."
NGINX_SITE_PATH="/etc/nginx/sites-available/${NGINX_SITE_NAME}.conf"
cat > "$NGINX_SITE_PATH" <<EOF
server {
    listen 80;
    server_name _;

    root ${APP_PUBLIC_PATH};
    index index.php index.html index.htm;

    access_log /var/log/nginx/${NGINX_SITE_NAME}.access.log;
    error_log /var/log/nginx/${NGINX_SITE_NAME}.error.log;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass unix:/run/php/php${PHP_VERSION}-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        fastcgi_param PATH_INFO \$fastcgi_path_info;
    }

    location ~ /(?:\.env|composer\.json|composer\.lock|artisan|readme\.md|docker-compose\.yml) {
        deny all;
        return 404;
    }

    location ~ /\.git {
        deny all;
        return 404;
    }

    client_max_body_size 20M;
}
EOF

if [[ -e /etc/nginx/sites-enabled/default ]]; then
  rm -f /etc/nginx/sites-enabled/default
fi
ln -sf "$NGINX_SITE_PATH" "/etc/nginx/sites-enabled/${NGINX_SITE_NAME}.conf"

# ---- 8. .env configuration ----------------------------------------------------
info "Setting up .env file..."
cd "$APP_PATH"

if [[ ! -f .env ]]; then
  cp .env.example .env
  info "  Created .env from .env.example"
else
  warn "  .env already exists, skipping creation"
fi

# Update .env with generated credentials
sed -i "s/^DB_DATABASE=.*/DB_DATABASE=${DB_NAME}/" .env
sed -i "s/^DB_USERNAME=.*/DB_USERNAME=${DB_USER}/" .env
sed -i "s/^DB_PASSWORD=.*/DB_PASSWORD=${DB_PASSWORD}/" .env
sed -i "s/^REDIS_PASSWORD=.*/REDIS_PASSWORD=${REDIS_PASSWORD}/" .env
sed -i "s|^APP_URL=.*|APP_URL=http://localhost|" .env

# Prompt for APP_KEY generation
if grep -q "^APP_KEY=$" .env; then
  php artisan key:generate --quiet
  info "  Application key generated"
fi

# ---- 9. Composer install ------------------------------------------------------
info "Running composer install..."
export COMPOSER_ALLOW_SUPERUSER=1
composer install --no-interaction --prefer-dist --optimize-autoloader

# ---- 10. Storage link ---------------------------------------------------------
info "Creating storage symlink..."
php artisan storage:link --quiet || true

# ---- 11. Database migration and seeding ---------------------------------------
info "Running database migrations..."
php artisan migrate --force --quiet

if php artisan db:show --quiet 2>/dev/null; then
  if [[ -z "$(php artisan tinker --execute='echo \App\Models\User::count();' --quiet 2>/dev/null)" ]]; then
    info "Seeding database..."
    php artisan db:seed --force --quiet || warn "  Seeding skipped (may already have data)"
  fi
fi

# ---- 12. Production optimization ----------------------------------------------
info "Caching routes, config, and views for production..."
php artisan config:cache --quiet
php artisan route:cache --quiet
php artisan view:cache --quiet
php artisan event:cache --quiet

setfacl -dR -m u:www-data:rwX,u:$(stat -c '%U' .):rwX storage bootstrap/cache public/uploads 2>/dev/null || true
chmod -R 775 storage bootstrap/cache public/uploads 2>/dev/null || true

# ---- 13. Cron setup -----------------------------------------------------------
info "Setting up Laravel scheduler cron..."
CRON_JOB="* * * * * cd ${APP_PATH} && php artisan schedule:run >> /dev/null 2>&1"
if ! crontab -l 2>/dev/null | grep -Fq "$CRON_JOB"; then
  (crontab -l 2>/dev/null; echo "$CRON_JOB") | crontab -
  info "  Cron job added"
else
  info "  Cron job already exists"
fi

# ---- 14. Restart services -----------------------------------------------------
info "Testing and restarting services..."
apache2ctl configtest
nginx -t
systemctl restart "php${PHP_VERSION}-fpm"
systemctl restart apache2
systemctl restart nginx

# ---- 15. Summary --------------------------------------------------------------
echo ""
echo -e "${GREEN}============================================${NC}"
echo -e "${GREEN}  Setup complete!${NC}"
echo -e "${GREEN}============================================${NC}"
echo ""
echo "  Apache:         http://localhost:${APACHE_PORT}"
echo "  Nginx:          http://localhost"
echo ""
echo "  Database:       ${DB_NAME}"
echo "  DB Username:    ${DB_USER}"
echo "  DB Password:    ${DB_PASSWORD}"
echo ""
echo "  Redis Password: ${REDIS_PASSWORD}"
echo ""
echo "  Save these credentials securely."
echo "  For production, update APP_URL and configure HTTPS."
echo ""
