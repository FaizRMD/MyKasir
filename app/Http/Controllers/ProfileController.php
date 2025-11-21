<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit()
    {
        return view('profile.edit', [
            'user' => Auth::user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        // ✅ Validasi input
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'], // max 2MB
            'remove_photo' => ['nullable', 'boolean'],
        ]);

        // ✅ Update nama dan email
        $user->name = $validated['name'];
        $user->email = $validated['email'];

        // ✅ Update password jika diisi
        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        // ✅ Handle foto profil
        if ($request->hasFile('photo')) {
            // Hapus foto lama jika ada
            if ($user->profile_photo_path) {
                Storage::disk('public')->delete($user->profile_photo_path);
            }

            // Upload foto baru
            $path = $request->file('photo')->store('profile-photos', 'public');
            $user->profile_photo_path = $path;
        }
        // ✅ Handle hapus foto
        elseif ($request->has('remove_photo') && $request->remove_photo == '1') {
            if ($user->profile_photo_path) {
                Storage::disk('public')->delete($user->profile_photo_path);
                $user->profile_photo_path = null;
            }
        }

        // ✅ Simpan perubahan
        $user->save();

        return redirect()->route('profile.edit')->with('success', 'Profil berhasil diperbarui!');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request)
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = Auth::user();

        Auth::logout();

        // Hapus foto profil jika ada
        if ($user->profile_photo_path) {
            Storage::disk('public')->delete($user->profile_photo_path);
        }

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    /**
     * Display user avatar (with privacy control)
     */
    public function avatar(Request $request, $userId = null)
    {
        // Jika tidak ada userId, gunakan user yang login
        $targetUserId = $userId ?? Auth::id();

        // Cari user
        $user = \App\Models\User::find($targetUserId);

        if (!$user || !$user->profile_photo_path) {
            // Return default avatar
            $defaultAvatar = public_path('img/default-avatar.png');

            if (file_exists($defaultAvatar)) {
                return response()->file($defaultAvatar);
            }

            // Generate default avatar sederhana
            return $this->generateDefaultAvatar($user?->name ?? 'User');
        }

        // Return foto profil user
        $path = storage_path('app/public/' . $user->profile_photo_path);

        if (!file_exists($path)) {
            return $this->generateDefaultAvatar($user->name);
        }

        return response()->file($path);
    }

    /**
     * Generate default avatar with initials
     */
    private function generateDefaultAvatar($name)
    {
        // Ambil inisial nama (max 2 huruf)
        $words = explode(' ', $name);
        $initials = '';
        foreach ($words as $word) {
            if (strlen($initials) < 2 && !empty($word)) {
                $initials .= strtoupper(substr($word, 0, 1));
            }
        }
        if (empty($initials)) {
            $initials = 'U';
        }

        // Warna random berdasarkan nama
        $colors = [
            ['bg' => '#3B82F6', 'text' => '#FFFFFF'], // blue
            ['bg' => '#10B981', 'text' => '#FFFFFF'], // green
            ['bg' => '#F59E0B', 'text' => '#FFFFFF'], // amber
            ['bg' => '#EF4444', 'text' => '#FFFFFF'], // red
            ['bg' => '#8B5CF6', 'text' => '#FFFFFF'], // violet
            ['bg' => '#EC4899', 'text' => '#FFFFFF'], // pink
        ];
        $colorIndex = abs(crc32($name)) % count($colors);
        $color = $colors[$colorIndex];

        // Buat SVG
        $svg = <<<SVG
<svg width="200" height="200" xmlns="http://www.w3.org/2000/svg">
    <rect width="200" height="200" fill="{$color['bg']}"/>
    <text x="100" y="100" font-family="Arial, sans-serif" font-size="80" font-weight="bold"
          fill="{$color['text']}" text-anchor="middle" dominant-baseline="central">
        {$initials}
    </text>
</svg>
SVG;

        return response($svg, 200)->header('Content-Type', 'image/svg+xml');
    }
}
