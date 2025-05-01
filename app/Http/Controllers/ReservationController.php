<?php

namespace App\Http\Controllers;

use App\Http\Requests\Reservation\StoreReservationRequest;
use App\Http\Requests\Reservation\UpdateReservationRequest;
use App\Http\Requests\Reservation\UpdateReservationStatusRequest;
use App\Services\ReservationService;
use Illuminate\Http\JsonResponse;
use App\Mail\ConfirmacionReserva;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

/**
 * Controlador para gestionar las reservas
 *
 * @package App\Http\Controllers
 */
class ReservationController extends Controller
{
    private ReservationService $reservationService;

    public function __construct(ReservationService $reservationService)
    {
        $this->reservationService = $reservationService;
    }

    /**
     * Lista todas las reservas con filtros opcionales
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $this->extractFiltersFromRequest($request);
        $reservations = $this->reservationService->listReservations(
            $request->input('search'),
            $filters,
            $request->boolean('with_trashed', false)
        );

        return response()->json(['reservations' => $reservations]);
    }

    /**
     * Crea una nueva reserva
     *
     * @param StoreReservationRequest $request
     * @return JsonResponse
     */
    public function store(StoreReservationRequest $request): JsonResponse
    {
        $reservation = $this->reservationService->createReservation($request->validated());

        return response()->json([
            'message'     => 'Reserva completada correctamente',
            'reservation' => $reservation,
        ], 201);
    }

    /**
     * Actualiza el estado de una reserva
     *
     * @param UpdateReservationStatusRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function updateStatus(UpdateReservationStatusRequest $request, int $id): JsonResponse
    {
        $status = $request->input('status', 'paid');

        Log::info('ReservationController::updateStatus - Iniciando actualización', [
            'id' => $id,
            'status' => $status
        ]);

        $reservation = $this->reservationService->updateReservationStatus($id, $status);

        // Si la reserva fue marcada como pagada, enviar notificaciones
        if ($status === 'paid') {
            $this->reservationService->sendNotificationsForPaidReservation($reservation);
        }

        return response()->json([
            'message'     => 'Estado de reserva actualizado correctamente',
            'reservation' => $reservation,
        ]);
    }

    /**
     * Muestra una reserva específica
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $withTrashed = request()->boolean('with_trashed', false);
        $reservation = $this->reservationService->getReservation($id, $withTrashed);

        return response()->json(['reservation' => $reservation]);
    }

    /**
     * Actualiza una reserva
     *
     * @param UpdateReservationRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(UpdateReservationRequest $request, int $id): JsonResponse
    {
        $reservation = $this->reservationService->updateReservation($id, $request->validated());

        return response()->json([
            'message'     => 'Reserva actualizada correctamente',
            'reservation' => $reservation,
        ]);
    }

    /**
     * Elimina lógicamente una reserva (soft delete)
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $this->reservationService->deleteReservation($id);

        return response()->json(['message' => 'Reserva eliminada correctamente']);
    }

    /**
     * Restaura una reserva previamente eliminada
     *
     * @param int $id
     * @return JsonResponse
     */
    public function restore(int $id): JsonResponse
    {
        $this->reservationService->restoreReservation($id);

        return response()->json(['message' => 'Reserva restaurada correctamente']);
    }

    /**
     * Elimina permanentemente una reserva
     *
     * @param int $id
     * @return JsonResponse
     */
    public function forceDelete(int $id): JsonResponse
    {
        $this->reservationService->forceDeleteReservation($id);

        return response()->json(['message' => 'Reserva eliminada permanentemente']);
    }

    /**
     * Exporta las reservas a formato XLSX
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function exportToExcel(Request $request): JsonResponse
    {
        try {
            $filters = $this->extractFiltersFromRequest($request);
            $fileUrl = $this->reservationService->exportToExcel($filters);

            return response()->json([
                'success' => true,
                'message' => 'Archivo Excel generado correctamente',
                'url' => $fileUrl
            ]);
        } catch (\Exception $e) {
            Log::error('Error al exportar reservas a Excel: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al generar el archivo Excel: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Marca una reserva como pagada
     *
     * @param int $id
     * @return JsonResponse
     */
    public function markAsPaid(int $id): JsonResponse
    {
        $reservation = $this->reservationService->updateReservationStatus($id, 'paid');

        $this->reservationService->sendNotificationsForPaidReservation($reservation);

        return response()->json([
            'message'     => 'Reserva marcada como pagada correctamente',
            'reservation' => $reservation,
        ]);
    }

    /**
     * Extrae los filtros de la solicitud
     *
     * @param Request $request
     * @return array
     */
    private function extractFiltersFromRequest(Request $request): array
    {
        $filters = [];
        $possibleFilters = ['tour_id', 'status', 'date', 'from_date', 'to_date', 'client_id'];

        foreach ($possibleFilters as $filter) {
            if ($request->has($filter)) {
                $filters[$filter] = $request->input($filter);
            }
        }

        return $filters;
    }

    /**
     * Envía notificaciones para una reserva marcada como pagada
     *
     * @param Reservation $reservation
     * @return void
     */
    private function sendEmailNotification($client, array $datos): void
    {
        if (empty($client->email)) {
            Log::info('ReservationController::updateStatus - No se envía email: sin dirección de correo');
            return;
        }

        try {
            Mail::to($client->email)->send(new ConfirmacionReserva($datos));
            Log::info('ReservationController::updateStatus - Email enviado', [
                'email' => $client->email
            ]);
        } catch (\Exception $e) {
            Log::error('Error al enviar email: ' . $e->getMessage(), [
                'client_id' => $client->id
            ]);
        }
    }
}
