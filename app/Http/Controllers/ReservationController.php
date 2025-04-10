<?php

namespace App\Http\Controllers;

use App\Http\Requests\Reservation\StoreReservationRequest;
use App\Http\Requests\Reservation\UpdateReservationRequest;
use App\Http\Requests\Reservation\UpdateReservationStatusRequest;
use App\Services\ReservationService;
use Illuminate\Http\JsonResponse;
use App\Mail\ConfirmacionReserva;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;


class ReservationController extends Controller
{
    private ReservationService $reservationService;

    public function __construct(ReservationService $reservationService)
    {
        $this->reservationService = $reservationService;
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
        $reservation = $this->reservationService->updateReservationStatus($id, $status);

        // Cargar relaciones necesarias: client, trip, y tourTemplate
        $reservation->load('client', 'trip.tourTemplate');

        if (
            $status === 'paid' &&
            $reservation->client &&
            $reservation->trip &&
            $reservation->trip->tourTemplate
        ) {
            $datos = [
                'nombre'  => $reservation->client->name,                          // Asegúrate de tener este campo
                'destino' => $reservation->trip->tourTemplate->name ?? 'N/A',
                'fecha'   => $reservation->trip->departure_date ?? $reservation->date,
            ];

            Mail::to($reservation->client->email)->send(new ConfirmacionReserva($datos));
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
}
