<?php

namespace App\Http\Requests\Reservation;

use Illuminate\Foundation\Http\FormRequest;

class StoreReservationRequest extends FormRequest
{
    public function authorize()
    {
        // Ajusta la autorización según tus necesidades.
        return true;
    }

    public function rules()
    {
        return [
            'client' => 'required|array',
            'client.name' => 'required|string',
            'client.rut' => 'required|string',
            'client.date_of_birth' => 'required|date',
            'client.nationality' => 'required|string',
            'client.email' => 'required|email',
            'client.phone' => 'required|string',

            'trip' => 'required|array',
            'trip.destination' => 'required|string',
            'trip.departure_date' => 'required|date',
            'trip.return_date' => 'required|date',
            'trip.service_type' => 'required|string',

            'payment' => 'required|array',
            'payment.amount' => 'required|numeric',
            'payment.transaction_id' => 'required|string',
            // El comprobante es opcional, pero si se envía, debe ser un archivo de imagen
            'payment.receipt' => 'nullable|file|mimes:jpeg,jpg,png,bmp|max:2048',
        ];
    }
}
