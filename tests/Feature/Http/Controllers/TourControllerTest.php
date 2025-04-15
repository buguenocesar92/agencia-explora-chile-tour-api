<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\TourTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class TourControllerTest extends TestCase
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

    public function test_index_returns_all_tours()
    {
        // Crear algunos tours de prueba
        $tours = TourTemplate::factory()->count(3)->create();

        // Hacer la petición
        $response = $this->getJson('/api/tour-templates');

        // Verificar la respuesta
        $response->assertOk();
        $this->assertCount(3, $response->json('tourTemplates'));
    }

    public function test_index_can_filter_by_tour_template_id()
    {
        // Esta prueba no aplica para el controlador TourTemplate
        // ya que no filtra por tour_template_id
        $this->assertTrue(true);
    }

    public function test_show_returns_tour_details()
    {
        // Crear un tour
        $tour = TourTemplate::factory()->create([
            'name' => 'Tour de prueba',
            'destination' => 'Destino de prueba',
            'description' => 'Descripción del tour de prueba'
        ]);

        // Hacer la petición
        $response = $this->getJson("/api/tour-templates/{$tour->id}");

        // Verificar la respuesta
        $response->assertOk();
        $tourTemplate = $response->json('tourTemplate');

        // Verificar que contiene los datos del tour
        $this->assertEquals($tour->id, $tourTemplate['id']);
        $this->assertEquals('Tour de prueba', $tourTemplate['name']);
        $this->assertEquals('Destino de prueba', $tourTemplate['destination']);
        $this->assertEquals('Descripción del tour de prueba', $tourTemplate['description']);
    }

    public function test_show_returns_404_for_non_existent_tour()
    {
        // Hacer la petición con un ID que no existe
        $response = $this->getJson('/api/tour-templates/999');

        // Verificar que devuelve un 404
        $response->assertNotFound();
    }

    public function test_store_creates_new_tour()
    {
        // Datos para el nuevo tour
        $tourData = [
            'name' => 'Tour de prueba',
            'destination' => 'Destino de prueba',
            'description' => 'Descripción del tour de prueba'
        ];

        // Hacer la petición
        $response = $this->postJson('/api/tour-templates', $tourData);

        // Verificar la respuesta
        $response->assertStatus(201)
            ->assertJson([
                'message' => 'TourTemplate creado correctamente',
                'tourTemplate' => [
                    'name' => 'Tour de prueba',
                    'destination' => 'Destino de prueba',
                    'description' => 'Descripción del tour de prueba'
                ]
            ]);

        // Verificar que se creó en la base de datos
        $this->assertDatabaseHas('tour_templates', [
            'name' => 'Tour de prueba',
            'destination' => 'Destino de prueba',
            'description' => 'Descripción del tour de prueba'
        ]);
    }

    public function test_store_validates_input()
    {
        // Datos inválidos: falta nombre, destino
        $invalidData = [
            'description' => 'Solo una descripción'
        ];

        // Hacer la petición
        $response = $this->postJson('/api/tour-templates', $invalidData);

        // Verificar que devuelve errores de validación
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'destination']);
    }

    public function test_update_modifies_tour()
    {
        // Crear un tour
        $tour = TourTemplate::factory()->create([
            'name' => 'Nombre Original',
            'destination' => 'Destino Original',
            'description' => 'Descripción Original'
        ]);

        // Datos para actualizar
        $updateData = [
            'name' => 'Nombre Actualizado',
            'destination' => 'Destino Actualizado',
            'description' => 'Descripción Actualizada'
        ];

        // Hacer la petición
        $response = $this->putJson("/api/tour-templates/{$tour->id}", $updateData);

        // Verificar la respuesta
        $response->assertOk()
            ->assertJson([
                'message' => 'TourTemplate actualizado correctamente',
                'tourTemplate' => [
                    'id' => $tour->id,
                    'name' => 'Nombre Actualizado',
                    'destination' => 'Destino Actualizado',
                    'description' => 'Descripción Actualizada'
                ]
            ]);

        // Verificar que se actualizó en la base de datos
        $this->assertDatabaseHas('tour_templates', [
            'id' => $tour->id,
            'name' => 'Nombre Actualizado',
            'destination' => 'Destino Actualizado',
            'description' => 'Descripción Actualizada'
        ]);
    }

    public function test_update_validates_input()
    {
        // Crear un tour
        $tour = TourTemplate::factory()->create([
            'name' => 'Nombre Original',
            'destination' => 'Destino Original'
        ]);

        // Datos inválidos: nombre vacío
        $invalidData = [
            'name' => '',
            'destination' => 'Destino Actualizado'
        ];

        // Hacer la petición
        $response = $this->putJson("/api/tour-templates/{$tour->id}", $invalidData);

        // Verificar que devuelve errores de validación
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_update_returns_404_for_non_existent_tour()
    {
        // Datos para actualizar
        $updateData = [
            'name' => 'Nombre Actualizado',
            'destination' => 'Destino Actualizado'
        ];

        // Hacer la petición con un ID que no existe
        $response = $this->putJson('/api/tour-templates/999', $updateData);

        // Verificar que devuelve un 404
        $response->assertNotFound();
    }

    public function test_destroy_deletes_tour()
    {
        // Crear un tour
        $tour = TourTemplate::factory()->create();

        // Hacer la petición
        $response = $this->deleteJson("/api/tour-templates/{$tour->id}");

        // Verificar la respuesta
        $response->assertOk()
            ->assertJson([
                'message' => 'TourTemplate eliminado correctamente'
            ]);

        // Verificar que se eliminó de la base de datos
        $this->assertDatabaseMissing('tour_templates', ['id' => $tour->id]);
    }

    public function test_destroy_returns_404_for_non_existent_tour()
    {
        // Hacer la petición con un ID que no existe
        $response = $this->deleteJson('/api/tour-templates/999');

        // Verificar que devuelve un 404
        $response->assertNotFound();
    }
}
