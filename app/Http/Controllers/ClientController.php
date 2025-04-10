<?php

namespace App\Http\Controllers;

use App\Http\Requests\Client\StoreClientRequest;
use App\Http\Requests\Client\UpdateClientRequest;
use App\Services\ClientService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    private ClientService $clientService;

    public function __construct(ClientService $clientService)
    {
        $this->clientService = $clientService;
    }

    public function index(Request $request): JsonResponse
    {
        $search = $request->input('search');
        $clients = $this->clientService->getAll($search);
        return response()->json($clients);
    }

    public function show(int $id): JsonResponse
    {
        $client = $this->clientService->findById($id);
        return response()->json($client);
    }

    public function store(StoreClientRequest $request): JsonResponse
    {
        $client = $this->clientService->create($request->validated());
        return response()->json([
            'message' => 'Cliente creado con éxito.',
            'client' => $client
        ], 201);
    }

    public function update(UpdateClientRequest $request, int $id): JsonResponse
    {
        $client = $this->clientService->update($id, $request->validated());
        return response()->json([
            'message' => 'Cliente actualizado con éxito.',
            'client' => $client
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->clientService->delete($id);
        return response()->json(['message' => 'Cliente eliminado con éxito.']);
    }

    /**
     * Busca un cliente por su RUT (endpoint público)
     */
    public function findByRut(Request $request): JsonResponse
    {
        $rut = $request->query('rut');

        if (empty($rut)) {
            return response()->json(['message' => 'El parámetro RUT es requerido'], 400);
        }

        // Normalizar el RUT (eliminar puntos y guiones)
        $normalizedRut = preg_replace('/[.-]/', '', $rut);

        // Buscar cliente por RUT normalizado
        $client = $this->clientService->findByRut($normalizedRut);

        if ($client) {
            return response()->json($client);
        }

        return response()->json(null);
    }
}
