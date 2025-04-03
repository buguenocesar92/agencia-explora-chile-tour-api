<?php

namespace App\Http\Controllers;

use App\Models\TourTemplate;
use Illuminate\Http\Request;

class TourController extends Controller
{
    /**
     * Devuelve la lista de tours definidos.
     */
    public function index()
    {
        $tours = TourTemplate::all();
        return response()->json([
            'tours' => $tours,
        ]);
    }

    /**
     * Devuelve las fechas programadas para un tour específico.
     *
     * @param int $tourId
     */
    public function getDates($tourId)
    {
        $tour = TourTemplate::with('trips')->findOrFail($tourId);
        return response()->json([
            'trips' => $tour->trips,
        ]);
    }
}
