<?php

namespace App\Http\Controllers;

use App\Http\Requests\Client\StoreClientRequest;
use App\Http\Requests\Client\UpdateClientRequest;
use App\Services\ClientService;
use Illuminate\Http\JsonResponse;

class ClientController extends Controller
{
    private ClientService $clientService;

    public function __construct(ClientService $clientService)
    {
        $this->clientService = $clientService;
    }

    public function index(): JsonResponse
    {
        $clients = $this->clientService->getAll();
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
}
