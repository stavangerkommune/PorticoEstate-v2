<?php

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Container\ContainerInterface;
use App\Controllers\UserController;
use PDO;
use PDOStatement;


class UserControllerTest extends TestCase
{
    private $db;
    private $container;
    private $request;
    private $response;

    protected function setUp(): void
    {
        $this->db = $this->createMock(PDO::class);
        $this->container = $this->createMock(ContainerInterface::class);
        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->response = $this->createMock(ResponseInterface::class);
    
        // Set the container to return the database mock when 'db' is passed to the get method
        $this->container->method('get')->willReturnMap([
            ['db', $this->db],
        ]);
    }
    
    public function testIndex()
    {
        $this->request->method('getQueryParams')->willReturn(['page' => 1, 'perPage' => 10]);

        $userController = new UserController($this->container);
        $response = $userController->index($this->request, $this->response);

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testStore()
    {
        $this->request->method('getParsedBody')->willReturn(['name' => 'Test Name', 'email' => 'test@example.com']);

        $userController = new UserController($this->container);
        $response = $userController->store($this->request, $this->response);

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testShow()
    {
        $userId = 1;
        $this->request->method('getAttribute')->willReturn($userId);

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('fetch')->willReturn(['id' => $userId, 'name' => 'Test Name', 'email' => 'test@example.com']);
        $this->db->method('prepare')->willReturn($stmt);

        $userController = new UserController($this->container);
        $response = $userController->show($this->request, $this->response, ['id' => $userId]);

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testUpdate()
    {
        $userId = 1;
        $name = 'New Name';
        $email = 'new@example.com';

        $this->request->method('getAttribute')->willReturn($userId);
        $this->request->method('getParsedBody')->willReturn(['name' => $name, 'email' => $email]);

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $this->db->method('prepare')->willReturn($stmt);

        $userController = new UserController($this->container);
        $response = $userController->update($this->request, $this->response, ['id' => $userId]);

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testDestroy()
    {
        $userId = 1;

        $this->request->method('getAttribute')->willReturn($userId);

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('rowCount')->willReturn(1);
        $this->db->method('prepare')->willReturn($stmt);

        $userController = new UserController($this->container);
        $response = $userController->destroy($this->request, $this->response, ['id' => $userId]);

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }
}