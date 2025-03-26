<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado a realizar esta solicitud.
     */
    public function authorize()
    {
        // Aquí puedes implementar la lógica de autorización, por ahora devolvemos true.
        return true;
    }

    /**
     * Obtén las reglas de validación que se aplican a la solicitud.
     */
    public function rules()
    {
        // Se obtiene el ID del usuario desde la ruta para excluirlo de la validación única del email.
        $userId = $this->route('id');

        return [
            'name'        => 'sometimes|required|string|max:255',
            'email'       => 'sometimes|required|email|unique:users,email,' . $userId,
            // La contraseña es opcional en el update, se valida solo si se envía.
            'password'    => 'nullable|string|min:6',
            'location_id' => 'sometimes|required|exists:locations,id',
        ];
    }
}
