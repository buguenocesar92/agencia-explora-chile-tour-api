<?php

namespace Tests\Unit\Services;

use App\Repositories\RoleRepository;
use App\Services\RoleService;
use Mockery;
use Tests\TestCase;

class RoleServiceTest extends TestCase
{
    protected RoleService $service;
    protected $mockRepo;

    public function setUp(): void
    {
        parent::setUp();

        // Mock del repositorio
        $this->mockRepo = Mockery::mock(RoleRepository::class);

        // Inyección del mock en el servicio
        $this->service = new RoleService($this->mockRepo);
    }

    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_get_all_roles_delegates_to_repository()
    {
        // Arrange
        $roles = collect([
            (object)['id' => 1, 'name' => 'Admin'],
            (object)['id' => 2, 'name' => 'User'],
            (object)['id' => 3, 'name' => 'Guest']
        ]);

        $this->mockRepo->shouldReceive('getAll')
            ->once()
            ->andReturn($roles);

        // Act
        $result = $this->service->getAllRoles();

        // Assert
        $this->assertEquals($roles, $result);
    }

    public function test_find_role_delegates_to_repository()
    {
        // Arrange
        $role = (object)[
            'id' => 1,
            'name' => 'Admin'
        ];

        $this->mockRepo->shouldReceive('find')
            ->once()
            ->with(1)
            ->andReturn($role);

        // Act
        $result = $this->service->findRole(1);

        // Assert
        $this->assertEquals($role, $result);
    }

    public function test_create_role_delegates_to_repository()
    {
        // Arrange
        $roleData = [
            'name' => 'New Role',
            'description' => 'New role description'
        ];

        $newRole = (object)[
            'id' => 4,
            'name' => 'New Role',
            'description' => 'New role description'
        ];

        $this->mockRepo->shouldReceive('create')
            ->once()
            ->with($roleData)
            ->andReturn($newRole);

        // Act
        $result = $this->service->createRole($roleData);

        // Assert
        $this->assertEquals($newRole, $result);
    }

    public function test_update_role_delegates_to_repository()
    {
        // Arrange
        $roleData = [
            'name' => 'Updated Role',
            'description' => 'Updated description'
        ];

        $updatedRole = (object)[
            'id' => 1,
            'name' => 'Updated Role',
            'description' => 'Updated description'
        ];

        $this->mockRepo->shouldReceive('update')
            ->once()
            ->with(1, $roleData)
            ->andReturn($updatedRole);

        // Act
        $result = $this->service->updateRole(1, $roleData);

        // Assert
        $this->assertEquals($updatedRole, $result);
    }

    public function test_delete_role_delegates_to_repository()
    {
        // Arrange
        $this->mockRepo->shouldReceive('delete')
            ->once()
            ->with(1)
            ->andReturn(null);

        // Act
        $this->service->deleteRole(1);

        // Assert - No hay retorno para verificar, solo comprobamos que se llamó al método del repositorio
        $this->assertTrue(true);
    }

    public function test_assign_role_to_user_delegates_to_repository()
    {
        // Arrange
        $this->mockRepo->shouldReceive('assignToUser')
            ->once()
            ->with(1, 2)
            ->andReturn(null);

        // Act
        $this->service->assignRoleToUser(1, 2);

        // Assert - No hay retorno para verificar, solo comprobamos que se llamó al método del repositorio
        $this->assertTrue(true);
    }

    public function test_update_users_delegates_to_repository()
    {
        // Arrange
        $userIds = [1, 2, 3];

        $this->mockRepo->shouldReceive('updateUsers')
            ->once()
            ->with(1, $userIds)
            ->andReturn(null);

        // Act
        $this->service->updateUsers(1, $userIds);

        // Assert - No hay retorno para verificar, solo comprobamos que se llamó al método del repositorio
        $this->assertTrue(true);
    }
}
