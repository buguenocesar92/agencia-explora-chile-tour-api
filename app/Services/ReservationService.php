<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Trip;
use App\Models\Payment;
use App\Models\Reservation;
use Carbon\Carbon;
use App\Repositories\Contracts\ReservationRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Exports\ReservationsExport;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\ConfirmacionReserva;
use Illuminate\Support\Facades\Config;

/**
 * Servicio para gestionar las reservas
 *
 * @package App\Services
 */
class ReservationService
{
    private ReservationRepositoryInterface $reservationRepo;
    private WhatsAppService $whatsAppService;

    public function __construct(
        ReservationRepositoryInterface $reservationRepo,
        WhatsAppService $whatsAppService
    ) {
        $this->reservationRepo = $reservationRepo;
        $this->whatsAppService = $whatsAppService;
    }

    /**
     * Crea una nueva reserva
     *
     * @param array $data
     * @return Reservation
     */
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
                $this->updateClientData($client, $data['client']);
            } else {
                // Si ya existe y está activo, actualizar sus datos por si han cambiado
                $this->updateClientData($client, $data['client']);
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
                'status'    => 'pending', // Estado por defecto
            ];

            return $this->reservationRepo->create($reservationData);
        });
    }

    /**
     * Actualiza los datos del cliente verificando duplicidades de email
     *
     * @param Client $client
     * @param array $clientData
     * @return void
     */
    private function updateClientData(Client $client, array $clientData): void
    {
        // Verificar si el email ya existe en otro cliente
        $emailExists = Client::where('email', $clientData['email'])
            ->where('id', '!=', $client->id)
            ->exists();

        if ($emailExists) {
            // Si el email ya está en uso, no lo actualizamos
            unset($clientData['email']); // Eliminar el email de los datos a actualizar
        }

        // Actualizar datos del cliente
        $client->update($clientData);
    }

    /**
     * Lista todas las reservas con filtros opcionales
     *
     * @param string|null $search
     * @param array $filters
     * @param bool $withTrashed
     * @return mixed
     */
    public function listReservations(?string $search = null, array $filters = [], bool $withTrashed = false)
    {
        return $this->reservationRepo->getAll($search, $filters, $withTrashed);
    }

    /**
     * Actualiza el estado de una reserva
     *
     * @param int $id
     * @param string $status
     * @return Reservation
     */
    public function updateReservationStatus(int $id, string $status)
    {
        // Validar que el estado sea válido según la configuración
        if (!array_key_exists($status, Config::get('reservations.status', []))) {
            throw new \InvalidArgumentException("Estado de reserva no válido: {$status}");
        }

        $reservation = $this->reservationRepo->updateStatus($id, $status);
        $reservation->load('client', 'trip.tourTemplate');

        return $reservation;
    }

    /**
     * Obtiene una reserva por su ID
     *
     * @param int $id
     * @param bool $withTrashed
     * @return Reservation
     */
    public function getReservation(int $id, bool $withTrashed = false)
    {
        return $this->reservationRepo->getById($id, $withTrashed);
    }

    /**
     * Actualiza una reserva
     *
     * @param int $id
     * @param array $data
     * @return Reservation
     */
    public function updateReservation(int $id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            // Obtener la reserva
            $reservation = $this->reservationRepo->getById($id);

            // Actualizar campos directos de la reserva
            if (isset($data['status'])) {
                // Validar que el estado sea válido según la configuración
                if (!array_key_exists($data['status'], Config::get('reservations.status', []))) {
                    throw new \InvalidArgumentException("Estado de reserva no válido: {$data['status']}");
                }
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
                $this->updateClientData($reservation->client, $data['client']);
            }

            // Actualizar el viaje si se proporciona
            if (isset($data['trip']) && is_array($data['trip']) && $reservation->trip) {
                $reservation->trip->update($data['trip']);
            }

            // Actualizar el pago si se proporciona
            if (isset($data['payment']) && is_array($data['payment']) && $reservation->payment) {
                $this->updatePaymentData($reservation->payment, $data['payment']);
            }

            // Recargar la reserva con sus relaciones
            return $reservation->fresh(['client', 'trip.tourTemplate', 'payment']);
        });
    }

    /**
     * Actualiza los datos de un pago
     *
     * @param Payment $payment
     * @param array $paymentData
     * @return void
     */
    private function updatePaymentData(Payment $payment, array $paymentData): void
    {
        // Validar el método de pago si se proporciona
        if (isset($paymentData['payment_method'])) {
            $validMethods = array_keys(Config::get('reservations.payment_methods', []));
            if (!empty($validMethods) && !in_array($paymentData['payment_method'], $validMethods)) {
                throw new \InvalidArgumentException("Método de pago no válido: {$paymentData['payment_method']}");
            }
        }

        // Procesar nuevo comprobante de pago si se proporciona
        if (isset($paymentData['receipt']) && $paymentData['receipt'] instanceof UploadedFile) {
            // Eliminar archivo anterior si existe
            if ($payment->receipt) {
                Storage::disk('public')->delete($payment->receipt);
            }

            // Almacenar nuevo archivo
            $path = $paymentData['receipt']->store('payments', 'public');

            // Actualizar la ruta del archivo
            $paymentData['receipt'] = $path;
        }

        // Actualizar el modelo de pago
        $payment->update($paymentData);
    }

    /**
     * Elimina una reserva (soft delete)
     *
     * @param int $id
     * @return bool
     */
    public function deleteReservation(int $id)
    {
        return DB::transaction(function () use ($id) {
            return $this->reservationRepo->delete($id);
        });
    }

    /**
     * Restaura una reserva eliminada
     *
     * @param int $id
     * @return bool
     */
    public function restoreReservation(int $id)
    {
        return DB::transaction(function () use ($id) {
            return $this->reservationRepo->restore($id);
        });
    }

    /**
     * Elimina permanentemente una reserva
     *
     * @param int $id
     * @return bool
     */
    public function forceDeleteReservation(int $id)
    {
        return DB::transaction(function () use ($id) {
            return $this->reservationRepo->forceDelete($id);
        });
    }

    /**
     * Exporta las reservas a un archivo Excel
     *
     * @param array $filters
     * @return string URL del archivo generado
     */
    public function exportToExcel(array $filters = []): string
    {
        $reservations = $this->reservationRepo->getAllForExport($filters);
        $export = new ReservationsExport($reservations);

        $filenamePrefix = Config::get('reservations.export.filename_prefix', 'reservas_');
        $disk = Config::get('reservations.export.disk', 'public');

        $filename = $filenamePrefix . date('Y-m-d_H-i-s') . '.xlsx';
        $export->store($filename, $disk);

        return Storage::url($filename);
    }

    /**
     * Envía notificaciones cuando una reserva es marcada como pagada
     *
     * @param Reservation $reservation
     * @return void
     */
    public function sendNotificationsForPaidReservation(Reservation $reservation): void
    {
        if (!$reservation->client) {
            Log::warning('No se puede enviar notificación - Cliente no encontrado', [
                'reservation_id' => $reservation->id
            ]);
            return;
        }

        // Cargar relaciones necesarias si no están cargadas
        $reservation->load(['client', 'trip.tourTemplate']);

        $datos = [
            'nombre' => $reservation->client->name,
            'destino' => $reservation->trip->tourTemplate->name ?? 'Tour',
            'fecha' => $reservation->trip->date ?? date('Y-m-d'),
        ];

        // Enviar notificaciones por diferentes canales
        if (Config::get('reservations.notifications.email.enabled', true)) {
            $this->sendEmailNotification($reservation->client, $datos);
        }

        if (Config::get('reservations.notifications.whatsapp.enabled', true)) {
            $this->sendWhatsAppNotification($reservation->client, $datos);
        }
    }

    /**
     * Envía una notificación por correo electrónico
     *
     * @param Client $client
     * @param array $datos
     * @return void
     */
    private function sendEmailNotification(Client $client, array $datos): void
    {
        if (empty($client->email)) {
            Log::warning('No se puede enviar email - Email no disponible', [
                'client_id' => $client->id
            ]);
            return;
        }

        try {
            Mail::to($client->email)->send(new ConfirmacionReserva($datos));
            Log::info('Email de confirmación enviado', [
                'client_email' => $client->email
            ]);
        } catch (\Exception $e) {
            Log::error('Error al enviar email de confirmación', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Envía una notificación por WhatsApp
     *
     * @param Client $client
     * @param array $datos
     * @return void
     */
    private function sendWhatsAppNotification(Client $client, array $datos): void
    {
        if (empty($client->phone)) {
            Log::warning('No se puede enviar WhatsApp - Teléfono no disponible', [
                'client_id' => $client->id
            ]);
            return;
        }

        try {
            $result = $this->whatsAppService->sendPaymentConfirmation($client->phone, $datos);
            Log::info('WhatsApp de confirmación: ' . ($result ? 'Enviado' : 'No enviado'), [
                'client_phone' => $client->phone,
                'success' => $result
            ]);
        } catch (\Exception $e) {
            Log::error('Error al enviar WhatsApp de confirmación', [
                'error' => $e->getMessage()
            ]);
        }
    }
}
