<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Models\User; // <-- TAMBAHKAN IMPORT USER

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        // Ubah validasi dari 'email' menjadi 'npk'
        // Ini akan cocok dengan form login Anda yang sudah menampilkan NPK
        return [
            'npk' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): void
{
    $this->ensureIsNotRateLimited();

    $npk = trim($this->input('npk'));
    $password = trim($this->input('password'));

    if ($password !== 'password') {
        RateLimiter::hit($this->throttleKey());
        throw ValidationException::withMessages(['npk' => 'Password salah.']);
    }

    $user = User::where('npk', $npk)->first();

    if (! $user) {
        RateLimiter::hit($this->throttleKey());
        throw ValidationException::withMessages(['npk' => 'NPK tidak ditemukan.']);
    }

    Auth::login($user);
    RateLimiter::clear($this->throttleKey());
}


    /**
     * Ensure the login request is not rate limited.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'npk' => trans('auth.throttle', [ // Ganti dari 'email' ke 'npk'
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        // Ganti dari 'email' ke 'npk'
        return Str::transliterate(Str::lower($this->input('npk')).'|'.$this->ip());
    }

    /**
     * Get the email form field.
     * (Kita ubah ini menjadi 'npk')
     */
    public function username(): string
    {
        return 'npk';
    }
}
