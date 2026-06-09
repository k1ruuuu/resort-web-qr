<?php

namespace App\Console\Commands;

use App\Models\Guest;
use Illuminate\Console\Command;

class CheckPhoneNumbers extends Command
{
    protected $signature = 'phone:check {--fix : Automatically fix phone numbers}';
    protected $description = 'Check and optionally fix guest phone numbers';

    public function handle()
    {
        $this->info('Checking guest phone numbers...');
        $this->newLine();

        $guests = Guest::whereNotNull('phone')->where('phone', '!=', '')->get();
        $invalid = [];
        $valid = 0;

        foreach ($guests as $guest) {
            $phone = $guest->phone;
            $isValid = $this->isValidIndonesianMobile($phone);

            if (!$isValid) {
                $invalid[] = [
                    'id' => $guest->id,
                    'name' => $guest->full_name,
                    'phone' => $phone,
                    'suggested' => $this->suggestFix($phone),
                ];
            } else {
                $valid++;
            }
        }

        $this->info("✅ Valid numbers: {$valid}");
        $this->warn("❌ Invalid numbers: " . count($invalid));
        $this->newLine();

        if (count($invalid) > 0) {
            $this->table(
                ['ID', 'Name', 'Current Phone', 'Suggested Fix'],
                array_map(function ($item) {
                    return [
                        $item['id'],
                        $item['name'],
                        $item['phone'],
                        $item['suggested'] ?? 'Manual fix needed',
                    ];
                }, $invalid)
            );

            if ($this->option('fix')) {
                if ($this->confirm('Do you want to automatically fix these numbers?')) {
                    $fixed = 0;
                    foreach ($invalid as $item) {
                        if ($item['suggested'] && $item['suggested'] !== 'Manual fix needed') {
                            $guest = Guest::find($item['id']);
                            $guest->phone = $item['suggested'];
                            $guest->save();
                            $fixed++;
                            $this->info("Fixed: {$item['name']} -> {$item['suggested']}");
                        }
                    }
                    $this->newLine();
                    $this->info("✅ Fixed {$fixed} phone numbers");
                }
            } else {
                $this->newLine();
                $this->info('💡 Run with --fix flag to automatically fix numbers:');
                $this->info('   php artisan phone:check --fix');
            }
        } else {
            $this->info('🎉 All phone numbers are valid!');
        }

        return 0;
    }

    private function isValidIndonesianMobile(string $phone): bool
    {
        // Clean phone
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        // Valid patterns:
        // +628xxxxxxxxx (10-13 digits after 628)
        // 628xxxxxxxxx
        // 08xxxxxxxxx
        // 8xxxxxxxxx

        if (preg_match('/^(\+?62|0)?8[0-9]{8,11}$/', $phone)) {
            return true;
        }

        return false;
    }

    private function suggestFix(string $phone): ?string
    {
        // Remove all non-numeric except +
        $cleaned = preg_replace('/[^0-9+]/', '', $phone);

        // If already valid, return as-is
        if ($this->isValidIndonesianMobile($cleaned)) {
            return $cleaned;
        }

        // Try common fixes
        // If starts with +620, should be +628
        if (str_starts_with($cleaned, '+620')) {
            $suggested = '+628' . substr($cleaned, 4);
            if ($this->isValidIndonesianMobile($suggested)) {
                return $suggested;
            }
        }

        // If starts with 620, should be 628
        if (str_starts_with($cleaned, '620')) {
            $suggested = '628' . substr($cleaned, 3);
            if ($this->isValidIndonesianMobile($suggested)) {
                return $suggested;
            }
        }

        // If starts with 00, might be international
        if (str_starts_with($cleaned, '00')) {
            $suggested = '+' . substr($cleaned, 2);
            return $suggested;
        }

        // If too long and starts with 62, might have extra digits
        if (str_starts_with($cleaned, '62') && strlen($cleaned) > 13) {
            // Take first 12-13 digits
            $suggested = substr($cleaned, 0, 13);
            if ($this->isValidIndonesianMobile($suggested)) {
                return $suggested;
            }
        }

        // Can't auto-fix
        return null;
    }
}
