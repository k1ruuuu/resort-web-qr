# Resort Voucher System - Installation Guide

Complete guide for installing and deploying the Resort Voucher System with Docker, Redis, and MySQL.

---

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Quick Start](#quick-start)
3. [Manual Installation](#manual-installation)
4. [Configuration](#configuration)
5. [Verification](#verification)
6. [Common Operations](#common-operations)
7. [Troubleshooting](#troubleshooting)
8. [Production Deployment](#production-deployment)

---

## Prerequisites

### Required Software

- **Docker Desktop** (Windows/Mac) or **Docker Engine** (Linux)
  - Download: https://www.docker.com/products/docker-desktop
  - Includes Docker Compose
  
- **Git** (optional, for cloning repository)
  - Download: https://git-scm.com/downloads

### System Requirements

- **RAM**: Minimum 4GB, recommended 8GB+
- **Disk Space**: Minimum 5GB free space
- **Ports**: 80, 443, 3306, 6379 (must be available)

### Verify Docker Installation

```bash
docker --version
docker-compose --version
```

Expected output showing versions 20.x+ and 2.x+

---

## Quick Start

### Automated Setup (Recommended)

**Windows:**
```batch
docker-setup.bat
```

**Linux/Mac:**
```bash
chmod +x docker-quick-start.sh
./docker-quick-start.sh
```

This script will:
- Create environment configuration
- Build Docker images
- Start all services
- Run database migrations
- Set up storage links
- Generate application key

**Access the application:**
```
http://localhost
```

---

## Manual Installation

### Step 1: Clone or Download Project

```bash
git clone <repository-url>
cd resort-web-qr
```

### Step 2: Environment Configuration

Copy the Docker environment template:

```bash
# Windows
copy .env.docker .env

# Linux/Mac
cp .env.docker .env
```

Edit `.env` file with required settings:

```ini
# Application
APP_NAME="Resort Voucher System"
APP_ENV=production
APP_DEBUG=false
APP_URL=http://localhost

# Database (Docker service names)
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=resort_voucher
DB_USERNAME=resort_user
DB_PASSWORD=your_secure_password_here
DB_ROOT_PASSWORD=your_secure_root_password_here

# Redis (Docker service name)
REDIS_CLIENT=phpredis
REDIS_HOST=redis
REDIS_PORT=6379
REDIS_PASSWORD=your_secure_redis_password_here

# Cache & Session (use Redis)
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

**Critical Settings:**
- `DB_HOST=mysql` (NOT 127.0.0.1)
- `REDIS_HOST=redis` (NOT 127.0.0.1)
- `CACHE_STORE=redis` (NOT file)
- `SESSION_DRIVER=redis` (NOT file)
- Change all default passwords

### Step 3: Build Docker Images

```bash
docker-compose build --no-cache
```

This will:
- Build PHP 8.2-FPM container with PhpRedis extension
- Set up Nginx web server
- Configure MySQL 8.0
- Configure Redis 7
- Set up queue worker and scheduler

Build time: 5-10 minutes on first run

### Step 4: Start Services

```bash
docker-compose up -d
```

Wait 30-60 seconds for services to initialize.

Check status:
```bash
docker-compose ps
```

All services should show "Up" and "(healthy)" status.

### Step 5: Generate Application Key

```bash
docker-compose exec app php artisan key:generate
```

### Step 6: Run Database Migrations

```bash
docker-compose exec app php artisan migrate
```

### Step 7: Create Storage Link

```bash
docker-compose exec app php artisan storage:link
```

### Step 8: Set Permissions (if needed)

```bash
docker-compose exec app chmod -R 775 storage bootstrap/cache
```

---

## Configuration

### Environment Variables Reference

#### Application Settings
```ini
APP_NAME="Resort Voucher System"
APP_ENV=production              # production, local, staging
APP_DEBUG=false                 # false for production
APP_URL=http://localhost        # Your domain
APP_LOCALE=en
```

#### Database Configuration
```ini
DB_CONNECTION=mysql
DB_HOST=mysql                   # Docker service name
DB_PORT=3306
DB_DATABASE=resort_voucher
DB_USERNAME=resort_user
DB_PASSWORD=secure_password     # CHANGE THIS
DB_ROOT_PASSWORD=secure_root    # CHANGE THIS
```

#### Redis Configuration
```ini
REDIS_CLIENT=phpredis           # Best performance
REDIS_HOST=redis                # Docker service name
REDIS_PORT=6379
REDIS_PASSWORD=secure_password  # CHANGE THIS

# Redis Database Separation
REDIS_DB=0                      # Default
REDIS_CACHE_DB=1                # Cache
REDIS_QUEUE_DB=2                # Queues
REDIS_SESSION_DB=3              # Sessions
```

#### Cache & Session
```ini
CACHE_STORE=redis               # redis, file, database
SESSION_DRIVER=redis            # redis, file, database
QUEUE_CONNECTION=redis          # redis, sync, database
BROADCAST_CONNECTION=redis
```

#### Session Security
```ini
SESSION_LIFETIME=120            # Minutes
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true      # true if using HTTPS
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=strict        # strict, lax, none
```

#### Security
```ini
FORCE_HTTPS=false               # true for production
ADMIN_IP_WHITELIST=             # Comma-separated IPs
VOUCHER_QR_RATE_LIMIT=30
VOUCHER_REDEEM_RATE_LIMIT=10
```

#### Docker Port Mapping
```ini
APP_PORT=80                     # HTTP port
APP_SSL_PORT=443                # HTTPS port
DB_PORT_EXTERNAL=3306           # MySQL external port
REDIS_PORT_EXTERNAL=6379        # Redis external port
```

### Services Architecture

The application runs in 6 Docker containers:

1. **app** - PHP 8.2-FPM with Laravel application
2. **nginx** - Web server and reverse proxy
3. **mysql** - MySQL 8.0 database
4. **redis** - Redis 7 cache/session/queue server
5. **queue** - Laravel queue worker
6. **scheduler** - Laravel task scheduler

### Redis Database Separation

| Database | Purpose | Configuration |
|----------|---------|---------------|
| 0 | Default operations | `REDIS_DB=0` |
| 1 | Cache storage | `REDIS_CACHE_DB=1` |
| 2 | Queue jobs | `REDIS_QUEUE_DB=2` |
| 3 | Sessions | `REDIS_SESSION_DB=3` |

---

## Verification

### Check Container Status

```bash
docker-compose ps
```

Expected output:
```
Name                 Status          Ports
resort_app           Up (healthy)    9000/tcp
resort_nginx         Up (healthy)    0.0.0.0:80->80/tcp
resort_mysql         Up (healthy)    0.0.0.0:3306->3306/tcp
resort_redis         Up (healthy)    0.0.0.0:6379->6379/tcp
resort_queue         Up              9000/tcp
resort_scheduler     Up              9000/tcp
```

### Test Redis Connection

```bash
docker-compose exec app php artisan tinker
```

```php
>>> Redis::ping()
=> true

>>> Cache::put('test', 'value', 60)
=> true

>>> Cache::get('test')
=> "value"

>>> exit
```

### Test MySQL Connection

```bash
docker-compose exec app php artisan tinker
```

```php
>>> DB::connection()->getPdo()
=> PDO {#...}

>>> DB::table('migrations')->count()
=> [number of migrations]

>>> exit
```

### View Logs

```bash
# All services
docker-compose logs -f

# Specific service
docker-compose logs -f app
docker-compose logs -f redis
docker-compose logs -f mysql
```

### Access Application

Open browser: http://localhost

You should see the login page.

---

## Common Operations

### Container Management

```bash
# Start all containers
docker-compose up -d

# Stop all containers
docker-compose down

# Restart all containers
docker-compose restart

# Restart specific service
docker-compose restart redis

# View container status
docker-compose ps

# View resource usage
docker stats resort_app resort_mysql resort_redis
```

### Access Container Shells

```bash
# Application shell
docker-compose exec app sh

# MySQL CLI
docker-compose exec mysql mysql -u resort_user -p

# Redis CLI
docker-compose exec redis redis-cli -a YOUR_REDIS_PASSWORD
```

### Laravel Artisan Commands

```bash
# Run any artisan command
docker-compose exec app php artisan [command]

# Examples:
docker-compose exec app php artisan migrate
docker-compose exec app php artisan db:seed
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan route:list
docker-compose exec app php artisan tinker
```

### Cache Management

```bash
# Clear all caches
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan view:clear
docker-compose exec app php artisan route:clear

# Cache for production
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache
docker-compose exec app php artisan view:cache
```

### Database Operations

```bash
# Run migrations
docker-compose exec app php artisan migrate

# Rollback migrations
docker-compose exec app php artisan migrate:rollback

# Fresh migration with seed
docker-compose exec app php artisan migrate:fresh --seed

# Backup database
docker-compose exec mysql mysqldump -u resort_user -p resort_voucher > backup.sql

# Restore database
docker-compose exec -T mysql mysql -u resort_user -p resort_voucher < backup.sql
```

### Using Makefile (Optional)

If `make` is installed, use convenient shortcuts:

```bash
make help           # Show all commands
make up             # Start containers
make down           # Stop containers
make logs           # View logs
make shell          # Access app shell
make redis          # Access Redis CLI
make mysql          # Access MySQL CLI
make migrate        # Run migrations
make cache-clear    # Clear all caches
```

---

## Troubleshooting

### Redis Connection Failed

**Symptoms:**
- Application errors about Redis connection
- Session not persisting
- Cache not working

**Solutions:**

1. **Check Redis host configuration:**
   ```bash
   grep REDIS_HOST .env
   ```
   Should show: `REDIS_HOST=redis` (NOT 127.0.0.1)

2. **Check Redis container:**
   ```bash
   docker-compose ps redis
   ```
   Should show "Up (healthy)"

3. **Check Redis password:**
   Ensure `REDIS_PASSWORD` in `.env` matches the password in `docker-compose.yml`

4. **Check Redis client:**
   ```bash
   grep REDIS_CLIENT .env
   ```
   Should show: `REDIS_CLIENT=phpredis`

5. **View Redis logs:**
   ```bash
   docker-compose logs redis
   ```

6. **Restart Redis:**
   ```bash
   docker-compose restart redis
   ```

### MySQL Connection Failed

**Symptoms:**
- Migration errors
- Database connection errors

**Solutions:**

1. **Check MySQL host:**
   ```bash
   grep DB_HOST .env
   ```
   Should show: `DB_HOST=mysql` (NOT 127.0.0.1)

2. **Wait for MySQL initialization:**
   First startup takes 30-60 seconds
   ```bash
   docker-compose logs mysql
   ```
   Wait for: "ready for connections"

3. **Check credentials:**
   Verify `DB_USERNAME` and `DB_PASSWORD` in `.env` match `docker-compose.yml`

4. **Check MySQL container:**
   ```bash
   docker-compose ps mysql
   ```

5. **Restart MySQL:**
   ```bash
   docker-compose restart mysql
   ```

### Containers Won't Start

**Solutions:**

1. **Check Docker is running:**
   ```bash
   docker ps
   ```

2. **Check port conflicts:**
   Ensure ports 80, 443, 3306, 6379 are not in use
   ```bash
   # Windows
   netstat -ano | findstr ":80 :443 :3306 :6379"
   
   # Linux/Mac
   netstat -tuln | grep -E ':80|:443|:3306|:6379'
   ```

3. **Check logs:**
   ```bash
   docker-compose logs
   ```

4. **Clean rebuild:**
   ```bash
   docker-compose down -v
   docker-compose up -d --build
   ```

5. **Check disk space:**
   ```bash
   docker system df
   ```

6. **Prune unused resources:**
   ```bash
   docker system prune -a
   ```

### Permission Errors

**Solutions:**

1. **Fix storage permissions:**
   ```bash
   docker-compose exec app chmod -R 775 storage bootstrap/cache
   ```

2. **Check file ownership:**
   ```bash
   docker-compose exec app ls -la storage
   ```

### Application Not Accessible

**Solutions:**

1. **Check Nginx container:**
   ```bash
   docker-compose ps nginx
   docker-compose logs nginx
   ```

2. **Check firewall:**
   Ensure port 80 is not blocked

3. **Check APP_URL:**
   ```bash
   grep APP_URL .env
   ```

4. **Access logs:**
   ```bash
   docker-compose logs nginx
   tail -f storage/logs/laravel.log
   ```

### Session Not Persisting

**Solutions:**

1. **Verify Redis session configuration:**
   ```bash
   grep SESSION_DRIVER .env
   ```
   Should be: `SESSION_DRIVER=redis`

2. **Check Redis session database:**
   ```bash
   docker-compose exec redis redis-cli -a YOUR_PASSWORD
   SELECT 3
   KEYS *
   ```

3. **Clear sessions:**
   ```bash
   docker-compose exec app php artisan cache:clear
   docker-compose exec redis redis-cli -a YOUR_PASSWORD FLUSHDB
   ```

---

## Production Deployment

### Pre-Deployment Checklist

#### Security Configuration

- [ ] Change all default passwords
- [ ] Set `APP_ENV=production`
- [ ] Set `APP_DEBUG=false`
- [ ] Set `FORCE_HTTPS=true`
- [ ] Set `SESSION_SECURE_COOKIE=true`
- [ ] Configure `ADMIN_IP_WHITELIST`
- [ ] Generate new `APP_KEY`

#### SSL/HTTPS Setup

1. **Obtain SSL certificates:**
   - Let's Encrypt (free)
   - Commercial certificate authority
   - Self-signed (development only)

2. **Place certificates:**
   ```
   docker/nginx/ssl/certificate.crt
   docker/nginx/ssl/private.key
   ```

3. **Update Nginx configuration:**
   Edit `docker/nginx/default.conf` to enable SSL

4. **Update environment:**
   ```ini
   APP_URL=https://yourdomain.com
   FORCE_HTTPS=true
   SESSION_SECURE_COOKIE=true
   ```

#### Performance Optimization

1. **Cache configuration:**
   ```bash
   docker-compose exec app php artisan config:cache
   docker-compose exec app php artisan route:cache
   docker-compose exec app php artisan view:cache
   ```

2. **Optimize autoloader:**
   ```bash
   docker-compose exec app composer dump-autoload --optimize --classmap-authoritative
   ```

3. **Enable OPcache:**
   Already configured in `docker/php/opcache.ini`

#### Monitoring & Backups

1. **Set up database backups:**
   ```bash
   # Create backup script
   docker-compose exec mysql mysqldump -u resort_user -p resort_voucher > backup-$(date +%Y%m%d).sql
   ```

2. **Set up Redis backups:**
   Redis persistence is enabled (RDB + AOF)
   Backup volume: `redis_data`

3. **Monitor logs:**
   ```bash
   # Application logs
   tail -f storage/logs/laravel.log
   
   # Container logs
   docker-compose logs -f
   ```

4. **Monitor resources:**
   ```bash
   docker stats
   ```

#### Environment Variables for Production

```ini
# Application
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Security
FORCE_HTTPS=true
SESSION_SECURE_COOKIE=true
SESSION_LIFETIME=30
ADMIN_IP_WHITELIST=your.admin.ip,another.ip

# Database (use strong passwords)
DB_PASSWORD=very_strong_unique_password
DB_ROOT_PASSWORD=very_strong_root_password

# Redis (use strong password)
REDIS_PASSWORD=very_strong_redis_password

# Logging
LOG_LEVEL=warning
LOG_CHANNEL=stack
```

### Deployment Steps

1. **Build production images:**
   ```bash
   docker-compose build --no-cache
   ```

2. **Start services:**
   ```bash
   docker-compose up -d
   ```

3. **Run migrations:**
   ```bash
   docker-compose exec app php artisan migrate --force
   ```

4. **Cache configuration:**
   ```bash
   docker-compose exec app php artisan config:cache
   docker-compose exec app php artisan route:cache
   docker-compose exec app php artisan view:cache
   ```

5. **Verify deployment:**
   - Check all containers are healthy
   - Test application access
   - Test login functionality
   - Check logs for errors
   - Monitor resource usage

### Scaling

To scale queue workers:

```bash
docker-compose up -d --scale queue=3
```

This runs 3 queue worker instances for better job processing.

### Updates and Maintenance

1. **Pull latest code:**
   ```bash
   git pull origin main
   ```

2. **Rebuild and restart:**
   ```bash
   docker-compose down
   docker-compose build --no-cache
   docker-compose up -d
   ```

3. **Run migrations:**
   ```bash
   docker-compose exec app php artisan migrate --force
   ```

4. **Clear and cache:**
   ```bash
   docker-compose exec app php artisan config:cache
   docker-compose exec app php artisan route:cache
   docker-compose exec app php artisan view:cache
   ```

---

## Additional Resources

### Docker Compose Services

- **app**: Main PHP application (Laravel)
- **nginx**: Web server
- **mysql**: Database server
- **redis**: Cache/session/queue server
- **queue**: Background job processor
- **scheduler**: Scheduled task runner

### Network Architecture

All services communicate via Docker network `resort_network` (172.20.0.0/16).

Services use Docker service names for internal communication:
- MySQL: `mysql:3306`
- Redis: `redis:6379`
- App: `app:9000`

### Data Persistence

Persistent data volumes:
- `mysql_data`: MySQL database files
- `redis_data`: Redis persistence files

To backup volumes:
```bash
docker volume ls
docker run --rm -v mysql_data:/data -v $(pwd):/backup alpine tar czf /backup/mysql_backup.tar.gz /data
```

### Health Checks

All services include health checks:
- App: PHP-FPM status
- MySQL: Database connectivity
- Redis: PING command
- Nginx: HTTP request to /health

### Resource Limits

Configured in `docker-compose.yml`:
- Redis: 256MB max memory
- MySQL: 512MB InnoDB buffer pool

Adjust based on your server capacity.

---

## Support

For issues or questions:

1. Check logs: `docker-compose logs [service]`
2. Review this installation guide
3. Check Docker and Laravel documentation
4. Verify environment configuration

---

## Summary

You now have a fully functional Resort Voucher System running with:

✓ Docker containerization  
✓ Redis caching, sessions, and queues  
✓ MySQL database  
✓ Nginx web server  
✓ Queue workers and task scheduler  
✓ Production-ready security configuration  
✓ Health monitoring  
✓ Data persistence  

Access your application at: **http://localhost** (or your configured domain)

---

**Installation complete!**
