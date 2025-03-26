<?php

namespace App\Http\Requests\Task;

use App\Http\Requests\ApiFormRequest;

class UpdateTaskRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'       => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'completed'   => 'nullable|boolean',
        ];
    }
}
