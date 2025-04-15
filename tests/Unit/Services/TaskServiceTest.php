<?php

namespace Tests\Unit\Services;

use App\Models\Task;
use App\Repositories\Contracts\TaskRepositoryInterface;
use App\Services\TaskService;
use Mockery;
use Tests\TestCase;

class TaskServiceTest extends TestCase
{
    protected TaskService $service;
    protected $mockRepo;

    public function setUp(): void
    {
        parent::setUp();

        // Mock del repositorio
        $this->mockRepo = Mockery::mock(TaskRepositoryInterface::class);

        // Inyección del mock en el servicio
        $this->service = new TaskService($this->mockRepo);
    }

    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_get_all_tasks_delegates_to_repository()
    {
        // Arrange
        $tasks = collect([
            (object)['id' => 1, 'title' => 'Tarea 1'],
            (object)['id' => 2, 'title' => 'Tarea 2'],
            (object)['id' => 3, 'title' => 'Tarea 3']
        ]);

        $this->mockRepo->shouldReceive('getAll')
            ->once()
            ->andReturn($tasks);

        // Act
        $result = $this->service->getAll();

        // Assert
        $this->assertEquals($tasks, $result);
    }

    public function test_find_task_by_id_delegates_to_repository()
    {
        // Arrange
        $task = new Task();
        $task->id = 1;
        $task->title = 'Test Task';

        $this->mockRepo->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($task);

        // Act
        $result = $this->service->findById(1);

        // Assert
        $this->assertEquals($task, $result);
    }

    public function test_create_task_delegates_to_repository()
    {
        // Arrange
        $taskData = [
            'title' => 'Nueva Tarea',
            'description' => 'Descripción de la tarea',
            'status' => 'pendiente'
        ];

        $task = new Task();
        $task->id = 1;
        $task->title = 'Nueva Tarea';
        $task->description = 'Descripción de la tarea';
        $task->status = 'pendiente';

        $this->mockRepo->shouldReceive('create')
            ->once()
            ->with($taskData)
            ->andReturn($task);

        // Act
        $result = $this->service->create($taskData);

        // Assert
        $this->assertEquals($task, $result);
    }

    public function test_update_task_delegates_to_repository()
    {
        // Arrange
        $taskData = [
            'title' => 'Tarea Actualizada',
            'status' => 'completada'
        ];

        $task = new Task();
        $task->id = 1;
        $task->title = 'Tarea Actualizada';
        $task->status = 'completada';

        $this->mockRepo->shouldReceive('update')
            ->once()
            ->with(1, $taskData)
            ->andReturn($task);

        // Act
        $result = $this->service->update(1, $taskData);

        // Assert
        $this->assertEquals($task, $result);
    }

    public function test_delete_task_delegates_to_repository()
    {
        // Arrange
        $this->mockRepo->shouldReceive('delete')
            ->once()
            ->with(1)
            ->andReturn(null);

        // Act
        $this->service->delete(1);

        // Assert - No hay retorno para verificar, solo comprobamos que se llamó al método del repositorio
        $this->assertTrue(true);
    }
}
