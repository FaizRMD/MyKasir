<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProfileController extends Controller
{
    public function edit()
    {
        $user = Auth::user();
        return view('profile.edit', compact('user'));
    }

    public function update(Request $request): RedirectResponse
    {
        $user = Auth::user();

        // Normalisasi email ke lowercase agar konsisten di DB & unik
        if ($request->filled('email')) {
            $request->merge(['email' => mb_strtolower($request->string('email'))]);
        }

        $data = $request->validate([
            'name'          => ['required', 'string', 'max:255'],
            'email'         => [
                'required','string','email','max:255',
                Rule::unique('users','email')->ignore($user->id),
            ],
            'password'      => ['nullable', 'confirmed', 'min:6'],
            'photo'         => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_photo'  => ['nullable', 'boolean'],
        ]);

        // Tentukan disk untuk avatar (pakai 'private' kalau dikonfigurasi)
        $diskName = config('filesystems.disks.private') ? 'private' : 'local';
        $disk     = Storage::disk($diskName);

        // Hapus foto jika diminta
        if ($request->boolean('remove_photo') && $user->profile_photo_path) {
            if ($disk->exists($user->profile_photo_path)) {
                $disk->delete($user->profile_photo_path);
            }
            $data['profile_photo_path'] = null;
        }

        // Upload foto baru
        if ($request->hasFile('photo')) {
            // Hapus lama jika ada
            if ($user->profile_photo_path && $disk->exists($user->profile_photo_path)) {
                $disk->delete($user->profile_photo_path);
            }

            // Simpan ke folder khusus user
            $path = $request->file('photo')->store('avatars/'.$user->id, $diskName);
            $data['profile_photo_path'] = $path;
        }

        // Password opsional
        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        // (opsional) paksa update timestamp kalau yang berubah hanya foto
        // $user->touch();

        return back()->with('ok', 'Profil berhasil diperbarui.');
    }

    /**
     * Stream avatar dari storage privat.
     * URL: /avatar/{user}
     */
    public function avatar(User $user): StreamedResponse|RedirectResponse
    {
        // Hanya pemilik (atau admin/owner) yang boleh mengakses avatar privat
        $me = Auth::user();
        abort_unless(
            $me && ($me->id === $user->id || in_array($me->role ?? '', ['admin','owner'], true)),
            403
        );

        $diskName = config('filesystems.disks.private') ? 'private' : 'local';
        $disk     = Storage::disk($diskName);
        $path     = $user->profile_photo_path;

        // Fallback ke UI-Avatars bila belum ada file
        if (!$path || !$disk->exists($path)) {
            $fallback = 'https://ui-avatars.com/api/?name='
                . urlencode($user->name ?? 'U')
                . '&background=EAF1FF&color=0F172A';
            return redirect()->away($fallback);
        }

        // Cache-friendly headers (ETag)
        $etag = md5(($user->updated_at?->timestamp ?? time()).'|'.$path);
        if (request()->headers->get('If-None-Match') === $etag) {
            return response()->noContent(304);
        }

        $mime   = $disk->mimeType($path) ?: 'image/jpeg';
        $stream = $disk->readStream($path);

        abort_unless($stream !== false, 404);

        return response()->stream(function () use ($stream) {
            fpassthru($stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        }, 200, [
            'Content-Type'  => $mime,
            'Cache-Control' => 'public, max-age=86400',
            'ETag'          => $etag,
            'Content-Disposition' => 'inline; filename="avatar"',
        ]);
    }
}
