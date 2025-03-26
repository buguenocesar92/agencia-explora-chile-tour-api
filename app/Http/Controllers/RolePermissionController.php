<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateRolePermissionsRequest;
use App\Services\RolePermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class RolePermissionController extends Controller
{
    private RolePermissionService $rolePermissionService;

    public function __construct(RolePermissionService $rolePermissionService)
    {
        $this->rolePermissionService = $rolePermissionService;
    }

    /**
     * Obtener todos los roles con sus permisos asociados.
     */
    public function index(): JsonResponse
    {
        $rolesWithPermissions = $this->rolePermissionService->getRolesWithPermissions();
        return response()->json(['roles' => $rolesWithPermissions]);
    }

    /**
     * Mostrar un rol específico con sus permisos.
     */
    public function show(int $roleId): JsonResponse
    {
        $role = $this->rolePermissionService->getRoleWithPermissions($roleId);
        return response()->json($role);
    }

    /**
     * Actualizar los permisos activos de un rol específico.
     */
    public function updateRolePermissions(UpdateRolePermissionsRequest $request, int $roleId): JsonResponse
    {
        $validatedData = $request->validated();

        $updatedRole = $this->rolePermissionService->updateRolePermissions($roleId, $validatedData['permissions']);

        return response()->json([
            'message' => 'Role permissions updated successfully.',
            'role' => $updatedRole,
        ]);
    }

    public function sync(Request $request)
    {
        Artisan::call('generate:route-permissions');
        $output = Artisan::output();

        return response()->json([
            'message' => 'Sincronización completada.',
            'output'  => $output,
        ]);
    }

      /**
     * Eliminar un rol y removerlo de los usuarios asociados (sin borrar los usuarios).
     *
     * Advertencia: Esta acción solo remueve el rol de los usuarios y elimina el rol,
     * sin borrar los registros de los usuarios.
     */
    public function destroy(int $roleId): JsonResponse
    {
        $this->rolePermissionService->deleteRole($roleId);
        return response()->json([
            'message' => 'Rol eliminado y removido de usuarios correctamente.',
        ]);
    }
}
