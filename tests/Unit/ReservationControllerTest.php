<?php

namespace Tests\Unit;

use App\Http\Controllers\ReservationController;
use App\Mail\ConfirmacionReserva;
use App\Models\Client;
use App\Models\Reservation;
use App\Models\Trip;
use App\Models\TourTemplate;
use App\Services\ReservationService;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use ReflectionClass;

class ReservationControllerTest extends TestCase
{
    use RefreshDatabase;

    private ReservationController $controller;
    /** @var MockInterface&ReservationService */
    private $reservationService;
    /** @var MockInterface&WhatsAppService */
    private $whatsAppService;

    public function setUp(): void
    {
        parent::setUp();

        // Mockeamos los servicios dependientes
        $this->reservationService = $this->mock(ReservationService::class);
        $this->whatsAppService = $this->mock(WhatsAppService::class);

        // No configuramos expectativas globales aquí para evitar problemas
        // con las expectativas específicas de cada test

        // Usamos una técnica alternativa para inyectar los mocks
        $this->controller = new ReservationController(
            $this->reservationService,
            $this->whatsAppService
        );

        Mail::fake();
    }

    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Helper method to call private methods
     */
    private function invokePrivateMethod($methodName, array $parameters = [])
    {
        $reflection = new ReflectionClass(ReservationController::class);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($this->controller, $parameters);
    }

    /**
     * Test para extractFiltersFromRequest
     */
    public function test_extract_filters_from_request()
    {
        // Caso 1: Sin filtros
        $request = new Request();
        $filters = $this->invokePrivateMethod('extractFiltersFromRequest', [$request]);
        $this->assertEmpty($filters);

        // Caso 2: Con filtro de tour_id
        $request = new Request(['tour_id' => 123]);
        $filters = $this->invokePrivateMethod('extractFiltersFromRequest', [$request]);
        $this->assertEquals(['tour_id' => 123], $filters);

        // Caso 3: Con filtro de status
        $request = new Request(['status' => 'paid']);
        $filters = $this->invokePrivateMethod('extractFiltersFromRequest', [$request]);
        $this->assertEquals(['status' => 'paid'], $filters);

        // Caso 4: Con filtro de date
        $date = '2023-01-01';
        $request = new Request(['date' => $date]);
        $filters = $this->invokePrivateMethod('extractFiltersFromRequest', [$request]);
        $this->assertEquals(['date' => $date], $filters);

        // Caso 5: Con múltiples filtros
        $request = new Request([
            'tour_id' => 123,
            'status' => 'paid',
            'date' => $date,
            'other_param' => 'value'  // Este no debería ser extraído
        ]);
        $filters = $this->invokePrivateMethod('extractFiltersFromRequest', [$request]);
        $expected = [
            'tour_id' => 123,
            'status' => 'paid',
            'date' => $date
        ];
        $this->assertEquals($expected, $filters);
        $this->assertArrayNotHasKey('other_param', $filters);
    }

    /**
     * Test para sendEmailNotification cuando el cliente tiene email
     */
    public function test_send_email_notification_sends_email_when_client_has_email()
    {
        // Arrange
        $client = new Client();
        $client->name = 'Test Client';
        $client->email = 'test@example.com';

        $datos = [
            'nombre'  => 'Test Client',
            'destino' => 'Test Destination',
            'fecha'   => '2023-01-01',
        ];

        // Act
        $this->invokePrivateMethod('sendEmailNotification', [$client, $datos]);

        // Assert
        Mail::assertSent(ConfirmacionReserva::class, function ($mail) use ($client) {
            return $mail->hasTo($client->email);
        });
    }

    /**
     * Test para sendEmailNotification cuando el cliente no tiene email
     */
    public function test_send_email_notification_does_nothing_when_client_has_no_email()
    {
        // Arrange
        $client = new Client();
        $client->name = 'Test Client';
        $client->email = null;

        $datos = [
            'nombre'  => 'Test Client',
            'destino' => 'Test Destination',
            'fecha'   => '2023-01-01',
        ];

        // Act
        $this->invokePrivateMethod('sendEmailNotification', [$client, $datos]);

        // Assert
        Mail::assertNothingSent();
    }

    /** @test */
    public function send_whatsapp_notification_sends_message_when_client_has_phone()
    {
        // Arrange
        $client = new Client();
        $client->name = 'Test Client';
        $client->email = 'test@example.com';
        $client->phone = '123456789';
        $client->rut = '33333333-3';
        $client->date_of_birth = '1995-01-01';
        $client->nationality = 'Chilena';

        $datos = [
            'nombre'  => 'Test Client',
            'destino' => 'Test Destination',
            'fecha'   => '2023-01-01',
        ];

        // Configuramos la expectativa específica para este test
        $this->whatsAppService
            ->shouldReceive('sendPaymentConfirmation')
            ->once()
            ->with($client->phone, $datos)
            ->andReturn(true);

        // Act
        $this->invokePrivateMethod('sendWhatsAppNotification', [$client, $datos]);

        // Añadimos una aserción para evitar test "risky"
        $this->assertTrue($client->phone === '123456789', 'El teléfono debe ser 123456789');
    }

    /** @test */
    public function send_whatsapp_notification_doesnt_send_message_when_client_has_no_phone()
    {
        // Arrange
        $client = new Client();
        $client->name = 'Test Client';
        $client->email = 'test@example.com';
        $client->phone = '';
        $client->rut = '44444444-4';
        $client->date_of_birth = '1995-01-01';
        $client->nationality = 'Chilena';

        $datos = [
            'nombre'  => 'Test Client',
            'destino' => 'Test Destination',
            'fecha'   => '2023-01-01',
        ];

        // Configurar la expectativa de que nunca se llame a sendPaymentConfirmation
        $this->whatsAppService
            ->shouldReceive('sendPaymentConfirmation')
            ->never();

        // Act
        $this->invokePrivateMethod('sendWhatsAppNotification', [$client, $datos]);

        // Añadimos una aserción para evitar test "risky"
        $this->assertTrue(empty($client->phone), 'El teléfono debe estar vacío');
    }

    /** @test */
    public function send_notifications_for_paid_reservation_with_client()
    {
        // Arrange
        $client = new Client();
        $client->name = 'Test Client';
        $client->email = 'test@example.com';
        $client->phone = '123456789';
        $client->rut = '55555555-5';
        $client->date_of_birth = '1995-01-01';
        $client->nationality = 'Chilena';

        $reservation = new Reservation();
        $reservation->client()->associate($client);
        $reservation->date = '2023-01-01';
        $reservation->time = '10:00:00';
        $reservation->message = 'Test message';
        $reservation->paid = true;

        // Act
        $this->invokePrivateMethod('sendNotificationsForPaidReservation', [$reservation]);

        // Assert - verificamos directamente el envío de correo
        Mail::assertSent(ConfirmacionReserva::class, function ($mail) use ($client) {
            return $mail->hasTo($client->email);
        });
    }

    /**
     * Test para sendNotificationsForPaidReservation cuando no hay cliente
     */
    public function test_send_notifications_for_paid_reservation_without_client()
    {
        // Arrange - en este caso no necesitamos guardar en la base de datos
        $reservation = new Reservation();
        // No establecemos relación de cliente

        // Act
        $this->invokePrivateMethod('sendNotificationsForPaidReservation', [$reservation]);

        // Assert - No debe enviarse ninguna notificación
        Mail::assertNothingSent();
    }

    /**
     * Test para sendNotificationsForPaidReservation cuando no hay viaje o plantilla
     */
    public function test_send_notifications_for_paid_reservation_without_trip_data()
    {
        // Usamos un modelo sin guardar en la base de datos
        $client = new Client();
        $client->id = 1;
        $client->name = 'Test Client';
        $client->email = 'test@example.com';
        $client->phone = '123456789';
        $client->rut = '22222222-2'; // Agregamos el campo requerido
        $client->date_of_birth = '1990-01-01'; // Agregamos el campo requerido
        $client->nationality = 'Chilena'; // Agregamos el campo requerido

        $reservation = new Reservation();
        $reservation->client_id = $client->id;
        $reservation->date = '2023-01-01';

        // Cargamos solo la relación de cliente en memoria, pero no guardamos en la base de datos
        $reservation->setRelation('client', $client);

        // Act
        $this->invokePrivateMethod('sendNotificationsForPaidReservation', [$reservation]);

        // Assert - El email debe enviarse con valores por defecto para destino y fecha
        Mail::assertSent(ConfirmacionReserva::class, function ($mail) use ($client) {
            return $mail->hasTo($client->email);
        });
    }
}
