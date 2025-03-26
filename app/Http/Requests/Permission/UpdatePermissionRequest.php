<?php

namespace App\Http\Requests\Permission;

use App\Http\Requests\ApiFormRequest;

class UpdatePermissionRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:permissions,name,' . $this->route('permissionId'),
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'The permission name is required.',
            'name.unique' => 'The permission name must be unique.',
        ];
    }
}
