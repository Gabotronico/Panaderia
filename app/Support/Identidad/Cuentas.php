<?php

namespace App\Support\Identidad;

use App\Models\Persona;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Licencias\Licencia;
use App\Support\Tenancy\Contexto;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Vínculo entre la persona (cuenta global) y el usuario de cada panadería.
 *
 * El correo y la contraseña viven una sola vez, en `personas`. Cada panadería
 * conserva su usuario con el rol, el almacén y todo lo que depende de la base
 * de esa panadería; acá solo se registra a quién corresponde.
 *
 * Todas las escrituras van por la conexión central a propósito: esto se llama
 * también desde adentro de una panadería (alta de usuario, instalación
 * inicial), donde la conexión por defecto es la de la panadería.
 */
final class Cuentas
{
    public static function vincular(Tenant $tenant, User $user, ?string $plano = null): Persona
    {
        $central = self::central();
        $correo = mb_strtolower(trim((string) $user->email));

        // Si el usuario ya estaba vinculado, se reutiliza su persona aunque haya
        // cambiado el correo (si no, el correo nuevo crearía una cuenta aparte).
        $vinculo = $central->table('persona_tenant')
            ->where('tenant_id', $tenant->id)
            ->where('user_id', $user->id)
            ->first();

        $persona = $vinculo === null
            ? Persona::query()->firstOrNew(['email' => $correo])
            : Persona::query()->find($vinculo->persona_id);

        if ($persona === null) {
            $persona = Persona::query()->firstOrNew(['email' => $correo]);
        }

        if (! $persona->exists) {
            $persona->name = $user->name;
            // La contraseña que se acaba de definir (o el hash que ya tenía el
            // usuario, en la sincronización de los que ya existían).
            $persona->password = $plano ?? $user->getRawOriginal('password');
        } else {
            if ($persona->email !== $correo) {
                $ocupado = Persona::query()
                    ->where('email', $correo)
                    ->where('id', '!=', $persona->id)
                    ->exists();

                if ($ocupado) {
                    throw ValidationException::withMessages([
                        'email' => 'Ese correo ya pertenece a otra cuenta del sistema.',
                    ]);
                }

                $persona->email = $correo;
            }

            if ($plano !== null) {
                // Cambiar la contraseña en una panadería la cambia en todas: es
                // una sola cuenta.
                $persona->password = $plano;
            }
        }

        $persona->save();

        $existente = $central->table('persona_tenant')
            ->where('persona_id', $persona->id)
            ->where('tenant_id', $tenant->id)
            ->first();

        if ($existente === null) {
            $central->table('persona_tenant')->insert([
                'persona_id' => $persona->id,
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } elseif ((int) $existente->user_id !== (int) $user->id) {
            $central->table('persona_tenant')
                ->where('id', $existente->id)
                ->update(['user_id' => $user->id, 'updated_at' => now()]);
        }

        return $persona;
    }

    /**
     * Deja los vínculos de una panadería iguales a sus usuarios actuales.
     *
     * Se usa después de restaurar un respaldo (los usuarios pueden cambiar) y
     * como reparación: quita vínculos de usuarios que ya no existen y personas
     * que se quedaron sin ninguna panadería.
     *
     * @return int cantidad de usuarios vinculados
     */
    public static function sincronizar(Tenant $tenant): int
    {
        $ids = [];

        Contexto::ejecutar($tenant, function () use ($tenant, &$ids): void {
            foreach (User::query()->orderBy('id')->get() as $usuario) {
                self::vincular($tenant, $usuario);
                $ids[] = (int) $usuario->id;
            }
        });

        $central = self::central();

        $sobrantes = $central->table('persona_tenant')->where('tenant_id', $tenant->id);

        if ($ids === []) {
            $sobrantes->delete();
        } else {
            $sobrantes->whereNotIn('user_id', $ids)->delete();
        }

        $central->table('personas')
            ->whereNotExists(function ($consulta): void {
                $consulta->selectRaw('1')
                    ->from('persona_tenant')
                    ->whereColumn('persona_tenant.persona_id', 'personas.id');
            })
            ->delete();

        return count($ids);
    }

    /** Quita el vínculo; si la persona se quedó sin panaderías, se borra. */
    public static function desvincular(Tenant $tenant, User $user): void
    {
        $central = self::central();

        $persona = $central->table('persona_tenant')
            ->where('tenant_id', $tenant->id)
            ->where('user_id', $user->id)
            ->value('persona_id');

        if ($persona === null) {
            return;
        }

        $central->table('persona_tenant')
            ->where('tenant_id', $tenant->id)
            ->where('user_id', $user->id)
            ->delete();

        $quedan = $central->table('persona_tenant')->where('persona_id', $persona)->exists();

        if (! $quedan) {
            $central->table('personas')->where('id', $persona)->delete();
        }
    }

    /**
     * Panaderías donde esta persona tiene usuario, con su rol y el estado de su
     * licencia (para poder deshabilitarla en el selector).
     *
     * @return list<array{tenant_id: string, nombre: string, user_id: int, rol: string|null, estado: string, entra: bool}>
     */
    public static function panaderias(Persona $persona): array
    {
        $vinculos = self::central()->table('persona_tenant')
            ->where('persona_id', $persona->id)
            ->get();

        if ($vinculos->isEmpty()) {
            return [];
        }

        $tenants = Tenant::query()
            ->whereIn('id', $vinculos->pluck('tenant_id'))
            ->get()
            ->keyBy('id');

        $panaderias = [];

        foreach ($vinculos as $vinculo) {
            $tenant = $tenants->get($vinculo->tenant_id);

            if ($tenant === null) {
                continue;
            }

            $rol = null;

            try {
                $rol = Contexto::ejecutar($tenant,
                    fn () => User::query()->find($vinculo->user_id)?->getRoleNames()->first(),
                );
            } catch (\Throwable) {
                // Una panadería a medio crear no puede romper el login.
            }

            if ($rol === null) {
                // El usuario ya no existe en esa panadería: vínculo huérfano.
                continue;
            }

            $estado = Licencia::estado($tenant->id);

            $panaderias[] = [
                'tenant_id' => $tenant->id,
                'nombre' => (string) ($tenant->negocio ?: $tenant->id),
                'user_id' => (int) $vinculo->user_id,
                'rol' => $rol,
                'estado' => $estado,
                'entra' => in_array($estado, ['sin_licencia', 'pending', 'active', 'expired'], true),
            ];
        }

        usort($panaderias, fn (array $a, array $b) => strcasecmp($a['nombre'], $b['nombre']));

        return $panaderias;
    }

    /** Conexión central, aunque la llamada venga desde una panadería. */
    private static function central(): \Illuminate\Database\ConnectionInterface
    {
        return DB::connection(config('tenancy.database.central_connection'));
    }
}
