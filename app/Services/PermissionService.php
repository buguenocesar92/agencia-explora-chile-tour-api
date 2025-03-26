<?php

namespace App\Services;

use App\Repositories\PermissionRepository;

class PermissionService
{
    private PermissionRepository $permissionRepository;

    public function __construct(PermissionRepository $permissionRepository)
    {
        $this->permissionRepository = $permissionRepository;
    }

    public function getAllPermissions()
    {
        return $this->permissionRepository->getAll();
    }

    public function findPermission(int $permissionId)
    {
        return $this->permissionRepository->find($permissionId);
    }

    public function createPermission(array $data)
    {
        return $this->permissionRepository->create($data);
    }

    public function updatePermission(int $permissionId, array $data)
    {
        return $this->permissionRepository->update($permissionId, $data);
    }

    public function deletePermission(int $permissionId): void
    {
        $this->permissionRepository->delete($permissionId);
    }

    public function assignPermissionToUser(int $userId, int $permissionId): void
    {
        $this->permissionRepository->assignPermissionToUser($userId, $permissionId);
    }
}
