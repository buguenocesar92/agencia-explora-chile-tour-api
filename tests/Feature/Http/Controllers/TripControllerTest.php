<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\TourTemplate;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class TripControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Autenticar un usuario para las acciones protegidas
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);
        $this->withHeader('Authorization', 'Bearer ' . $token);
    }

    public function test_index_returns_all_trips()
    {
        // Crear algunos viajes de prueba
        $trips = Trip::factory()->count(3)->create();

        // Hacer la petición
        $response = $this->getJson('/api/trips');

        // Verificar la respuesta
        $response->assertOk()
            ->assertJsonCount(3, 'trips')
            ->assertJsonStructure([
                'trips' => [
                    '*' => ['id', 'tour_template_id', 'departure_date', 'return_date', 'created_at', 'updated_at']
                ]
            ]);
    }

    public function test_index_can_filter_by_tour_template_id()
    {
        // Crear tours y viajes
        $tour1 = TourTemplate::factory()->create();
        $tour2 = TourTemplate::factory()->create();

        // 2 viajes para tour1
        Trip::factory()->count(2)->create(['tour_template_id' => $tour1->id]);
        // 1 viaje para tour2
        Trip::factory()->create(['tour_template_id' => $tour2->id]);

        // Hacer la petición filtrada
        $response = $this->getJson("/api/trips?tour_template_id={$tour1->id}");

        // Verificar que solo devuelve los viajes del tour1
        $response->assertOk()
            ->assertJsonCount(2, 'trips');

        $tripIds = collect($response->json('trips'))->pluck('tour_template_id')->unique();
        $this->assertEquals([$tour1->id], $tripIds->all());
    }

    public function test_store_creates_new_trip()
    {
        // Crear un tour
        $tour = TourTemplate::factory()->create();

        // Datos para el nuevo viaje
        $tripData = [
            'tour_template_id' => $tour->id,
            'departure_date' => '2024-05-01',
            'return_date' => '2024-05-10',
        ];

        // Hacer la petición
        $response = $this->postJson('/api/trips', $tripData);

        // Verificar la respuesta
        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Trip creado correctamente',
                'trip' => [
                    'tour_template_id' => $tour->id,
                    'departure_date' => '2024-05-01',
                    'return_date' => '2024-05-10',
                ]
            ]);

        // Verificar que se creó en la base de datos
        $this->assertDatabaseHas('trips', $tripData);
    }

    public function test_store_validates_input()
    {
        // Datos inválidos: falta departure_date
        $invalidData = [
            'tour_template_id' => 1,
            'return_date' => '2024-05-10',
        ];

        // Hacer la petición
        $response = $this->postJson('/api/trips', $invalidData);

        // Verificar que devuelve un error de validación
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['departure_date']);
    }

    public function test_show_returns_trip_details()
    {
        // Crear un viaje
        $trip = Trip::factory()->create();

        // Hacer la petición
        $response = $this->getJson("/api/trips/{$trip->id}");

        // Verificar la respuesta
        $response->assertOk()
            ->assertJson([
                'trip' => [
                    'id' => $trip->id,
                    'tour_template_id' => $trip->tour_template_id,
                    'departure_date' => $trip->departure_date,
                    'return_date' => $trip->return_date,
                ]
            ]);
    }

    public function test_show_returns_404_for_non_existent_trip()
    {
        // Hacer la petición con un ID que no existe
        $response = $this->getJson('/api/trips/999');

        // Verificar que devuelve un 404
        $response->assertNotFound();
    }

    public function test_update_modifies_trip()
    {
        // Crear un viaje
        $trip = Trip::factory()->create([
            'departure_date' => '2024-05-01',
            'return_date' => '2024-05-10',
        ]);

        // Datos para actualizar
        $updateData = [
            'departure_date' => '2024-06-01',
            'return_date' => '2024-06-15',
        ];

        // Hacer la petición
        $response = $this->putJson("/api/trips/{$trip->id}", $updateData);

        // Verificar la respuesta
        $response->assertOk()
            ->assertJson([
                'message' => 'Trip actualizado correctamente',
                'trip' => [
                    'id' => $trip->id,
                    'departure_date' => '2024-06-01',
                    'return_date' => '2024-06-15',
                ]
            ]);

        // Verificar que se actualizó en la base de datos
        $this->assertDatabaseHas('trips', [
            'id' => $trip->id,
            'departure_date' => '2024-06-01',
            'return_date' => '2024-06-15',
        ]);
    }

    public function test_update_validates_input()
    {
        // Crear un viaje
        $trip = Trip::factory()->create();

        // Datos inválidos: return_date antes de departure_date
        $invalidData = [
            'departure_date' => '2024-05-10',
            'return_date' => '2024-05-01',
        ];

        // Hacer la petición
        $response = $this->putJson("/api/trips/{$trip->id}", $invalidData);

        // Verificar que devuelve un error de validación
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['return_date']);
    }

    public function test_destroy_deletes_trip()
    {
        // Crear un viaje
        $trip = Trip::factory()->create();

        // Hacer la petición
        $response = $this->deleteJson("/api/trips/{$trip->id}");

        // Verificar la respuesta
        $response->assertOk()
            ->assertJson([
                'message' => 'Trip eliminado correctamente'
            ]);

        // Verificar que se eliminó de la base de datos
        $this->assertSoftDeleted('trips', ['id' => $trip->id]);
    }

    public function test_destroy_returns_404_for_non_existent_trip()
    {
        // Hacer la petición con un ID que no existe
        $response = $this->deleteJson('/api/trips/999');

        // Verificar que devuelve un 404
        $response->assertNotFound();
    }
}
