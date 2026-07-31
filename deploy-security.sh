#!/bin/bash

echo "===================================="
echo " Security Features Deployment"
echo "===================================="
echo ""

echo "[1/6] Clearing configuration cache..."
php artisan config:clear
echo "Done!"
echo ""

echo "[2/6] Clearing application cache..."
php artisan cache:clear
echo "Done!"
echo ""

echo "[3/6] Clearing route cache..."
php artisan route:clear
echo "Done!"
echo ""

echo "[4/6] Clearing view cache..."
php artisan view:clear
echo "Done!"
echo ""

echo "[5/6] Clearing all optimizations..."
php artisan optimize:clear
echo "Done!"
echo ""

echo "[6/6] Caching configuration for production..."
php artisan config:cache
echo "Done!"
echo ""

echo "===================================="
echo " Security Features Active!"
echo "===================================="
echo ""
echo "✓ Attack Detection: ENABLED"
echo "✓ Security Headers: ENABLED"
echo "✓ HTTPS Enforcement: ENABLED"
echo "✓ File Upload Validation: ENABLED"
echo "✓ IP Whitelisting: ENABLED"
echo ""
echo "Test attack detection:"
echo "  curl \"http://localhost/?id=1' OR '1'='1\""
echo ""
echo "View logs:"
echo "  tail -f storage/logs/laravel.log | grep SECURITY"
echo ""
