<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Models\RoutePermission;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class GenerateRoutePermissions extends Command
{
    protected $signature = 'generate:route-permissions';
    protected $description = 'Sincroniza rutas y permisos en la base de datos, eliminando registros obsoletos.';

    public function handle()
    {
        DB::beginTransaction(); // Iniciar transacción
        try {
            $routes = Route::getRoutes();
            $countCreated = 0;
            $countDeleted = 0;
            $countPermissionsDeleted = 0;

            $excludedPermissions = [
                'csrfcookiecontroller.show',
                'authcontroller.login',
                'authcontroller.register',
                'authcontroller.logout',
                'authcontroller.refresh',
                'authcontroller.me',
            ];

            $existingRoutes = RoutePermission::pluck('route_name')->toArray();
            $currentRoutes = [];
            $usedPermissions = [];

            foreach ($routes as $route) {
                $action = $route->getAction();
                $routeName = $route->getName() ?: $route->uri();
                $currentRoutes[] = $routeName;

                if (isset($action['controller'])) {
                    $controllerAction = explode('@', $action['controller']);
                    if (count($controllerAction) === 2) {
                        $controller = class_basename($controllerAction[0]);
                        $method = $controllerAction[1];
                        $permissionName = strtolower($controller . '.' . $method);
                    } else {
                        $permissionName = null;
                    }
                } else {
                    $permissionName = null;
                }

                $permissionId = null;
                if ($permissionName && !in_array($permissionName, $excludedPermissions)) {
                    $permission = Permission::firstOrCreate(['name' => $permissionName]);
                    $permissionId = $permission->id;
                    $usedPermissions[] = $permissionName;
                } else {
                    $permissionName = null;
                    $permissionId = null;
                }

                if (!RoutePermission::where('route_name', $routeName)->exists()) {
                    RoutePermission::create([
                        'route_name'      => $routeName,
                        'permission_name' => $permissionName,
                        'permission_id'   => $permissionId,
                    ]);
                    $this->info("Creada: {$routeName} con permiso: {$permissionName}");
                    $countCreated++;
                }
            }

            // **Eliminar rutas obsoletas**
            $routesToDelete = array_diff($existingRoutes, $currentRoutes);
            if (!empty($routesToDelete)) {
                $deletedPermissions = RoutePermission::whereIn('route_name', $routesToDelete)
                    ->pluck('permission_name')
                    ->toArray();

                RoutePermission::whereIn('route_name', $routesToDelete)->delete();
                $countDeleted = count($routesToDelete);
                $this->info("Se eliminaron {$countDeleted} rutas obsoletas.");

                // **Eliminar permisos que ya no se usan en rutas ni roles**
                $permissionsToDelete = array_diff($deletedPermissions, $usedPermissions);
                if (!empty($permissionsToDelete)) {
                    foreach ($permissionsToDelete as $permissionName) {
                        $this->deletePermission($permissionName);
                        $countPermissionsDeleted++;
                    }
                    $this->info("Se eliminaron {$countPermissionsDeleted} permisos obsoletos.");
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Error al sincronizar rutas y permisos: " . $e->getMessage());
        }

        $this->info("Total de rutas creadas: {$countCreated}");
        $this->info("Total de rutas eliminadas: {$countDeleted}");
        $this->info("Total de permisos eliminados: {$countPermissionsDeleted}");
    }

    private function deletePermission($permissionName)
    {
        $permission = Permission::where('name', $permissionName)->first();

        if ($permission) {
            // **Eliminar el permiso de todos los roles**
            foreach ($permission->roles as $role) {
                $role->revokePermissionTo($permission);
            }

            // **Eliminar el permiso**
            $permission->delete();
            $this->info("Permiso eliminado: {$permissionName}");
        }
    }
}
