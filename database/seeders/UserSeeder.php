<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Usuario Administrador
        $admin = User::create([
            'name' => 'Administrador',
            'email' => 'admin@panaderialuna.com',
            'password' => Hash::make('admin123'),
        ]);
        $admin->assignRole('Administrador');

        // Usuario Cajero
        $cajero = User::create([
            'name' => 'Cajero Principal',
            'email' => 'cajero@panaderialuna.com',
            'password' => Hash::make('cajero123'),
        ]);
        $cajero->assignRole('Cajero');
    }
}