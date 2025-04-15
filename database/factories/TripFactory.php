<?php

namespace Database\Factories;

use App\Models\Trip;
use App\Models\TourTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

class TripFactory extends Factory
{
    protected $model = Trip::class;

    public function definition()
    {
        // Crear un TourTemplate si no existe ninguno
        if (TourTemplate::count() === 0) {
            $tourTemplate = TourTemplate::factory()->create();
            $tourTemplateId = $tourTemplate->id;
        } else {
            $tourTemplateId = TourTemplate::inRandomOrder()->first()->id;
        }

        $departureDate = $this->faker->dateTimeBetween('+1 month', '+6 months');
        $returnDate = clone $departureDate;
        $returnDate->modify('+'. rand(5, 15) .' days');

        return [
            'tour_template_id' => $tourTemplateId,
            'departure_date' => $departureDate->format('Y-m-d'),
            'return_date' => $returnDate->format('Y-m-d'),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
