<?php

namespace App\Services;

use App\Repositories\RoleRepository;

class RoleService
{
    private RoleRepository $roleRepository;

    public function __construct(RoleRepository $roleRepository)
    {
        $this->roleRepository = $roleRepository;
    }

    public function getAllRoles()
    {
        return $this->roleRepository->getAll();
    }

    public function findRole(int $roleId)
    {
        return $this->roleRepository->find($roleId);
    }

    public function createRole(array $data)
    {
        return $this->roleRepository->create($data);
    }

    public function updateRole(int $roleId, array $data)
    {
        return $this->roleRepository->update($roleId, $data);
    }

    public function deleteRole(int $roleId): void
    {
        $this->roleRepository->delete($roleId);
    }

    public function assignRoleToUser(int $userId, int $roleId): void
    {
        $this->roleRepository->assignToUser($userId, $roleId);
    }

    /**
     * Actualizar los usuarios asignados a un rol.
     *
     * @param int $roleId
     * @param array $userIds
     * @return void
     */
    public function updateUsers(int $roleId, array $userIds): void
    {
        $this->roleRepository->updateUsers($roleId, $userIds);
    }
}
