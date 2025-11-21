<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles  Contoh: 'admin', 'kasir', 'owner' atau 'admin|kasir'
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // ✅ Redirect ke login jika belum login
        if (!Auth::check()) {
            return redirect()
                ->route('login')
                ->with('error', 'Silakan login terlebih dahulu untuk mengakses halaman ini.');
        }

        $user = Auth::user();

        // ✅ Parse roles (support pipe separator: 'admin|kasir')
        $allowedRoles = $this->parseRoles($roles);

        // ✅ Cek akses user
        if ($this->userHasAccess($user, $allowedRoles)) {
            return $next($request);
        }

        // ✅ Log unauthorized access untuk monitoring
        Log::warning('Unauthorized access attempt', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'user_role' => $user->role ?? 'N/A',
            'user_spatie_roles' => method_exists($user, 'getRoleNames')
                ? $user->getRoleNames()->toArray()
                : [],
            'required_roles' => $allowedRoles,
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // ✅ Response untuk unauthorized access
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses ke resource ini.',
                'required_roles' => $allowedRoles,
                'your_role' => $user->role ?? 'N/A',
            ], 403);
        }

        // Redirect dengan pesan error
        return redirect()
            ->route('dashboard')
            ->with('error', sprintf(
                'Akses ditolak! Halaman ini hanya untuk: %s. Role Anda: %s',
                implode(', ', array_map('ucfirst', $allowedRoles)),
                ucfirst($user->role ?? 'Tidak ada')
            ));
    }

    /**
     * Parse roles dari parameter middleware
     * Support: role:admin atau role:admin|kasir|owner
     */
    private function parseRoles(array $roles): array
    {
        $parsed = [];

        foreach ($roles as $role) {
            // Split by pipe
            $splitRoles = explode('|', $role);
            $parsed = array_merge($parsed, $splitRoles);
        }

        return array_unique(array_filter($parsed));
    }

    /**
     * Cek apakah user punya akses
     */
    private function userHasAccess($user, array $allowedRoles): bool
    {
        // ✅ Cek dari kolom database 'role'
        if (isset($user->role) && in_array($user->role, $allowedRoles)) {
            return true;
        }

        // ✅ Cek dari Spatie Permission (hasRole)
        if (method_exists($user, 'hasRole')) {
            foreach ($allowedRoles as $role) {
                if ($user->hasRole($role)) {
                    return true;
                }
            }
        }

        // ✅ Cek dari Spatie Permission (hasAnyRole) - lebih efisien
        if (method_exists($user, 'hasAnyRole') && $user->hasAnyRole($allowedRoles)) {
            return true;
        }

        return false;
    }
}
