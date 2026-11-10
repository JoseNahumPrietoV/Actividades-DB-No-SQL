<?php

namespace Database\Seeders;

use App\Enums\RoleEnum;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permisos = [
            // Permisos de Inventario
            'inventario.ver',
            'inventario.crear',
            'inventario.editar',
            'inventario.eliminar',

            // Permisos de Solicitudes
            'solicitudes.ver',
            'solicitudes.crear',
            'solicitudes.autorizar',
            'solicitudes.completar',

            // Permisos de Mantenimiento
            'mantenimiento.ver',
            'mantenimiento.crear',
            'mantenimiento.editar',
            'mantenimiento.completar',

            // Permisos de Catálogos
            'catalogos.ver',
            'catalogos.crear',
            'catalogos.editar',
            'catalogos.eliminar',

            // Permisos de Bitácora
            'bitacora.ver',

            // Permisos de Reportes
            'reportes.inventario',
            'reportes.solicitudes',
            'reportes.mantenimiento',

            // Permisos de Aprobación
            'aprobaciones.ver',
            'aprobaciones.aprobar',
            'aprobaciones.rechazar',

            // Permisos de Usuarios
            'usuarios.ver',
            'usuarios.crear',
            'usuarios.editar',
            'usuarios.eliminar',

            // Permisos de Acceso a Paneles
            RoleEnum::PERM_ACCESS_ADMIN,
            RoleEnum::PERM_ACCESS_SERVICIO,
        ];

        foreach ($permisos as $permiso) {
            Permission::firstOrCreate(['name' => $permiso]);
        }

        // Rol Administrador
        $admin = Role::firstOrCreate(['name' => 'Administrador']);
        $adminPermissions = array_filter($permisos, fn ($p) => $p !== RoleEnum::PERM_ACCESS_SERVICIO);
        $admin->syncPermissions($adminPermissions);

        // Rol Servicio Social
        $servicioSocial = Role::firstOrCreate(['name' => 'Servicio Social']);
        $servicioSocial->syncPermissions([
            'inventario.ver',
            'inventario.crear',
            'inventario.editar',
            'catalogos.ver',
            'catalogos.crear',
            'catalogos.editar',
            'mantenimiento.ver',
            'mantenimiento.crear',
            'mantenimiento.editar',
            'solicitudes.ver',
            'reportes.inventario',
            RoleEnum::PERM_ACCESS_SERVICIO,
        ]);
    }
}
