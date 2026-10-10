<?php

namespace App\Console\Commands;

use App\Support\AltaPanaderia;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

/**
 * Da de alta una panadería por consola; lo mismo que el panel /panel del
 * dominio central. Crea y migra su base y deja el primer administrador.
 */
class CrearTenant extends Command
{
    protected $signature = 'obrador:tenant-crear
        {id : Slug de la panadería; también es su subdominio y nombra su base (obrador_<id>)}
        {--negocio= : Nombre del negocio que muestra la aplicación}
        {--dominio=* : Dominios propios adicionales (panaderialuna.com)}
        {--admin-nombre= : Nombre del primer administrador}
        {--admin-email= : Correo del primer administrador}
        {--admin-password= : Contraseña del primer administrador (si se omite, se pide)}
        {--sin-admin : Solo crea la base vacía y migrada, para importar después un respaldo}';

    protected $description = 'Da de alta una panadería con su propia base de datos';

    public function handle(): int
    {
        $datos = [
            'id' => strtolower((string) $this->argument('id')),
            'dominios' => array_values(array_unique(array_map('strtolower', (array) $this->option('dominio')))),
        ];

        $validador = Validator::make($datos, [
            'id' => AltaPanaderia::reglasId(),
            'dominios.*' => AltaPanaderia::reglasDominio(),
        ], AltaPanaderia::mensajes());

        if ($validador->fails()) {
            return $this->fallar($validador->errors()->all());
        }

        $negocio = $this->option('negocio') ?: $this->ask('Nombre del negocio', $datos['id']);

        $administrador = null;
        if (! $this->option('sin-admin')) {
            $administrador = [
                'nombre' => $this->option('admin-nombre') ?: $this->ask('Nombre del administrador'),
                'email' => $this->option('admin-email') ?: $this->ask('Correo del administrador'),
                'password' => $this->option('admin-password') ?: $this->secret('Contraseña del administrador'),
            ];

            $validador = Validator::make($administrador, AltaPanaderia::reglasAdministrador());
            if ($validador->fails()) {
                return $this->fallar($validador->errors()->all());
            }
        }

        $this->info("Creando la base de \"{$datos['id']}\" y aplicando migraciones...");

        try {
            $tenant = AltaPanaderia::crear($datos['id'], (string) $negocio, $datos['dominios'], $administrador);
        } catch (\Throwable $exception) {
            return $this->fallar(['No se pudo completar el alta: '.$exception->getMessage()]);
        }

        $this->info("Panadería \"{$datos['id']}\" creada en la base {$tenant->database()->getName()}.");

        if ($administrador === null) {
            $this->warn('Se creó sin administrador. Importá un respaldo (obrador:tenant-importar) o creá uno desde el panel.');
        }

        return self::SUCCESS;
    }

    /**
     * @param  list<string>  $errores
     */
    private function fallar(array $errores): int
    {
        foreach ($errores as $error) {
            $this->error($error);
        }

        return self::FAILURE;
    }
}
