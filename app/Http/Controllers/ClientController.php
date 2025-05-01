<?php

namespace App\Http\Controllers;

use App\Http\Requests\Client\StoreClientRequest;
use App\Http\Requests\Client\UpdateClientRequest;
use App\Services\ClientService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Controlador para gestionar clientes
 */
class ClientController extends Controller
{
    private ClientService $clientService;

    public function __construct(ClientService $clientService)
    {
        $this->clientService = $clientService;
    }

    /**
     * Lista todos los clientes con búsqueda opcional
     */
    public function index(Request $request): JsonResponse
    {
        $search = $request->input('search');
        $withTrashed = $request->boolean('with_trashed', false);

        $clients = $this->clientService->getAll($search, $withTrashed);

        return response()->json($clients);
    }

    /**
     * Muestra un cliente específico
     */
    public function show(int $id): JsonResponse
    {
        $client = $this->clientService->findById($id);

        return response()->json($client);
    }

    /**
     * Busca un cliente por su RUT
     */
    public function findByRut(Request $request): JsonResponse
    {
        $rut = $request->query('rut');
        $withTrashed = $request->boolean('with_trashed', false);

        if (empty($rut)) {
            return response()->json(['message' => 'El parámetro RUT es requerido'], 400);
        }

        $client = $this->clientService->findByRut($rut, $withTrashed);

        if (!$client) {
            return response()->json(['message' => 'No se encontró ningún cliente con el RUT proporcionado'], 404);
        }

        return response()->json($client);
    }

    /**
     * Crea un nuevo cliente
     */
    public function store(StoreClientRequest $request): JsonResponse
    {
        $validatedData = $request->validated();

        try {
            $client = $this->clientService->create($validatedData);

            return response()->json([
                'message' => 'Cliente creado con éxito.',
                'client' => $client
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error al crear cliente', [
                'error' => $e->getMessage(),
                'data' => $validatedData
            ]);

            return response()->json([
                'message' => 'No se pudo crear el cliente. ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualiza un cliente existente
     */
    public function update(UpdateClientRequest $request, int $id): JsonResponse
    {
        try {
            $client = $this->clientService->update($id, $request->validated());

            return response()->json([
                'message' => 'Cliente actualizado con éxito.',
                'client' => $client
            ]);
        } catch (\Exception $e) {
            Log::error('Error al actualizar cliente', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                return response()->json(['message' => 'Cliente no encontrado'], 404);
            }

            return response()->json([
                'message' => 'No se pudo actualizar el cliente. ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Elimina un cliente (soft delete)
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->clientService->delete($id);

            return response()->json(['message' => 'Cliente eliminado con éxito.']);
        } catch (\Exception $e) {
            Log::error('Error al eliminar cliente', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                return response()->json(['message' => 'Cliente no encontrado'], 404);
            }

            return response()->json([
                'message' => 'No se pudo eliminar el cliente. ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Restaura un cliente eliminado
     */
    public function restore(int $id): JsonResponse
    {
        try {
            $this->clientService->restore($id);

            return response()->json(['message' => 'Cliente restaurado con éxito.']);
        } catch (\Exception $e) {
            Log::error('Error al restaurar cliente', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                return response()->json(['message' => 'Cliente no encontrado'], 404);
            }

            return response()->json([
                'message' => 'No se pudo restaurar el cliente. ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Elimina permanentemente un cliente
     */
    public function forceDelete(int $id): JsonResponse
    {
        try {
            $this->clientService->forceDelete($id);

            return response()->json(['message' => 'Cliente eliminado permanentemente.']);
        } catch (\Exception $e) {
            Log::error('Error al eliminar permanentemente cliente', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                return response()->json(['message' => 'Cliente no encontrado'], 404);
            }

            return response()->json([
                'message' => 'No se pudo eliminar permanentemente el cliente. ' . $e->getMessage()
            ], 500);
        }
    }
}
