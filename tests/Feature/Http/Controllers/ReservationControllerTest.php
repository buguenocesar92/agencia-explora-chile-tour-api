<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Client;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Trip;
use App\Models\TourTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Illuminate\Support\Facades\Mail;

class ReservationControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private User $user;
    private Client $client;
    private TourTemplate $tourTemplate;
    private Trip $trip;
    private Payment $payment;

    public function setUp(): void
    {
        parent::setUp();

        // Crear usuario autenticado para pruebas
        $this->user = new User();
        $this->user->name = 'Test User';
        $this->user->email = 'test@example.com';
        $this->user->password = bcrypt('password');
        $this->user->save();

        // Configurar almacenamiento falso
        Storage::fake('s3');

        // Mock del servicio WhatsApp para evitar llamadas reales
        $this->mock(\App\Services\WhatsAppService::class, function ($mock) {
            $mock->shouldReceive('sendPaymentConfirmation')->andReturn(true);
        });

        // Crear datos comunes para pruebas
        $this->createTestData();
    }

    /**
     * Crea los datos comunes necesarios para las pruebas
     */
    private function createTestData(): void
    {
        $this->client = new Client();
        $this->client->name = 'Test Client';
        $this->client->email = 'test@example.com';
        $this->client->phone = '123456789';
        $this->client->rut = '12345678-9';
        $this->client->date_of_birth = '1990-01-01';
        $this->client->nationality = 'Chilena';
        $this->client->save();

        $this->tourTemplate = new TourTemplate();
        $this->tourTemplate->name = 'Test Tour';
        $this->tourTemplate->description = 'Test Description';
        $this->tourTemplate->destination = 'Test Destination';
        $this->tourTemplate->save();

        $this->trip = new Trip();
        $this->trip->tour_template_id = $this->tourTemplate->id;
        $this->trip->departure_date = '2024-12-01';
        $this->trip->return_date = '2024-12-10';
        $this->trip->save();

        $this->payment = new Payment();
        $this->payment->receipt = 'receipt.jpg';
        $this->payment->save();
    }

    /**
     * Crea una reserva para pruebas
     */
    private function createReservation(array $attributes = []): Reservation
    {
        $reservation = new Reservation();
        $reservation->client_id = $attributes['client_id'] ?? $this->client->id;
        $reservation->trip_id = $attributes['trip_id'] ?? $this->trip->id;
        $reservation->payment_id = $attributes['payment_id'] ?? $this->payment->id;
        $reservation->date = $attributes['date'] ?? now()->toDateString();
        $reservation->status = $attributes['status'] ?? 'not paid';
        $reservation->descripcion = $attributes['descripcion'] ?? null;
        $reservation->save();

        return $reservation;
    }

    public function test_index_returns_reservations_list()
    {
        // Arrange
        $reservation = $this->createReservation();

        // Act
        $response = $this->actingAs($this->user)
                         ->getJson('/api/reservations');

        // Assert
        $response->assertOk()
                 ->assertJsonStructure([
                     'reservations' => [
                         '*' => [
                             'id',
                             'status',
                             'date',
                             'client',
                             'trip',
                             'payment'
                         ]
                     ]
                 ]);

        $this->assertCount(1, $response->json('reservations'));
    }

    public function test_index_filters_by_search_term()
    {
        // Arrange
        $client1 = new Client();
        $client1->name = 'John Doe';
        $client1->email = 'john@example.com';
        $client1->phone = '123456789';
        $client1->rut = '11111111-1';
        $client1->date_of_birth = '1990-01-01';
        $client1->nationality = 'Chilena';
        $client1->save();

        $client2 = new Client();
        $client2->name = 'Jane Smith';
        $client2->email = 'jane@example.com';
        $client2->phone = '987654321';
        $client2->rut = '22222222-2';
        $client2->date_of_birth = '1992-02-02';
        $client2->nationality = 'Chilena';
        $client2->save();

        $this->createReservation(['client_id' => $client1->id]);
        $this->createReservation(['client_id' => $client2->id]);

        // Act
        $response = $this->actingAs($this->user)
                         ->getJson('/api/reservations?search=John');

        // Assert
        $response->assertOk();
        $this->assertCount(1, $response->json('reservations'));
        $this->assertEquals('John Doe', $response->json('reservations.0.client.name'));
    }

    public function test_index_filters_by_status()
    {
        // Arrange
        $this->createReservation(['status' => 'paid']);
        $this->createReservation(['status' => 'not paid']);

        // Act
        $response = $this->actingAs($this->user)
                         ->getJson('/api/reservations?status=paid');

        // Assert
        $response->assertOk();
        $this->assertCount(1, $response->json('reservations'));
        $this->assertEquals('paid', $response->json('reservations.0.status'));
    }

    public function test_index_filters_by_date()
    {
        // Arrange
        $today = now()->toDateString();
        $tomorrow = now()->addDay()->toDateString();

        $this->createReservation(['date' => $today]);
        $this->createReservation(['date' => $tomorrow]);

        // Act - Buscar reservas para hoy
        $response = $this->actingAs($this->user)
                         ->getJson("/api/reservations?date={$today}");

        // Assert
        $response->assertOk();
        $this->assertCount(1, $response->json('reservations'));
        $this->assertEquals($today, $response->json('reservations.0.date'));
    }

    public function test_index_filters_by_tour_id()
    {
        // Arrange
        // Crear un segundo tour y viaje
        $tourTemplate2 = new TourTemplate();
        $tourTemplate2->name = 'Tour B';
        $tourTemplate2->description = 'Description B';
        $tourTemplate2->destination = 'Destination B';
        $tourTemplate2->save();

        $trip2 = new Trip();
        $trip2->tour_template_id = $tourTemplate2->id;
        $trip2->departure_date = '2024-12-15';
        $trip2->return_date = '2024-12-25';
        $trip2->save();

        // Crear reservaciones
        $this->createReservation();
        $this->createReservation(['trip_id' => $trip2->id]);

        // Act - Filtrar por el ID del primer tour
        $response = $this->actingAs($this->user)
                         ->getJson("/api/reservations?tour_id={$this->tourTemplate->id}");

        // Assert
        $response->assertOk();
        $this->assertCount(1, $response->json('reservations'));
        $this->assertEquals($this->trip->id, $response->json('reservations.0.trip_id'));
        $this->assertEquals('Test Tour', $response->json('reservations.0.trip.tour_template.name'));
    }

    public function test_store_creates_new_reservation()
    {
        // Arrange
        $requestData = [
            'client' => [
                'name' => 'Test Client',
                'email' => 'test@example.com',
                'phone' => '123456789',
                'rut' => '12345678-9',
                'date_of_birth' => '1990-01-01',
                'nationality' => 'Chilena'
            ],
            'trip' => [
                'trip_date_id' => $this->trip->id
            ],
            'payment' => [
                'receipt' => UploadedFile::fake()->image('receipt.jpg')
            ]
        ];

        // Act
        $response = $this->actingAs($this->user)
                         ->postJson('/api/reservations', $requestData);

        // Assert
        $response->assertStatus(201)
                 ->assertJsonPath('message', 'Reserva completada correctamente')
                 ->assertJsonStructure(['reservation', 'message']);

        $this->assertDatabaseHas('clients', [
            'name' => 'Test Client',
            'rut' => '12345678-9'
        ]);

        $this->assertDatabaseCount('reservations', 1);
    }

    public function test_show_returns_reservation_details()
    {
        // Arrange
        $reservation = $this->createReservation();

        // Act
        $response = $this->actingAs($this->user)
                         ->getJson("/api/reservations/{$reservation->id}");

        // Assert
        $response->assertOk()
                 ->assertJsonStructure([
                     'reservation' => [
                         'id',
                         'client_id',
                         'trip_id',
                         'payment_id',
                         'status',
                         'date',
                         'client',
                         'trip',
                         'payment'
                     ]
                 ]);

        $this->assertEquals($reservation->id, $response->json('reservation.id'));
    }

    public function test_update_modifies_reservation()
    {
        // Arrange
        $reservation = $this->createReservation(['descripcion' => 'Initial description']);

        // Datos completos para actualizar
        $updateData = [
            'status' => 'paid',
            'descripcion' => 'Updated description',
            'client' => [
                'name' => 'Updated Client Name',
                'email' => 'updated@example.com',
                'phone' => '987654321',
                'rut' => '12345678-9',
                'date_of_birth' => '1990-01-01',
                'nationality' => 'Chilena'
            ],
            'trip' => [
                'destination' => 'Updated Destination',
                'departure_date' => '2024-12-15',
                'return_date' => '2024-12-25'
            ],
            'payment' => [
                'receipt' => UploadedFile::fake()->image('updated_receipt.jpg')
            ]
        ];

        // Act
        $response = $this->actingAs($this->user)
                         ->putJson("/api/reservations/{$reservation->id}", $updateData);

        // Assert
        $response->assertOk()
                 ->assertJsonPath('message', 'Reserva actualizada correctamente')
                 ->assertJsonStructure(['reservation', 'message']);

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => 'paid',
            'descripcion' => 'Updated description'
        ]);

        $this->assertDatabaseHas('clients', [
            'id' => $this->client->id,
            'name' => 'Updated Client Name'
        ]);
    }

    public function test_update_status_changes_reservation_status()
    {
        // Arrange
        $reservation = $this->createReservation();

        // Act
        $response = $this->actingAs($this->user)
                         ->putJson("/api/reservations/status/{$reservation->id}", [
                             'status' => 'paid'
                         ]);

        // Assert
        $response->assertOk()
                 ->assertJsonPath('message', 'Reserva actualizada correctamente')
                 ->assertJsonStructure(['reservation', 'message']);

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => 'paid'
        ]);
    }

    public function test_destroy_deletes_reservation()
    {
        // Arrange
        $reservation = $this->createReservation();

        // Act
        $response = $this->actingAs($this->user)
                         ->deleteJson("/api/reservations/{$reservation->id}");

        // Assert
        $response->assertOk()
                 ->assertJsonPath('message', 'Reserva eliminada correctamente');

        $this->assertSoftDeleted('reservations', [
            'id' => $reservation->id
        ]);
    }

    public function test_show_returns_404_for_nonexistent_reservation()
    {
        // Act
        $response = $this->actingAs($this->user)
                         ->getJson('/api/reservations/9999');

        // Assert
        $response->assertStatus(404);
    }

    public function test_update_returns_404_for_nonexistent_reservation()
    {
        // Arrange
        $updateData = [
            'status' => 'paid',
            'descripcion' => 'Test description',
            'client' => [
                'name' => 'Test Client',
                'email' => 'test@example.com',
                'phone' => '123456789',
                'rut' => '12345678-9',
                'date_of_birth' => '1990-01-01',
                'nationality' => 'Chilena'
            ],
            'trip' => [
                'destination' => 'Test Destination',
                'departure_date' => '2024-12-01',
                'return_date' => '2024-12-10'
            ],
            'payment' => [
                'receipt' => UploadedFile::fake()->image('receipt.jpg')
            ]
        ];

        // Act
        $response = $this->actingAs($this->user)
                         ->putJson('/api/reservations/9999', $updateData);

        // Assert
        $response->assertStatus(404);
    }

    public function test_delete_returns_404_for_nonexistent_reservation()
    {
        // Act
        $response = $this->actingAs($this->user)
                         ->deleteJson('/api/reservations/9999');

        // Assert
        $response->assertStatus(404);
    }

    public function test_update_status_validates_invalid_status()
    {
        // Arrange
        $reservation = $this->createReservation();

        // Act
        $response = $this->actingAs($this->user)
                         ->putJson("/api/reservations/status/{$reservation->id}", [
                             'status' => 'invalid_status'
                         ]);

        // Assert
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['status']);

        // Verificar que la reserva mantiene su estado original
        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => 'not paid'
        ]);
    }

    public function test_store_validates_missing_required_fields()
    {
        // Act
        $response = $this->actingAs($this->user)
                         ->postJson('/api/reservations', []);

        // Assert
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['client', 'trip']);
    }

    /**
     * Test que la exportación a Excel devuelve una URL válida
     */
    public function test_export_to_excel_returns_valid_url()
    {
        // Arrange
        Storage::fake('s3');
        $reservation = $this->createReservation(['status' => 'paid']);

        // Act
        $response = $this->actingAs($this->user)
                         ->getJson('/api/reservations/export/excel');

        // Assert
        $response->assertOk()
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'url'
                 ]);

        $this->assertTrue($response->json('success'));
        $this->assertStringContainsString('exports/reservas_', $response->json('url'));
    }

    /**
     * Test que la exportación a Excel filtra por estado
     */
    public function test_export_to_excel_filters_by_status()
    {
        // Arrange
        Storage::fake('s3');
        $this->createReservation(['status' => 'paid']);
        $this->createReservation(['status' => 'not paid']);

        // Act - Exportar solo las reservas con estado "paid"
        $response = $this->actingAs($this->user)
                         ->getJson('/api/reservations/export/excel?status=paid');

        // Assert
        $response->assertOk()
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'url'
                 ]);

        $this->assertTrue($response->json('success'));
        $this->assertStringContainsString('exports/reservas_', $response->json('url'));
    }

    /**
     * Test que la exportación a Excel maneja errores correctamente
     */
    public function test_export_to_excel_handles_errors()
    {
        // Arrange - Mockear el servicio para forzar un error
        $this->mock(\App\Services\ReservationService::class, function ($mock) {
            $mock->shouldReceive('exportToExcel')->once()->andThrow(new \Exception('Test exception'));
        });

        // Act
        $response = $this->actingAs($this->user)
                         ->getJson('/api/reservations/export/excel');

        // Assert
        $response->assertStatus(500)
                 ->assertJsonStructure([
                     'success',
                     'message'
                 ]);

        $this->assertFalse($response->json('success'));
        $this->assertStringContainsString('Error al generar', $response->json('message'));
    }

    /**
     * Test para verificar que el método restore restaura correctamente una reserva eliminada
     */
    public function test_restore_recovers_deleted_reservation()
    {
        // Arrange
        $reservation = $this->createReservation();
        $reservation->delete();

        // Verificar que está eliminado
        $this->assertSoftDeleted('reservations', ['id' => $reservation->id]);

        // Act - Restaurar la reserva
        $response = $this->actingAs($this->user)
                         ->putJson("/api/reservations/{$reservation->id}/restore");

        // Assert
        $response->assertOk()
                 ->assertJsonPath('message', 'Reserva restaurada correctamente');

        // Verificar que la reserva fue restaurada
        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'deleted_at' => null
        ]);
    }

    /**
     * Test para verificar que el método restore devuelve 404 para una reserva inexistente
     */
    public function test_restore_returns_404_for_nonexistent_reservation()
    {
        // Act
        $response = $this->actingAs($this->user)
                         ->putJson('/api/reservations/9999/restore');

        // Assert
        $response->assertStatus(404);
    }

    /**
     * Test para verificar que el método forceDelete elimina permanentemente una reserva
     */
    public function test_force_delete_permanently_removes_reservation()
    {
        // Arrange
        $reservation = $this->createReservation();
        $reservation->delete();

        // Act - Eliminar permanentemente la reserva
        $response = $this->actingAs($this->user)
                         ->deleteJson("/api/reservations/{$reservation->id}/force");

        // Assert
        $response->assertOk()
                 ->assertJsonPath('message', 'Reserva eliminada permanentemente');

        // Verificar que la reserva fue eliminada permanentemente (no debería encontrarse ni con withTrashed)
        $found = Reservation::withTrashed()->find($reservation->id);
        $this->assertNull($found);
    }

    /**
     * Test para verificar que el método forceDelete devuelve 404 para una reserva inexistente
     */
    public function test_force_delete_returns_404_for_nonexistent_reservation()
    {
        // Act
        $response = $this->actingAs($this->user)
                         ->deleteJson('/api/reservations/9999/force');

        // Assert
        $response->assertStatus(404);
    }

    /**
     * Test para verificar que index con with_trashed=true incluye reservas eliminadas
     */
    public function test_index_includes_trashed_reservations_when_requested()
    {
        // Arrange
        $activeReservation = $this->createReservation(['status' => 'paid']);

        $deletedReservation = $this->createReservation(['status' => 'not paid']);
        $deletedReservation->delete();

        // Act - Sin with_trashed
        $responseWithoutTrashed = $this->actingAs($this->user)
                                       ->getJson('/api/reservations');

        // Assert - Solo debería mostrar la reserva activa
        $responseWithoutTrashed->assertOk();
        $this->assertCount(1, $responseWithoutTrashed->json('reservations'));

        // Act - Con with_trashed=true
        $responseWithTrashed = $this->actingAs($this->user)
                                    ->getJson('/api/reservations?with_trashed=true');

        // Assert - Debería mostrar ambas reservas
        $responseWithTrashed->assertOk();
        $this->assertCount(2, $responseWithTrashed->json('reservations'));
    }

    /**
     * Test para verificar que show con with_trashed=true permite ver reservas eliminadas
     */
    public function test_show_displays_trashed_reservation_when_requested()
    {
        // Arrange
        $reservation = $this->createReservation();
        $reservation->delete();

        // Act - Sin with_trashed
        $responseWithoutTrashed = $this->actingAs($this->user)
                                       ->getJson("/api/reservations/{$reservation->id}");

        // Assert - Debería dar 404
        $responseWithoutTrashed->assertStatus(404);

        // Act - Con with_trashed=true
        $responseWithTrashed = $this->actingAs($this->user)
                                    ->getJson("/api/reservations/{$reservation->id}?with_trashed=true");

        // Assert - Debería mostrar la reserva eliminada
        $responseWithTrashed->assertOk()
                            ->assertJsonPath('reservation.id', $reservation->id);
    }

    /**
     * Test para verificar el envío correcto de email cuando una reserva se marca como pagada
     */
    public function test_update_status_sends_email_notification_when_paid()
    {
        // Arrange
        $reservation = $this->createReservation();
        Mail::fake();

        // Act
        $response = $this->actingAs($this->user)
                         ->putJson("/api/reservations/status/{$reservation->id}", [
                             'status' => 'paid'
                         ]);

        // Assert
        $response->assertOk();

        // Verificar que se envió el email
        Mail::assertSent(function (\App\Mail\ConfirmacionReserva $mail) {
            return $mail->hasTo($this->client->email);
        });
    }

    /**
     * Test para verificar que no se envían notificaciones cuando una reserva se marca con un estado diferente a "paid"
     */
    public function test_update_status_doesnt_send_notifications_for_non_paid_status()
    {
        // Arrange
        $reservation = $this->createReservation(['status' => 'paid']);
        Mail::fake();

        // Act - Cambiar a "not paid"
        $response = $this->actingAs($this->user)
                         ->putJson("/api/reservations/status/{$reservation->id}", [
                             'status' => 'not paid'
                         ]);

        // Assert
        $response->assertOk();

        // Verificar que NO se envió ningún email
        Mail::assertNothingSent();
    }

    /**
     * Test para verificar que el email no se envía si el cliente no tiene email
     */
    public function test_update_status_doesnt_send_email_when_client_has_no_email()
    {
        // Este test se salta porque no podemos crear un cliente sin email debido a la restricción NOT NULL
        // y no podemos mockear métodos privados fácilmente en pruebas de Laravel
        $this->markTestSkipped('No se puede testear sin email debido a restricciones de la base de datos');

        // Podríamos crear una prueba unitaria separada para la función sendEmailNotification
        // si es crítico comprobar ese comportamiento
    }

    /**
     * Test para verificar que la actualización de estado maneja correctamente cuando no hay información de cliente
     */
    public function test_update_status_with_minimal_client_info()
    {
        // Este test se salta porque no podemos crear un cliente sin teléfono debido a la restricción NOT NULL
        $this->markTestSkipped('No se puede testear sin phone debido a restricciones de la base de datos');
    }

    /**
     * Test para verificar que el controlador maneja correctamente el caso en que la reserva no tiene viaje
     */
    public function test_update_status_handles_missing_trip_gracefully()
    {
        // Este test se salta porque no podemos crear una reserva sin trip_id debido a la restricción NOT NULL
        $this->markTestSkipped('No se puede testear sin trip_id debido a restricciones de la base de datos');
    }

    /**
     * Test para verificar que el controlador exportToExcel maneja correctamente cuando se especifican varios filtros
     */
    public function test_export_to_excel_with_multiple_filters()
    {
        // Arrange
        Storage::fake('s3');
        $today = now()->toDateString();
        $reservation = $this->createReservation([
            'status' => 'paid',
            'date' => $today
        ]);

        // Act - Usar múltiples filtros
        $response = $this->actingAs($this->user)
                         ->getJson("/api/reservations/export/excel?status=paid&date={$today}&tour_id={$this->tourTemplate->id}");

        // Assert
        $response->assertOk()
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'url'
                 ]);

        $this->assertTrue($response->json('success'));
    }

    /**
     * Test para verificar que el método extractFiltersFromRequest extrae correctamente los filtros
     */
    public function test_extract_filters_method_through_multiple_filter_combinations()
    {
        // Arrange
        $reservation = $this->createReservation();
        $today = now()->toDateString();

        // Case 1: Sin filtros
        $response1 = $this->actingAs($this->user)
                          ->getJson('/api/reservations');
        $response1->assertOk();
        $this->assertCount(1, $response1->json('reservations'));

        // Case 2: Solo filtro de tour_id
        $response2 = $this->actingAs($this->user)
                          ->getJson("/api/reservations?tour_id={$this->tourTemplate->id}");
        $response2->assertOk();
        $this->assertCount(1, $response2->json('reservations'));

        // Case 3: Filtro de tour_id inexistente
        $response3 = $this->actingAs($this->user)
                          ->getJson('/api/reservations?tour_id=9999');
        $response3->assertOk();
        $this->assertCount(0, $response3->json('reservations'));

        // Case 4: Combinación de filtros
        $response4 = $this->actingAs($this->user)
                          ->getJson("/api/reservations?tour_id={$this->tourTemplate->id}&status=not%20paid&date={$today}");
        $response4->assertOk();
        $this->assertCount(1, $response4->json('reservations'));
    }
}
