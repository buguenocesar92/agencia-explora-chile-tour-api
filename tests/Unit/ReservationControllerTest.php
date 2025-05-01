<?php

namespace Tests\Unit;

use App\Http\Controllers\ReservationController;
use App\Mail\ConfirmacionReserva;
use App\Models\Client;
use App\Models\Reservation;
use App\Models\Trip;
use App\Models\TourTemplate;
use App\Services\ReservationService;
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

    public function setUp(): void
    {
        parent::setUp();

        // Mockeamos los servicios dependientes
        $this->reservationService = $this->mock(ReservationService::class);

        // Usamos una técnica alternativa para inyectar los mocks
        $this->controller = new ReservationController(
            $this->reservationService
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
}
