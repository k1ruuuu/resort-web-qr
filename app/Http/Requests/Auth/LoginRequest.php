<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            // SECURITY LOG: Failed login attempt
            \Log::warning('[SECURITY] Failed login attempt', [
                'email' => $this->input('email'),
                'ip' => $this->ip(),
                'user_agent' => $this->userAgent(),
                'timestamp' => now()->toDateTimeString(),
            ]);

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        if (! Auth::user()->is_active) {
            // SECURITY LOG: Inactive account login attempt
            \Log::warning('[SECURITY] Inactive account login attempt', [
                'email' => $this->input('email'),
                'user_id' => Auth::id(),
                'ip' => $this->ip(),
            ]);

            Auth::logout();
            throw ValidationException::withMessages([
                'email' => __('Your account is inactive.'),
            ]);
        }

        // SECURITY LOG: Successful login
        \Log::info('[SECURITY] Successful login', [
            'user_id' => Auth::id(),
            'email' => Auth::user()->email,
            'ip' => $this->ip(),
            'user_agent' => $this->userAgent(),
        ]);

        RateLimiter::clear($this->throttleKey());
    }

    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        // SECURITY LOG: Account lockout due to rate limiting
        \Log::warning('[SECURITY] Account lockout - too many login attempts', [
            'email' => $this->input('email'),
            'ip' => $this->ip(),
            'attempts' => RateLimiter::attempts($this->throttleKey()),
            'available_in' => RateLimiter::availableIn($this->throttleKey()) . ' seconds',
        ]);

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
