<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
// use Laravel\Sanctum\HasApiTokens; // aktifkan jika pakai Sanctum
use Spatie\Permission\Traits\HasRoles; // <- penting untuk role & permission

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles; // , HasApiTokens;

    /**
     * Kolom yang boleh diisi mass-assignment.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        // kalau kamu masih pakai kolom role di tabel users,
        // boleh dibiarkan di sini, tapi biasanya spatie pakai tabel roles sendiri
        'role',
        'profile_photo_path',
    ];

    /**
     * Kolom yang disembunyikan saat serialisasi.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Casting atribut.
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        // 'password' => 'hashed', // aktifkan jika di controller TIDAK memakai Hash::make()
    ];

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function inventoryMovements()
    {
        return $this->hasMany(InventoryMovement::class);
    }

    // Kalau produk juga per kasir:
    public function products()
    {
        return $this->hasMany(Product::class);
    }

}
