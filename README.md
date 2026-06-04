# Resort Digital QR Voucher System

Laravel 12 application for resort booking check-in, daily facility vouchers, UUID QR redemption, and reporting.

## Stack

- Laravel 12, PHP 8.2+
- MySQL 8
- Laravel Breeze-style session auth
- Spatie Laravel Permission (RBAC)
- AdminLTE 3 + Bootstrap 5 (CDN)
- Simple QR Code (dynamic voucher URLs)

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Default admin: `admin@resort.local` / `password`

## API (session cookie after login)

- `POST /api/login`
- `GET /api/bookings`
- `POST /api/vouchers/generate`
- `POST /api/vouchers/redeem`

## Tests

```bash
php artisan test
```

## Workflow

Check-in → Generate voucher (per booking + facility + date) → Guest receives QR → Outlet scans/redeems → Audit & usage logs.
