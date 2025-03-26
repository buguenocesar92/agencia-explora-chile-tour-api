<?php

namespace App\Repositories;

use Spatie\Permission\Models\Permission;
use App\Models\User;

class PermissionRepository
{

    public function getAll()
    {
        $permissionNames = config('permissions'); // Cargamos el diccionario de nombres legibles

        return Permission::all()->map(function ($permission) use ($permissionNames) {
            return [
                'id' => $permission->id,
                'name' => $permission->name,
                'readable_name' => $permissionNames[$permission->name] ?? $permission->name, // Si no está en el diccionario, usa el nombre original
            ];
        });
    }


    public function find(int $permissionId)
    {
        return Permission::findOrFail($permissionId);
    }

    public function create(array $data)
    {
        return Permission::create($data);
    }

    public function update(int $permissionId, array $data)
    {
        $permission = Permission::findOrFail($permissionId);
        $permission->update($data);
        return $permission;
    }

    public function delete(int $permissionId): void
    {
        Permission::findOrFail($permissionId)->delete();
    }

    public function assignPermissionToUser(int $userId, int $permissionId): void
    {
        $user = User::findOrFail($userId);
        $user->givePermissionTo($permissionId); // Usa la función de Spatie para asignar permisos
    }
}
