<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const PERMISSIONS = [
        'editar-cortes-cerrados',
        'eliminar-cortes-cerrados',
    ];

    /**
     * Agrega a instalaciones existentes los permisos que antes solo se
     * creaban al ejecutar RoleSeeder durante una instalación nueva.
     */
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = collect(self::PERMISSIONS)->map(
            fn (string $name) => Permission::query()->firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ])
        );

        $administrator = Role::query()
            ->where('name', 'Administrador')
            ->where('guard_name', 'web')
            ->first();

        if ($administrator) {
            $administrator->givePermissionTo($permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $administrator = Role::query()
            ->where('name', 'Administrador')
            ->where('guard_name', 'web')
            ->first();

        foreach (self::PERMISSIONS as $name) {
            $permission = Permission::query()
                ->where('name', $name)
                ->where('guard_name', 'web')
                ->first();

            if (! $permission) {
                continue;
            }

            $administrator?->revokePermissionTo($permission);
            $permission->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
