<?php

namespace App\Console\Commands;

use App\Models\SuperAdmin;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/**
 * Crea un superadministrador del panel /panel, o le cambia la contraseña si
 * el correo ya existe. Es la única forma de crearlos: el panel no tiene
 * registro público.
 */
class CrearSuperAdmin extends Command
{
    protected $signature = 'obrador:superadmin-crear
        {email : Correo con el que ingresa al panel}
        {--nombre= : Nombre visible}
        {--password= : Contraseña (si se omite, se pide)}';

    protected $description = 'Crea un superadministrador del panel central o le cambia la contraseña';

    public function handle(): int
    {
        $datos = [
            'email' => strtolower((string) $this->argument('email')),
            'nombre' => $this->option('nombre') ?: $this->ask('Nombre', 'Administrador'),
            'password' => $this->option('password') ?: $this->secret('Contraseña'),
        ];

        $validador = Validator::make($datos, [
            'email' => ['required', 'email', 'max:150'],
            'nombre' => ['required', 'string', 'min:2', 'max:100'],
            'password' => ['required', Password::min(12)->mixedCase()->numbers()],
        ]);

        if ($validador->fails()) {
            foreach ($validador->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $admin = SuperAdmin::query()->firstOrNew(['email' => $datos['email']]);
        $existia = $admin->exists;
        $admin->fill(['name' => $datos['nombre'], 'password' => $datos['password']])->save();

        $this->info($existia
            ? "Se actualizó la contraseña de {$admin->email}."
            : "Superadministrador {$admin->email} creado. Ingresa en /panel del dominio central.");

        return self::SUCCESS;
    }
}
