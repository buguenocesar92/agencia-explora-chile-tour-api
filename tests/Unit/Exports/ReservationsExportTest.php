<?php

namespace Tests\Unit\Exports;

use App\Exports\ReservationsExport;
use App\Models\Client;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Trip;
use App\Models\TourTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class ReservationsExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_generates_xlsx_file()
    {
        // Arrange
        // Crear datos de prueba
        $client = Client::factory()->create([
            'name' => 'Test Client',
            'email' => 'test@example.com',
            'phone' => '123456789',
            'rut' => '12345678-9'
        ]);

        $tourTemplate = TourTemplate::factory()->create([
            'name' => 'Test Tour',
            'description' => 'Test Description',
            'destination' => 'Test Destination'
        ]);

        $trip = Trip::factory()->create([
            'tour_template_id' => $tourTemplate->id,
            'departure_date' => '2024-12-01',
            'return_date' => '2024-12-10'
        ]);

        // Crear Payment directamente sin usar factory
        $payment = new Payment();
        $payment->receipt = 'payments/receipt.jpg';
        $payment->save();

        $reservation = Reservation::factory()->create([
            'client_id' => $client->id,
            'trip_id' => $trip->id,
            'payment_id' => $payment->id,
            'date' => now()->toDateString(),
            'status' => 'paid'
        ]);

        $exporter = new ReservationsExport();

        // Act
        $filePath = $exporter->export();

        // Assert
        $this->assertFileExists($filePath);

        // Verificar que el archivo es un XLSX válido
        $spreadsheet = IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();

        // Verificar encabezados
        $this->assertEquals('ID', $worksheet->getCell('A1')->getValue());
        $this->assertEquals('Cliente', $worksheet->getCell('B1')->getValue());
        $this->assertEquals('RUT', $worksheet->getCell('C1')->getValue());
        $this->assertEquals('Email', $worksheet->getCell('D1')->getValue());
        $this->assertEquals('Teléfono', $worksheet->getCell('E1')->getValue());
        $this->assertEquals('Destino/Tour', $worksheet->getCell('F1')->getValue());
        $this->assertEquals('Fecha', $worksheet->getCell('G1')->getValue());
        $this->assertEquals('Estado', $worksheet->getCell('H1')->getValue());
        $this->assertEquals('Comprobante de Pago', $worksheet->getCell('I1')->getValue());

        // Verificar contenido para la primera reserva
        $this->assertEquals($reservation->id, $worksheet->getCell('A2')->getValue());
        $this->assertEquals('Test Client', $worksheet->getCell('B2')->getValue());
        $this->assertEquals('12345678-9', $worksheet->getCell('C2')->getValue());
        $this->assertEquals('test@example.com', $worksheet->getCell('D2')->getValue());
        $this->assertEquals('123456789', $worksheet->getCell('E2')->getValue());
        $this->assertEquals('Test Tour', $worksheet->getCell('F2')->getValue());
        $this->assertEquals('Pagado', $worksheet->getCell('H2')->getValue());

        // Limpiar después de la prueba
        @unlink($filePath);
    }

    public function test_export_applies_filters()
    {
        // Arrange
        // Crear varios clientes y reservas con diferentes estados
        $client1 = Client::factory()->create(['name' => 'Client 1']);
        $client2 = Client::factory()->create(['name' => 'Client 2']);

        $tourTemplate = TourTemplate::factory()->create();
        $trip1 = Trip::factory()->create(['tour_template_id' => $tourTemplate->id]);
        $trip2 = Trip::factory()->create(['tour_template_id' => $tourTemplate->id]);

        // Crear Payment directamente sin usar factory
        $payment = new Payment();
        $payment->receipt = 'payments/receipt.jpg';
        $payment->save();

        // Reserva pagada para el cliente 1
        Reservation::factory()->create([
            'client_id' => $client1->id,
            'trip_id' => $trip1->id,
            'payment_id' => $payment->id,
            'status' => 'paid'
        ]);

        // Reserva no pagada para el cliente 2
        Reservation::factory()->create([
            'client_id' => $client2->id,
            'trip_id' => $trip2->id,
            'payment_id' => $payment->id,
            'status' => 'not paid'
        ]);

        // Exportador con filtro de estado = pagado
        $exporter = new ReservationsExport(['status' => 'paid']);

        // Act
        $filePath = $exporter->export();

        // Assert
        $spreadsheet = IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();

        // Debería haber solo 1 reserva (encabezado + 1 fila de datos)
        $this->assertEquals('Pagado', $worksheet->getCell('H2')->getValue());
        // No debe existir una tercera fila (solo debe haber el encabezado y una reserva)
        $this->assertEquals(null, $worksheet->getCell('B3')->getValue());

        // Limpiar
        @unlink($filePath);
    }
}
