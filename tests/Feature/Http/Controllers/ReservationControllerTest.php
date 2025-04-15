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

class ReservationControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private User $user;

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
    }

    public function test_index_returns_reservations_list()
    {
        // Arrange
        $client = new Client();
        $client->name = 'Test Client';
        $client->email = 'test@example.com';
        $client->phone = '123456789';
        $client->rut = '12345678-9';
        $client->date_of_birth = '1990-01-01';
        $client->nationality = 'Chilena';
        $client->save();

        $tourTemplate = new TourTemplate();
        $tourTemplate->name = 'Test Tour';
        $tourTemplate->description = 'Test Description';
        $tourTemplate->destination = 'Test Destination';
        $tourTemplate->save();

        $trip = new Trip();
        $trip->tour_template_id = $tourTemplate->id;
        $trip->departure_date = '2024-12-01';
        $trip->return_date = '2024-12-10';
        $trip->save();

        $payment = new Payment();
        $payment->receipt = 'receipt.jpg';
        $payment->save();

        $reservation = new Reservation();
        $reservation->client_id = $client->id;
        $reservation->trip_id = $trip->id;
        $reservation->payment_id = $payment->id;
        $reservation->date = now()->toDateString();
        $reservation->status = 'not paid';
        $reservation->save();

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
        $client1->rut = '12345678-9';
        $client1->date_of_birth = '1990-01-01';
        $client1->nationality = 'Chilena';
        $client1->save();

        $client2 = new Client();
        $client2->name = 'Jane Smith';
        $client2->email = 'jane@example.com';
        $client2->phone = '987654321';
        $client2->rut = '87654321-9';
        $client2->date_of_birth = '1992-02-02';
        $client2->nationality = 'Chilena';
        $client2->save();

        $tourTemplate = new TourTemplate();
        $tourTemplate->name = 'Test Tour';
        $tourTemplate->description = 'Test Description';
        $tourTemplate->destination = 'Test Destination';
        $tourTemplate->save();

        $trip = new Trip();
        $trip->tour_template_id = $tourTemplate->id;
        $trip->departure_date = '2024-12-01';
        $trip->return_date = '2024-12-10';
        $trip->save();

        $payment = new Payment();
        $payment->receipt = 'receipt.jpg';
        $payment->save();

        $reservation1 = new Reservation();
        $reservation1->client_id = $client1->id;
        $reservation1->trip_id = $trip->id;
        $reservation1->payment_id = $payment->id;
        $reservation1->date = now()->toDateString();
        $reservation1->status = 'not paid';
        $reservation1->save();

        $reservation2 = new Reservation();
        $reservation2->client_id = $client2->id;
        $reservation2->trip_id = $trip->id;
        $reservation2->payment_id = $payment->id;
        $reservation2->date = now()->toDateString();
        $reservation2->status = 'not paid';
        $reservation2->save();

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
        $client = new Client();
        $client->name = 'Test Client';
        $client->email = 'test@example.com';
        $client->phone = '123456789';
        $client->rut = '12345678-9';
        $client->date_of_birth = '1990-01-01';
        $client->nationality = 'Chilena';
        $client->save();

        $tourTemplate = new TourTemplate();
        $tourTemplate->name = 'Test Tour';
        $tourTemplate->description = 'Test Description';
        $tourTemplate->destination = 'Test Destination';
        $tourTemplate->save();

        $trip = new Trip();
        $trip->tour_template_id = $tourTemplate->id;
        $trip->departure_date = '2024-12-01';
        $trip->return_date = '2024-12-10';
        $trip->save();

        $payment = new Payment();
        $payment->receipt = 'receipt.jpg';
        $payment->save();

        $reservation1 = new Reservation();
        $reservation1->client_id = $client->id;
        $reservation1->trip_id = $trip->id;
        $reservation1->payment_id = $payment->id;
        $reservation1->date = now()->toDateString();
        $reservation1->status = 'paid';
        $reservation1->save();

        $reservation2 = new Reservation();
        $reservation2->client_id = $client->id;
        $reservation2->trip_id = $trip->id;
        $reservation2->payment_id = $payment->id;
        $reservation2->date = now()->toDateString();
        $reservation2->status = 'not paid';
        $reservation2->save();

        // Act
        $response = $this->actingAs($this->user)
                         ->getJson('/api/reservations?status=paid');

        // Assert
        $response->assertOk();
        $this->assertCount(1, $response->json('reservations'));
        $this->assertEquals('paid', $response->json('reservations.0.status'));
    }

    public function test_store_creates_new_reservation()
    {
        // Arrange
        $tourTemplate = new TourTemplate();
        $tourTemplate->name = 'Test Tour';
        $tourTemplate->description = 'Test Description';
        $tourTemplate->destination = 'Test Destination';
        $tourTemplate->save();

        $trip = new Trip();
        $trip->tour_template_id = $tourTemplate->id;
        $trip->departure_date = '2024-12-01';
        $trip->return_date = '2024-12-10';
        $trip->save();

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
                'trip_date_id' => $trip->id
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
        $client = new Client();
        $client->name = 'Test Client';
        $client->email = 'test@example.com';
        $client->phone = '123456789';
        $client->rut = '12345678-9';
        $client->date_of_birth = '1990-01-01';
        $client->nationality = 'Chilena';
        $client->save();

        $tourTemplate = new TourTemplate();
        $tourTemplate->name = 'Test Tour';
        $tourTemplate->description = 'Test Description';
        $tourTemplate->destination = 'Test Destination';
        $tourTemplate->save();

        $trip = new Trip();
        $trip->tour_template_id = $tourTemplate->id;
        $trip->departure_date = '2024-12-01';
        $trip->return_date = '2024-12-10';
        $trip->save();

        $payment = new Payment();
        $payment->receipt = 'receipt.jpg';
        $payment->save();

        $reservation = new Reservation();
        $reservation->client_id = $client->id;
        $reservation->trip_id = $trip->id;
        $reservation->payment_id = $payment->id;
        $reservation->date = now()->toDateString();
        $reservation->status = 'not paid';
        $reservation->save();

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
        $client = new Client();
        $client->name = 'Test Client';
        $client->email = 'test@example.com';
        $client->phone = '123456789';
        $client->rut = '12345678-9';
        $client->date_of_birth = '1990-01-01';
        $client->nationality = 'Chilena';
        $client->save();

        $tourTemplate = new TourTemplate();
        $tourTemplate->name = 'Test Tour';
        $tourTemplate->description = 'Test Description';
        $tourTemplate->destination = 'Test Destination';
        $tourTemplate->save();

        $trip = new Trip();
        $trip->tour_template_id = $tourTemplate->id;
        $trip->departure_date = '2024-12-01';
        $trip->return_date = '2024-12-10';
        $trip->save();

        $payment = new Payment();
        $payment->receipt = 'receipt.jpg';
        $payment->save();

        $reservation = new Reservation();
        $reservation->client_id = $client->id;
        $reservation->trip_id = $trip->id;
        $reservation->payment_id = $payment->id;
        $reservation->date = now()->toDateString();
        $reservation->status = 'not paid';
        $reservation->descripcion = 'Old description';
        $reservation->save();

        $updateData = [
            'descripcion' => 'Updated description',
            'status' => 'paid',
            'date' => now()->addDays(5)->toDateString(),
            'client' => [
                'name' => 'Updated Client Name',
                'date_of_birth' => '1990-01-01',
                'nationality' => 'Chilena'
            ],
            'payment' => [
                'receipt' => 'updated_receipt.jpg'
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
            'id' => $client->id,
            'name' => 'Updated Client Name'
        ]);
    }

    public function test_update_status_changes_reservation_status()
    {
        // Arrange
        $client = new Client();
        $client->name = 'Test Client';
        $client->email = 'test@example.com';
        $client->phone = '56944964919';
        $client->rut = '12345678-9';
        $client->date_of_birth = '1990-01-01';
        $client->nationality = 'Chilena';
        $client->save();

        $tourTemplate = new TourTemplate();
        $tourTemplate->name = 'Test Tour';
        $tourTemplate->description = 'Test Description';
        $tourTemplate->destination = 'Test Destination';
        $tourTemplate->save();

        $trip = new Trip();
        $trip->tour_template_id = $tourTemplate->id;
        $trip->departure_date = '2024-12-01';
        $trip->return_date = '2024-12-10';
        $trip->save();

        $payment = new Payment();
        $payment->receipt = 'receipt.jpg';
        $payment->save();

        $reservation = new Reservation();
        $reservation->client_id = $client->id;
        $reservation->trip_id = $trip->id;
        $reservation->payment_id = $payment->id;
        $reservation->date = now()->toDateString();
        $reservation->status = 'not paid';
        $reservation->save();

        // Act
        $response = $this->actingAs($this->user)
                         ->patchJson("/api/reservations/{$reservation->id}/status", [
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
        $client = new Client();
        $client->name = 'Test Client';
        $client->email = 'test@example.com';
        $client->phone = '123456789';
        $client->rut = '12345678-9';
        $client->date_of_birth = '1990-01-01';
        $client->nationality = 'Chilena';
        $client->save();

        $tourTemplate = new TourTemplate();
        $tourTemplate->name = 'Test Tour';
        $tourTemplate->description = 'Test Description';
        $tourTemplate->destination = 'Test Destination';
        $tourTemplate->save();

        $trip = new Trip();
        $trip->tour_template_id = $tourTemplate->id;
        $trip->departure_date = '2024-12-01';
        $trip->return_date = '2024-12-10';
        $trip->save();

        $payment = new Payment();
        $payment->receipt = 'receipt.jpg';
        $payment->save();

        $reservation = new Reservation();
        $reservation->client_id = $client->id;
        $reservation->trip_id = $trip->id;
        $reservation->payment_id = $payment->id;
        $reservation->date = now()->toDateString();
        $reservation->status = 'not paid';
        $reservation->save();

        // Act
        $response = $this->actingAs($this->user)
                         ->deleteJson("/api/reservations/{$reservation->id}");

        // Assert
        $response->assertOk()
                 ->assertJsonPath('message', 'Reserva eliminada correctamente');

        $this->assertDatabaseMissing('reservations', [
            'id' => $reservation->id
        ]);
    }
}
