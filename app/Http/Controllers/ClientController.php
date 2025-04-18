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
        $withTrashed = $request->boolean('with_trashed', false);
        $clients = $this->clientService->getAll($search, $withTrashed);
        return response()->json($clients);
    }

    public function show(int $id): JsonResponse
    {
        $client = $this->clientService->findById($id);
        return response()->json($client);
    }

    public function store(StoreClientRequest $request): JsonResponse
    {
        $validatedData = $request->validated();

        // Normalizar el RUT para la búsqueda
        $normalizedRut = preg_replace('/[.-]/', '', $validatedData['rut']);

        // Buscar si existe un cliente con este RUT (incluyendo soft deleted)
        $existingClient = $this->clientService->findByRut($normalizedRut, true);

        if ($existingClient && $existingClient->trashed()) {
            // Si existe y está eliminado, lo restauramos y actualizamos
            $this->clientService->restore($existingClient->id);
            $client = $this->clientService->update($existingClient->id, $validatedData);

            return response()->json([
                'message' => 'Cliente restaurado y actualizado con éxito.',
                'client' => $client
            ], 200);
        }

        // Si no existe o no está eliminado, creamos uno nuevo
        $client = $this->clientService->create($validatedData);
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
        $withTrashed = $request->boolean('with_trashed', false);

        if (empty($rut)) {
            return response()->json(['message' => 'El parámetro RUT es requerido'], 400);
        }

        // Normalizar el RUT (eliminar puntos y guiones)
        $normalizedRut = preg_replace('/[.-]/', '', $rut);

        // Buscar cliente por RUT normalizado
        $client = $this->clientService->findByRut($normalizedRut, $withTrashed);

        if ($client) {
            return response()->json($client);
        }

        // Devolver 404 cuando no se encuentra el cliente
        return response()->json(['message' => 'No se encontró ningún cliente con el RUT proporcionado'], 404);
    }

    /**
     * Restaura un cliente que ha sido eliminado con soft delete.
     */
    public function restore(int $id): JsonResponse
    {
        $this->clientService->restore($id);
        return response()->json(['message' => 'Cliente restaurado con éxito.']);
    }

    /**
     * Elimina permanentemente un cliente.
     */
    public function forceDelete(int $id): JsonResponse
    {
        $this->clientService->forceDelete($id);
        return response()->json(['message' => 'Cliente eliminado permanentemente.']);
    }
}
