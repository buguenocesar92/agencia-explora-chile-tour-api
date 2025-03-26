<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\RoutePermission;

class CheckRoutePermission
{
    public function handle(Request $request, Closure $next)
    {
        // Obtiene el nombre de la ruta definida o, en su defecto, la URI
        $currentRoute = $request->route()->getName() ?: $request->route()->uri();

        // Busca la configuración del permiso para la ruta en la base de datos, cargando la relación 'permission'
        $routePermission = RoutePermission::with('permission')
            ->where('route_name', $currentRoute)
            ->first();

        // Si se ha definido un permiso para la ruta y está relacionado correctamente,
        // verifica que el usuario tenga dicho permiso.
        if ($routePermission && $routePermission->permission) {
            $permissionName = $routePermission->permission->name;
            if (!$request->user() || !$request->user()->can($permissionName)) {
                abort(403, 'No tienes permiso para acceder a esta ruta.');
            }
        }

        // Continúa con la petición si el permiso no está definido o el usuario lo tiene.
        return $next($request);
    }
}
