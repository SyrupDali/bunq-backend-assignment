<?php

namespace Tests\Controllers;

use App\Controllers\MessageController;
use App\Services\MessageService;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Request; // Import Slim's Request class
use Slim\Psr7\Response; // Import Slim's Response class

class MessageControllerTest extends TestCase {
    public function testSendMessageSuccess() {
        // Create a mock request
        $request = $this->createMock(ServerRequestInterface::class);
        $response = new Response();

        // Create a mock for the MessageService
        $messageService = $this->createMock(MessageService::class);
        $messageService->method('sendMessage')->willReturn([
            'status' => 201,
            'body' => json_encode(['message' => 'Message sent successfully'])
        ]);

        // Instantiate the controller with the mocked service
        $controller = new MessageController($messageService);

        // Prepare request body for the mock request
        $requestBody = json_encode([
            'group_name' => 'Test Group',
            'username' => 'testuser',
            'message' => 'Hello, group!'
        ]);

        // Mock the method getBody to return the request body
        $request->method('getBody')->willReturn(new \Slim\Psr7\Stream(fopen('php://temp', 'r+')));
        $request->getBody()->write($requestBody);
        $request->getBody()->rewind();

        // Call the method
        $result = $controller->sendMessage($request, $response);

        // Assertions
        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(201, $result->getStatusCode());
        $this->assertEquals('{"message":"Message sent successfully"}', (string)$result->getBody());
    }

    public function testSendMessageUserNotFound() {
        // Create a mock request
        $request = $this->createMock(ServerRequestInterface::class);
        $response = new Response();

        // Create a mock for the MessageService
        $messageService = $this->createMock(MessageService::class);
        $messageService->method('sendMessage')->willReturn([
            'status' => 404,
            'body' => json_encode(['error' => 'User not found'])
        ]);

        // Instantiate the controller with the mocked service
        $controller = new MessageController($messageService);

        // Prepare request body for the mock request
        $requestBody = json_encode([
            'group_name' => 'Test Group',
            'username' => 'testuser',
            'message' => 'Hello, group!'
        ]);

        // Mock the method getBody to return the request body
        $request->method('getBody')->willReturn(new \Slim\Psr7\Stream(fopen('php://temp', 'r+')));
        $request->getBody()->write($requestBody);
        $request->getBody()->rewind();

        // Call the method
        $result = $controller->sendMessage($request, $response);

        // Assertions
        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(404, $result->getStatusCode());
        $this->assertStringContainsString('error', (string)$result->getBody());
    }

    public function testListMessagesSuccess() {
        // Create a mock request
        $request = $this->createMock(ServerRequestInterface::class);
        $response = new Response();

        // Create a mock for the MessageService
        $messageService = $this->createMock(MessageService::class);
        $messageService->method('listMessages')->willReturn([
            'status' => 200,
            'body' => json_encode([
                ['message' => 'Hello, group!', 'username' => 'testuser', 'created_at' => '2024-10-10 10:00:00']
            ])
        ]);

        // Instantiate the controller with the mocked service
        $controller = new MessageController($messageService);

        // Prepare request body for the mock request
        $requestBody = json_encode([
            'group_name' => 'Test Group',
            'username' => 'testuser'
        ]);

        // Mock the method getBody to return the request body
        $request->method('getBody')->willReturn(new \Slim\Psr7\Stream(fopen('php://temp', 'r+')));
        $request->getBody()->write($requestBody);
        $request->getBody()->rewind();

        // Call the method
        $result = $controller->listMessages($request, $response);

        // Assertions
        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('[{"message":"Hello, group!","username":"testuser","created_at":"2024-10-10 10:00:00"}]', (string)$result->getBody());
    }

    public function testListMessagesUserNotFound() {
        // Create a mock request
        $request = $this->createMock(ServerRequestInterface::class);
        $response = new Response();

        // Create a mock for the MessageService
        $messageService = $this->createMock(MessageService::class);
        $messageService->method('listMessages')->willReturn([
            'status' => 404,
            'body' => json_encode(['error' => 'User not found'])
        ]);

        // Instantiate the controller with the mocked service
        $controller = new MessageController($messageService);

        // Prepare request body for the mock request
        $requestBody = json_encode([
            'group_name' => 'Test Group',
            'username' => 'testuser'
        ]);

        // Mock the method getBody to return the request body
        $request->method('getBody')->willReturn(new \Slim\Psr7\Stream(fopen('php://temp', 'r+')));
        $request->getBody()->write($requestBody);
        $request->getBody()->rewind();

        // Call the method
        $result = $controller->listMessages($request, $response);

        // Assertions
        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(404, $result->getStatusCode());
        $this->assertStringContainsString('error', (string)$result->getBody());
    }
}
