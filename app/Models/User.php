<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role', // ✅ PASTIKAN 'role' ADA DI SINI!
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // ✅ Helper method untuk mendapatkan role display name
    public function getRoleDisplayAttribute(): string
    {
        return match($this->role) {
            'admin' => 'Admin',
            'kasir' => 'Kasir',
            'owner' => 'Owner',
            default => 'User',
        };
    }
}
