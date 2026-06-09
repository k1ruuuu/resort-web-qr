# Automatic Voucher Expiration Setup

This document explains how the automatic voucher expiration system works and how to set it up.

## Overview

Vouchers automatically change from `active` to `expired` status when they pass their checkout time (9 PM on checkout date). This happens through:

1. **Scheduled Command** - Runs every hour to batch expire vouchers
2. **Real-time Check** - Expires immediately when voucher is scanned/redeemed
3. **Audit Logging** - All auto-expirations are logged

## How It Works

### Expiration Time
- Vouchers expire at **21:00 (9 PM)** on the checkout date
- Uses the property's configured timezone (e.g., Asia/Jakarta / WIB)

### Example
**Booking:**
- Check-in: June 9, 2026
- Check-out: June 11, 2026
- Timezone: Asia/Jakarta

**Status:**
- ✅ Active until: June 11, 2026 20:59:59 WIB
- ❌ Expired from: June 11, 2026 21:00:00 WIB onwards

### Expiration Triggers

#### 1. Scheduled Command (Batch Processing)
Runs **every hour** to check all active vouchers:

```bash
php artisan voucher:expire
```

**Schedule:** Hourly (via Laravel Scheduler)

#### 2. Real-time Check (Immediate)
Automatically checks and expires when:
- Voucher is scanned (`verifyScannedCode`)
- Voucher is redeemed (`redeem`)

## Setup Instructions

### 1. Configure Laravel Scheduler

The scheduler is already configured in `routes/console.php`. You need to add ONE cron entry to your server:

#### On Linux/Mac:
```bash
# Open crontab editor
crontab -e

# Add this line:
* * * * * cd /path/to/resort-web-qr && php artisan schedule:run >> /dev/null 2>&1
```

#### On Windows (Task Scheduler):
1. Open Task Scheduler
2. Create a new task
3. Set trigger: Every 1 minute
4. Set action: Run program
   - Program: `C:\path\to\php.exe`
   - Arguments: `artisan schedule:run`
   - Start in: `C:\path\to\resort-web-qr`

### 2. Verify Scheduler is Running

Check if scheduler is working:

```bash
php artisan schedule:list
```

You should see:
```
0 * * * * php artisan voucher:expire ............... Next Due: 1 hour from now
*/5 * * * * php artisan voucher:send-scheduled ..... Next Due: 5 minutes from now
```

### 3. Manual Testing

Test the expiration command manually:

```bash
# Run expiration check
php artisan voucher:expire

# Expected output:
# Checking for vouchers to expire...
# Expired voucher #123 for booking #456
# Successfully expired 1 voucher(s).
```

### 4. Verify in Database

Check voucher status in database:

```sql
SELECT id, booking_id, status, created_at, updated_at 
FROM guest_vouchers 
WHERE status = 'expired';
```

### 5. Check Audit Logs

All auto-expirations are logged:

```sql
SELECT * FROM audit_logs 
WHERE action = 'voucher.auto_expired' 
ORDER BY created_at DESC;
```

## Scheduled Tasks Summary

| Command | Schedule | Purpose |
|---------|----------|---------|
| `voucher:expire` | Every hour | Auto-expire vouchers past checkout time |
| `voucher:send-scheduled` | Every 5 minutes | Send scheduled WhatsApp vouchers |

## Monitoring

### Check Last Run Time

```bash
# View scheduler logs
tail -f storage/logs/laravel.log | grep "voucher:expire"
```

### Force Immediate Run

```bash
# Force run the expiration command now
php artisan voucher:expire --verbose
```

## Troubleshooting

### Scheduler Not Running

**Issue:** Vouchers not expiring automatically

**Solution:**
1. Verify cron job is configured: `crontab -l`
2. Check Laravel logs: `tail -f storage/logs/laravel.log`
3. Test manually: `php artisan voucher:expire`

### Wrong Timezone

**Issue:** Vouchers expiring at wrong time

**Solution:**
1. Check property timezone in database: `SELECT timezone FROM properties;`
2. Verify system timezone: `php artisan tinker` → `now()`
3. Update property timezone if needed

### Permissions Issues (Linux)

**Issue:** Cron job fails due to permissions

**Solution:**
```bash
# Set correct ownership
sudo chown -R www-data:www-data storage bootstrap/cache

# Set correct permissions
sudo chmod -R 775 storage bootstrap/cache
```

## Development vs Production

### Development
- Run scheduler manually for testing: `php artisan schedule:work`
- This keeps the scheduler running in the foreground

### Production
- Use cron job (Linux/Mac) or Task Scheduler (Windows)
- Scheduler runs in background automatically

## Status Flow Diagram

```
[Active Voucher]
       ↓
  (Time passes)
       ↓
[Checkout Date @ 9 PM]
       ↓
  (Hourly check OR scan attempt)
       ↓
[Status changed to Expired]
       ↓
[Audit log created]
```

## API Behavior After Expiration

When an expired voucher is scanned:

**Response:**
```json
{
  "success": false,
  "message": "This voucher is no longer active."
}
```

**Scan Log:**
- `scan_result`: `voucher_not_active`
- Voucher status shown as `expired` in database

## Questions?

If you have issues with the automatic expiration system:
1. Check the Laravel logs
2. Verify cron job is running
3. Test the command manually
4. Check database timestamps
