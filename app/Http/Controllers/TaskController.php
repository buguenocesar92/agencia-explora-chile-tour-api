<?php

namespace App\Http\Controllers;

use App\Http\Requests\Task\StoreTaskRequest;
use App\Http\Requests\Task\UpdateTaskRequest;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;

/*
Controller: Se encarga de recibir las solicitudes HTTP, delegar el procesamiento y retornar las respuestas.
No debe contener lógica de negocio ni lógica de acceso a datos.
*/
class TaskController extends Controller
{
    private TaskService $taskService;

    public function __construct(TaskService $taskService)
    {
        $this->taskService = $taskService;
    }

    public function index(): JsonResponse
    {
        $tasks = $this->taskService->getAll();
        return response()->json($tasks);
    }

    public function show(int $id): JsonResponse
    {
        $task = $this->taskService->findById($id);
        return response()->json($task);
    }

    public function store(StoreTaskRequest $request): JsonResponse
    {
        $task = $this->taskService->create($request->validated());
        return response()->json(['message' => 'Tarea creada con éxito.', 'task' => $task], 201);
    }

    public function update(UpdateTaskRequest $request, int $id): JsonResponse
    {
        $task = $this->taskService->update($id, $request->validated());
        return response()->json(['message' => 'Tarea actualizada con éxito.', 'task' => $task]);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->taskService->delete($id);
        return response()->json(['message' => 'Tarea eliminada con éxito.']);
    }
}
