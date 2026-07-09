# Sistem Voucher QR Code Resort

[**🇬🇧 English Version**](#english-version) | [**🇮🇩 Versi Indonesia**](#versi-indonesia)

---

# Versi Indonesia

Sistem manajemen voucher QR digital yang komprehensif untuk properti resort, dibangun dengan Laravel 12 dan dirancang untuk keamanan dan performa tingkat enterprise.

## 📑 Daftar Isi

- [Fitur Utama](#fitur-utama)
- [Teknologi](#teknologi)
- [Persyaratan Sistem](#persyaratan-sistem)
- [Instalasi](#instalasi)
- [Konfigurasi](#konfigurasi)
- [Cara Penggunaan](#cara-penggunaan)
- [Keamanan](#keamanan)
- [Troubleshooting](#troubleshooting)

## 🎯 Fitur Utama

### Fungsi Inti
- **Generasi Voucher QR Code** - Pembuatan voucher dinamis dengan token keamanan
- **Manajemen Tamu & Booking** - Sistem pelacakan reservasi lengkap
- **Manajemen Fasilitas** - Dukungan multi-fasilitas dengan pelacakan kuota
- **Scanning Real-time** - Penebusan QR code yang ramah mobile
- **Multi-Property** - Kelola beberapa properti resort sekaligus
- **Audit Trail** - Pencatatan aktivitas lengkap dan forensik

### Fitur Keamanan
- **Deteksi Serangan Tingkat Enterprise** - Perlindungan SQL Injection, XSS, Path Traversal, IDOR
- **Rate Limiting & Perlindungan DDoS** - Rate limiting terdistribusi berbasis Redis
- **Distributed Locking** - Mencegah race condition dengan Redis locks
- **Security Headers** - CSP, HSTS, X-Frame-Options, dan lainnya
- **Validasi File Upload** - Pemindaian MIME type dan konten tingkat lanjut
- **Kebijakan Password Kuat** - Deteksi kebocoran via HaveIBeenPwned API
- **IP Whitelisting** - Kontrol akses admin berdasarkan IP
- **Perlindungan Dua Lapis** - .htaccess + Middleware tingkat aplikasi

### Performa
- **Integrasi Redis** - Caching, sessions, dan manajemen queue
- **Arsitektur Terdistribusi** - Skalabilitas horizontal di beberapa server
- **Database Optimal** - Eloquent ORM dengan optimasi query
- **API Rate-Limited** - Mencegah penyalahgunaan dan menjaga stabilitas

### Import/Export
- **Import CSV/Excel** - Import tamu dan booking secara massal
- **Export Multi-Format** - Ekspor CSV, XLS, XLSX
- **Generasi Template** - Template import yang telah diformat
- **Validasi & Laporan Error** - Feedback detail hasil import

## 🛠️ Teknologi

- **Framework**: Laravel 12.x (PHP 8.2+)
- **Database**: MySQL 8.0+
- **Cache/Sessions**: Redis 7.0+
- **Queue**: Pemrosesan job berbasis Redis
- **Authentication**: Laravel Breeze
- **Permissions**: Spatie Laravel Permission
- **QR Codes**: chillerlan/php-qrcode
- **Excel**: Maatwebsite Excel
- **Containerization**: Docker & Docker Compose

## 📋 Persyaratan Sistem

### Persyaratan Server
- PHP >= 8.2
- MySQL >= 8.0 atau MariaDB >= 10.3
- Redis >= 7.0
- Composer 2.x
- Node.js & NPM (untuk assets)

### Ekstensi PHP Wajib
- BCMath
- Ctype
- Fileinfo
- JSON
- Mbstring
- OpenSSL
- PDO
- Tokenizer
- XML
- Redis (phpredis direkomendasikan)

### Web Server
- Apache 2.4+ dengan mod_rewrite
- ATAU Nginx 1.18+

## 🚀 Instalasi

### Langkah 1: Clone Repository
```bash
git clone <repository-url>
cd resort-web-qr
```

### Langkah 2: Install Dependencies
```bash
# Install dependencies PHP
composer install

# Install dependencies JavaScript
npm install
npm run build
```

### Langkah 3: Konfigurasi Environment

```bash
# Salin file environment
cp .env.example .env

# Generate application key
php artisan key:generate
```

Edit file `.env` dan sesuaikan konfigurasi:

```env
APP_NAME="Resort Voucher System"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

# Konfigurasi Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=resort_voucher
DB_USERNAME=root
DB_PASSWORD=password_anda

# Konfigurasi Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Cache & Session
CACHE_STORE=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
```

### Langkah 4: Setup Database

```bash
# Jalankan migrasi database
php artisan migrate

# Seed data awal (opsional)
php artisan db:seed
```

### Langkah 5: Setup Storage

```bash
# Buat symbolic link untuk storage
php artisan storage:link

# Set permission (Linux/Mac)
chmod -R 775 storage bootstrap/cache
```

### Langkah 6: Jalankan Aplikasi

```bash
# Development server
php artisan serve

# Aplikasi akan berjalan di:
# http://localhost:8000
```

## ⚙️ Konfigurasi

### Konfigurasi Cache (Opsional - Untuk Performa Lebih Baik)

Jika menggunakan Redis untuk cache:

```env
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

Pastikan Redis sudah terinstall dan berjalan:
```bash
# Cek status Redis
redis-cli ping  # Harus return "PONG"
```

### Konfigurasi Rate Limiting

Edit file `.env`:
```env
# Limit scanning QR per menit
VOUCHER_QR_RATE_LIMIT=30

# Limit redemption per menit
VOUCHER_REDEEM_RATE_LIMIT=10
```

### Konfigurasi Permissions

Sistem menggunakan role-based access control. Role default:
- **Super Admin** - Akses penuh
- **Admin** - Manajemen properti
- **Staff** - Penebusan voucher
- **Guest** - View only

Setelah seeding, login default tersedia di seeder.

## 📖 Cara Penggunaan

### 1. Login ke Sistem

Akses aplikasi di browser:
```
http://localhost:8000
```

Gunakan kredensial default dari seeder (jika sudah dijalankan).

### 2. Menambah Tamu Baru

1. Navigasi ke **Guests** → **Add Guest**
2. Isi informasi tamu:
   - Nama depan dan belakang
   - Email (opsional)
   - Nomor telepon
   - Alamat
3. Klik **Save**

### 3. Membuat Booking

1. Navigasi ke **Bookings** → **New Booking**
2. Pilih tamu yang sudah terdaftar
3. Pilih properti dan kamar
4. Tentukan tanggal check-in dan check-out
5. Pilih fasilitas yang tersedia
6. Klik **Create Booking**

### 4. Generate Voucher QR

Voucher akan otomatis ter-generate saat booking berstatus **Checked In**:
1. Buka detail booking
2. Ubah status menjadi **Checked In**
3. Voucher QR akan otomatis dibuat
4. Tamu dapat menerima voucher via WhatsApp (jika dikonfigurasi)

### 5. Scan & Redeem Voucher

#### Menggunakan Kamera:
1. Navigasi ke **Vouchers** → **Scan**
2. Pilih outlet/lokasi
3. Klik **Start Camera**
4. Arahkan kamera ke QR code
5. Sistem akan otomatis memverifikasi
6. Pilih fasilitas yang ingin diredeem
7. Masukkan jumlah pax
8. Klik **Confirm Redemption**

#### Manual Entry:
1. Navigasi ke **Vouchers** → **Scan**
2. Pilih outlet/lokasi
3. Paste kode QR atau secure token di kolom manual
4. Klik **Verify**
5. Lanjutkan dengan redemption

### 6. Import Data Massal

#### Import Tamu:
1. Navigasi ke **Guests** → **Import**
2. Klik **Download Template**
3. Isi data tamu di template Excel/CSV
4. Upload file yang sudah diisi
5. Review hasil import

#### Import Booking:
1. Navigasi ke **Bookings** → **Import**
2. Klik **Download Template**
3. Isi data booking di template
4. Upload file
5. Sistem akan memvalidasi dan import data

### 7. Export Laporan

1. Navigasi ke **Reports**
2. Pilih tipe laporan:
   - Redemption Report (laporan penebusan)
   - Delivery Log (log pengiriman)
   - Scan History (riwayat scan)
3. Tentukan range tanggal
4. Pilih format: CSV, XLS, atau XLSX
5. Klik **Export**

## 🔐 Keamanan

### Fitur Keamanan Aktif

✅ **Perlindungan SQL Injection** - Filter otomatis query berbahaya  
✅ **Perlindungan XSS** - Sanitasi input dan output  
✅ **Perlindungan CSRF** - Token validasi setiap request  
✅ **Rate Limiting** - Batasi request berlebihan  
✅ **Session Security** - Enkripsi session, HTTP-only cookies  
✅ **File Upload Validation** - Validasi tipe dan ukuran file  
✅ **Audit Logging** - Pencatatan semua aktivitas penting  

### Konfigurasi HTTPS (Production)

Untuk production, aktifkan HTTPS:

```env
APP_URL=https://yourdomain.com
FORCE_HTTPS=true
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
```

### IP Whitelisting (Opsional)

Batasi akses admin berdasarkan IP:

```env
ADMIN_IP_WHITELIST=192.168.1.100,10.0.0.0/8
```

## 🐛 Troubleshooting

### Error: "SQLSTATE[HY000] [1045] Access denied"

**Penyebab:** Kredensial database salah

**Solusi:**
```bash
# Cek kredensial di .env
DB_USERNAME=root
DB_PASSWORD=password_yang_benar

# Clear config cache
php artisan config:clear
```

### Error: "Class 'Redis' not found"

**Penyebab:** Redis extension tidak terinstall

**Solusi:**
```bash
# Install Redis extension (Ubuntu/Debian)
sudo apt-get install php-redis

# Atau gunakan predis
composer require predis/predis

# Ubah .env
REDIS_CLIENT=predis
```

### Error: "The stream or file could not be opened"

**Penyebab:** Permission storage/logs tidak benar

**Solusi:**
```bash
# Linux/Mac
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Windows (run as Administrator)
icacls storage /grant Users:F /T
icacls bootstrap\cache /grant Users:F /T
```

### Error: "Unexpected token '<', "<!DOCTYPE "... is not valid JSON"

**Penyebab:** Session cookie tidak terkirim dengan AJAX request

**Solusi:**
```bash
# 1. Clear browser cache (Ctrl+F5)
# 2. Clear application cache
php artisan config:clear
php artisan view:clear
php artisan cache:clear

# 3. Pastikan .env sudah benar
SESSION_SAME_SITE=lax
```

### Scan QR Tidak Berfungsi

**Solusi:**
1. Pastikan menggunakan HTTPS atau localhost
2. Berikan permission kamera di browser
3. Hard refresh browser (Ctrl+F5)
4. Coba browser lain (Chrome direkomendasikan)

### Queue Jobs Tidak Berjalan

**Solusi:**
```bash
# Development
php artisan queue:work

# Cek failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all
```

## 📊 Monitoring

### Cek Status Aplikasi

```bash
# Cek database connection
php artisan db:monitor

# Cek cache status
php artisan cache:clear

# Lihat logs
tail -f storage/logs/laravel.log
```

### Cek Performance

```bash
# Clear semua cache untuk reset
php artisan optimize:clear

# Cache untuk production
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 📞 Support

Untuk masalah, pertanyaan, atau permintaan fitur, hubungi tim development.

---

## 🔒 Lisensi

Perangkat lunak proprietary. Semua hak dilindungi.

---

**Versi**: 1.0.0  
**Terakhir Diupdate**: 2026-07-08  
**Status**: Production Ready 🚀

---
---
---

# English Version

A comprehensive digital QR voucher management system for resort properties, built with Laravel 12 and designed for enterprise-grade security and performance.

## 📑 Table of Contents

- [Key Features](#key-features)
- [Technology Stack](#technology-stack)
- [System Requirements](#system-requirements)
- [Installation](#installation-1)
- [Configuration](#configuration-1)
- [Usage Guide](#usage-guide)
- [Security](#security-1)
- [Troubleshooting](#troubleshooting-1)

## 🎯 Key Features

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
- **Containerization**: Docker & Docker Compose

## 📋 System Requirements

### Server Requirements
- PHP >= 8.2
- MySQL >= 8.0 or MariaDB >= 10.3
- Redis >= 7.0
- Composer 2.x
- Node.js & NPM (for assets)

### Required PHP Extensions
- BCMath
- Ctype
- Fileinfo
- JSON
- Mbstring
- OpenSSL
- PDO
- Tokenizer
- XML
- Redis (phpredis recommended)

### Web Server
- Apache 2.4+ with mod_rewrite
- OR Nginx 1.18+

## 🚀 Installation

### Step 1: Clone Repository
```bash
git clone <repository-url>
cd resort-web-qr
```

### Step 2: Install Dependencies
```bash
# Install PHP dependencies
composer install

# Install JavaScript dependencies
npm install
npm run build
```

### Step 3: Environment Configuration

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

Edit `.env` file and configure:

```env
APP_NAME="Resort Voucher System"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=resort_voucher
DB_USERNAME=root
DB_PASSWORD=your_password

# Redis Configuration
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Cache & Session
CACHE_STORE=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
```

### Step 4: Database Setup

```bash
# Run database migrations
php artisan migrate

# Seed initial data (optional)
php artisan db:seed
```

### Step 5: Storage Setup

```bash
# Create symbolic link for storage
php artisan storage:link

# Set permissions (Linux/Mac)
chmod -R 775 storage bootstrap/cache
```

### Step 6: Run Application

```bash
# Development server
php artisan serve

# Application will run at:
# http://localhost:8000
```

## ⚙️ Configuration

### Cache Configuration (Optional - For Better Performance)

If using Redis for cache:

```env
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

Ensure Redis is installed and running:
```bash
# Check Redis status
redis-cli ping  # Should return "PONG"
```

### Rate Limiting Configuration

Edit `.env` file:
```env
# QR scanning limit per minute
VOUCHER_QR_RATE_LIMIT=30

# Redemption limit per minute
VOUCHER_REDEEM_RATE_LIMIT=10
```

### Permissions Configuration

System uses role-based access control. Default roles:
- **Super Admin** - Full access
- **Admin** - Property management
- **Staff** - Voucher redemption
- **Guest** - View only

After seeding, default login credentials are available in seeder.

## 📖 Usage Guide

### 1. Login to System

Access application in browser:
```
http://localhost:8000
```

Use default credentials from seeder (if already run).

### 2. Add New Guest

1. Navigate to **Guests** → **Add Guest**
2. Fill guest information:
   - First and last name
   - Email (optional)
   - Phone number
   - Address
3. Click **Save**

### 3. Create Booking

1. Navigate to **Bookings** → **New Booking**
2. Select registered guest
3. Choose property and room
4. Set check-in and check-out dates
5. Select available facilities
6. Click **Create Booking**

### 4. Generate QR Voucher

Voucher will be auto-generated when booking status is **Checked In**:
1. Open booking details
2. Change status to **Checked In**
3. QR voucher will be automatically created
4. Guest can receive voucher via WhatsApp (if configured)

### 5. Scan & Redeem Voucher

#### Using Camera:
1. Navigate to **Vouchers** → **Scan**
2. Select outlet/location
3. Click **Start Camera**
4. Point camera at QR code
5. System will auto-verify
6. Select facility to redeem
7. Enter pax amount
8. Click **Confirm Redemption**

#### Manual Entry:
1. Navigate to **Vouchers** → **Scan**
2. Select outlet/location
3. Paste QR code or secure token in manual field
4. Click **Verify**
5. Proceed with redemption

### 6. Bulk Import Data

#### Import Guests:
1. Navigate to **Guests** → **Import**
2. Click **Download Template**
3. Fill guest data in Excel/CSV template
4. Upload completed file
5. Review import results

#### Import Bookings:
1. Navigate to **Bookings** → **Import**
2. Click **Download Template**
3. Fill booking data in template
4. Upload file
5. System will validate and import data

### 7. Export Reports

1. Navigate to **Reports**
2. Select report type:
   - Redemption Report
   - Delivery Log
   - Scan History
3. Set date range
4. Choose format: CSV, XLS, or XLSX
5. Click **Export**

## 🔐 Security

### Active Security Features

✅ **SQL Injection Protection** - Automatic filtering of malicious queries  
✅ **XSS Protection** - Input and output sanitization  
✅ **CSRF Protection** - Token validation for every request  
✅ **Rate Limiting** - Limit excessive requests  
✅ **Session Security** - Session encryption, HTTP-only cookies  
✅ **File Upload Validation** - Type and size validation  
✅ **Audit Logging** - Log all important activities  

### HTTPS Configuration (Production)

For production, enable HTTPS:

```env
APP_URL=https://yourdomain.com
FORCE_HTTPS=true
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
```

### IP Whitelisting (Optional)

Restrict admin access by IP:

```env
ADMIN_IP_WHITELIST=192.168.1.100,10.0.0.0/8
```

## 🐛 Troubleshooting

### Error: "SQLSTATE[HY000] [1045] Access denied"

**Cause:** Wrong database credentials

**Solution:**
```bash
# Check credentials in .env
DB_USERNAME=root
DB_PASSWORD=correct_password

# Clear config cache
php artisan config:clear
```

### Error: "Class 'Redis' not found"

**Cause:** Redis extension not installed

**Solution:**
```bash
# Install Redis extension (Ubuntu/Debian)
sudo apt-get install php-redis

# Or use predis
composer require predis/predis

# Change .env
REDIS_CLIENT=predis
```

### Error: "The stream or file could not be opened"

**Cause:** Incorrect storage/logs permissions

**Solution:**
```bash
# Linux/Mac
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Windows (run as Administrator)
icacls storage /grant Users:F /T
icacls bootstrap\cache /grant Users:F /T
```

### Error: "Unexpected token '<', "<!DOCTYPE "... is not valid JSON"

**Cause:** Session cookie not sent with AJAX request

**Solution:**
```bash
# 1. Clear browser cache (Ctrl+F5)
# 2. Clear application cache
php artisan config:clear
php artisan view:clear
php artisan cache:clear

# 3. Ensure .env is correct
SESSION_SAME_SITE=lax
```

### QR Scan Not Working

**Solution:**
1. Ensure using HTTPS or localhost
2. Grant camera permission in browser
3. Hard refresh browser (Ctrl+F5)
4. Try different browser (Chrome recommended)

### Queue Jobs Not Running

**Solution:**
```bash
# Development
php artisan queue:work

# Check failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all
```

## 📊 Monitoring

### Check Application Status

```bash
# Check database connection
php artisan db:monitor

# Check cache status
php artisan cache:clear

# View logs
tail -f storage/logs/laravel.log
```

### Check Performance

```bash
# Clear all caches for reset
php artisan optimize:clear

# Cache for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 📞 Support

For issues, questions, or feature requests, contact the development team.

---

## 🔒 License

Proprietary software. All rights reserved.

---

**Version**: 1.0.0  
**Last Updated**: 2026-07-08  
**Status**: Production Ready 🚀
