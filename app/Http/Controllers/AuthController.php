<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Inicia sesión y genera un token JWT.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();
        $token = $this->authService->login($credentials);
        if (!$token) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        return $this->authService->respondWithToken($token);
    }

    /**
     * Registra un nuevo usuario.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        // 1. Crear el usuario
        $user = $this->authService->register($request->validated());

        // 2. Auto-login con las credenciales
        $credentials = $request->only('email', 'password');
        $token = $this->authService->login($credentials);

        // 3. Devolver respuesta estándar con tokens
        return $this->authService->respondWithToken($token);
    }

    /**
     * Devuelve el usuario autenticado junto con sus roles y permisos.
     */
    public function me(): JsonResponse
    {
        $user = auth()->user(); // Carga relaciones adicionales si es necesario
        $roles = $user->getRoleNames();
        $permissions = $user->getAllPermissions()->pluck('name');

        return response()->json([
            'id'          => $user->id,
            'name'        => $user->name,
            'email'       => $user->email,
            'roles'       => $roles,
            'permissions' => $permissions,
        ]);
    }

    /**
     * Cierra la sesión del usuario.
     */
    public function logout(): JsonResponse
    {
        auth()->logout();
        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Refresca el token JWT.
     */
    public function refresh(): JsonResponse
    {
        $token = auth()->getToken();
        $claims = auth()->getPayload($token)->toArray();

        if (!isset($claims['refresh']) || !$claims['refresh']) {
            return response()->json(['error' => 'Invalid refresh token'], 401);
        }

        return $this->authService->respondWithToken(auth()->refresh());
    }
}
