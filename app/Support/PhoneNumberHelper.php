<?php

namespace App\Support;

class PhoneNumberHelper
{
    /**
     * Mask phone number for logs and display (PII protection).
     * Example: 08123456789 -> 081****89
     */
    public static function mask(string $phone): string
    {
        $digits = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($digits) <= 4) {
            return $digits === '' ? '(empty)' : '****';
        }

        return substr($digits, 0, 3) . '****' . substr($digits, -2);
    }

    /**
     * Clean phone number for Fonnte API (national number without country code or leading 0).
     */
    public static function cleanForFonnte(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (str_starts_with($phone, '62')) {
            return ltrim(substr($phone, 2), '0');
        }

        if (str_starts_with($phone, '0')) {
            return ltrim($phone, '0');
        }

        return $phone;
    }

    /**
     * Check if phone number is an Indonesian phone number.
     */
    public static function isIndonesian(string $phone): bool
    {
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        if (str_starts_with($phone, '+62') || str_starts_with($phone, '62') || str_starts_with($phone, '08')) {
            return true;
        }

        if (str_starts_with($phone, '8') && strlen($phone) >= 10 && strlen($phone) <= 13) {
            return true;
        }

        return false;
    }

    /**
     * Normalize phone number to standard international 62 format (without +).
     * Example: 08123456789 -> 628123456789
     */
    public static function normalizeTo62(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (str_starts_with($phone, '0')) {
            return '62' . substr($phone, 1);
        }

        if (str_starts_with($phone, '62')) {
            return $phone;
        }

        if (str_starts_with($phone, '8')) {
            return '62' . $phone;
        }

        return $phone;
    }
}
