<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const NUEVOS = [
        'ver-produccion',
        'crear-produccion',
        'eliminar-produccion',
    ];

    private const OBSOLETOS = [
        'ver-recetas',
        'crear-recetas',
        'editar-recetas',
        'eliminar-recetas',
    ];

    /**
     * Cambia los permisos de recetas por los de producción.
     *
     * No hay "editar-produccion": una corrida ya movió stock, así que se anula
     * y se vuelve a cargar en lugar de editarse. Eso mantiene el historial
     * consistente con lo que realmente pasó en el depósito.
     */
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permisos = collect(self::NUEVOS)->map(
            fn (string $nombre) => Permission::query()->firstOrCreate([
                'name'       => $nombre,
                'guard_name' => 'web',
            ])
        );

        $admin = Role::query()->where('name', 'Administrador')->where('guard_name', 'web')->first();
        $admin?->givePermissionTo($permisos);

        Permission::query()->whereIn('name', self::OBSOLETOS)->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permisos = collect(self::OBSOLETOS)->map(
            fn (string $nombre) => Permission::query()->firstOrCreate([
                'name'       => $nombre,
                'guard_name' => 'web',
            ])
        );

        $admin = Role::query()->where('name', 'Administrador')->where('guard_name', 'web')->first();
        $admin?->givePermissionTo($permisos);

        Permission::query()->whereIn('name', self::NUEVOS)->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
