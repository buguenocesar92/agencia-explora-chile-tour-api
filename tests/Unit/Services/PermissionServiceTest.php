<?php

namespace Tests\Unit\Services;

use App\Repositories\PermissionRepository;
use App\Services\PermissionService;
use Mockery;
use Tests\TestCase;

class PermissionServiceTest extends TestCase
{
    protected PermissionService $service;
    protected $mockRepo;

    public function setUp(): void
    {
        parent::setUp();

        // Mock del repositorio
        $this->mockRepo = Mockery::mock(PermissionRepository::class);

        // Inyección del mock en el servicio
        $this->service = new PermissionService($this->mockRepo);
    }

    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_get_all_permissions_delegates_to_repository()
    {
        // Arrange
        $permissions = collect([
            (object)['id' => 1, 'name' => 'create_user'],
            (object)['id' => 2, 'name' => 'update_user'],
            (object)['id' => 3, 'name' => 'delete_user']
        ]);

        $this->mockRepo->shouldReceive('getAll')
            ->once()
            ->andReturn($permissions);

        // Act
        $result = $this->service->getAllPermissions();

        // Assert
        $this->assertEquals($permissions, $result);
    }

    public function test_find_permission_delegates_to_repository()
    {
        // Arrange
        $permission = (object)[
            'id' => 1,
            'name' => 'create_user'
        ];

        $this->mockRepo->shouldReceive('find')
            ->once()
            ->with(1)
            ->andReturn($permission);

        // Act
        $result = $this->service->findPermission(1);

        // Assert
        $this->assertEquals($permission, $result);
    }

    public function test_create_permission_delegates_to_repository()
    {
        // Arrange
        $permissionData = [
            'name' => 'new_permission',
            'description' => 'New permission description'
        ];

        $newPermission = (object)[
            'id' => 4,
            'name' => 'new_permission',
            'description' => 'New permission description'
        ];

        $this->mockRepo->shouldReceive('create')
            ->once()
            ->with($permissionData)
            ->andReturn($newPermission);

        // Act
        $result = $this->service->createPermission($permissionData);

        // Assert
        $this->assertEquals($newPermission, $result);
    }

    public function test_update_permission_delegates_to_repository()
    {
        // Arrange
        $permissionData = [
            'name' => 'updated_permission',
            'description' => 'Updated description'
        ];

        $updatedPermission = (object)[
            'id' => 1,
            'name' => 'updated_permission',
            'description' => 'Updated description'
        ];

        $this->mockRepo->shouldReceive('update')
            ->once()
            ->with(1, $permissionData)
            ->andReturn($updatedPermission);

        // Act
        $result = $this->service->updatePermission(1, $permissionData);

        // Assert
        $this->assertEquals($updatedPermission, $result);
    }

    public function test_delete_permission_delegates_to_repository()
    {
        // Arrange
        $this->mockRepo->shouldReceive('delete')
            ->once()
            ->with(1)
            ->andReturn(null);

        // Act
        $this->service->deletePermission(1);

        // Assert - No hay retorno para verificar, solo comprobamos que se llamó al método del repositorio
        $this->assertTrue(true);
    }

    public function test_assign_permission_to_user_delegates_to_repository()
    {
        // Arrange
        $this->mockRepo->shouldReceive('assignPermissionToUser')
            ->once()
            ->with(1, 2)
            ->andReturn(null);

        // Act
        $this->service->assignPermissionToUser(1, 2);

        // Assert - No hay retorno para verificar, solo comprobamos que se llamó al método del repositorio
        $this->assertTrue(true);
    }
}
