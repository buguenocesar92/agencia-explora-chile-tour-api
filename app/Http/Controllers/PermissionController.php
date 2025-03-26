<?php

namespace App\Http\Controllers;

use App\Http\Requests\Permission\StorePermissionRequest;
use App\Http\Requests\Permission\UpdatePermissionRequest;
use App\Http\Requests\Permission\AssignPermissionToUserRequest;
use App\Services\PermissionService;
use Illuminate\Http\JsonResponse;

class PermissionController extends Controller
{
    private PermissionService $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    /**
     * Listar todos los permisos.
     */
    public function index(): JsonResponse
    {
        $permissions = $this->permissionService->getAllPermissions();
        return response()->json($permissions);
    }

    /**
     * Mostrar un permiso específico.
     */
    public function show(int $permissionId): JsonResponse
    {
        $permission = $this->permissionService->findPermission($permissionId);
        return response()->json($permission);
    }

    /**
     * Crear un nuevo permiso.
     */
    public function store(StorePermissionRequest $request): JsonResponse
    {
        $permission = $this->permissionService->createPermission($request->validated());
        return response()->json($permission, 201);
    }

    /**
     * Actualizar un permiso existente.
     */
    public function update(UpdatePermissionRequest $request, int $permissionId): JsonResponse
    {
        $permission = $this->permissionService->updatePermission($permissionId, $request->validated());
        return response()->json($permission);
    }

    /**
     * Eliminar un permiso.
     */
    public function destroy(int $permissionId): JsonResponse
    {
        $this->permissionService->deletePermission($permissionId);
        return response()->json(['message' => 'Permission deleted successfully']);
    }

    public function assignToUser(AssignPermissionToUserRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $this->permissionService->assignPermissionToUser($validated['user_id'], $validated['permission_id']);

        return response()->json(['message' => 'Permission assigned to user successfully']);
    }

}
