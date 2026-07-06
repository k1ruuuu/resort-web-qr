<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;

class StrongPassword implements ValidationRule
{
    protected int $minLength = 12;
    protected bool $requireUppercase = true;
    protected bool $requireLowercase = true;
    protected bool $requireNumbers = true;
    protected bool $requireSpecialChars = true;
    protected bool $checkCompromised = true;

    /**
     * Common weak passwords to reject
     */
    protected array $commonPasswords = [
        'password', 'password123', '123456', '12345678', 'qwerty', 'abc123',
        'monkey', '1234567', 'letmein', 'trustno1', 'dragon', 'baseball',
        'iloveyou', 'master', 'sunshine', 'ashley', 'bailey', 'passw0rd',
        'shadow', '123123', '654321', 'superman', 'qazwsx', 'michael',
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Check minimum length
        if (strlen($value) < $this->minLength) {
            $fail("The {$attribute} must be at least {$this->minLength} characters.");
            return;
        }

        // Check for uppercase
        if ($this->requireUppercase && !preg_match('/[A-Z]/', $value)) {
            $fail("The {$attribute} must contain at least one uppercase letter.");
            return;
        }

        // Check for lowercase
        if ($this->requireLowercase && !preg_match('/[a-z]/', $value)) {
            $fail("The {$attribute} must contain at least one lowercase letter.");
            return;
        }

        // Check for numbers
        if ($this->requireNumbers && !preg_match('/\d/', $value)) {
            $fail("The {$attribute} must contain at least one number.");
            return;
        }

        // Check for special characters
        if ($this->requireSpecialChars && !preg_match('/[^a-zA-Z\d]/', $value)) {
            $fail("The {$attribute} must contain at least one special character.");
            return;
        }

        // Check against common passwords
        $lowerValue = strtolower($value);
        foreach ($this->commonPasswords as $common) {
            if (str_contains($lowerValue, $common)) {
                $fail("The {$attribute} is too common. Please choose a more secure password.");
                return;
            }
        }

        // Check for sequential characters
        if ($this->hasSequentialChars($value)) {
            $fail("The {$attribute} contains sequential characters. Please choose a more secure password.");
            return;
        }

        // Check for repeated characters
        if ($this->hasRepeatedChars($value)) {
            $fail("The {$attribute} contains too many repeated characters.");
            return;
        }

        // Optional: Check against "Have I Been Pwned" database
        if ($this->checkCompromised && $this->isCompromised($value)) {
            $fail("The {$attribute} has been found in data breaches. Please choose a different password.");
            return;
        }
    }

    /**
     * Check for sequential characters (123, abc, etc.)
     */
    protected function hasSequentialChars(string $value): bool
    {
        $sequences = ['0123456789', '9876543210', 'abcdefghijklmnopqrstuvwxyz', 'zyxwvutsrqponmlkjihgfedcba'];
        
        foreach ($sequences as $sequence) {
            for ($i = 0; $i < strlen($sequence) - 3; $i++) {
                $substr = substr($sequence, $i, 4);
                if (stripos($value, $substr) !== false) {
                    return true;
                }
            }
        }
        
        return false;
    }

    /**
     * Check for repeated characters (aaaa, 1111, etc.)
     */
    protected function hasRepeatedChars(string $value): bool
    {
        return preg_match('/(.)\1{3,}/', $value) === 1;
    }

    /**
     * Check if password exists in data breaches using HaveIBeenPwned API
     * Uses k-anonymity: only sends first 5 chars of SHA1 hash
     */
    protected function isCompromised(string $password): bool
    {
        try {
            // SHA1 hash of password
            $sha1 = strtoupper(sha1($password));
            $prefix = substr($sha1, 0, 5);
            $suffix = substr($sha1, 5);

            // Query HaveIBeenPwned API (k-anonymity model)
            $response = Http::timeout(3)->get("https://api.pwnedpasswords.com/range/{$prefix}");

            if ($response->successful()) {
                $hashes = explode("\r\n", $response->body());
                
                foreach ($hashes as $hash) {
                    [$hashSuffix, $count] = explode(':', $hash);
                    if ($hashSuffix === $suffix) {
                        \Log::warning('Compromised password detected', [
                            'breach_count' => $count,
                            'hash_prefix' => $prefix,
                        ]);
                        return true;
                    }
                }
            }
        } catch (\Exception $e) {
            // If API call fails, don't block the user
            \Log::warning('Password breach check failed', ['error' => $e->getMessage()]);
        }

        return false;
    }

    /**
     * Customize validation requirements
     */
    public static function make(
        int $minLength = 12,
        bool $requireUppercase = true,
        bool $requireLowercase = true,
        bool $requireNumbers = true,
        bool $requireSpecialChars = true,
        bool $checkCompromised = true
    ): self {
        $rule = new self();
        $rule->minLength = $minLength;
        $rule->requireUppercase = $requireUppercase;
        $rule->requireLowercase = $requireLowercase;
        $rule->requireNumbers = $requireNumbers;
        $rule->requireSpecialChars = $requireSpecialChars;
        $rule->checkCompromised = $checkCompromised;
        
        return $rule;
    }
}
