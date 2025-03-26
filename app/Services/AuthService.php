<?php

namespace App\Services;

use App\Repositories\UserRepository;
use App\Repositories\ProfessionalRepository;
use Illuminate\Support\Facades\Auth;

class AuthService
{
    protected UserRepository $userRepository;
    protected ProfessionalRepository $professionalRepository;

    public function __construct(
        UserRepository $userRepository,
        ProfessionalRepository $professionalRepository
    ) {
        $this->userRepository = $userRepository;
        $this->professionalRepository = $professionalRepository;
    }

    /**
     * Registra un nuevo usuario y devuelve sus datos.
     *
     * @param array $data
     * @return array
     */
    public function register(array $data): array
    {
        // 1. Crear el usuario en la tabla users
        $user = $this->userRepository->create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => bcrypt($data['password']),
        ]);


        return $user->toArray();
    }


    /**
     * Intenta autenticar al usuario y devuelve un token JWT o null si falla.
     *
     * @param array $credentials
     * @return string|null
     */
    public function login(array $credentials): ?string
    {
        // Se utiliza explícitamente el guard 'api' para autenticación JWT.
        if (Auth::guard('api')->attempt($credentials)) {
            return Auth::guard('api')->tokenById(Auth::guard('api')->id());
        }
        return null;
    }

    /**
     * Prepara y retorna la respuesta JSON con el token.
     *
     * @param string $token
     * @return \Illuminate\Http\JsonResponse
     */
    public function respondWithToken(string $token): \Illuminate\Http\JsonResponse
    {
        $user = Auth::user(); // Asegurar que la ubicación se cargue

        return response()->json([
            'access_token' => $token,
            'refresh_token' => auth()->claims(['refresh' => true])->setTTL(config('jwt.refresh_ttl'))->tokenById(auth()->id()),
            'token_type' => 'bearer',
            'expires_in' => Auth::factory()->getTTL() * 60,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }
}
