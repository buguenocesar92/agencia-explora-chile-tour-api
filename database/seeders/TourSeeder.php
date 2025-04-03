<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TourTemplate;
use App\Models\Trip;

class TourSeeder extends Seeder
{
    public function run()
    {
        // --- Tour 1 ---
        $tour1 = TourTemplate::create([
            'name' => 'Tour Argentina, Paraguay, Brasil',
            'destination' => 'Argentina, Paraguay, Brasil',
            'description' => 'Recorrido que visita Argentina, Paraguay y Brasil.',
        ]);

        // Programaciones para el Tour 1
        Trip::create([
            'tour_template_id' => $tour1->id,
            'destination' => $tour1->destination, // Se asigna el destino
            'departure_date' => '2025-04-01',
            'return_date'    => '2025-04-10',
            // Otros campos opcionales, por ejemplo:
            // 'price' => 1500,
            // 'capacity' => 30,
        ]);

        Trip::create([
            'tour_template_id' => $tour1->id,
            'destination' => $tour1->destination, // Se asigna el destino
            'departure_date' => '2025-05-01',
            'return_date'    => '2025-05-10',
        ]);

        // --- Tour 2 ---
        $tour2 = TourTemplate::create([
            'name' => 'Tour Europa',
            'destination' => 'Europa',
            'description' => 'Recorrido por las principales ciudades de Europa.',
        ]);

        // Programaciones para el Tour 2
        Trip::create([
            'tour_template_id' => $tour2->id,
            'destination' => $tour2->destination, // Se asigna el destino
            'departure_date' => '2025-07-01',
            'return_date'    => '2025-07-15',
        ]);

        Trip::create([
            'tour_template_id' => $tour2->id,
            'destination' => $tour2->destination, // Se asigna el destino
            'departure_date' => '2025-08-01',
            'return_date'    => '2025-08-15',
        ]);
    }
}
