<?php

namespace Database\Factories;

use App\Models\Reservation;
use App\Models\Client;
use App\Models\Trip;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReservationFactory extends Factory
{
    protected $model = Reservation::class;

    public function definition()
    {
        // Crear o recuperar un cliente
        if (Client::count() === 0) {
            $clientId = Client::factory()->create()->id;
        } else {
            $clientId = Client::inRandomOrder()->first()->id;
        }

        // Crear o recuperar un viaje
        if (Trip::count() === 0) {
            $tripId = Trip::factory()->create()->id;
        } else {
            $tripId = Trip::inRandomOrder()->first()->id;
        }

        // Crear un pago
        $payment = Payment::create([
            'receipt' => null
        ]);

        return [
            'client_id' => $clientId,
            'trip_id' => $tripId,
            'payment_id' => $payment->id,
            'date' => $this->faker->dateTimeBetween('-1 month', '+2 months')->format('Y-m-d'),
            'descripcion' => $this->faker->paragraph,
            'status' => $this->faker->randomElement(['not paid', 'pass', 'paid']),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
