<?php

namespace App\Http\Controllers;

use App\Http\Requests\Role\StoreRoleRequest;
use App\Http\Requests\Role\UpdateRoleRequest;
use App\Http\Requests\Role\AssignRoleToUserRequest;
use App\Http\Requests\Role\UpdateRoleUsersRequest;
use App\Services\RoleService;
use Illuminate\Http\JsonResponse;

class RoleController extends Controller
{
    private RoleService $roleService;

    public function __construct(RoleService $roleService)
    {
        $this->roleService = $roleService;
    }

    /**
     * Listar todos los roles.
     */
    public function index(): JsonResponse
    {
        $roles = $this->roleService->getAllRoles();
        return response()->json($roles);
    }

    /**
     * Mostrar un rol específico.
     */
    public function show(int $roleId): JsonResponse
    {
        $role = $this->roleService->findRole($roleId);
        return response()->json($role);
    }

    /**
     * Crear un nuevo rol.
     */
    public function store(StoreRoleRequest $request): JsonResponse
    {
        $role = $this->roleService->createRole($request->validated());
        return response()->json($role, 201);
    }

    /**
     * Actualizar un rol existente.
     */
    public function update(UpdateRoleRequest $request, int $roleId): JsonResponse
    {
        $role = $this->roleService->updateRole($roleId, $request->validated());
        return response()->json($role);
    }

    /**
     * Eliminar un rol.
     */
    public function destroy(int $roleId): JsonResponse
    {
        $this->roleService->deleteRole($roleId);
        return response()->json(['message' => 'Role deleted successfully']);
    }

    /**
     * Asignar un rol a un usuario.
     */
    public function assignToUser(AssignRoleToUserRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $this->roleService->assignRoleToUser($validated['user_id'], $validated['role_id']);

        return response()->json(['message' => 'Role assigned to user successfully']);
    }

    /**
     * Actualizar los usuarios asignados a un rol.
     *
     * @param UpdateRoleUsersRequest $request
     * @param int $roleId
     * @return JsonResponse
     */
    public function updateUsers(UpdateRoleUsersRequest $request, int $roleId): JsonResponse
    {
        $validated = $request->validated();
        $this->roleService->updateUsers($roleId, $validated['users']); // Aquí usa 'users'
        return response()->json(['message' => 'Users successfully assigned to the role.'], 200);
    }

}

