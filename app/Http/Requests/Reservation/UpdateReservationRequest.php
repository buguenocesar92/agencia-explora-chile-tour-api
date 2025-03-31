<?php

namespace App\Http\Requests\Reservation;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReservationRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            // Reglas para la reserva
            'status'         => 'required|in:not paid,pass,paid',
            'description'    => 'nullable|string',
            'date'           => 'nullable|date',

            // Reglas para el cliente
            'client.name'         => 'required|string',
            'client.email'        => 'required|email',
            'client.rut'          => 'required|string',
            'client.date_of_birth'=> 'required|date',
            'client.nationality'  => 'required|string',
            'client.phone'        => 'required|string',

            // Reglas para el viaje
            'trip.destination'     => 'required|string',
            'trip.departure_date'  => 'required|date',
            'trip.return_date'     => 'required|date',

            'payment.receipt'           => 'nullable|file|mimes:jpg,jpeg,png,pdf',
        ];
    }

    public function messages()
    {
        return [
            'status.required'              => 'El campo status es obligatorio.',
            'status.in'                    => 'El campo status debe ser "not paid", "pass" o "paid".',
            // Mensajes para los demás campos según lo necesites...
        ];
    }
}
