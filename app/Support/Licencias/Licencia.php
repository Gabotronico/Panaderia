<?php

namespace App\Support\Licencias;

use App\Models\Producto;
use App\Models\Tenant;
use App\Models\TenantLicencia;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Estado y aplicación de la licencia de cada panadería.
 *
 * El panel central de INC Group manda el estado (plan, cupos, vigencia) y acá
 * se guarda una copia. Todo el sistema consulta esta clase, así que el criterio
 * queda en un solo lugar: sin licencia o pendiente no se limita nada (una
 * panadería recién creada tiene que poder arrancar), vencida es solo lectura y
 * suspendida o revocada no entra nadie.
 */
final class Licencia
{
    /** @var array<string, TenantLicencia|null> */
    private static array $memoria = [];

    public static function de(string $tenantId): ?TenantLicencia
    {
        if (! array_key_exists($tenantId, self::$memoria)) {
            self::$memoria[$tenantId] = TenantLicencia::query()
                ->where('tenant_id', $tenantId)
                ->first();
        }

        return self::$memoria[$tenantId];
    }

    /**
     * 'sin_licencia' | 'pending' | 'active' | 'expired' | 'suspended' | 'revoked'
     */
    public static function estado(string $tenantId): string
    {
        $registro = self::de($tenantId);

        if ($registro === null) {
            return 'sin_licencia';
        }

        if ($registro->status === 'active'
            && $registro->valid_until !== null
            && $registro->valid_until->isPast()) {
            return 'expired';
        }

        return $registro->status;
    }

    public static function vigente(string $tenantId): bool
    {
        return in_array(self::estado($tenantId), ['sin_licencia', 'pending', 'active'], true);
    }

    /**
     * Motivo por el que no se puede crear otro usuario con ese rol, o null si
     * todavía hay cupo. Los roles sin cupo configurado nunca se limitan.
     */
    public static function bloqueoPorRol(string $rol): ?string
    {
        $columna = config("licencias.roles.{$rol}");

        if (! is_string($columna) || ! tenancy()->initialized) {
            return null;
        }

        $tenantId = (string) tenant('id');
        $estado = self::estado($tenantId);

        // Todavía sin licencia (recién creada) o con la licencia en camino: no
        // se le corta el arranque a la panadería.
        if ($estado === 'sin_licencia' || $estado === 'pending') {
            return null;
        }

        if (! in_array($estado, ['active'], true)) {
            return 'La licencia de la panadería no está vigente. '
                .'Regularizala desde el panel de INC Group para volver a administrar usuarios.';
        }

        $registro = self::de($tenantId);
        $limite = $registro?->{$columna};

        if ($limite === null) {
            return null;
        }

        $usados = User::query()->role($rol)->count();

        if ($usados < $limite) {
            return null;
        }

        $etiqueta = $rol === 'Cajero' ? 'cajeros' : 'administradores';

        return "El plan \"{$registro->plan}\" permite {$limite} {$etiqueta} y ya hay {$usados}. "
            ."Pedí un cambio de plan para agregar más.";
    }

    /**
     * Aplica el estado que manda el central. Solo avanza: un mensaje con una
     * versión igual o menor no toca nada (puede llegar fuera de orden).
     *
     * @param  array<string, mixed>  $datos
     * @return array{aplicada: bool, registro: TenantLicencia}
     */
    public static function sincronizar(array $datos): array
    {
        $tenantId = (string) $datos['external_tenant_ref'];

        if (! Tenant::query()->whereKey($tenantId)->exists()) {
            throw (new ModelNotFoundException)->setModel(Tenant::class, [$tenantId]);
        }

        $registro = TenantLicencia::query()->firstOrNew(['tenant_id' => $tenantId]);
        $entrante = (int) ($datos['version'] ?? 0);

        if ($registro->exists && $entrante <= $registro->version) {
            return ['aplicada' => false, 'registro' => $registro];
        }

        $registro->fill([
            'status' => (string) $datos['status'],
            'plan' => (string) $datos['plan'],
            'max_admins' => $datos['max_admins'] ?? null,
            'max_cajeros' => $datos['max_cajeros'] ?? null,
            'valid_until' => $datos['valid_until'] ?? null,
            'version' => $entrante,
            'synced_at' => now(),
            'payload' => $datos,
        ])->save();

        self::olvidar();

        return ['aplicada' => true, 'registro' => $registro];
    }

    /**
     * Todas las panaderías con su consumo real, para que el panel central
     * muestre los cupos usados contra los contratados.
     *
     * @return list<array<string, mixed>>
     */
    public static function resumen(): array
    {
        $licencias = TenantLicencia::query()->get()->keyBy('tenant_id');

        return Tenant::query()
            ->orderBy('id')
            ->get()
            ->map(function (Tenant $tenant) use ($licencias): array {
                $uso = self::usoDe($tenant);
                $registro = $licencias->get($tenant->id);

                return [
                    'tenant' => $tenant->id,
                    'nombre' => $tenant->negocio ?: $tenant->id,
                    'admins' => $uso['admins'],
                    'cajeros' => $uso['cajeros'],
                    'productos' => $uso['productos'],
                    'ventas' => $uso['ventas'],
                    'licencia' => $registro === null ? null : [
                        'status' => self::estado($tenant->id),
                        'plan' => $registro->plan,
                        'valid_until' => $registro->valid_until?->toIso8601String(),
                        'version' => $registro->version,
                    ],
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array{admins: int, cajeros: int, productos: int, ventas: int}
     */
    private static function usoDe(Tenant $tenant): array
    {
        try {
            return $tenant->run(fn (): array => [
                'admins' => User::query()->role('Administrador')->count(),
                'cajeros' => User::query()->role('Cajero')->count(),
                'productos' => Producto::query()->count(),
                'ventas' => Venta::query()->count(),
            ]);
        } catch (\Throwable) {
            // Una panadería a medio crear (o con la base caída) no puede
            // tumbar el listado del panel.
            return ['admins' => 0, 'cajeros' => 0, 'productos' => 0, 'ventas' => 0];
        }
    }

    public static function olvidar(?string $tenantId = null): void
    {
        if ($tenantId === null) {
            self::$memoria = [];

            return;
        }

        unset(self::$memoria[$tenantId]);
    }
}
