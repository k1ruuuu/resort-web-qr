#!/usr/bin/env python3
import argparse
import os
import subprocess
import sys
from pathlib import Path

PHP_VERSION = "8.1"
APACHE_PORT = "8080"
NGINX_SITE_NAME = "resort"
APACHE_SITE_NAME = "resort"


def run(cmd, check=True, capture_output=False, text=True):
    return subprocess.run(cmd, shell=True, check=check, capture_output=capture_output, text=text)


def require_root():
    if os.geteuid() != 0:
        print("This script must be run as root. Re-run with sudo.")
        sys.exit(1)


def verify_project_root(app_path: Path):
    if not app_path.exists() or not app_path.is_dir():
        raise FileNotFoundError(f"Project path does not exist: {app_path}")
    if not (app_path / 'artisan').exists() or not (app_path / 'public' / 'index.php').exists():
        raise FileNotFoundError(f"Not a Laravel project root: {app_path}")


def write_apache_site(app_path: Path):
    site_file = Path('/etc/apache2/sites-available') / f"{APACHE_SITE_NAME}.conf"
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
    enabled_link = Path('/etc/apache2/sites-enabled') / f"{APACHE_SITE_NAME}.conf"
    if enabled_link.exists() or enabled_link.is_symlink():
        enabled_link.unlink()
    enabled_link.symlink_to(site_file)


def write_nginx_site(app_path: Path):
    site_file = Path('/etc/nginx/sites-available') / f"{NGINX_SITE_NAME}.conf"
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

    location ~ \.php$ {{
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass unix:/run/php/php{PHP_VERSION}-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param PATH_INFO $fastcgi_path_info;
    }}

    location ~ /(\.env|composer\.json|composer\.lock|artisan|readme\.md|docker-compose\.yml) {{
        deny all;
        return 404;
    }}

    location ~ /\.git {{
        deny all;
        return 404;
    }}

    client_max_body_size 16M;
}}
"""
    site_file.write_text(content)
    default_link = Path('/etc/nginx/sites-enabled/default')
    if default_link.exists() or default_link.is_symlink():
        default_link.unlink()
    enabled_link = Path('/etc/nginx/sites-enabled') / f"{NGINX_SITE_NAME}.conf"
    if enabled_link.exists() or enabled_link.is_symlink():
        enabled_link.unlink()
    enabled_link.symlink_to(site_file)


def ensure_apache_port():
    ports_conf = Path('/etc/apache2/ports.conf')
    text = ports_conf.read_text()
    if f"Listen {APACHE_PORT}" not in text:
        ports_conf.write_text(text + f"\nListen {APACHE_PORT}\n")


def main():
    parser = argparse.ArgumentParser(description='Ubuntu 22 webserver installer for Laravel app.')
    parser.add_argument('path', nargs='?', default='.', help='Laravel project root path')
    args = parser.parse_args()

    require_root()
    app_path = Path(args.path).resolve()
    verify_project_root(app_path)

    print('Updating package database...')
    run('apt update -y')

    packages = [
        'nginx',
        'apache2',
        f'php{PHP_VERSION}',
        f'php{PHP_VERSION}-cli',
        f'php{PHP_VERSION}-fpm',
        f'php{PHP_VERSION}-mbstring',
        f'php{PHP_VERSION}-xml',
        f'php{PHP_VERSION}-curl',
        f'php{PHP_VERSION}-zip',
        f'php{PHP_VERSION}-mysql',
        f'php{PHP_VERSION}-gd',
        f'php{PHP_VERSION}-bcmath',
        f'php{PHP_VERSION}-intl',
        f'libapache2-mod-php{PHP_VERSION}',
    ]
    print('Installing required packages...')
    run('apt install -y ' + ' '.join(packages))

    print('Configuring Apache...')
    ensure_apache_port()
    write_apache_site(app_path)
    run('a2enmod rewrite headers ssl expires')
    run('a2dissite 000-default.conf || true')

    print('Configuring Nginx...')
    write_nginx_site(app_path)

    print('Validating configurations...')
    run('apache2ctl configtest')
    run('nginx -t')

    print('Restarting services...')
    run(f'systemctl restart php{PHP_VERSION}-fpm')
    run('systemctl restart apache2')
    run('systemctl restart nginx')

    print('\nSetup complete.')
    print(f'Apache is available on port {APACHE_PORT}.')
    print('Nginx is available on port 80 and uses PHP-FPM.');


if __name__ == '__main__':
    main()
