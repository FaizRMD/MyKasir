<?php

namespace Database\Seeders;

// database/seeders/UserSeeder.php
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder {
    public function run(): void {
        User::updateOrCreate(
            ['email' => 'owner@contoh.id'],
            ['name' => 'Owner', 'password' => Hash::make('password'), 'role' => 'owner']
        );

        User::updateOrCreate(
            ['email' => 'admin@contoh.id'],
            ['name' => 'Admin', 'password' => Hash::make('password'), 'role' => 'admin']
        );

        User::updateOrCreate(
            ['email' => 'kasir@contoh.id'],
            ['name' => 'Kasir', 'password' => Hash::make('password'), 'role' => 'kasir']
        );
    }
}
