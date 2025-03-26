<?php

namespace App\Http\Requests\Task;

use App\Http\Requests\ApiFormRequest;

class StoreTaskRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        // Si no necesitas restricciones de autorización, retorna true
        return true;
    }

    public function rules(): array
    {
        return [
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'completed'   => 'nullable|boolean',
        ];
    }
}
