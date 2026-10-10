<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * El cajero deja de ver las ventas ya realizadas.
     *
     * Registra ventas pero no consulta el historial: el control de lo vendido
     * queda del lado de la administración. El cajero sigue viendo el total de
     * su turno en el cierre de caja, que es lo que necesita para arquear.
     *
     * Se le conserva `crear-ventas`: entra directo al formulario de venta.
     */
    public function up(): void
    {
        $cajero = Role::where('name', 'Cajero')->first();
        $ver    = Permission::where('name', 'ver-ventas')->first();

        if ($cajero && $ver) {
            $cajero->revokePermissionTo($ver);
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        $cajero = Role::where('name', 'Cajero')->first();
        $ver    = Permission::where('name', 'ver-ventas')->first();

        if ($cajero && $ver) {
            $cajero->givePermissionTo($ver);
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
