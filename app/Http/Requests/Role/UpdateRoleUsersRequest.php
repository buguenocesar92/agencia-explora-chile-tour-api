<?php

namespace App\Http\Requests\Role;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRoleUsersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Ajusta la lógica de autorización si es necesario
    }

    public function rules(): array
    {
        return [
            'users' => ['nullable', 'array'], // Permite que el campo sea nulo o un array vacío
            'users.*' => ['integer', 'exists:users,id'], // Valida que los IDs existan en la tabla de usuarios
        ];
    }


}
