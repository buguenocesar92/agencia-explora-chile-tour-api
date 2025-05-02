<?php

namespace App\Exports;

use App\Models\Reservation;
use Illuminate\Database\Eloquent\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

/**
 * Clase para exportar reservas a Excel
 *
 * @package App\Exports
 */
class ReservationsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $reservations;

    /**
     * Constructor que recibe las reservas a exportar
     *
     * @param Collection $reservations
     */
    public function __construct(Collection $reservations)
    {
        $this->reservations = $reservations;
    }

    /**
     * Devuelve la colección de reservas
     *
     * @return Collection
     */
    public function collection()
    {
        return $this->reservations;
    }

    /**
     * Define los encabezados del archivo Excel
     *
     * @return array
     */
    public function headings(): array
    {
        return [
            'ID',
            'Cliente',
            'RUT',
            'Email',
            'Teléfono',
            'Destino/Tour',
            'Fecha',
            'Estado',
            'Método de Pago',
            'Monto',
            'Comprobante'
        ];
    }

    /**
     * Mapea los datos de cada reserva
     *
     * @param mixed $reservation
     * @return array
     */
    public function map($reservation): array
    {
        $status = match($reservation->status) {
            'paid' => 'Pagado',
            'pending' => 'Pendiente',
            'cancelled' => 'Cancelado',
            default => $reservation->status
        };

        return [
            'id' => $reservation->id,
            'cliente' => $reservation->client->name ?? 'N/A',
            'rut' => $reservation->client->rut ?? 'N/A',
            'email' => $reservation->client->email ?? 'N/A',
            'telefono' => $reservation->client->phone ?? 'N/A',
            'destino' => $reservation->trip->tourTemplate->name ?? 'N/A',
            'fecha' => optional($reservation->trip)->date ?? $reservation->date,
            'estado' => $status,
            'metodo_pago' => $reservation->payment->payment_method ?? 'N/A',
            'monto' => $reservation->payment->amount ?? 'N/A',
            'comprobante' => $reservation->payment->receipt_url ?? 'No disponible'
        ];
    }

    /**
     * Almacena el archivo Excel en el disco especificado
     *
     * @param string $fileName
     * @param string $disk
     * @return void
     */
    public function store(string $fileName, string $disk = 'public'): void
    {
        Excel::store($this, $fileName, $disk);
    }
}
