#!/bin/bash
# Daily cron job for Resort QR System
# Tasks:
#   1. Delete bookings past Expected Departure (check_out date + cutoff)
#   2. Cancel no-show Expected Arrival bookings (past check_in + N days)
#   3. Expire vouchers past deadline
#
# === INSTALLASI DI CPANEL ===
#
# Opsi 1 — Jalankan script ini langsung (UTC):
#   35 5 * * * /home/chanayac/public_html/resort-web-qr/cron-daily.sh
#
# Opsi 2 — Jalankan artisan langsung (UTC):
#   35 5 * * * /usr/local/bin/ea-php83 /home/chanayac/public_html/resort-web-qr/artisan daily:maintenance --all --no-interaction >> /home/chanayac/public_html/resort-web-qr/storage/logs/cron-daily.log 2>&1
#
# Laravel Scheduler (rekomendasi — jalankan tiap menit, Laravel atur jadwal di console.php):
#   * * * * * /usr/local/bin/ea-php83 /home/chanayac/public_html/resort-web-qr/artisan schedule:run >> /dev/null 2>&1
#
# Semua jadwal di atas menggunakan zona waktu server (cPanel pakai UTC).
# Expected Departure akan dihapus setelah pukul 12:30 WIB (= 05:30 UTC).
# Laravel Scheduler sudah dikonfigurasi timezone 'Asia/Jakarta' di console.php.
#
# Catatan: Ganti "ea-php83" dengan versi PHP yang terdaftar di MultiPHP Manager domain ini.
# PERHATIAN: --auto-checkout akan MENGHAPUS booking beserta data terkait secara permanen.

PROJECT_DIR="/home/chanayac/public_html/resort-web-qr"
PHP_BIN="/usr/local/bin/ea-php83"

cd "$PROJECT_DIR" || { echo "[$(date)] ERROR: Directory $PROJECT_DIR not found."; exit 1; }

echo "[$(date)] ====== Daily Maintenance Start ======"

$PHP_BIN artisan daily:maintenance --all --no-interaction

echo "[$(date)] ====== Daily Maintenance End ======"
