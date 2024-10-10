<?php

namespace Tests\Controllers;

use App\Controllers\UserController;
use App\Services\UserService;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Request; // Import Slim's Request class
use Slim\Psr7\Response; // Import Slim's Response class

class UserControllerTest extends TestCase {
    public function testCreateUserSuccess() {
        // Create a mock request
        $request = $this->createMock(ServerRequestInterface::class);
        $response = new Response();

        // Create a mock for the UserService
        $userService = $this->createMock(UserService::class);
        $userService->method('createUser')->willReturn([
            'status' => 201,
            'body' => json_encode(['message' => 'User created successfully'])
        ]);

        // Instantiate the controller with the mocked service
        $controller = new UserController($userService);

        // Call the method
        $result = $controller->createUser($request, $response);

        // Assertions
        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(201, $result->getStatusCode());
        $this->assertEquals('{"message":"User created successfully"}', (string)$result->getBody());
    }

    public function testCreateUserAlreadyExists() {
        // Create a mock request
        $request = $this->createMock(ServerRequestInterface::class);
        $response = new Response();

        // Create a mock for the UserService
        $userService = $this->createMock(UserService::class);
        $userService->method('createUser')->willReturn([
            'status' => 409,
            'body' => json_encode(['error' => 'User already exists'])
        ]);

        // Instantiate the controller with the mocked service
        $controller = new UserController($userService);

        // Call the method
        $result = $controller->createUser($request, $response);

        // Assertions
        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(409, $result->getStatusCode());
        $this->assertStringContainsString('error', (string)$result->getBody());
    }

    public function testGetUserSuccess() {
        // Create a mock request
        $request = $this->createMock(ServerRequestInterface::class);
        $response = new Response();

        // Create a mock for the UserService
        $userService = $this->createMock(UserService::class);
        $userService->method('getUsers')->willReturn([
            'status' => 200,
            'body' => json_encode(['username' => 'testuser', 'email' => 'testuser@example.com'])
        ]);

        // Instantiate the controller with the mocked service
        $controller = new UserController($userService);

        // Call the method
        $result = $controller->getUsers($request, $response);

        // Assertions
        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('{"username":"testuser","email":"testuser@example.com"}', (string)$result->getBody());
    }

    public function testGetUserNotFound() {
        // Create a mock request
        $request = $this->createMock(ServerRequestInterface::class);
        $response = new Response();

        // Create a mock for the UserService
        $userService = $this->createMock(UserService::class);
        $userService->method('getUsers')->willReturn([
            'status' => 404,
            'body' => json_encode(['error' => 'User not found'])
        ]);

        // Instantiate the controller with the mocked service
        $controller = new UserController($userService);

        // Call the method
        $result = $controller->getUsers($request, $response);

        // Assertions
        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(404, $result->getStatusCode());
        $this->assertStringContainsString('error', (string)$result->getBody());
    }
}
