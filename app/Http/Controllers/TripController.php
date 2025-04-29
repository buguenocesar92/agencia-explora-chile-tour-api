<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TripController extends Controller
{
    /**
     * Lista todos los trips.
     */
    public function index(Request $request)
    {
        // Iniciamos la consulta
        $query = Trip::with('tourTemplate');

        // Aplicamos filtros si están presentes en la petición
        if ($request->has('tour_template_id')) {
            $query->where('tour_template_id', $request->tour_template_id);
        }

        // Ejecutamos la consulta y obtenemos los resultados
        $trips = $query->get();

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
            'pdf_file'         => 'nullable|file|mimes:pdf|max:10240', // PDF de hasta 10MB
        ]);

        // Extraer los campos básicos para el Trip
        $tripData = [
            'tour_template_id' => $data['tour_template_id'],
            'departure_date'   => $data['departure_date'],
            'return_date'      => $data['return_date'],
        ];

        // Procesar el archivo del programa si se proporcionó
        if ($request->hasFile('pdf_file') && $request->file('pdf_file')->isValid()) {
            $programaPath = $request->file('pdf_file')->store('programas', 'public');
            $tripData['programa'] = $programaPath;
        }

        $trip = Trip::create($tripData);

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
            'pdf_file'         => 'nullable|file|mimes:pdf|max:10240', // PDF de hasta 10MB
        ]);

        // Extraer los campos a actualizar
        $tripData = [];

        if (isset($data['tour_template_id'])) {
            $tripData['tour_template_id'] = $data['tour_template_id'];
        }

        if (isset($data['departure_date'])) {
            $tripData['departure_date'] = $data['departure_date'];
        }

        if (isset($data['return_date'])) {
            $tripData['return_date'] = $data['return_date'];
        }

        // Procesar el archivo del programa si se proporcionó
        if ($request->hasFile('pdf_file') && $request->file('pdf_file')->isValid()) {
            // Eliminar el archivo anterior si existe
            if ($trip->programa && Storage::disk('public')->exists($trip->programa)) {
                Storage::disk('public')->delete($trip->programa);
            }

            $programaPath = $request->file('pdf_file')->store('programas', 'public');
            $tripData['programa'] = $programaPath;
        }

        $trip->update($tripData);

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

        // Eliminar el archivo de programa asociado si existe
        if ($trip->programa && Storage::disk('public')->exists($trip->programa)) {
            Storage::disk('public')->delete($trip->programa);
        }

        $trip->delete();

        return response()->json([
            'message' => 'Trip eliminado correctamente',
        ]);
    }

    /**
     * Devuelve el archivo PDF del programa del trip.
     */
    public function getProgramaFile($id)
    {
        $trip = Trip::findOrFail($id);

        if (!$trip->programa) {
            return response()->json([
                'message' => 'Este trip no tiene un programa asociado.',
            ], 404);
        }

        if (!Storage::disk('public')->exists($trip->programa)) {
            return response()->json([
                'message' => 'El archivo del programa no existe.',
            ], 404);
        }

        // Devuelve la URL pública del archivo
        $fileUrl = Storage::url($trip->programa);

        return response()->json([
            'file_url' => $fileUrl,
            'file_name' => basename($trip->programa),
        ]);
    }

    /**
     * Descarga el archivo PDF del programa del trip.
     */
    public function downloadProgramaFile($id)
    {
        $trip = Trip::findOrFail($id);

        if (!$trip->programa || !Storage::disk('public')->exists($trip->programa)) {
            return response()->json([
                'message' => 'El archivo del programa no existe.',
            ], 404);
        }

        $path = Storage::disk('public')->path($trip->programa);
        $filename = basename($trip->programa);

        return response()->download($path, 'programa_viaje.pdf', [
            'Content-Type' => 'application/pdf'
        ]);
    }
}
