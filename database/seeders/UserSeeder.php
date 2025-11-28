<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Pastikan roles ada di Spatie
        foreach (['owner', 'admin', 'kasir'] as $r) {
            Role::firstOrCreate(['name' => $r, 'guard_name' => 'web']);
        }

        // ========== OWNER ==========
        $owner = User::updateOrCreate(
            ['email' => 'owner@contoh.id'],
            [
                'name' => 'Owner',
                'password' => Hash::make('password'),
                'role' => 'owner',
            ]
        );
        $owner->syncRoles(['owner']);

        // ========== ADMIN ==========
        $admin = User::updateOrCreate(
            ['email' => 'admin@contoh.id'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
                'role' => 'admin',
            ]
        );
        $admin->syncRoles(['admin']);

        // ========== KASIR ==========
        $kasir = User::updateOrCreate(
            ['email' => 'kasir@contoh.id'],
            [
                'name' => 'Kasir',
                'password' => Hash::make('password'),
                'role' => 'kasir',
            ]
        );
        $kasir->syncRoles(['kasir']);
    }
}
