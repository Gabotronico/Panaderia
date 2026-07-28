<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        if (! env('SEED_DEMO_USERS', false)) {
            $this->command?->warn('Usuarios de demostración omitidos. Use el asistente de configuración inicial.');

            return;
        }

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
