#!/usr/bin/env bash
set -euo pipefail

# Ubuntu 22.04 automated installer for Nginx, Apache2, and PHP for this Laravel app.
# Run from the project root or pass the application path as the first argument.

APP_PATH="${1:-$(pwd)}"
PHP_VERSION="8.1"
APACHE_PORT="8080"
NGINX_SITE_NAME="resort"
APACHE_SITE_NAME="resort"
APP_PUBLIC_PATH="$APP_PATH/public"

if [[ ! -d "$APP_PATH" || ! -f "$APP_PUBLIC_PATH/index.php" || ! -f "$APP_PATH/artisan" ]]; then
  echo "ERROR: This script must be run from the Laravel project root or with the project path as argument."
  echo "Expected files not found in: $APP_PATH"
  exit 1
fi

if [[ "$EUID" -ne 0 ]]; then
  echo "This script requires sudo privileges. Re-run with sudo."
  exit 1
fi

echo "Updating apt cache..."
apt update -y

echo "Installing Nginx, Apache2, PHP, and common PHP extensions..."
apt install -y nginx apache2 php${PHP_VERSION} php${PHP_VERSION}-cli php${PHP_VERSION}-fpm php${PHP_VERSION}-mbstring php${PHP_VERSION}-xml php${PHP_VERSION}-curl php${PHP_VERSION}-zip php${PHP_VERSION}-mysql php${PHP_VERSION}-gd php${PHP_VERSION}-bcmath php${PHP_VERSION}-intl libapache2-mod-php${PHP_VERSION}

echo "Configuring Apache to listen on port $APACHE_PORT..."
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

echo "Enabling Apache modules and site..."
a2enmod rewrite headers ssl expires
if [[ -e /etc/apache2/sites-enabled/${APACHE_SITE_NAME}.conf ]]; then
  rm -f /etc/apache2/sites-enabled/${APACHE_SITE_NAME}.conf
fi
ln -sf "$APACHE_SITE_PATH" "/etc/apache2/sites-enabled/${APACHE_SITE_NAME}.conf"

a2dissite 000-default.conf || true

echo "Configuring Nginx site for the Laravel application..."
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

    client_max_body_size 16M;
}
EOF

if [[ -e /etc/nginx/sites-enabled/default ]]; then
  rm -f /etc/nginx/sites-enabled/default
fi
ln -sf "$NGINX_SITE_PATH" "/etc/nginx/sites-enabled/${NGINX_SITE_NAME}.conf"

echo "Testing Apache configuration..."
apache2ctl configtest

echo "Testing Nginx configuration..."
nginx -t

echo "Restarting services..."
systemctl restart php${PHP_VERSION}-fpm
systemctl restart apache2
systemctl restart nginx

echo "Setup complete."
echo "Apache is available on port ${APACHE_PORT}."
echo "Nginx is available on port 80 and proxies the app using PHP-FPM."
echo "If you want Apache on port 80 instead, update /etc/apache2/ports.conf and your Apache site file accordingly."
