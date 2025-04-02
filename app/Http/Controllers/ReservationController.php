<?php

namespace App\Http\Controllers;

use App\Http\Requests\Reservation\StoreReservationRequest;
use App\Http\Requests\Reservation\UpdateReservationRequest;
use App\Http\Requests\Reservation\UpdateReservationStatusRequest;
use App\Services\ReservationService;
use Illuminate\Http\JsonResponse;

class ReservationController extends Controller
{
    private ReservationService $reservationService;

    public function __construct(ReservationService $reservationService)
    {
        $this->reservationService = $reservationService;
    }

    public function store(StoreReservationRequest $request): JsonResponse
    {
        $reservation = $this->reservationService->createReservation($request->all());
        return response()->json([
            'message'     => 'Reserva completada correctamente',
            'reservation' => $reservation,
        ], 201);
    }

    public function index(): JsonResponse
    {
        $reservations = $this->reservationService->listReservations();
        return response()->json([
            'reservations' => $reservations,
        ]);
    }
    // Endpoint para actualizar el status de una reserva
    public function updateStatus(UpdateReservationStatusRequest $request, int $id): JsonResponse
    {
        $status = $request->input('status', 'paid'); // Por defecto a "paid" si se desea.
        $reservation = $this->reservationService->updateReservationStatus($id, $status);

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
