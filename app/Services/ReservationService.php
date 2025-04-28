<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Trip;
use App\Models\Payment;
use Carbon\Carbon;
use App\Repositories\Contracts\ReservationRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Exports\ReservationsExport;

class ReservationService
{
    private ReservationRepositoryInterface $reservationRepo;

    public function __construct(ReservationRepositoryInterface $reservationRepo)
    {
        $this->reservationRepo = $reservationRepo;
    }
    public function createReservation(array $data)
    {
        return DB::transaction(function () use ($data) {
            // Buscar si ya existe un cliente con este RUT (incluyendo clientes eliminados)
            $client = Client::withTrashed()->where('rut', $data['client']['rut'])->first();

            if (!$client) {
                // Antes de crear, verificar si el email ya existe
                $emailExists = Client::where('email', $data['client']['email'])->exists();
                if ($emailExists) {
                    // Si el email ya está en uso, generamos un email único temporal
                    $data['client']['email'] = $data['client']['email'] . '_' . uniqid();
                }

                // Si no existe, crear un nuevo cliente
                $client = Client::create($data['client']);
            } else if ($client->trashed()) {
                // Si el cliente existe pero está eliminado, restaurarlo
                $client->restore();

                // Verificar si el email ya existe en otro cliente
                $emailExists = Client::where('email', $data['client']['email'])
                    ->where('id', '!=', $client->id)
                    ->exists();

                if ($emailExists) {
                    // Si el email ya está en uso, no lo actualizamos
                    $clientData = $data['client'];
                    unset($clientData['email']); // Eliminar el email de los datos a actualizar
                    $client->update($clientData);
                } else {
                    // Si el email no está en uso, actualizamos todos los datos
                    $client->update($data['client']);
                }
            } else {
                // Si ya existe y está activo, actualizar sus datos por si han cambiado
                // Verificar si el email ya existe en otro cliente
                $emailExists = Client::where('email', $data['client']['email'])
                    ->where('id', '!=', $client->id)
                    ->exists();

                if ($emailExists) {
                    // Si el email ya está en uso, no lo actualizamos
                    $clientData = $data['client'];
                    unset($clientData['email']); // Eliminar el email de los datos a actualizar
                    $client->update($clientData);
                } else {
                    // Si el email no está en uso, actualizamos todos los datos
                    $client->update($data['client']);
                }
            }

            // Obtener el viaje programado existente usando el ID que envía el wizard
            $trip = Trip::findOrFail($data['trip']['trip_date_id']);

            // Procesar el pago:
            if (empty($data['payment']['payment_date'])) {
                $data['payment']['payment_date'] = Carbon::now()->toDateString();
            }

            if (
                isset($data['payment']['receipt']) &&
                $data['payment']['receipt'] instanceof UploadedFile
            ) {
                $path = $data['payment']['receipt']->store('payments', 'public');
                $data['payment']['receipt'] = $path;
            }

            // Crear el pago
            $payment = Payment::create($data['payment']);

            // Preparar los datos para la reserva
            $reservationData = [
                'client_id' => $client->id,
                'trip_id'   => $trip->id,
                'payment_id'=> $payment->id,
                'date'      => Carbon::now()->toDateString(),
            ];

            return $this->reservationRepo->create($reservationData);
        });
    }


    public function listReservations(?string $search = null, array $filters = [], bool $withTrashed = false)
    {
        return $this->reservationRepo->getAll($search, $filters, $withTrashed);
    }

    // Método para actualizar el status de una reserva
    public function updateReservationStatus(int $id, string $status)
    {
        return $this->reservationRepo->updateStatus($id, $status);
    }

    public function getReservation(int $id, bool $withTrashed = false)
    {
        return $this->reservationRepo->getById($id, $withTrashed);
    }

    public function updateReservation(int $id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            // Obtener la reserva
            $reservation = $this->reservationRepo->getById($id);

            // Actualizar campos directos de la reserva
            if (isset($data['status'])) {
                $reservation->status = $data['status'];
            }
            if (isset($data['description'])) {
                $reservation->description = $data['description'];
            }
            if (isset($data['descripcion'])) {
                $reservation->descripcion = $data['descripcion'];
            }
            if (isset($data['date'])) {
                $reservation->date = $data['date'];
            }

            // Guardar la reserva
            $reservation->save();

            // Actualizar el cliente si se proporciona
            if (isset($data['client']) && is_array($data['client']) && $reservation->client) {
                $reservation->client->update($data['client']);
            }

            // Actualizar el viaje si se proporciona
            if (isset($data['trip']) && is_array($data['trip']) && $reservation->trip) {
                $reservation->trip->update($data['trip']);
            }

            // Actualizar el pago si se proporciona
            if (isset($data['payment']) && is_array($data['payment']) && $reservation->payment) {
                // Procesar nuevo comprobante de pago si se proporciona
                if (isset($data['payment']['receipt']) && $data['payment']['receipt'] instanceof UploadedFile) {
                    // Eliminar archivo anterior si existe
                    if ($reservation->payment->receipt) {
                        Storage::disk('public')->delete($reservation->payment->receipt);
                    }

                    // Almacenar nuevo archivo
                    $path = $data['payment']['receipt']->store('payments', 'public');

                    // Actualizar la ruta del archivo
                    $data['payment']['receipt'] = $path;
                }

                // Actualizar el modelo de pago
                $reservation->payment->update($data['payment']);
            }

            // Recargar la reserva con sus relaciones
            return $reservation->fresh(['client', 'trip', 'payment']);
        });
    }

    /**
     * Elimina una reserva (soft delete)
     */
    public function deleteReservation(int $id)
    {
        return DB::transaction(function () use ($id) {
            // La eliminación del pago ya se maneja en el repositorio
            return $this->reservationRepo->delete($id);
        });
    }

    /**
     * Restaura una reserva eliminada
     */
    public function restoreReservation(int $id)
    {
        return DB::transaction(function () use ($id) {
            return $this->reservationRepo->restore($id);
        });
    }

    /**
     * Elimina permanentemente una reserva
     */
    public function forceDeleteReservation(int $id)
    {
        return DB::transaction(function () use ($id) {
            return $this->reservationRepo->forceDelete($id);
        });
    }

    /**
     * Exporta reservas a un archivo XLSX
     *
     * @param array $filters Filtros a aplicar a las reservas (tour_id, status, date)
     * @return string URL del archivo generado
     */
    public function exportToExcel(array $filters = []): string
    {
        // Crear el exportador con los filtros
        $exporter = new ReservationsExport($filters);

        // Obtener la ruta del archivo generado
        $filePath = $exporter->export();

        // Verificar si el archivo existe
        if (!file_exists($filePath)) {
            throw new \Exception('Error al generar el archivo Excel');
        }

        // Generar nombre único para el archivo
        $fileName = 'reservas_' . Carbon::now()->format('Y-m-d_His') . '.xlsx';
        $localPath = 'exports/' . $fileName;

        // Asegurarse de que la carpeta de destino exista
        $exportDir = public_path('storage_files/exports');
        if (!file_exists($exportDir)) {
            if (!mkdir($exportDir, 0755, true)) {
                throw new \Exception('No se pudo crear el directorio de exportación');
            }
        }

        // Verificar permisos de escritura
        if (!is_writable($exportDir)) {
            chmod($exportDir, 0755);
        }

        try {
            // Usar copy directo en lugar de Storage para mayor control
            copy($filePath, public_path('storage_files/' . $localPath));

            // Eliminar el archivo temporal
            @unlink($filePath);

            // Generar URL para acceder al archivo
            $url = url('storage_files/' . $localPath);

            return $url;
        } catch (\Exception $e) {
            throw new \Exception('Error al guardar el archivo: ' . $e->getMessage());
        }
    }
}
