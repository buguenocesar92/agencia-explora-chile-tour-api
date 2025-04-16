<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class ClientControllerTest extends TestCase
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

    public function test_index_returns_all_clients()
    {
        // Crear algunos clientes de prueba
        $clients = Client::factory()->count(3)->create();

        // Hacer la petición
        $response = $this->getJson('/api/clients');

        // Verificar la respuesta
        $response->assertOk()
            ->assertJsonCount(3);
    }

    public function test_index_can_search_by_name()
    {
        // Crear clientes con nombres específicos
        Client::factory()->create(['name' => 'Juan Pérez']);
        Client::factory()->create(['name' => 'Ana López']);
        Client::factory()->create(['name' => 'Juan García']);

        // Hacer la petición de búsqueda
        $response = $this->getJson('/api/clients?search=Juan');

        // Verificar que solo devuelve los clientes con "Juan" en el nombre
        $response->assertOk();

        $responseData = $response->json();
        $this->assertCount(2, $responseData);

        // Extraer nombres para verificación
        $names = collect($responseData)->pluck('name')->toArray();
        $this->assertContains('Juan Pérez', $names);
        $this->assertContains('Juan García', $names);
        $this->assertNotContains('Ana López', $names);
    }

    public function test_find_by_rut_returns_client()
    {
        // Crear un cliente con RUT específico
        $client = Client::factory()->create(['rut' => '12345678-9']);

        // Hacer la petición a la URL correcta
        $response = $this->getJson('/api/clients/search-by-rut?rut=12345678-9');

        // Verificar la respuesta
        $response->assertOk();

        $responseData = $response->json();
        $this->assertEquals($client->id, $responseData['id']);
        $this->assertEquals($client->name, $responseData['name']);
        $this->assertEquals('12345678-9', $responseData['rut']);
    }

    public function test_find_by_rut_returns_404_for_non_existent_rut()
    {
        // Hacer la petición con un RUT que no existe a la URL correcta
        $response = $this->getJson('/api/clients/search-by-rut?rut=99999999-9');

        // Verificar que devuelve un 404
        $response->assertNotFound();
    }

    public function test_store_creates_new_client()
    {
        // Datos para el nuevo cliente con RUT chileno válido
        $clientData = [
            'name' => 'Carlos Rodríguez',
            'email' => 'carlos@example.com',
            'phone' => '+56912345678',
            'rut' => '11.111.111-1', // RUT chileno válido
            'date_of_birth' => '1990-01-01',
            'nationality' => 'Chilena'
        ];

        // Hacer la petición
        $response = $this->postJson('/api/clients', $clientData);

        // Verificar la respuesta
        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Cliente creado con éxito.',
                'client' => [
                    'name' => 'Carlos Rodríguez',
                    'email' => 'carlos@example.com',
                    'phone' => '+56912345678',
                    'rut' => '11.111.111-1',
                    'date_of_birth' => '1990-01-01',
                    'nationality' => 'Chilena'
                ]
            ]);

        // Verificar que se creó en la base de datos
        $this->assertDatabaseHas('clients', [
            'name' => 'Carlos Rodríguez',
            'email' => 'carlos@example.com'
        ]);
    }

    public function test_store_validates_input()
    {
        // Datos inválidos: falta nombre y email
        $invalidData = [
            'phone' => '+56912345678',
            'rut' => '12.345.678-5'
        ];

        // Hacer la petición
        $response = $this->postJson('/api/clients', $invalidData);

        // Verificar que devuelve errores de validación
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'date_of_birth', 'nationality']);
    }

    public function test_store_validates_unique_rut()
    {
        // Crear un cliente existente con RUT chileno válido
        Client::factory()->create([
            'rut' => '11.111.111-1', // RUT chileno válido
            'email' => 'existente@example.com'
        ]);

        // Intentar crear otro cliente con el mismo RUT
        $clientData = [
            'name' => 'Carlos Rodríguez',
            'email' => 'carlos@example.com',
            'phone' => '+56912345678',
            'rut' => '11.111.111-1', // El mismo RUT
            'date_of_birth' => '1990-01-01',
            'nationality' => 'Chilena'
        ];

        // Hacer la petición
        $response = $this->postJson('/api/clients', $clientData);

        // Verificar que devuelve error de RUT único
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['rut']);
    }

    public function test_update_modifies_client()
    {
        // Crear un cliente con un RUT chileno válido
        $client = Client::factory()->create([
            'name' => 'Nombre Original',
            'email' => 'original@example.com',
            'date_of_birth' => '1990-01-01',
            'nationality' => 'Chilena',
            'rut' => '11.111.111-1' // RUT chileno válido
        ]);

        // Datos para actualizar (mantener el mismo RUT)
        $updateData = [
            'name' => 'Nombre Actualizado',
            'email' => 'actualizado@example.com'
        ];

        // Hacer la petición
        $response = $this->putJson("/api/clients/{$client->id}", $updateData);

        // Verificar la respuesta
        $response->assertOk()
            ->assertJson([
                'message' => 'Cliente actualizado con éxito.',
                'client' => [
                    'id' => $client->id,
                    'name' => 'Nombre Actualizado',
                    'email' => 'actualizado@example.com'
                ]
            ]);

        // Verificar que se actualizó en la base de datos
        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'name' => 'Nombre Actualizado',
            'email' => 'actualizado@example.com'
        ]);
    }

    public function test_update_validates_email_format()
    {
        // Crear un cliente
        $client = Client::factory()->create();

        // Datos inválidos: email con formato incorrecto
        $invalidData = [
            'name' => 'Nombre Válido',
            'email' => 'correo-invalido'
        ];

        // Hacer la petición
        $response = $this->putJson("/api/clients/{$client->id}", $invalidData);

        // Verificar que devuelve error de formato de email
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_update_returns_404_for_non_existent_client()
    {
        // Datos para actualizar
        $updateData = [
            'name' => 'Nombre Actualizado',
            'email' => 'actualizado@example.com'
        ];

        // Hacer la petición con un ID que no existe
        $response = $this->putJson('/api/clients/999', $updateData);

        // Verificar que devuelve un 404
        $response->assertNotFound();
    }

    public function test_destroy_deletes_client()
    {
        // Crear un cliente
        $client = Client::factory()->create();

        // Hacer la petición
        $response = $this->deleteJson("/api/clients/{$client->id}");

        // Verificar la respuesta
        $response->assertOk()
            ->assertJson([
                'message' => 'Cliente eliminado con éxito.'
            ]);

        // Verificar que se marcó como eliminado (soft delete)
        $this->assertSoftDeleted('clients', ['id' => $client->id]);
    }

    public function test_destroy_returns_404_for_non_existent_client()
    {
        // Hacer la petición con un ID que no existe
        $response = $this->deleteJson('/api/clients/999');

        // Verificar que devuelve un 404
        $response->assertNotFound();
    }
}
