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
use App\Services\WhatsAppService;


class ReservationController extends Controller
{
    private ReservationService $reservationService;
    private WhatsAppService $whatsAppService;

    public function __construct(ReservationService $reservationService, WhatsAppService $whatsAppService)
    {
        $this->reservationService = $reservationService;
        $this->whatsAppService = $whatsAppService;
    }

    public function index(Request $request): JsonResponse
    {
        $search = $request->input('search');
        $tourId = $request->input('tour_id');
        $status = $request->input('status');
        $date = $request->input('date');

        // Crear array de filtros
        $filters = [];
        if ($tourId) {
            $filters['tour_id'] = $tourId;
        }

        // Agregar filtro de status si está presente
        if ($status) {
            $filters['status'] = $status;
        }

        // Agregar filtro de fecha si está presente
        if ($date) {
            $filters['date'] = $date;
        }

        $reservations = $this->reservationService->listReservations($search, $filters);

        return response()->json([
            'reservations' => $reservations,
        ]);
    }

    public function store(StoreReservationRequest $request): JsonResponse
    {
        $reservation = $this->reservationService->createReservation($request->all());
        return response()->json([
            'message'     => 'Reserva completada correctamente',
            'reservation' => $reservation,
        ], 201);
    }

    public function updateStatus(UpdateReservationStatusRequest $request, int $id): JsonResponse
    {
        $status = $request->input('status', 'paid');

        // Añadir debug para ver los datos recibidos
        Log::info('ReservationController::updateStatus - Iniciando actualización', [
            'id' => $id,
            'status' => $status
        ]);

        $reservation = $this->reservationService->updateReservationStatus($id, $status);

        // Cargar relaciones necesarias: client, trip, y tourTemplate
        $reservation->load('client', 'trip.tourTemplate');

        Log::info('ReservationController::updateStatus - Reserva cargada', [
            'reservation_id' => $reservation->id,
            'client' => $reservation->client ? true : false,
            'trip' => $reservation->trip ? true : false,
            'tourTemplate' => ($reservation->trip && $reservation->trip->tourTemplate) ? true : false,
            'client_phone' => $reservation->client ? $reservation->client->phone : null
        ]);

        // Si la reserva fue marcada como pagada, enviar notificaciones
        if ($status === 'paid') {
            // Verificar si tenemos los datos necesarios
            if ($reservation->client) {
                $datos = [
                    'nombre'  => $reservation->client->name,
                    'destino' => ($reservation->trip && $reservation->trip->tourTemplate)
                               ? $reservation->trip->tourTemplate->name
                               : 'N/A',
                    'fecha'   => ($reservation->trip && $reservation->trip->departure_date)
                              ? $reservation->trip->departure_date
                              : $reservation->date,
                ];

                // 1. Enviar correo electrónico
                if ($reservation->client->email) {
                    Log::info('ReservationController::updateStatus - Enviando correo', [
                        'email' => $reservation->client->email
                    ]);

                    try {
                        Mail::to($reservation->client->email)->send(new ConfirmacionReserva($datos));
                    } catch (\Exception $e) {
                        Log::error('Error al enviar correo: ' . $e->getMessage());
                    }
                }

                // 2. Enviar WhatsApp
                if ($reservation->client->phone) {
                    Log::info('ReservationController::updateStatus - Enviando WhatsApp', [
                        'phone' => $reservation->client->phone
                    ]);

                    try {
                        $whatsappResult = $this->whatsAppService->sendPaymentConfirmation(
                            $reservation->client->phone,
                            $datos
                        );

                        Log::info('ReservationController::updateStatus - Resultado WhatsApp', [
                            'success' => $whatsappResult
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Error al enviar WhatsApp: ' . $e->getMessage(), [
                            'exception' => get_class($e),
                            'trace' => $e->getTraceAsString()
                        ]);
                    }
                } else {
                    Log::info('ReservationController::updateStatus - No se envía WhatsApp: sin teléfono');
                }
            } else {
                Log::warning('ReservationController::updateStatus - No se encontró cliente para la reserva');
            }
        } else {
            Log::info('ReservationController::updateStatus - No se envían notificaciones: estado no es "paid"');
        }

        return response()->json([
            'message'     => 'Reserva actualizada correctamente',
            'reservation' => $reservation,
        ]);
    }



    public function show(int $id): JsonResponse
    {
        $reservation = $this->reservationService->getReservation($id);
        return response()->json([
            'reservation' => $reservation,
        ]);
    }

    public function update(UpdateReservationRequest $request, int $id): JsonResponse
    {
        // Se asume que el request contiene todos los campos de la reserva y sus relaciones
        $reservation = $this->reservationService->updateReservation($id, $request->all());

        return response()->json([
            'message'     => 'Reserva actualizada correctamente',
            'reservation' => $reservation,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->reservationService->deleteReservation($id);
        return response()->json([
            'message' => 'Reserva eliminada correctamente',
        ]);
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
            // Obtener los filtros de la solicitud
            $filters = [];

            if ($request->has('tour_id')) {
                $filters['tour_id'] = $request->input('tour_id');
            }

            if ($request->has('status')) {
                $filters['status'] = $request->input('status');
            }

            if ($request->has('date')) {
                $filters['date'] = $request->input('date');
            }

            // Exportar a Excel
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
}
