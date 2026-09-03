<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
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
            'email' => ['required_without_all:login,username', 'string'],
            'login' => ['required_without_all:email,username', 'string'],
            'username' => ['required_without_all:email,login', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    public function attributes(): array
    {
        return [
            'email' => __('Username or Email'),
            'login' => __('Username or Email'),
            'username' => __('Username or Email'),
        ];
    }

    public function loginIdentifier(): string
    {
        return trim((string) ($this->input('email') ?? $this->input('login') ?? $this->input('username')));
    }

    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $identifier = $this->loginIdentifier();

        // Match user by email, username, or name
        $user = User::where('email', $identifier)
            ->orWhere('username', $identifier)
            ->orWhere('name', $identifier)
            ->first();

        $attemptSuccessful = false;
        if ($user) {
            $attemptSuccessful = Auth::attempt([
                'id' => $user->id,
                'password' => $this->input('password'),
            ], $this->boolean('remember'));
        } else {
            // Run a dummy attempt to preserve timing characteristics and fire event
            Auth::attempt([
                'email' => $identifier,
                'password' => $this->input('password'),
            ], $this->boolean('remember'));
        }

        if (! $attemptSuccessful) {
            RateLimiter::hit($this->throttleKey());

            // SECURITY LOG: Failed login attempt
            \Log::warning('[SECURITY] Failed login attempt', [
                'login' => $identifier,
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
                'login' => $identifier,
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
            'username' => Auth::user()->username,
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
            'login' => $this->loginIdentifier(),
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
        return Str::transliterate(Str::lower($this->loginIdentifier()).'|'.$this->ip());
    }
}
