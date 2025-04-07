<?php

namespace App\Http\Requests\Client;

use App\Http\Requests\ApiFormRequest;

class StoreClientRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'rut' => 'required|string|max:12|unique:clients',
            'date_of_birth' => 'required|date',
            'nationality' => 'required|string|max:100',
            'email' => 'required|email|max:255|unique:clients',
            'phone' => 'required|string|max:20',
        ];
    }
}
