<?php

namespace App\Http\Requests\Permission;

use Illuminate\Foundation\Http\FormRequest;

class AssignPermissionToUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Cambiar según las políticas de autorización
    }

    public function rules(): array
    {
        return [
            'user_id' => 'required|exists:users,id',
            'permission_id' => 'required|exists:permissions,id',
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'The user ID is required.',
            'user_id.exists' => 'The user ID must exist in the database.',
            'permission_id.required' => 'The permission ID is required.',
            'permission_id.exists' => 'The permission ID must exist in the database.',
        ];
    }
}
