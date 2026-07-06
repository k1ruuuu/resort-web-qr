# Resort QR Code Voucher System

A comprehensive digital QR voucher management system for resort properties, built with Laravel 12 and designed for enterprise-grade security and performance.

## 🎯 Features

### Core Functionality
- **QR Code Voucher Generation** - Dynamic voucher creation with secure tokens
- **Guest & Booking Management** - Complete reservation tracking system
- **Facility Management** - Multi-facility support with quota tracking
- **Real-time Scanning** - Mobile-friendly QR code redemption
- **Multi-Property Support** - Manage multiple resort properties
- **Audit Trail** - Complete activity logging and forensics

### Security Features
- **Enterprise-Grade Attack Detection** - SQL Injection, XSS, Path Traversal, IDOR protection
- **Rate Limiting & DDoS Protection** - Redis-based distributed rate limiting
- **Distributed Locking** - Prevents race conditions with Redis locks
- **Security Headers** - CSP, HSTS, X-Frame-Options, and more
- **File Upload Validation** - Advanced MIME type and content scanning
- **Strong Password Policy** - Breach detection via HaveIBeenPwned API
- **IP Whitelisting** - Admin access control by IP address
- **Two-Layer Protection** - .htaccess + Application-level middleware

### Performance
- **Redis Integration** - Caching, sessions, and queue management
- **Distributed Architecture** - Scales horizontally across multiple servers
- **Optimized Database** - Eloquent ORM with query optimization
- **Rate-Limited APIs** - Prevents abuse and ensures stability

### Import/Export
- **CSV/Excel Import** - Bulk guest and booking imports
- **Multi-Format Export** - CSV, XLS, XLSX export capabilities
- **Template Generation** - Pre-formatted import templates
- **Validation & Error Reporting** - Detailed import feedback

## 🛠️ Technology Stack

- **Framework**: Laravel 12.x (PHP 8.2+)
- **Database**: MySQL 8.0+
- **Cache/Sessions**: Redis 7.0+
- **Queue**: Redis-based job processing
- **Authentication**: Laravel Breeze
- **Permissions**: Spatie Laravel Permission
- **QR Codes**: chillerlan/php-qrcode
- **Excel**: Maatwebsite Excel

## 📋 Requirements

### Server Requirements
- PHP >= 8.2
- MySQL >= 8.0 or MariaDB >= 10.3
- Redis >= 7.0
- Composer 2.x
- Node.js & NPM (for assets)

### PHP Extensions
- BCMath
- Ctype
- Fileinfo
- JSON
- Mbstring
- OpenSSL
- PDO
- Tokenizer
- XML
- Redis (phpredis recommended) or predis/predis package

### Web Server
- Apache 2.4+ with mod_rewrite
- OR Nginx 1.18+

## 🚀 Installation

### 1. Clone Repository
```bash
git clone <repository-url>
cd resort-web-qr
```

### 2. Install Dependencies
```bash
composer install
npm install && npm run build
```

### 3. Environment Configuration
```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` and configure:
```env
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password

REDIS_PASSWORD=your_redis_password
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

### 4. Database Setup
```bash
php artisan migrate --seed
```

### 5. Storage Links
```bash
php artisan storage:link
```

### 6. Redis Configuration
Ensure Redis is running:
```bash
redis-cli ping  # Should return PONG
```

### 7. Clear & Cache
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 8. Start Queue Workers (Production)
```bash
php artisan queue:work redis --daemon --sleep=3 --tries=3
```

Or use supervisor (recommended).

## 🔐 Security Setup

### 1. HTTPS Configuration (Production Only)
Obtain SSL certificate (Let's Encrypt recommended):
```bash
sudo certbot --nginx -d yourdomain.com
```

Update `.env`:
```env
APP_URL=https://yourdomain.com
FORCE_HTTPS=true
SESSION_SECURE_COOKIE=true
```

### 2. Redis Security
Edit `/etc/redis/redis.conf`:
```conf
requirepass your_strong_redis_password
bind 127.0.0.1 ::1
```

### 3. Firewall Setup
```bash
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
```

### 4. IP Whitelisting (Optional)
For admin access restriction, add to `.env`:
```env
ADMIN_IP_WHITELIST=203.0.113.0/24,198.51.100.50
```

## 🔧 Configuration

### Permissions
The system uses role-based access control. After seeding, default roles are:
- **Super Admin** - Full access
- **Admin** - Property management
- **Staff** - Voucher redemption
- **Guest** - View only

Manage permissions via:
```bash
php artisan permission:cache-reset
```

### Rate Limiting
Configure in `.env`:
```env
VOUCHER_QR_RATE_LIMIT=30
VOUCHER_REDEEM_RATE_LIMIT=10
```

### CORS Configuration
For API access, set allowed origins:
```env
CORS_ALLOWED_ORIGINS=https://yourdomain.com,https://app.yourdomain.com
```

## 📖 Usage

### Default Login
After seeding, use the default credentials provided in the seeder.

### Creating a Booking
1. Navigate to Guests → Add Guest
2. Navigate to Bookings → New Booking
3. Assign facilities and generate voucher
4. Voucher is auto-generated on check-in

### Scanning QR Codes
1. Navigate to Vouchers → Scan
2. Use device camera or enter code manually
3. Select facility and pax count
4. Confirm redemption

### Importing Data
1. Navigate to Guests/Bookings → Import
2. Download template
3. Fill in data
4. Upload CSV/Excel file
5. Review results

### Exporting Reports
1. Navigate to Reports
2. Select date range and filters
3. Choose format (CSV, XLS, XLSX)
4. Download report

## 🧪 Testing

### Test Attack Detection
```bash
# SQL Injection
curl "http://localhost/?id=1' OR '1'='1"

# XSS
curl "http://localhost/?q=<script>alert(1)</script>"

# Path Traversal
curl "http://localhost/../../etc/passwd"
```

Expected: Custom block pages with HTTP 400/403/429 status codes.

### View Security Logs
```bash
tail -f storage/logs/laravel.log | grep "\[SECURITY\]"
```

## 🔄 Deployment

### Production Checklist
- [ ] Set `APP_ENV=production`
- [ ] Set `APP_DEBUG=false`
- [ ] Configure HTTPS
- [ ] Set strong passwords (DB, Redis)
- [ ] Configure backup strategy
- [ ] Set up queue workers (supervisor)
- [ ] Configure log rotation
- [ ] Enable monitoring
- [ ] Test all security features
- [ ] Clear all caches: `php artisan optimize:clear`
- [ ] Cache for production: `php artisan config:cache`

### Queue Workers (Supervisor)
Create `/etc/supervisor/conf.d/resort-queue.conf`:
```ini
[program:resort-queue-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/resort-web-qr/artisan queue:work redis --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/resort-web-qr/storage/logs/worker.log
```

## 📊 Monitoring

### Check Redis Status
```bash
redis-cli info | grep connected_clients
redis-cli info memory
```

### Check Queue Status
```bash
php artisan queue:work --once  # Process one job
php artisan queue:failed        # View failed jobs
php artisan queue:retry all     # Retry failed jobs
```

### Database Performance
```bash
php artisan db:monitor
```

## 🐛 Troubleshooting

### Redis Connection Error
```bash
# Check Redis is running
sudo systemctl status redis

# Test connection
redis-cli -a your_password ping
```

### Middleware Not Working
```bash
# Clear all caches
php artisan optimize:clear
php artisan config:cache
```

### Permission Denied Errors
```bash
# Fix storage permissions
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### 500 Internal Server Error
Check logs:
```bash
tail -f storage/logs/laravel.log
tail -f /var/log/nginx/error.log  # or apache2
```

## 📝 License

This project is proprietary software. All rights reserved.

## 🤝 Support

For issues, questions, or feature requests, contact the development team.

## 🔐 Security

### Reporting Vulnerabilities
If you discover a security vulnerability, please email security@yourdomain.com. Do not create public issues for security vulnerabilities.

### Security Features Active
- ✅ SQL Injection Protection
- ✅ XSS Protection
- ✅ CSRF Protection
- ✅ Path Traversal Protection
- ✅ Rate Limiting
- ✅ DDoS Protection
- ✅ File Upload Validation
- ✅ Strong Password Policy
- ✅ Session Security
- ✅ Audit Logging

---

**Version**: 1.0.0  
**Last Updated**: 2026-07-06  
**Status**: Production Ready 🚀
