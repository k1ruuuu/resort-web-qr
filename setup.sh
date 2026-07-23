#!/usr/bin/env bash
set -euo pipefail
IFS=$'\n\t'

# =============================================================================
# resort-web-qr — All-in-One Automated Setup
#
# Installs and configures everything needed to run the application:
#   PHP 8.2 + extensions + php.ini tuning
#   MySQL database + user
#   Composer + project dependencies
#   Laravel environment, key, storage, migrations, seed
#   Nginx virtual host (optional)
#   Cron schedule for queue/scheduler
#   Production optimizations
#
# Usage:
#   sudo ./setup.sh                    # Interactive setup (prompts for optional steps)
#   sudo ./setup.sh --seed             # Also seed demo data
#   sudo ./setup.sh --production       # Non-interactive production deploy
#   sudo ./setup.sh --skip-nginx       # Skip Nginx configuration
#   sudo ./setup.sh --dry-run          # Show what would be done, no changes
#
# Environment variables (skip prompts):
#   APP_URL       DB_DATABASE  DB_USERNAME  DB_PASSWORD
#   DB_HOST       DB_PORT      ADMIN_EMAIL   ADMIN_PASSWORD
# =============================================================================

PROJECT_DIR="$(cd "$(dirname "$0")" && pwd)"
cd "$PROJECT_DIR"

# ---- Flags ----------------------------------------------------------------
SEED=false
PRODUCTION=false
SKIP_NGINX=false
DRY_RUN=false

while [[ $# -gt 0 ]]; do
    case "$1" in
        --seed) SEED=true; shift ;;
        --production) PRODUCTION=true; shift ;;
        --skip-nginx) SKIP_NGINX=true; shift ;;
        --dry-run) DRY_RUN=true; shift ;;
        *) echo "Unknown option: $1"; exit 1 ;;
    esac
done

# ---- Helpers --------------------------------------------------------------
RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; CYAN='\033[0;36m'; NC='\033[0m'
info()     { echo -e "${GREEN}[INFO]${NC}  $*"; }
warn()     { echo -e "${YELLOW}[WARN]${NC}  $*"; }
error()    { echo -e "${RED}[ERROR]${NC} $*"; }
section()  { echo -e "\n${CYAN}══════════════════════════════════════════════════${NC}"; echo -e "${CYAN}  $*${NC}"; echo -e "${CYAN}══════════════════════════════════════════════════${NC}"; }
run()      { if [[ "$DRY_RUN" == "true" ]]; then echo "  [DRY-RUN] $*"; else eval "$*"; fi; }
ok()       { echo -e "${GREEN}[OK]${NC}    $*"; }
die()      { error "$*"; exit 1; }

require_root() {
    if [[ "$EUID" -ne 0 ]]; then
        die "This script requires root/sudo privileges. Run with: sudo $0"
    fi
}

confirm() {
    local prompt="$1" default="${2:-N}"
    if [[ "$PRODUCTION" == "true" ]]; then return 0; fi
    read -r -p "  ${prompt} [y/N] " REPLY
    [[ "$REPLY" =~ ^[Yy]$ ]]
}

if [[ "$DRY_RUN" == "true" ]]; then
    warn "DRY-RUN mode — no changes will be made."
fi

# ===========================================================================
# OS Detection
# ===========================================================================
section "Detecting operating system"

detect_os() {
    case "$(uname -s)" in
        Linux)
            if [[ -f /etc/os-release ]]; then
                . /etc/os-release
                OS="$ID"
                OS_LIKE="${ID_LIKE:-}"
            else
                die "Cannot detect OS (no /etc/os-release)."
            fi
            ;;
        Darwin) OS="macos" ;;
        *) die "Unsupported OS: $(uname -s)" ;;
    esac
}
detect_os
info "Detected: $OS"

PKG_MANAGER=""
INSTALL_CMD=""
PHP_PREFIX=""

if [[ "$OS" =~ ^(ubuntu|debian|pop|linuxmint|elementary|zorin)$ ]]; then
    PKG_MANAGER="apt"
    INSTALL_CMD="apt install -y"
    PHP_PREFIX="php8.2"
    PHP_INI_DIR="/etc/php/8.2"
    PHP_FPM_SERVICE="php8.2-fpm"
elif [[ "$OS" =~ ^(centos|rhel|fedora|rocky|almalinux)$ ]]; then
    if command -v dnf &>/dev/null; then
        PKG_MANAGER="dnf"
        INSTALL_CMD="dnf install -y"
    else
        PKG_MANAGER="yum"
        INSTALL_CMD="yum install -y"
    fi
    PHP_PREFIX="php8.2"
    PHP_INI_DIR="/etc/php/8.2"
    PHP_FPM_SERVICE="php8.2-fpm"
elif [[ "$OS" == "macos" ]]; then
    info "macOS detected — will use Homebrew where possible."
else
    die "Unsupported Linux distribution: $OS"
fi

# ===========================================================================
# 1. PHP 8.2 + Extensions
# ===========================================================================
section "Installing PHP $PHP_PREFIX and required extensions"

require_root

install_php_ubuntu() {
    add-apt-repository -y ppa:ondrej/php 2>/dev/null || true
    apt update -y
}

install_php_rhel() {
    if command -v dnf &>/dev/null; then
        dnf install -y epel-release
        dnf module enable -y php:8.2 2>/dev/null || true
    elif command -v yum &>/dev/null; then
        yum install -y epel-release
    fi
    if ! rpm -q remi-release &>/dev/null; then
        local url="https://rpms.remirepo.net/enterprise/remi-release-$(rpm -E %{rhel}).rpm"
        rpm -Uvh "$url" 2>/dev/null || true
    fi
}

if ! command -v php &>/dev/null || [[ "$(php -r 'echo PHP_MAJOR_VERSION;')" -lt 8 ]]; then
    case "$OS" in
        ubuntu|debian|pop|linuxmint|elementary|zorin)
            info "Adding ondrej/php PPA..."
            run "apt update -y && apt install -y software-properties-common && add-apt-repository -y ppa:ondrej/php && apt update -y"
            ;;
        centos|rhel|fedora|rocky|almalinux)
            info "Enabling PHP 8.2 repository..."
            run "dnf install -y epel-release dnf-utils 2>/dev/null || yum install -y epel-release yum-utils 2>/dev/null || true"
            run "dnf module enable -y php:8.2 2>/dev/null || yum module enable -y php:8.2 2>/dev/null || true"
            ;;
    esac

    info "Installing PHP 8.2 + extensions..."
    PHP_PACKAGES=(
        "$PHP_PREFIX" "$PHP_PREFIX-cli" "$PHP_PREFIX-fpm"
        "$PHP_PREFIX-mbstring" "$PHP_PREFIX-xml" "$PHP_PREFIX-curl"
        "$PHP_PREFIX-zip" "$PHP_PREFIX-mysql" "$PHP_PREFIX-gd"
        "$PHP_PREFIX-bcmath" "$PHP_PREFIX-intl" "$PHP_PREFIX-soap"
        "$PHP_PREFIX-sockets" "$PHP_PREFIX-bz2" "$PHP_PREFIX-gmp"
    )
    run "$INSTALL_CMD ${PHP_PACKAGES[*]}"
    ok "PHP 8.2 installed."
else
    PHP_VER=$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')
    info "PHP $PHP_VER already installed."

    if [[ "$(echo "$PHP_VER" | cut -d. -f1)" -eq 8 && "$(echo "$PHP_VER" | cut -d. -f2)" -ge 2 ]]; then
        ok "PHP version $PHP_VER meets requirement."
    else
        die "PHP ^8.2 required, found $PHP_VER. Upgrade PHP or remove it and re-run."
    fi
fi

# Verify extensions are available
# Map: PHP extension name → package suffix (empty = built-in, no package needed)
declare -A EXT_MAP=(
    [bcmath]="bcmath"
    [ctype]=""
    [curl]="curl"
    [fileinfo]="fileinfo"
    [gd]="gd"
    [intl]="intl"
    [json]=""
    [mbstring]="mbstring"
    [openssl]=""
    [pdo]=""
    [mysqli]="mysql"
    [tokenizer]=""
    [xml]="xml"
    [zip]="zip"
    [sockets]="sockets"
)
MISSING_PKGS=()
for ext in "${!EXT_MAP[@]}"; do
    pkg="${EXT_MAP[$ext]}"
    if [[ -z "$pkg" ]]; then
        continue  # built-in extension, no package needed
    fi
    if ! php -m 2>/dev/null | grep -qi "^$ext$"; then
        MISSING_PKGS+=("${PHP_PREFIX}-${pkg}")
    fi
done

if [[ ${#MISSING_PKGS[@]} -gt 0 ]]; then
    info "Installing missing PHP extensions: ${MISSING_PKGS[*]}"
    run "$INSTALL_CMD ${MISSING_PKGS[*]}"
fi

# ===========================================================================
# 2. php.ini Tuning
# ===========================================================================
section "Tuning php.ini"

if [[ -d "$PHP_INI_DIR" ]]; then
    PHP_INIS=()
    for d in "$PHP_INI_DIR/cli/php.ini" "$PHP_INI_DIR/fpm/php.ini" "$PHP_INI_DIR/apache2/php.ini"; do
        [[ -f "$d" ]] && PHP_INIS+=("$d")
    done

    for ini in "${PHP_INIS[@]}"; do
        run "sed -i \
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
            \"$ini\""
        info "  Tuned: $ini"
    done
else
    warn "PHP ini directory not found at $PHP_INI_DIR — skipping php.ini tuning."
    warn "You may need to tune php.ini manually."
fi

# ===========================================================================
# 3. MySQL
# ===========================================================================
section "Setting up MySQL"

if ! command -v mysql &>/dev/null; then
    info "Installing MySQL..."
    case "$OS" in
        ubuntu|debian|pop|linuxmint|elementary|zorin)
            run "apt install -y mysql-server"
            ;;
        centos|rhel|fedora|rocky|almalinux)
            run "$INSTALL_CMD mysql-server"
            ;;
        macos)
            run "brew install mysql"
            ;;
    esac
fi

if command -v mysql &>/dev/null; then
    run "systemctl enable mysql 2>/dev/null || systemctl enable mysqld 2>/dev/null || true"
    run "systemctl start mysql 2>/dev/null || systemctl start mysqld 2>/dev/null || true"

    # Database configuration
    DB_NAME="${DB_DATABASE:-resort_voucher}"
    DB_USER="${DB_USERNAME:-resort_user}"
    DB_PASS="${DB_PASSWORD:-$(openssl rand -base64 18 | tr -dc 'a-zA-Z0-9_!@#%' | head -c24)}"
    DB_HOST="${DB_HOST:-127.0.0.1}"
    DB_PORT="${DB_PORT:-3306}"

    info "Creating database '${DB_NAME}' and user '${DB_USER}'..."
    run "mysql -u root <<-EOSQL
        CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
        CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
        GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';
        FLUSH PRIVILEGES;
EOSQL"
    ok "Database '${DB_NAME}' ready with user '${DB_USER}'."
else
    warn "MySQL command not found."
    DB_NAME="${DB_DATABASE:-resort_voucher}"
    DB_USER="${DB_USERNAME:-resort_user}"
    DB_PASS="${DB_PASSWORD:-""}"
    DB_HOST="${DB_HOST:-127.0.0.1}"
    DB_PORT="${DB_PORT:-3306}"
fi

# ===========================================================================
# 4. Composer
# ===========================================================================
section "Installing Composer"

if ! command -v composer &>/dev/null; then
    info "Downloading Composer..."
    run "php -r 'copy(\"https://getcomposer.org/installer\", \"composer-setup.php\");'"
    run "php composer-setup.php --quiet --install-dir=/usr/local/bin --filename=composer"
    run "rm -f composer-setup.php"
    ok "Composer installed globally."
else
    info "Composer already installed: $(composer --version 2>/dev/null | head -1)"
fi

# ===========================================================================
# 5. Project Setup (directories, .env, key, storage)
# ===========================================================================
section "Configuring the application"

cd "$PROJECT_DIR"

# 5a. Directory structure
info "Creating required directories..."
run "mkdir -p storage/app/public/qrcodes \
         storage/framework/cache/data \
         storage/framework/sessions \
         storage/framework/testing \
         storage/framework/views \
         storage/logs \
         bootstrap/cache \
         public/uploads"
ok "Directories created."

# 5b. Environment file
if [[ ! -f .env ]]; then
    info "Creating .env from .env.example..."
    run "cp .env.example .env"
else
    warn ".env already exists — preserving existing file."
    if confirm "Overwrite .env with .env.example?"; then
        run "cp .env.example .env"
    fi
fi

# 5c. Write database credentials
run "sed -i 's|^DB_HOST=.*|DB_HOST=${DB_HOST}|' .env"
run "sed -i 's|^DB_PORT=.*|DB_PORT=${DB_PORT}|' .env"
run "sed -i 's|^DB_DATABASE=.*|DB_DATABASE=${DB_NAME}|' .env"
run "sed -i 's|^DB_USERNAME=.*|DB_USERNAME=${DB_USER}|' .env"
run "sed -i \"s|^DB_PASSWORD=.*|DB_PASSWORD=${DB_PASS}|\" .env"

# 5d. Production overrides
if [[ "$PRODUCTION" == "true" ]]; then
    run "sed -i 's/^APP_ENV=.*/APP_ENV=production/' .env"
    run "sed -i 's/^APP_DEBUG=.*/APP_DEBUG=false/' .env"
    run "sed -i 's/^SESSION_SECURE_COOKIE=.*/SESSION_SECURE_COOKIE=true/' .env"
    run "sed -i 's/^FORCE_HTTPS=.*/FORCE_HTTPS=true/' .env"

    APP_URL="${APP_URL:-http://localhost}"
    run "sed -i 's|^APP_URL=.*|APP_URL=${APP_URL}|' .env"
fi

# 5e. Generate APP_KEY
if grep -q "^APP_KEY=$" .env; then
    info "Generating application key..."
    run "php artisan key:generate --force"
    ok "APP_KEY generated."
else
    info "APP_KEY already set."
fi

# 5f. Composer install
info "Installing PHP dependencies..."
if [[ "$PRODUCTION" == "true" ]]; then
    run "composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev"
else
    run "composer install --no-interaction --prefer-dist --optimize-autoloader"
fi
ok "Dependencies installed."

# 5g. Storage link
info "Creating storage symlink..."
run "php artisan storage:link --force 2>/dev/null || true"
ok "Storage link created."

# ===========================================================================
# 6. Database Migration & Seeding
# ===========================================================================
section "Database migration"

info "Running migrations..."
run "php artisan migrate --force"
ok "Migrations completed."

if [[ "$SEED" == "true" ]]; then
    info "Running database seeder..."
    run "php artisan db:seed --force"
    ok "Database seeded."
elif [[ "$PRODUCTION" != "true" ]]; then
    if confirm "Seed database with demo data?"; then
        run "php artisan db:seed --force"
        ok "Database seeded."
    fi
fi

# ===========================================================================
# 7. Permissions
# ===========================================================================
section "Setting filesystem permissions"

run "chmod -R 775 storage bootstrap/cache public/uploads"
if command -v setfacl &>/dev/null; then
    run "setfacl -dR -m u:www-data:rwX,u:$(stat -c '%U' .):rwX storage bootstrap/cache public/uploads 2>/dev/null || true"
    ok "ACL permissions set."
fi
ok "Filesystem permissions configured."

# ===========================================================================
# 8. Nginx (optional)
# ===========================================================================
if [[ "$SKIP_NGINX" != "true" ]] && command -v nginx &>/dev/null; then
    section "Configuring Nginx"

    SERVER_NAME="${APP_URL:-localhost}"
    SERVER_NAME="${SERVER_NAME#http://}"
    SERVER_NAME="${SERVER_NAME#https://}"
    SERVER_NAME="${SERVER_NAME%%/*}"

    NGINX_CONF="/etc/nginx/sites-available/resort.conf"

    run "cat > ${NGINX_CONF} <<'NGINXEOF'
server {
    listen 80;
    server_name ${SERVER_NAME};

    root ${PROJECT_DIR}/public;
    index index.php index.html;

    access_log /var/log/nginx/resort.access.log;
    error_log  /var/log/nginx/resort.error.log;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
    }

    location ~ /(\.env|composer\.json|composer\.lock|artisan|readme\.md|Makefile) {
        deny all;
        return 404;
    }

    location ~ /\.git {
        deny all;
        return 404;
    }

    client_max_body_size 20M;
}
NGINXEOF"

    run "rm -f /etc/nginx/sites-enabled/default 2>/dev/null || true"
    run "ln -sf ${NGINX_CONF} /etc/nginx/sites-enabled/"

    if nginx -t 2>/dev/null; then
        run "systemctl restart nginx"
        ok "Nginx configured and restarted."
    else
        warn "Nginx configuration test failed — check ${NGINX_CONF} manually."
    fi
elif [[ "$SKIP_NGINX" == "true" ]]; then
    info "Nginx configuration skipped (--skip-nginx)."
else
    info "Nginx not installed — skipping web server configuration."
    info "  To install Nginx: sudo apt install nginx (or your distro equivalent)"
fi

# ===========================================================================
# 9. Cron
# ===========================================================================
section "Setting up cron"

CRON_JOB="* * * * * cd ${PROJECT_DIR} && php artisan schedule:run >> /dev/null 2>&1"
if crontab -l 2>/dev/null | grep -Fq "$CRON_JOB"; then
    info "Laravel scheduler cron job already exists."
else
    if confirm "Add Laravel scheduler to crontab?"; then
        run "(crontab -l 2>/dev/null; echo \"$CRON_JOB\") | crontab -"
        ok "Cron job added."
    fi
fi

# ===========================================================================
# 10. Production Optimizations
# ===========================================================================
if [[ "$PRODUCTION" == "true" ]]; then
    section "Production optimizations"

    info "Caching configuration..."
    run "php artisan config:cache"
    run "php artisan route:cache"
    run "php artisan view:cache"
    run "php artisan event:cache"
    ok "Configuration, routes, views, and events cached."

    info "Restarting PHP-FPM..."
    run "systemctl restart ${PHP_FPM_SERVICE} 2>/dev/null || true"
fi

# ===========================================================================
# Summary
# ===========================================================================
section "Setup complete"

DB_PASS_DISPLAY="${DB_PASS:-(unchanged)}"
echo ""
echo -e "  ${GREEN}Website:${NC}     ${APP_URL:-http://localhost}"
echo -e "  ${GREEN}Database:${NC}    ${DB_NAME}"
echo -e "  ${GREEN}DB User:${NC}     ${DB_USER}"
echo -e "  ${GREEN}DB Pass:${NC}     ${DB_PASS_DISPLAY}"
echo ""
echo "  Default login: admin@resort.local / password"
echo ""
echo "  Next steps:"
echo "    1. Set APP_URL and configure HTTPS in .env for production"
echo "    2. Set up Supervisor for queue worker:"
echo "       php artisan queue:work --daemon"
echo "    3. Review .env settings (mail, 2FA, rate limits)"
echo ""

if [[ "$DRY_RUN" == "true" ]]; then
    warn "DRY-RUN mode — no changes were actually made."
fi
