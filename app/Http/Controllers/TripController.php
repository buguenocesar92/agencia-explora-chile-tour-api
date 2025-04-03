<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use Illuminate\Http\Request;

class TripController extends Controller
{
    /**
     * Lista todos los trips.
     */
    public function index()
    {
        // Se incluye la relación con tourTemplate para mayor contexto
        $trips = Trip::with('tourTemplate')->get();
        return response()->json([
            'trips' => $trips,
        ]);
    }

    /**
     * Crea un nuevo trip.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'tour_template_id' => 'required|integer|exists:tour_templates,id',
            'departure_date'   => 'required|date',
            'return_date'      => 'required|date|after_or_equal:departure_date',
            // Si en el futuro agregas otros campos (precio, capacidad, etc.), puedes incluirlos aquí.
        ]);

        $trip = Trip::create($data);

        return response()->json([
            'message' => 'Trip creado correctamente',
            'trip'    => $trip,
        ], 201);
    }

    /**
     * Muestra los detalles de un trip.
     */
    public function show($id)
    {
        $trip = Trip::with('tourTemplate')->findOrFail($id);
        return response()->json([
            'trip' => $trip,
        ]);
    }

    /**
     * Actualiza un trip existente.
     */
    public function update(Request $request, $id)
    {
        $trip = Trip::findOrFail($id);

        $data = $request->validate([
            'tour_template_id' => 'sometimes|required|integer|exists:tour_templates,id',
            'departure_date'   => 'sometimes|required|date',
            'return_date'      => 'sometimes|required|date|after_or_equal:departure_date',
        ]);

        $trip->update($data);

        return response()->json([
            'message' => 'Trip actualizado correctamente',
            'trip'    => $trip,
        ]);
    }

    /**
     * Elimina un trip.
     */
    public function destroy($id)
    {
        $trip = Trip::findOrFail($id);
        $trip->delete();

        return response()->json([
            'message' => 'Trip eliminado correctamente',
        ]);
    }
}
