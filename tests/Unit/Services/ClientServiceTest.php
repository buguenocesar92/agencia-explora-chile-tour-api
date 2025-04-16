<?php

namespace Tests\Unit\Services;

use App\Models\Client;
use App\Repositories\Contracts\ClientRepositoryInterface;
use App\Services\ClientService;
use Mockery;
use Tests\TestCase;

class ClientServiceTest extends TestCase
{
    protected ClientService $service;
    protected $mockRepo;

    public function setUp(): void
    {
        parent::setUp();

        // Mock del repositorio
        $this->mockRepo = Mockery::mock(ClientRepositoryInterface::class);

        // Inyección del mock en el servicio
        $this->service = new ClientService($this->mockRepo);
    }

    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_get_all_clients_delegates_to_repository()
    {
        // Arrange
        $clients = collect([
            (object)['id' => 1, 'name' => 'Cliente 1'],
            (object)['id' => 2, 'name' => 'Cliente 2'],
            (object)['id' => 3, 'name' => 'Cliente 3']
        ]);

        $this->mockRepo->shouldReceive('getAll')
            ->once()
            ->with('test', false)
            ->andReturn($clients);

        // Act
        $result = $this->service->getAll('test');

        // Assert
        $this->assertEquals($clients, $result);
    }

    public function test_find_client_by_id_delegates_to_repository()
    {
        // Arrange
        $client = new Client();
        $client->id = 1;
        $client->name = 'Test Client';

        $this->mockRepo->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($client);

        // Act
        $result = $this->service->findById(1);

        // Assert
        $this->assertEquals($client, $result);
    }

    public function test_create_client_delegates_to_repository()
    {
        // Arrange
        $clientData = [
            'name' => 'Nuevo Cliente',
            'email' => 'cliente@example.com',
            'phone' => '123456789',
            'rut' => '12345678-9',
            'date_of_birth' => '1990-01-01',
            'nationality' => 'Chilena'
        ];

        $client = new Client();
        $client->id = 1;
        $client->name = 'Nuevo Cliente';
        $client->email = 'cliente@example.com';

        $this->mockRepo->shouldReceive('create')
            ->once()
            ->with($clientData)
            ->andReturn($client);

        // Act
        $result = $this->service->create($clientData);

        // Assert
        $this->assertEquals($client, $result);
    }

    public function test_update_client_delegates_to_repository()
    {
        // Arrange
        $clientData = [
            'name' => 'Cliente Actualizado',
            'email' => 'actualizado@example.com',
        ];

        $client = new Client();
        $client->id = 1;
        $client->name = 'Cliente Actualizado';
        $client->email = 'actualizado@example.com';

        $this->mockRepo->shouldReceive('update')
            ->once()
            ->with(1, $clientData)
            ->andReturn($client);

        // Act
        $result = $this->service->update(1, $clientData);

        // Assert
        $this->assertEquals($client, $result);
    }

    public function test_delete_client_delegates_to_repository()
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

    public function test_find_client_by_rut_delegates_to_repository()
    {
        // Arrange
        $client = new Client();
        $client->id = 1;
        $client->name = 'Test Client';
        $client->rut = '123456789';

        $this->mockRepo->shouldReceive('findByRut')
            ->once()
            ->with('123456789', false)
            ->andReturn($client);

        // Act
        $result = $this->service->findByRut('123456789');

        // Assert
        $this->assertEquals($client, $result);
    }
}
