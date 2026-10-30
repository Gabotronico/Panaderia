<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Le saca el dashboard al rol Cajero.
     *
     * El dashboard muestra cifras del negocio entero — ventas de todos,
     * compras de insumos, utilidad —, no del turno del cajero. Al entrar,
     * ahora cae directo en Ventas (ver HomeController::index).
     */
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $cajero  = Role::query()->where('name', 'Cajero')->where('guard_name', 'web')->first();
        $permiso = Permission::query()->where('name', 'ver-dashboard')->where('guard_name', 'web')->first();

        if ($cajero && $permiso) {
            $cajero->revokePermissionTo($permiso);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $cajero  = Role::query()->where('name', 'Cajero')->where('guard_name', 'web')->first();
        $permiso = Permission::query()->where('name', 'ver-dashboard')->where('guard_name', 'web')->first();

        if ($cajero && $permiso) {
            $cajero->givePermissionTo($permiso);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
