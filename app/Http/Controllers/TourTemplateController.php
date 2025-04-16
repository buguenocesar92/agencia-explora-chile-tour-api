<?php

namespace App\Http\Controllers;

use App\Models\TourTemplate;
use Illuminate\Http\Request;

class TourTemplateController extends Controller
{
    public function store(Request $request)
    {
        // Validar los datos
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'destination' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        // Crear el TourTemplate
        $tourTemplate = TourTemplate::create($data);

        // Retornar la respuesta en JSON
        return response()->json([
            'message'      => 'TourTemplate creado correctamente',
            'tourTemplate' => $tourTemplate,
        ], 201);
    }

    public function index ()
    {
        // Obtener todos los TourTemplates
        $tourTemplates = TourTemplate::all();

        // Retornar la respuesta en JSON
        return response()->json([
            'tourTemplates' => $tourTemplates,
        ]);
    }

    public function show($id)
    {
        // Obtener el TourTemplate por ID
        $tourTemplate = TourTemplate::findOrFail($id);

        // Retornar la respuesta en JSON
        return response()->json([
            'tourTemplate' => $tourTemplate,
        ]);
    }

    public function update(Request $request, $id)
    {
        // Validar los datos
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'destination' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        // Obtener el TourTemplate por ID
        $tourTemplate = TourTemplate::findOrFail($id);

        // Actualizar el TourTemplate
        $tourTemplate->update($data);

        // Retornar la respuesta en JSON
        return response()->json([
            'message'      => 'TourTemplate actualizado correctamente',
            'tourTemplate' => $tourTemplate,
        ]);
    }

    public function destroy($id)
    {
        // Obtener el TourTemplate por ID
        $tourTemplate = TourTemplate::findOrFail($id);

        // Eliminar el TourTemplate
        $tourTemplate->delete();

        // Retornar la respuesta en JSON
        return response()->json([
            'message' => 'TourTemplate eliminado correctamente',
        ]);
    }
}
