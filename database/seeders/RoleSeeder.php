<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear permisos
        $permisos = [
            // Dashboard
            'ver-dashboard',
            
            // Usuarios
            'ver-usuarios',
            'crear-usuarios',
            'editar-usuarios',
            'eliminar-usuarios',
            
            // Categorías
            'ver-categorias',
            'crear-categorias',
            'editar-categorias',
            'eliminar-categorias',
            
            // Productos
            'ver-productos',
            'crear-productos',
            'editar-productos',
            'eliminar-productos',
            
            // Insumos
            'ver-insumos',
            'crear-insumos',
            'editar-insumos',
            'eliminar-insumos',
            
            // Ventas
            'ver-ventas',
            'crear-ventas',
            'editar-ventas',
            'eliminar-ventas',
            
            // Cortes de Caja
            'ver-cortes',
            'crear-cortes',
            'editar-cortes',
            'eliminar-cortes',
            
            // Recetas
            'ver-recetas',
            'crear-recetas',
            'editar-recetas',
            'eliminar-recetas',

            // Almacenes
            'ver-almacenes',
            'crear-almacenes',
            'editar-almacenes',
            'eliminar-almacenes',

            // Reportes
            'ver-reportes',
            'generar-reportes',
        ];

        foreach ($permisos as $permiso) {
            Permission::firstOrCreate(['name' => $permiso]);
        }

        // Crear rol Administrador con todos los permisos
        $adminRole = Role::firstOrCreate(['name' => 'Administrador']);
        $adminRole->syncPermissions(Permission::all());

        // Crear rol Cajero con permisos limitados
        $cajeroRole = Role::firstOrCreate(['name' => 'Cajero']);
        $cajeroRole->syncPermissions([
            'ver-dashboard',
            'ver-categorias',
            'ver-productos',
            'ver-ventas',
            'crear-ventas',
            'ver-cortes',
            'crear-cortes',
            'editar-cortes',
            'ver-almacenes',
        ]);

        $this->command->info('Roles y permisos creados correctamente');
    }
}