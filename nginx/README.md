Nginx configuration for the Resort Voucher app

Files:
- `nginx/nginx.conf` — a minimal top-level nginx configuration that includes `conf.d` and `sites-enabled`.
- `nginx/sites-available/resort.conf` — site configuration for the Laravel app (document root points to `/var/www/resort-web-qr/public`).

Installation (system nginx):
1. Copy `nginx/nginx.conf` to `/etc/nginx/nginx.conf` (backup existing first).
2. Copy `nginx/sites-available/resort.conf` to `/etc/nginx/sites-available/resort.conf`.
3. Enable site: `ln -s /etc/nginx/sites-available/resort.conf /etc/nginx/sites-enabled/resort.conf`.
4. Test and reload nginx:

```bash
nginx -t
sudo systemctl reload nginx
```

PHP-FPM notes:
- Update the `fastcgi_pass` in `resort.conf` to match your PHP-FPM instance. Common values:
  - System: `unix:/run/php/php8.1-fpm.sock`
  - Docker: `php-fpm:9000`

Docker example (compose):
- If you run with Docker, use `root` in the container for `root /var/www/html/public;` and connect `nginx` to your `php-fpm` service on port `9000`.

Ubuntu installer scripts:
- `scripts/ubuntu-webserver-setup.sh`
- `scripts/ubuntu-webserver-setup.py`

Security:
- Keep `fastcgi_param SCRIPT_FILENAME` pointing to `$document_root$fastcgi_script_name`.
- Ensure sensitive files are denied by the config.

If you want, I can add a `docker-compose.yml` and an `nginx` Dockerfile so the project runs with Docker. Say the word and I'll add them.