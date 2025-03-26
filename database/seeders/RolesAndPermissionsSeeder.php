<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
        // Crear permisos relacionados con productos, roles y otros módulos
/*         $permissions = [
            'taskcontroller.index',
            'taskcontroller.store',
            'taskcontroller.show',
            'taskcontroller.update',
            'taskcontroller.destroy',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        } */

        // Crear roles y asignar permisos
        $roles = [
            'admin' => Permission::all(),
        ];

        foreach ($roles as $roleName => $permissions) {
            $role = Role::firstOrCreate(['name' => $roleName]);
            if (is_array($permissions)) {
                $role->givePermissionTo($permissions);
            } else {
                $role->syncPermissions($permissions);
            }
        }
    }
}
