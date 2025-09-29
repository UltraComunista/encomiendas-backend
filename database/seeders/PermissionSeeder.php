<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define aquí tus permisos por "módulo"
        $perms = [
            // Encomiendas
            'encomiendas.view',
            'encomiendas.create',
            'encomiendas.update',
            'encomiendas.delete',

            // Usuarios
            'users.view',
            'users.manage', // crear/editar/bloquear usuarios

            // Reportes
            'reports.view',
        ];

        foreach ($perms as $p) {
            Permission::findOrCreate($p, 'web');
        }

        // Roles
        $admin    = Role::findOrCreate('admin', 'web');
        $operador = Role::findOrCreate('operador', 'web');
        $lector   = Role::findOrCreate('lector', 'web');

        // Permisos por rol
        $admin->syncPermissions(Permission::all());

        $operador->syncPermissions([
            'encomiendas.view',
            'encomiendas.create',
            'encomiendas.update',
            'reports.view',
        ]);

        $lector->syncPermissions([
            'encomiendas.view',
            'reports.view',
        ]);
    }
}
