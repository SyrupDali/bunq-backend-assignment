<?php

namespace Tests\Controllers;

use App\Controllers\UserController;
use App\Services\UserService;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Request; // Import Slim's Request class
use Slim\Psr7\Response; // Import Slim's Response class

class UserControllerTest extends TestCase
{
    private $request;
    private $response;
    private $userService;
    private $userController;

    // This method is called before every test
    protected function setUp(): void
    {
        // Create a mock request
        $this->request = $this->createMock(ServerRequestInterface::class);
        // Use a real response object
        $this->response = new Response();

        // Create a mock for the UserService
        $this->userService = $this->createMock(UserService::class);

        // Instantiate the controller with the mocked service
        $this->userController = new UserController($this->userService);
    }

    public function testCreateUserSuccess()
    {
        $this->userService->method('createUser')->willReturn([
            'status' => 201,
            'body' => json_encode(['message' => 'User created successfully'])
        ]);

        $result = $this->userController->createUser($this->request, $this->response);

        // Assertions
        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(201, $result->getStatusCode());
        $this->assertEquals('{"message":"User created successfully"}', (string)$result->getBody());
    }

    public function testCreateUserAlreadyExists()
    {
        $this->userService->method('createUser')->willReturn([
            'status' => 409,
            'body' => json_encode(['error' => 'User already exists'])
        ]);

        $result = $this->userController->createUser($this->request, $this->response);

        // Assertions
        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(409, $result->getStatusCode());
        $this->assertStringContainsString('error', (string)$result->getBody());
    }

    public function testGetUserSuccess()
    {
        $this->userService->method('getUsers')->willReturn([
            'status' => 200,
            'body' => json_encode(['username' => 'testuser', 'email' => 'testuser@example.com'])
        ]);

        $result = $this->userController->getUsers($this->request, $this->response);

        // Assertions
        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('{"username":"testuser","email":"testuser@example.com"}', (string)$result->getBody());
    }

    public function testGetUserNotFound()
    {
        $this->userService->method('getUsers')->willReturn([
            'status' => 404,
            'body' => json_encode(['error' => 'User not found'])
        ]);

        $result = $this->userController->getUsers($this->request, $this->response);

        // Assertions
        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(404, $result->getStatusCode());
        $this->assertStringContainsString('error', (string)$result->getBody());
    }
}
