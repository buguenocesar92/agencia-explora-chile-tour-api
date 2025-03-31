<?php

namespace App\Http\Requests\Reservation;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReservationRequest extends FormRequest
{
    public function authorize()
    {
        // Puedes implementar la lógica de autorización según tus necesidades.
        return true;
    }

    public function rules()
    {
        return [
            // Se requiere el campo "status" y debe ser uno de los valores permitidos.
            'status' => 'required|in:not paid,pass,paid',
        ];
    }

    public function messages()
    {
        return [
            'status.required' => 'El campo status es obligatorio.',
            'status.in'       => 'El campo status debe ser "not paid", "pass" o "paid".',
        ];
    }
}
