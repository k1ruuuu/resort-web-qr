#!/usr/bin/env python3
"""
Ubuntu 22.04+ automated installer for resort-web-qr (Laravel 12).
Installs: Nginx, Apache2, PHP 8.2 + extensions, MySQL, Redis, Composer
Configures: php.ini, .env, DB, cron, opcache, production optimization
"""
import argparse
import os
import re
import secrets
import string
import subprocess
import sys
from pathlib import Path

PHP_VERSION = "8.2"
APACHE_PORT = "8080"
NGINX_SITE_NAME = "resort"
APACHE_SITE_NAME = "resort"
DB_NAME = "resort_voucher"
DB_USER = "resort_user"


def run(cmd, check=True, capture_output=False, text=True):
    return subprocess.run(cmd, shell=True, check=check, capture_output=capture_output, text=text)


def require_root():
    if os.geteuid() != 0:
        print("[ERROR] This script must be run as root. Re-run with sudo.")
        sys.exit(1)


def verify_project_root(app_path: Path):
    if not app_path.exists() or not app_path.is_dir():
        raise FileNotFoundError(f"Project path does not exist: {app_path}")
    if not (app_path / 'artisan').exists() or not (app_path / 'public' / 'index.php').exists():
        raise FileNotFoundError(f"Not a Laravel project root: {app_path}")


def random_password(length=24):
    chars = string.ascii_letters + string.digits + "!@#$%^&*"
    return ''.join(secrets.choice(chars) for _ in range(length))


def install_packages():
    print("[INFO] Updating apt cache...")
    run("apt update -y")

    packages = [
        "nginx", "apache2",
        f"php{PHP_VERSION}", f"php{PHP_VERSION}-cli", f"php{PHP_VERSION}-fpm",
        f"php{PHP_VERSION}-mbstring", f"php{PHP_VERSION}-xml",
        f"php{PHP_VERSION}-curl", f"php{PHP_VERSION}-zip",
        f"php{PHP_VERSION}-mysql", f"php{PHP_VERSION}-gd",
        f"php{PHP_VERSION}-bcmath", f"php{PHP_VERSION}-intl",
        f"php{PHP_VERSION}-redis", f"php{PHP_VERSION}-soap",
        f"php{PHP_VERSION}-sockets",
        f"libapache2-mod-php{PHP_VERSION}",
        "mysql-server", "redis-server",
        "unzip", "curl", "git",
    ]
    print("[INFO] Installing required packages...")
    run(f"apt install -y {' '.join(packages)}")


def tune_php_ini():
    print("[INFO] Tuning php.ini settings...")
    php_ini_dirs = [
        f"/etc/php/{PHP_VERSION}/cli/php.ini",
        f"/etc/php/{PHP_VERSION}/fpm/php.ini",
        f"/etc/php/{PHP_VERSION}/apache2/php.ini",
    ]
    replacements = {
        r"^memory_limit\s*=.*": "memory_limit = 256M",
        r"^upload_max_filesize\s*=.*": "upload_max_filesize = 20M",
        r"^post_max_size\s*=.*": "post_max_size = 20M",
        r"^max_execution_time\s*=.*": "max_execution_time = 300",
        r"^max_input_time\s*=.*": "max_input_time = 300",
        r"^max_input_vars\s*=.*": "max_input_vars = 3000",
        r"^;?date\.timezone\s*=.*": "date.timezone = UTC",
        r"^;?opcache\.enable\s*=.*": "opcache.enable = 1",
        r"^;?opcache\.memory_consumption\s*=.*": "opcache.memory_consumption = 128",
        r"^;?opcache\.max_accelerated_files\s*=.*": "opcache.max_accelerated_files = 10000",
        r"^;?opcache\.revalidate_freq\s*=.*": "opcache.revalidate_freq = 2",
    }

    for ini_path in php_ini_dirs:
        ini = Path(ini_path)
        if not ini.exists():
            continue
        text = ini.read_text()
        for pattern, replacement in replacements.items():
            text = re.sub(pattern, replacement, text, flags=re.MULTILINE)
        ini.write_text(text)
        print(f"  Tuned: {ini_path}")


def setup_mysql(db_password: str):
    print("[INFO] Configuring MySQL...")
    run(f'mysql -e "ALTER USER \'root\'@\'localhost\' IDENTIFIED WITH mysql_native_password BY \'\'; FLUSH PRIVILEGES;"')
    run(f'mysql -e "CREATE DATABASE IF NOT EXISTS `{DB_NAME}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"')
    run(f"mysql -e \"CREATE USER IF NOT EXISTS '{DB_USER}'@'localhost' IDENTIFIED BY '{db_password}';\"")
    run(f"mysql -e \"GRANT ALL PRIVILEGES ON `{DB_NAME}`.* TO '{DB_USER}'@'localhost'; FLUSH PRIVILEGES;\"")
    print(f"  Database '{DB_NAME}' created with user '{DB_USER}'")


def setup_redis(redis_password: str):
    print("[INFO] Configuring Redis...")
    run("systemctl enable redis-server")
    redis_conf = Path("/etc/redis/redis.conf")
    text = redis_conf.read_text()
    text = re.sub(r"^# requirepass .*", f"requirepass {redis_password}", text, flags=re.MULTILINE)
    if "requirepass" not in text:
        text += f"\nrequirepass {redis_password}\n"
    redis_conf.write_text(text)
    run("systemctl restart redis-server")
    print("  Redis secured with password")


def install_composer():
    print("[INFO] Installing Composer...")
    result = run("php -r 'copy(\"https://composer.github.io/installer.sig\", \"php://stdout\");'",
                 capture_output=True)
    expected = result.stdout.strip()
    run("php -r 'copy(\"https://getcomposer.org/installer\", \"composer-setup.php\");'")
    result = run("php -r 'echo hash_file(\"sha384\", \"composer-setup.php\");'", capture_output=True)
    actual = result.stdout.strip()

    if expected != actual:
        print("[ERROR] Composer installer checksum mismatch. Aborting.")
        Path("composer-setup.php").unlink(missing_ok=True)
        sys.exit(1)

    run("php composer-setup.php --quiet --install-dir=/usr/local/bin --filename=composer")
    Path("composer-setup.php").unlink(missing_ok=True)
    print("  Composer installed globally")


def write_apache_site(app_path: Path):
    print(f"[INFO] Configuring Apache on port {APACHE_PORT}...")
    ports_conf = Path("/etc/apache2/ports.conf")
    text = ports_conf.read_text()
    if f"Listen {APACHE_PORT}" not in text:
        ports_conf.write_text(text + f"\nListen {APACHE_PORT}\n")

    site_file = Path(f"/etc/apache2/sites-available/{APACHE_SITE_NAME}.conf")
    content = f"""<VirtualHost *:{APACHE_PORT}>
    ServerName localhost
    DocumentRoot {app_path / 'public'}

    <Directory {app_path / 'public'}>
        AllowOverride All
        Require all granted
    </Directory>

    DirectoryIndex index.php index.html
    ErrorLog "/var/log/apache2/{APACHE_SITE_NAME}_error.log"
    CustomLog "/var/log/apache2/{APACHE_SITE_NAME}_access.log" combined
</VirtualHost>
"""
    site_file.write_text(content)
    enabled_link = Path(f"/etc/apache2/sites-enabled/{APACHE_SITE_NAME}.conf")
    if enabled_link.exists() or enabled_link.is_symlink():
        enabled_link.unlink()
    enabled_link.symlink_to(site_file)
    run("a2enmod rewrite headers ssl expires")
    run("a2dissite 000-default.conf || true")


def write_nginx_site(app_path: Path):
    print("[INFO] Configuring Nginx...")
    site_file = Path(f"/etc/nginx/sites-available/{NGINX_SITE_NAME}.conf")
    content = f"""server {{
    listen 80;
    server_name _;

    root {app_path / 'public'};
    index index.php index.html index.htm;

    access_log /var/log/nginx/{NGINX_SITE_NAME}.access.log;
    error_log /var/log/nginx/{NGINX_SITE_NAME}.error.log;

    location / {{
        try_files $uri $uri/ /index.php?$query_string;
    }}

    location ~ \\.php$ {{
        fastcgi_split_path_info ^(.+\\.php)(/.+)$;
        fastcgi_pass unix:/run/php/php{PHP_VERSION}-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param PATH_INFO $fastcgi_path_info;
    }}

    location ~ /(\\.env|composer\\.json|composer\\.lock|artisan|readme\\.md|docker-compose\\.yml) {{
        deny all;
        return 404;
    }}

    location ~ /\\.git {{
        deny all;
        return 404;
    }}

    client_max_body_size 20M;
}}
"""
    site_file.write_text(content)
    default_link = Path("/etc/nginx/sites-enabled/default")
    if default_link.exists() or default_link.is_symlink():
        default_link.unlink()
    enabled_link = Path(f"/etc/nginx/sites-enabled/{NGINX_SITE_NAME}.conf")
    if enabled_link.exists() or enabled_link.is_symlink():
        enabled_link.unlink()
    enabled_link.symlink_to(site_file)


def setup_env(app_path: Path, db_password: str, redis_password: str):
    print("[INFO] Setting up .env file...")
    env_file = app_path / ".env"
    env_example = app_path / ".env.example"

    if not env_file.exists():
        if env_example.exists():
            env_file.write_text(env_example.read_text())
            print("  Created .env from .env.example")
        else:
            print("[WARN] No .env.example found, skipping .env creation")
            return

    env_text = env_file.read_text()
    replacements = {
        r"^DB_DATABASE=.*": f"DB_DATABASE={DB_NAME}",
        r"^DB_USERNAME=.*": f"DB_USERNAME={DB_USER}",
        r"^DB_PASSWORD=.*": f"DB_PASSWORD={db_password}",
        r"^REDIS_PASSWORD=.*": f"REDIS_PASSWORD={redis_password}",
        r"^APP_URL=.*": "APP_URL=http://localhost",
    }
    for pattern, replacement in replacements.items():
        env_text = re.sub(pattern, replacement, env_text, flags=re.MULTILINE)
    env_file.write_text(env_text)

    # Generate app key
    result = run("php artisan key:generate --quiet", capture_output=True, check=False)
    if result.returncode == 0:
        print("  Application key generated")
    else:
        print("  [WARN] Could not generate app key")


def main():
    parser = argparse.ArgumentParser(description="Ubuntu webserver installer for resort-web-qr (Laravel 12)")
    parser.add_argument("path", nargs="?", default=".", help="Laravel project root path")
    args = parser.parse_args()

    require_root()
    app_path = Path(args.path).resolve()
    verify_project_root(app_path)

    db_password = random_password()
    redis_password = random_password()

    # 1. Packages
    install_packages()

    # 2. php.ini tuning
    tune_php_ini()

    # 3. MySQL
    setup_mysql(db_password)

    # 4. Redis
    setup_redis(redis_password)

    # 5. Composer
    result = run("command -v composer", check=False, capture_output=True)
    if result.returncode != 0:
        install_composer()
    else:
        print("[INFO] Composer already installed, updating...")
        run("composer self-update --quiet", check=False)

    # 6. Apache
    write_apache_site(app_path)

    # 7. Nginx
    write_nginx_site(app_path)

    # 8. .env
    setup_env(app_path, db_password, redis_password)

    # 9. Composer install
    print("[INFO] Running composer install...")
    os.environ["COMPOSER_ALLOW_SUPERUSER"] = "1"
    run("composer install --no-interaction --prefer-dist --optimize-autoloader", cwd=str(app_path))

    # 10. Storage link
    print("[INFO] Creating storage symlink...")
    run("php artisan storage:link --quiet || true", cwd=str(app_path), check=False)

    # 11. Migration and seeding
    print("[INFO] Running database migrations...")
    run("php artisan migrate --force --quiet", cwd=str(app_path))
    print("[INFO] Seeding database...")
    run("php artisan db:seed --force --quiet || true", cwd=str(app_path), check=False)

    # 12. Production optimization
    print("[INFO] Caching routes, config, and views for production...")
    run("php artisan config:cache --quiet", cwd=str(app_path))
    run("php artisan route:cache --quiet", cwd=str(app_path))
    run("php artisan view:cache --quiet", cwd=str(app_path))
    run("php artisan event:cache --quiet", cwd=str(app_path))

    # Set permissions
    run("setfacl -dR -m u:www-data:rwX,u:$(stat -c '%U' .):rwX storage bootstrap/cache public/uploads 2>/dev/null || true", cwd=str(app_path), check=False)
    run("chmod -R 775 storage bootstrap/cache public/uploads 2>/dev/null || true", cwd=str(app_path), check=False)

    # 13. Cron
    print("[INFO] Setting up Laravel scheduler cron...")
    cron_job = f"* * * * * cd {app_path} && php artisan schedule:run >> /dev/null 2>&1"
    result = run("crontab -l 2>/dev/null", capture_output=True, check=False)
    existing = result.stdout if result.returncode == 0 else ""
    if cron_job not in existing:
        run(f'(crontab -l 2>/dev/null; echo "{cron_job}") | crontab -')
        print("  Cron job added")
    else:
        print("  Cron job already exists")

    # 14. Restart services
    print("[INFO] Testing and restarting services...")
    run("apache2ctl configtest")
    run("nginx -t")
    run(f"systemctl restart php{PHP_VERSION}-fpm")
    run("systemctl restart apache2")
    run("systemctl restart nginx")

    # 15. Summary
    print(f"""
[INFO] ============================================
[INFO]  Setup complete!
[INFO] ============================================

  Apache:         http://localhost:{APACHE_PORT}
  Nginx:          http://localhost

  Database:       {DB_NAME}
  DB Username:    {DB_USER}
  DB Password:    {db_password}

  Redis Password: {redis_password}

  Save these credentials securely.
  For production, update APP_URL and configure HTTPS.
""")


if __name__ == "__main__":
    main()
