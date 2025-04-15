<?php

namespace Database\Factories;

use App\Models\TourTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

class TourTemplateFactory extends Factory
{
    protected $model = TourTemplate::class;

    public function definition()
    {
        return [
            'name' => 'Tour ' . $this->faker->city,
            'destination' => $this->faker->country,
            'description' => $this->faker->paragraph,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
