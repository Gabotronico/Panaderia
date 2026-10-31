<?php

namespace App\Support;

use App\Models\AppSetting;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Identidad\Cuentas;
use App\Support\Respaldos\Respaldo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;
use Stancl\Tenancy\Database\Models\Domain;

/**
 * Alta y mantenimiento de panaderías, compartido por el panel del dominio
 * central y los comandos de consola.
 */
final class AltaPanaderia
{
    /**
     * Ids que no se le dan a una panadería: el id es el primer segmento de
     * sus URLs (obrador.incgroupbo.com/luna), y estos ya son rutas del
     * dominio central (/panel, /up) o carpetas de public/ (/build).
     */
    public const RESERVADOS = [
        'panel', 'up', 'build', 'images', 'storage', 'vendor', 'api', 'admin',
        'login', 'logout', 'setup', 'www', 'obrador', 'soporte',
        // Rutas del login central con cuenta única.
        'entrar', 'cuenta',
    ];

    /** Reglas del id: es la ruta de la panadería y nombra su base (obrador_<id>). */
    public static function reglasId(): array
    {
        return [
            'required', 'string',
            // El tope deja margen al prefijo dentro de los 64 caracteres que
            // MySQL admite como nombre de base.
            'max:40',
            'regex:/^[a-z0-9]([a-z0-9-]*[a-z0-9])?$/',
            Rule::notIn(self::RESERVADOS),
            Rule::unique(Tenant::class, 'id'),
            Rule::unique(Domain::class, 'domain'),
        ];
    }

    /** Las mismas que el asistente de setup. */
    public static function reglasAdministrador(): array
    {
        return [
            'nombre' => ['required', 'string', 'min:2', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:150'],
            'password' => ['required', Password::min(10)->mixedCase()->numbers()],
        ];
    }

    public static function mensajes(): array
    {
        return [
            'id.regex' => 'El id solo admite minúsculas, números y guiones (no al inicio ni al final).',
            'id.not_in' => 'Ese id está reservado.',
            'id.unique' => 'Ya hay una panadería con ese id.',
        ];
    }

    /**
     * Crea la panadería con su base migrada y la deja lista: con un
     * administrador nuevo, con los datos de un respaldo, o vacía si no se
     * pasa ninguno. Si algo falla se borra entera, base incluida.
     *
     * @param  array{nombre: string, email: string, password: string}|null  $administrador
     */
    public static function crear(
        string $id,
        string $negocio,
        ?array $administrador = null,
        ?string $respaldo = null,
    ): Tenant {
        // Al crearse dispara CreateDatabase + MigrateDatabase
        // (TenancyServiceProvider).
        $tenant = Tenant::query()->create(['id' => $id, 'negocio' => $negocio]);

        try {
            // Las panaderías se identifican por la ruta, no por dominio; el
            // registro reserva el id en la tabla de dominios de stancl.
            $tenant->domains()->create(['domain' => $id]);

            if ($respaldo !== null) {
                $tenant->run(function () use ($respaldo, $negocio) {
                    Respaldo::restaurar($respaldo);

                    if (AppSetting::read('business_name') === null) {
                        AppSetting::write('business_name', $negocio);
                    }
                });
            }

            if ($administrador !== null) {
                self::guardarAdministrador($tenant, $administrador['nombre'], $administrador['email'], $administrador['password']);
            }
        } catch (\Throwable $exception) {
            self::eliminar($tenant);

            throw $exception;
        }

        return $tenant;
    }

    /**
     * Crea un administrador en la panadería, o le da el rol y una contraseña
     * nueva si ese correo ya existe: es la forma de devolverle el acceso a
     * un cliente que la olvidó. En una panadería sin instalar hace la
     * instalación inicial completa.
     */
    public static function guardarAdministrador(Tenant $tenant, string $nombre, string $email, string $password): User
    {
        return $tenant->run(function () use ($tenant, $nombre, $email, $password): User {
            if (! Role::query()->where('name', 'Administrador')->exists()) {
                return InstalacionInicial::ejecutar($tenant->negocio ?? $tenant->getTenantKey(), $nombre, $email, $password);
            }

            return DB::transaction(function () use ($tenant, $nombre, $email, $password): User {
                $user = User::query()->firstOrNew(['email' => $email]);
                $user->fill(['name' => $nombre, 'password' => $password])->save();
                $user->assignRole('Administrador');

                Cuentas::vincular($tenant, $user, $password);

                return $user;
            });
        });
    }

    /** Borra la panadería: su base, sus dominios y sus archivos. */
    public static function eliminar(Tenant $tenant): void
    {
        $storage = $tenant->run(fn () => storage_path());

        $tenant->delete();

        if (is_dir($storage) && $storage !== storage_path()) {
            File::deleteDirectory($storage);
        }
    }

    /** Administradores de la panadería, para mostrarlos en el panel. */
    public static function administradores(Tenant $tenant): array
    {
        return $tenant->run(function (): array {
            // Una panadería creada vacía todavía no tiene roles.
            if (! Role::query()->where('name', 'Administrador')->exists()) {
                return [];
            }

            return User::role('Administrador')
                ->orderBy('name')
                ->get(['id', 'name', 'email'])
                ->map->only(['id', 'name', 'email'])
                ->all();
        });
    }
}
