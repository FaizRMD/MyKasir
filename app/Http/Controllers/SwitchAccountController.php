<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class SwitchAccountController extends Controller
{
    /**
     * Beralih ke akun lain tanpa logout
     * Memerlukan email dan password akun tujuan untuk keamanan
     */
    public function switch(Request $request)
    {
        // Validasi input
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $currentUser = Auth::user();

        // Cari user berdasarkan email
        $targetUser = User::where('email', $request->email)->first();

        // Validasi: User harus ada
        if (!$targetUser) {
            return back()->withErrors([
                'email' => 'Email tidak ditemukan dalam sistem.'
            ])->withInput();
        }

        // Validasi: Tidak bisa switch ke akun yang sama
        if ($targetUser->id === $currentUser->id) {
            return back()->withErrors([
                'email' => 'Anda sudah login dengan akun ini.'
            ])->withInput();
        }

        // Validasi: Password harus benar
        if (!Hash::check($request->password, $targetUser->password)) {
            return back()->withErrors([
                'password' => 'Password salah. Masukkan password yang benar untuk akun tujuan.'
            ])->withInput(['email' => $request->email]);
        }

        // Validasi: Akun harus aktif (jika ada field is_active)
        if (isset($targetUser->is_active) && !$targetUser->is_active) {
            return back()->withErrors([
                'email' => 'Akun ini sedang tidak aktif. Hubungi administrator.'
            ])->withInput(['email' => $request->email]);
        }

        try {
            // Log aktivitas switch account
            Log::info('Account Switch', [
                'from_user_id' => $currentUser->id,
                'from_email'   => $currentUser->email,
                'to_user_id'   => $targetUser->id,
                'to_email'     => $targetUser->email,
                'ip_address'   => $request->ip(),
                'user_agent'   => $request->userAgent(),
            ]);

            // Login ke akun baru tanpa logout
            Auth::login($targetUser, true);

            // Regenerate session untuk keamanan
            $request->session()->regenerate();

            return redirect()->route('dashboard')->with('ok',
                "Berhasil beralih ke akun {$targetUser->name} ({$targetUser->email})"
            );

        } catch (\Exception $e) {
            Log::error('Switch Account Failed', [
                'error' => $e->getMessage(),
                'from_user' => $currentUser->id,
                'to_user' => $targetUser->id,
            ]);

            return back()->withErrors([
                'error' => 'Terjadi kesalahan saat beralih akun. Silakan coba lagi.'
            ])->withInput(['email' => $request->email]);
        }
    }
}
