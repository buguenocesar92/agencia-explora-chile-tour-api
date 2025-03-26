<?php

namespace App\Services;

use App\Repositories\UserRepository;

class UserService
{
    private UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * Obtener todos los usuarios.
     */
    public function getAllUsers()
    {
        return $this->userRepository->getAll();
    }

}
