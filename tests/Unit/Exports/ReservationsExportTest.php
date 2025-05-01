<?php

namespace Tests\Unit\Exports;

use App\Exports\ReservationsExport;
use App\Models\Client;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Trip;
use App\Models\TourTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Collection;
use Tests\TestCase;
use Illuminate\Support\Facades\Config;
use Maatwebsite\Excel\Facades\Excel;

class ReservationsExportTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Preparar datos de prueba
     */
    private function setupTestData($rut = null, $status = 'paid')
    {
        // Crear cliente con RUT único si se proporciona
        $client = new Client();
        $client->name = 'Test Client';
        $client->email = 'test' . rand(1000, 9999) . '@example.com'; // Email único
        $client->phone = '123456789';
        $client->rut = $rut ?? '123' . rand(100000, 999999) . '-' . rand(0, 9); // RUT aleatorio
        $client->date_of_birth = '1990-01-01';
        $client->nationality = 'Chilena';
        $client->save();

        // Crear tour
        $tourTemplate = new TourTemplate();
        $tourTemplate->name = 'Test Tour';
        $tourTemplate->description = 'Test Description';
        $tourTemplate->destination = 'Test Destination';
        $tourTemplate->save();

        // Crear viaje
        $trip = new Trip();
        $trip->tour_template_id = $tourTemplate->id;
        $trip->departure_date = '2024-12-01';
        $trip->return_date = '2024-12-10';
        $trip->save();

        // Crear pago
        $payment = new Payment();
        $payment->receipt = 'receipt.jpg';
        $payment->save();

        // Crear reserva
        $reservation = new Reservation();
        $reservation->client_id = $client->id;
        $reservation->trip_id = $trip->id;
        $reservation->payment_id = $payment->id;
        $reservation->date = now()->toDateString();
        $reservation->status = $status;
        $reservation->save();

        return $reservation;
    }

    public function test_export_generates_xlsx_file()
    {
        // Simular exportación sin generar archivo real
        Excel::fake();

        // Preparar datos
        $reservation = $this->setupTestData();
        $reservations = Reservation::with(['client', 'trip.tourTemplate', 'payment'])->get();

        // Crear la exportación
        $export = new ReservationsExport($reservations);

        // Nombre de archivo de prueba
        $filename = 'test_export.xlsx';

        // Exportar
        $export->store($filename, 'public');

        // Verificar que se llamó a Excel::store
        Excel::assertStored($filename, 'public');
    }

    public function test_export_applies_filters()
    {
        // Simular exportación
        Excel::fake();

        // Crear dos reservas con estados diferentes
        $this->setupTestData(null, 'paid');

        // Crear otra reserva no pagada
        $this->setupTestData(null, 'not paid');

        // Obtener solo las reservas pagadas
        $paidReservations = Reservation::where('status', 'paid')
            ->with(['client', 'trip.tourTemplate', 'payment'])
            ->get();

        // Crear la exportación con filtro
        $export = new ReservationsExport($paidReservations);

        // Exportar
        $filename = 'filtered_export.xlsx';
        $export->store($filename, 'public');

        // Verificar que se exportó correctamente
        Excel::assertStored($filename, 'public');

        // Verificar que la cantidad de reservas exportadas coincide con las filtradas
        $this->assertEquals(1, $paidReservations->count());
    }

    /**
     * Test para verificar que el método map funciona correctamente
     */
    public function test_map_method_formats_data_correctly()
    {
        // Crear datos de prueba
        $reservation = $this->setupTestData();

        // Obtener reserva con relaciones
        $reservation = Reservation::with(['client', 'trip.tourTemplate', 'payment'])->first();

        // Crear exportación con Eloquent Collection
        $reservationsCollection = new Collection([$reservation]);
        $export = new ReservationsExport($reservationsCollection);

        // Obtener datos mapeados
        $mappedData = $export->map($reservation);

        // Verificar que los datos se hayan mapeado correctamente
        $this->assertEquals($reservation->id, $mappedData['id']);
        $this->assertEquals($reservation->client->name, $mappedData['cliente']);
        $this->assertEquals($reservation->client->rut, $mappedData['rut']);
        $this->assertEquals($reservation->client->email, $mappedData['email']);
        $this->assertEquals($reservation->client->phone, $mappedData['telefono']);
        $this->assertEquals($reservation->trip->tourTemplate->name, $mappedData['destino']);
        $this->assertEquals('Pagado', $mappedData['estado']); // Verifica la traducción del estado
    }
}
