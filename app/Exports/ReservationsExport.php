<?php

namespace App\Exports;

use App\Models\Reservation;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReservationsExport
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    /**
     * Exporta las reservas a un archivo XLSX
     *
     * @return string Ruta temporal del archivo
     */
    public function export(): string
    {
        // Crear una nueva instancia de Spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Configurar los encabezados
        $sheet->setCellValue('A1', 'ID');
        $sheet->setCellValue('B1', 'Cliente');
        $sheet->setCellValue('C1', 'RUT');
        $sheet->setCellValue('D1', 'Email');
        $sheet->setCellValue('E1', 'Teléfono');
        $sheet->setCellValue('F1', 'Destino/Tour');
        $sheet->setCellValue('G1', 'Fecha');
        $sheet->setCellValue('H1', 'Estado');
        $sheet->setCellValue('I1', 'Monto');
        $sheet->setCellValue('J1', 'Método de Pago');
        $sheet->setCellValue('K1', 'Fecha de Pago');

        // Consultar las reservas con sus relaciones
        $query = Reservation::with(['client', 'trip.tourTemplate', 'payment']);

        // Aplicar filtros si existen
        if (isset($this->filters['tour_id'])) {
            $query->where('trip_id', $this->filters['tour_id']);
        }

        if (isset($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        if (isset($this->filters['date'])) {
            $query->whereDate('date', $this->filters['date']);
        }

        $reservations = $query->get();

        // Llenar datos
        $row = 2;
        foreach ($reservations as $reservation) {
            $sheet->setCellValue('A' . $row, $reservation->id);
            $sheet->setCellValue('B' . $row, $reservation->client->name ?? 'N/A');
            $sheet->setCellValue('C' . $row, $reservation->client->rut ?? 'N/A');
            $sheet->setCellValue('D' . $row, $reservation->client->email ?? 'N/A');
            $sheet->setCellValue('E' . $row, $reservation->client->phone ?? 'N/A');
            $sheet->setCellValue('F' . $row, $reservation->trip->tourTemplate->name ?? 'N/A');
            $sheet->setCellValue('G' . $row, $reservation->trip->departure_date ?? $reservation->date);
            $sheet->setCellValue('H' . $row, $reservation->status);
            $sheet->setCellValue('I' . $row, $reservation->payment->amount ?? 'N/A');
            $sheet->setCellValue('J' . $row, $reservation->payment->method ?? 'N/A');
            $sheet->setCellValue('K' . $row, $reservation->payment->payment_date ?? 'N/A');
            $row++;
        }

        // Autoajustar columnas
        foreach (range('A', 'K') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        // Crear un archivo temporal para guardar el XLSX
        $fileName = 'reservas_' . Carbon::now()->format('Y-m-d_His') . '.xlsx';
        $tempPath = storage_path('app/public/temp/' . $fileName);

        // Asegurarse de que el directorio exista
        if (!file_exists(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0755, true);
        }

        // Guardar el archivo
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        return $tempPath;
    }
}
