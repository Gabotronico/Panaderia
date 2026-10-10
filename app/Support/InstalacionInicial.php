<?php

namespace App\Support;

use App\Models\AppSetting;
use App\Models\User;
use Database\Seeders\CategoriaSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\DB;

/**
 * Deja una base de panadería lista para usar: roles y permisos, categorías
 * por defecto, nombre del negocio y el primer administrador. La usan el
 * asistente de setup y el alta de panaderías por consola; trabaja sobre la
 * conexión activa, así que con tenancy iniciada cae en la base de la
 * panadería.
 */
final class InstalacionInicial
{
    public static function ejecutar(string $negocio, string $nombre, string $email, string $password): User
    {
        return DB::transaction(function () use ($negocio, $nombre, $email, $password): User {
            app(RoleSeeder::class)->run();
            app(CategoriaSeeder::class)->run();

            AppSetting::write('business_name', $negocio);
            AppSetting::write('installed_at', now()->toIso8601String());

            $user = User::query()->create([
                'name' => $nombre,
                'email' => $email,
                'password' => $password,
            ]);

            $user->assignRole('Administrador');

            return $user;
        });
    }
}
