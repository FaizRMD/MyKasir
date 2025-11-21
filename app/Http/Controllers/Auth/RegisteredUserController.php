<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        // ✅ Validasi input
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', 'in:admin,kasir,owner'], // ✅ Validasi role
        ], [
            'role.required' => 'Silakan pilih role Anda.',
            'role.in' => 'Role tidak valid. Pilih: Admin, Kasir, atau Owner.',
        ]);

        // ✅ DEBUG: Log data yang akan disimpan
        \Log::info('Creating user with data:', $validated);

        // ✅ Buat user DENGAN kolom 'role'
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'], // ✅ PASTIKAN INI ADA!
        ]);

        // ✅ DEBUG: Cek user setelah dibuat
        \Log::info('User created:', [
            'id' => $user->id,
            'email' => $user->email,
            'role' => $user->role, // ⚠️ Pastikan ini tidak 'kasir' jika pilih 'admin'
        ]);

        // ✅ Assign role Spatie (opsional, jika pakai Spatie)
        $user->assignRole($validated['role']);

        event(new Registered($user));

        Auth::login($user);

        // ✅ Simpan role ke session
        session(['active_role' => $validated['role']]);

        // ✅ Redirect berdasarkan role
        $message = match($validated['role']) {
            'admin' => 'Selamat datang, Admin ' . $user->name . '!',
            'owner' => 'Selamat datang, Owner ' . $user->name . '!',
            'kasir' => 'Selamat datang, Kasir ' . $user->name . '!',
            default => 'Selamat datang!',
        };

        return redirect()->route('dashboard')->with('success', $message);
    }
}
