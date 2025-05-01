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

    protected function setUp(): void
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

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Invoca un método privado del controlador para pruebas
     */
    private function invokePrivateMethod(string $methodName, array $parameters = [])
    {
        $reflection = new ReflectionClass(ReservationController::class);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($this->controller, $parameters);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('filterDataProvider')]
    public function test_extract_filters_from_request(array $requestParams, array $expectedFilters): void
    {
        // Arrange
        $request = new Request($requestParams);

        // Act
        $filters = $this->invokePrivateMethod('extractFiltersFromRequest', [$request]);

        // Assert
        $this->assertEquals($expectedFilters, $filters);
    }

    /**
     * Proveedor de datos para test_extract_filters_from_request
     */
    public static function filterDataProvider(): array
    {
        return [
            'sin filtros' => [
                [],  // requestParams
                []   // expectedFilters
            ],
            'solo tour_id' => [
                ['tour_id' => 123],
                ['tour_id' => 123]
            ],
            'solo status' => [
                ['status' => 'paid'],
                ['status' => 'paid']
            ],
            'solo date' => [
                ['date' => '2023-01-01'],
                ['date' => '2023-01-01']
            ],
            'múltiples filtros' => [
                [
                    'tour_id' => 123,
                    'status' => 'paid',
                    'date' => '2023-01-01',
                    'other_param' => 'value'  // Parámetro no filtrable
                ],
                [
                    'tour_id' => 123,
                    'status' => 'paid',
                    'date' => '2023-01-01'
                ]
            ]
        ];
    }

    /**
     * Test para sendEmailNotification cuando el cliente tiene email
     */
    public function test_send_email_notification_sends_email_when_client_has_email(): void
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
    public function test_send_email_notification_does_nothing_when_client_has_no_email(): void
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
