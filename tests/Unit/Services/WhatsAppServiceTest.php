<?php

namespace Tests\Unit\Services;

use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class WhatsAppServiceTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        // Mock de Log para evitar logs durante las pruebas
        Log::shouldReceive('info')->byDefault();
        Log::shouldReceive('error')->byDefault();
        Log::shouldReceive('warning')->byDefault();
    }

    public function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }

    public function test_token_validation_returns_false_if_token_is_empty()
    {
        // Crear una instancia real del servicio
        $service = new WhatsAppService();

        // Establecer token como vacío usando Reflection
        $reflection = new \ReflectionClass($service);
        $property = $reflection->getProperty('token');
        $property->setAccessible(true);
        $property->setValue($service, '');

        // Datos para el mensaje
        $phone = '56912345678';
        $data = [
            'nombre_cliente' => 'Test Client',
            'destino' => 'Test Destination',
            'fecha' => '2024-10-01'
        ];

        // Act - Intentar enviar mensaje sin token
        $result = $service->sendPaymentConfirmation($phone, $data);

        // Assert - Debe fallar porque no hay token
        $this->assertFalse($result);
    }

    public function test_phone_number_id_validation_returns_false_if_empty()
    {
        // Crear una instancia real del servicio
        $service = new WhatsAppService();

        // Establecer phoneNumberId como vacío usando Reflection
        $reflection = new \ReflectionClass($service);
        $tokenProperty = $reflection->getProperty('token');
        $tokenProperty->setAccessible(true);
        $tokenProperty->setValue($service, 'test_token');

        $phoneProperty = $reflection->getProperty('phoneNumberId');
        $phoneProperty->setAccessible(true);
        $phoneProperty->setValue($service, '');

        // Act - Intentar enviar mensaje sin phoneNumberId
        $result = $service->sendPaymentConfirmation('56912345678', []);

        // Assert - Debe fallar porque no hay phoneNumberId
        $this->assertFalse($result);
    }
}
