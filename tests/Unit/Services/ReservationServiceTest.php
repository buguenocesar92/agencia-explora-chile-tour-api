<?php

namespace Tests\Unit\Services;

use App\Models\Client;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Trip;
use App\Models\TourTemplate;
use App\Repositories\Contracts\ReservationRepositoryInterface;
use App\Services\ReservationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class ReservationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ReservationService $service;
    protected $mockRepo;

    public function setUp(): void
    {
        parent::setUp();

        // Mock del repositorio
        $this->mockRepo = Mockery::mock(ReservationRepositoryInterface::class);

        // Inyección del mock en el servicio
        $this->service = new ReservationService($this->mockRepo);
    }

    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_create_reservation_creates_client_if_not_exists()
    {
        // Arrange
        Storage::fake('s3');

        $clientData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '123456789',
            'rut' => '12345678-9',
            'date_of_birth' => '1990-01-01',
            'nationality' => 'Chilena'
        ];

        $tripData = [
            'trip_date_id' => 1
        ];

        $paymentData = [
            'receipt' => UploadedFile::fake()->image('receipt.jpg')
        ];

        $requestData = [
            'client' => $clientData,
            'trip' => $tripData,
            'payment' => $paymentData
        ];

        // Crear viaje en la base de datos
        $tourTemplate = new TourTemplate();
        $tourTemplate->name = 'Test Tour';
        $tourTemplate->description = 'Test Description';
        $tourTemplate->destination = 'Test Destination';
        $tourTemplate->save();

        $trip = new Trip();
        $trip->tour_template_id = $tourTemplate->id;
        $trip->departure_date = '2024-12-01';
        $trip->return_date = '2024-12-10';
        $trip->id = 1; // Asignar ID manualmente para que coincida con el mock
        $trip->save();

        // Crear una reserva mock para retornar
        $reservation = new Reservation();
        $reservation->client_id = 1;
        $reservation->trip_id = $trip->id;
        $reservation->payment_id = 1;
        $reservation->date = now()->toDateString();

        // Configurar expectativas del mock
        $this->mockRepo->shouldReceive('create')
            ->once()
            ->andReturn($reservation);

        // Act
        $result = $this->service->createReservation($requestData);

        // Assert
        $this->assertInstanceOf(Reservation::class, $result);
        $this->assertDatabaseHas('clients', ['rut' => $clientData['rut']]);
    }

    public function test_create_reservation_updates_client_if_exists()
    {
        // Arrange
        Storage::fake('s3');

        // Crear cliente existente
        $client = new Client();
        $client->name = 'Old Name';
        $client->email = 'old@example.com';
        $client->phone = '123456789';
        $client->rut = '12345678-9';
        $client->date_of_birth = '1990-01-01';
        $client->nationality = 'Chilena';
        $client->save();

        $clientData = [
            'name' => 'New Name',
            'email' => 'john@example.com',
            'phone' => '123456789',
            'rut' => '12345678-9',
            'date_of_birth' => '1990-01-01',
            'nationality' => 'Chilena'
        ];

        $tripData = [
            'trip_date_id' => 1
        ];

        $paymentData = [
            'receipt' => UploadedFile::fake()->image('receipt.jpg')
        ];

        $requestData = [
            'client' => $clientData,
            'trip' => $tripData,
            'payment' => $paymentData
        ];

        // Crear viaje en la base de datos
        $tourTemplate = new TourTemplate();
        $tourTemplate->name = 'Test Tour';
        $tourTemplate->description = 'Test Description';
        $tourTemplate->destination = 'Test Destination';
        $tourTemplate->save();

        $trip = new Trip();
        $trip->tour_template_id = $tourTemplate->id;
        $trip->departure_date = '2024-12-01';
        $trip->return_date = '2024-12-10';
        $trip->id = 1; // Asignar ID manualmente para que coincida con el mock
        $trip->save();

        // Crear una reserva mock para retornar
        $reservation = new Reservation();
        $reservation->client_id = $client->id;
        $reservation->trip_id = $trip->id;
        $reservation->payment_id = 1;
        $reservation->date = now()->toDateString();

        // Configurar expectativas del mock
        $this->mockRepo->shouldReceive('create')
            ->once()
            ->andReturn($reservation);

        // Act
        $result = $this->service->createReservation($requestData);

        // Assert
        $this->assertInstanceOf(Reservation::class, $result);
        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'name' => 'New Name'
        ]);
    }

    public function test_list_reservations_delegates_to_repository()
    {
        // Arrange
        $reservations = collect([
            (object)['id' => 1, 'status' => 'paid'],
            (object)['id' => 2, 'status' => 'paid'],
            (object)['id' => 3, 'status' => 'paid']
        ]);

        $this->mockRepo->shouldReceive('getAll')
            ->once()
            ->with('test', ['status' => 'paid'])
            ->andReturn($reservations);

        // Act
        $result = $this->service->listReservations('test', ['status' => 'paid']);

        // Assert
        $this->assertEquals($reservations, $result);
    }

    public function test_update_reservation_status_delegates_to_repository()
    {
        // Arrange
        $reservation = new Reservation();
        $reservation->id = 1;
        $reservation->status = 'paid';

        $this->mockRepo->shouldReceive('updateStatus')
            ->once()
            ->with(1, 'paid')
            ->andReturn($reservation);

        // Act
        $result = $this->service->updateReservationStatus(1, 'paid');

        // Assert
        $this->assertEquals($reservation, $result);
        $this->assertEquals('paid', $result->status);
    }

    public function test_get_reservation_delegates_to_repository()
    {
        // Arrange
        $reservation = new Reservation();
        $reservation->id = 1;

        $this->mockRepo->shouldReceive('getById')
            ->once()
            ->with(1)
            ->andReturn($reservation);

        // Act
        $result = $this->service->getReservation(1);

        // Assert
        $this->assertEquals($reservation, $result);
    }

    public function test_update_reservation()
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
        $reservation->id = 1;
        $reservation->client_id = $client->id;
        $reservation->trip_id = $trip->id;
        $reservation->payment_id = $payment->id;
        $reservation->status = 'not paid';
        $reservation->descripcion = 'Old description';
        $reservation->date = now()->toDateString();
        $reservation->save();

        $reservation->client = $client;
        $reservation->trip = $trip;
        $reservation->payment = $payment;

        $updateData = [
            'status' => 'paid',
            'descripcion' => 'New description',
            'client' => [
                'name' => 'Updated Name',
                'date_of_birth' => '1990-01-01',
                'nationality' => 'Chilena'
            ],
            'payment' => [
                'receipt' => 'new_receipt.jpg'
            ]
        ];

        // Mock para devolver la reserva con relaciones cargadas
        $this->mockRepo->shouldReceive('getById')
            ->once()
            ->with($reservation->id)
            ->andReturn($reservation);

        // Act
        $result = $this->service->updateReservation($reservation->id, $updateData);

        // Assert
        $this->assertEquals('paid', $result->status);
        $this->assertEquals('New description', $result->descripcion);
        $this->assertEquals('Updated Name', $client->fresh()->name);
    }

    public function test_delete_reservation()
    {
        // Arrange
        Storage::fake('s3');

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
        $payment->receipt = 'payments/receipt.jpg';
        $payment->save();

        $reservation = new Reservation();
        $reservation->id = 1;
        $reservation->client_id = $client->id;
        $reservation->trip_id = $trip->id;
        $reservation->payment_id = $payment->id;
        $reservation->date = now()->toDateString();

        $reservation->payment = $payment;

        $this->mockRepo->shouldReceive('getById')
            ->once()
            ->with(1)
            ->andReturn($reservation);

        $this->mockRepo->shouldReceive('delete')
            ->once()
            ->with(1)
            ->andReturn(true);

        // Act
        $result = $this->service->deleteReservation(1);

        // Assert
        $this->assertTrue($result);
    }
}
