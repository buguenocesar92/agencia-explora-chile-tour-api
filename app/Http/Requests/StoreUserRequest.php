<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado a realizar esta solicitud.
     */
    public function authorize()
    {
        // Aquí puedes implementar la lógica de autorización. Por ejemplo:
        // return auth()->check();
        return true;
    }

    /**
     * Obtén las reglas de validación que se aplican a la solicitud.
     */
    public function rules()
    {
        return [
            'name'        => 'required|string|max:255',
            'email'       => 'required|email|unique:users,email',
            'password'    => 'required|string|min:6',
            'location_id' => 'required|exists:locations,id',
        ];
    }
}
