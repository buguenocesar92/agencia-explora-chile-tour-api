<?php

namespace App\Services;

use App\Models\Client;
use App\Repositories\Contracts\ClientRepositoryInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ClientService
{
    private ClientRepositoryInterface $clientRepo;

    public function __construct(ClientRepositoryInterface $clientRepo)
    {
        $this->clientRepo = $clientRepo;
    }

    /**
     * Recupera todos los clientes, opcionalmente filtrados por búsqueda
     *
     * @param string|null $search Término de búsqueda
     * @param bool $withTrashed Incluir clientes eliminados
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAll(?string $search = null, bool $withTrashed = false)
    {
        return $this->clientRepo->getAll($search, $withTrashed);
    }

    /**
     * Encuentra un cliente por su ID
     *
     * @param int $id
     * @return \App\Models\Client
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findById(int $id)
    {
        return $this->clientRepo->findById($id);
    }

    /**
     * Crea un nuevo cliente validando datos únicos
     *
     * @param array $data
     * @return \App\Models\Client
     */
    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            // Normalizar RUT para comparación
            $normalizedRut = $this->normalizeRut($data['rut']);

            // Verificar si existe un cliente eliminado con este RUT
            $existingClient = $this->findByRut($normalizedRut, true);

            if ($existingClient && $existingClient->trashed()) {
                // Restaurar y actualizar cliente eliminado
                $this->restore($existingClient->id);
                return $this->update($existingClient->id, $data);
            }

            // Manejar posible duplicidad de email
            $data = $this->handleDuplicateEmail($data);

            return $this->clientRepo->create($data);
        });
    }

    /**
     * Actualiza un cliente existente
     *
     * @param int $id
     * @param array $data
     * @return \App\Models\Client
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function update(int $id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            // Si el email está siendo actualizado, verificar duplicidad
            if (isset($data['email'])) {
                $client = $this->clientRepo->findById($id);

                if ($client->email !== $data['email']) {
                    $data = $this->handleDuplicateEmail($data, $id);
                }
            }

            return $this->clientRepo->update($id, $data);
        });
    }

    /**
     * Elimina un cliente (soft delete)
     *
     * @param int $id
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function delete(int $id): void
    {
        $this->clientRepo->delete($id);
    }

    /**
     * Restaura un cliente eliminado
     *
     * @param int $id
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function restore(int $id): void
    {
        $this->clientRepo->restore($id);
    }

    /**
     * Elimina permanentemente un cliente
     *
     * @param int $id
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function forceDelete(int $id): void
    {
        $this->clientRepo->forceDelete($id);
    }

    /**
     * Busca un cliente por su RUT
     *
     * @param string $rutInput RUT con o sin formato
     * @param bool $withTrashed Incluir clientes eliminados
     * @return \App\Models\Client|null
     */
    public function findByRut(string $rutInput, bool $withTrashed = false)
    {
        $normalizedRut = $this->normalizeRut($rutInput);
        return $this->clientRepo->findByRut($normalizedRut, $withTrashed);
    }

    /**
     * Normaliza un RUT eliminando puntos y guiones
     */
    private function normalizeRut(string $rut): string
    {
        return preg_replace('/[.-]/', '', $rut);
    }

    /**
     * Maneja la duplicidad de email al crear o actualizar un cliente
     *
     * @param array $data Datos del cliente
     * @param int|null $excludeClientId ID del cliente a excluir de la validación
     * @return array Datos del cliente, posiblemente modificados
     */
    private function handleDuplicateEmail(array $data, ?int $excludeClientId = null): array
    {
        if (!isset($data['email'])) {
            return $data;
        }

        // Verificar duplicidad
        $query = Client::where('email', $data['email']);

        if ($excludeClientId) {
            $query->where('id', '!=', $excludeClientId);
        }

        $emailExists = $query->exists();

        if ($emailExists) {
            // Generar un email único como solución temporal
            $originalEmail = $data['email'];
            $uniqueSuffix = Str::random(8);
            $data['email'] = "{$originalEmail}+{$uniqueSuffix}";

            Log::info('Email duplicado detectado', [
                'original_email' => $originalEmail,
                'modified_email' => $data['email']
            ]);
        }

        return $data;
    }
}
