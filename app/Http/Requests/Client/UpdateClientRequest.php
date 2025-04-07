<?php

namespace App\Http\Requests\Client;

use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rule;

class UpdateClientRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|required|string|max:255',
            'rut' => ['sometimes', 'required', 'string', 'max:12', Rule::unique('clients')->ignore($this->route('client'))],
            'date_of_birth' => 'sometimes|required|date',
            'nationality' => 'sometimes|required|string|max:100',
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('clients')->ignore($this->route('client'))],
            'phone' => 'sometimes|required|string|max:20',
        ];
    }
}
