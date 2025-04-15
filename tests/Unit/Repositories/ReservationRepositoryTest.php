<?php

namespace Tests\Unit\Repositories;

use App\Models\Client;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Trip;
use App\Models\TourTemplate;
use App\Repositories\ReservationRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected ReservationRepository $repository;

    public function setUp(): void
    {
        parent::setUp();
        $this->repository = new ReservationRepository();
    }

    public function test_can_create_reservation()
    {
        // Arrange - Crear las relaciones manualmente
        $client = new Client();
        $client->name = 'John Doe';
        $client->email = 'john@example.com';
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

        $data = [
            'client_id' => $client->id,
            'trip_id' => $trip->id,
            'payment_id' => $payment->id,
            'date' => now()->toDateString(),
            'descripcion' => 'Test reservation'
        ];

        // Act
        $reservation = $this->repository->create($data);

        // Assert
        $this->assertInstanceOf(Reservation::class, $reservation);
        $this->assertEquals($client->id, $reservation->client_id);
        $this->assertEquals($trip->id, $reservation->trip_id);
        $this->assertEquals($payment->id, $reservation->payment_id);
        $this->assertEquals($data['date'], $reservation->date);
        $this->assertEquals($data['descripcion'], $reservation->descripcion);
    }

    public function test_can_get_all_reservations()
    {
        // Arrange - Crear las relaciones manualmente
        $client = new Client();
        $client->name = 'John Doe';
        $client->email = 'john@example.com';
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
        $reservations = $this->repository->getAll();

        // Assert
        $this->assertCount(1, $reservations);
        $this->assertEquals($client->id, $reservations->first()->client_id);
    }

    public function test_can_search_reservations_by_client_name()
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
        $reservations = $this->repository->getAll('John');

        // Assert
        $this->assertCount(1, $reservations);
        $this->assertEquals($client1->id, $reservations->first()->client_id);
    }

    public function test_can_filter_reservations_by_status()
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
        $reservations = $this->repository->getAll(null, ['status' => 'paid']);

        // Assert
        $this->assertCount(1, $reservations);
        $this->assertEquals('paid', $reservations->first()->status);
    }

    public function test_can_update_reservation_status()
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
        $updated = $this->repository->updateStatus($reservation->id, 'paid');

        // Assert
        $this->assertEquals('paid', $updated->status);
        $this->assertEquals('paid', $reservation->fresh()->status);
    }

    public function test_can_get_reservation_by_id()
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
        $found = $this->repository->getById($reservation->id);

        // Assert
        $this->assertEquals($reservation->id, $found->id);
        $this->assertNotNull($found->client);
        $this->assertNotNull($found->trip);
        $this->assertNotNull($found->payment);
    }

    public function test_can_delete_reservation()
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
        $this->repository->delete($reservation->id);

        // Assert
        $this->assertNull(Reservation::find($reservation->id));
    }
}
