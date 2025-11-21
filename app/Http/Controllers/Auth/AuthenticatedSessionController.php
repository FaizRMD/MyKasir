<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Illuminate\Support\Str;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(Request $request): RedirectResponse
    {
        // ✅ Validasi input login
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email', 'lowercase'],
            'password' => ['required', 'string'],
            'role' => ['required', 'in:admin,kasir,owner'], // ✅ Wajib pilih role
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'password.required' => 'Password wajib diisi.',
            'role.required' => 'Silakan pilih role login Anda.',
            'role.in' => 'Role tidak valid.',
        ]);

        // ✅ Cek rate limiting (anti brute force)
        $this->ensureIsNotRateLimited($request);

        // ✅ Attempt login dengan email & password
        if (!Auth::attempt(
            $request->only('email', 'password'),
            $request->boolean('remember')
        )) {
            // Increment rate limiter
            RateLimiter::hit($this->throttleKey($request), 300); // 5 menit

            throw ValidationException::withMessages([
                'email' => 'Email atau password salah.',
            ]);
        }

        // ✅ Clear rate limiter setelah berhasil login
        RateLimiter::clear($this->throttleKey($request));

        $user = Auth::user();

        // ✅ Validasi: user harus punya role yang dipilih
        if (!$user->hasRole($request->role)) {
            Auth::logout();

            throw ValidationException::withMessages([
                'role' => "Akun Anda tidak terdaftar sebagai {$request->role}. Pilih role yang sesuai.",
            ]);
        }

        // ✅ Regenerate session (keamanan)
        $request->session()->regenerate();

        // ✅ Redirect berdasarkan role
        return match($request->role) {
            'admin' => redirect()->intended(route('dashboard', absolute: false))
                ->with('success', 'Selamat datang kembali, Admin!'),

            'owner' => redirect()->intended(route('dashboard', absolute: false))
                ->with('success', 'Selamat datang kembali, Owner!'),

            'kasir' => redirect()->intended(route('dashboard', absolute: false))
                ->with('success', 'Selamat datang kembali, Kasir!'),

            default => redirect()->intended(route('dashboard', absolute: false)),
        };
    }

    /**
     * Destroy an authenticated session (logout).
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function ensureIsNotRateLimited(Request $request): void
    {
        if (!RateLimiter::tooManyAttempts($this->throttleKey($request), 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey($request));

        throw ValidationException::withMessages([
            'email' => "Terlalu banyak percobaan login. Silakan coba lagi dalam {$seconds} detik.",
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    protected function throttleKey(Request $request): string
    {
        return Str::transliterate(
            Str::lower($request->string('email')) . '|' . $request->ip()
        );
    }
}
