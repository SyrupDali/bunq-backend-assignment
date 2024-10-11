<?php

namespace Tests\Controllers;

use App\Controllers\MessageController;
use App\Services\MessageService;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Request; // Import Slim's Request class
use Slim\Psr7\Response; // Import Slim's Response class

class MessageControllerTest extends TestCase
{
    private $request;
    private $response;
    private $messageService;
    private $messageController;

    // This method is called before every test
    protected function setUp(): void
    {
        // Create a mock request
        $this->request = $this->createMock(ServerRequestInterface::class);
        // Use a real response object
        $this->response = new Response();

        // Create a mock for the MessageService
        $this->messageService = $this->createMock(MessageService::class);

        // Instantiate the controller with the mocked service
        $this->messageController = new MessageController($this->messageService);
    }

    public function testSendMessageSuccess()
    {
        $this->messageService->method('sendMessage')->willReturn([
            'status' => 201,
            'body' => json_encode(['message' => 'Message sent successfully'])
        ]);

        // Call the method
        $result = $this->messageController->sendMessage($this->request, $this->response);

        // Assertions
        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(201, $result->getStatusCode());
        $this->assertEquals('{"message":"Message sent successfully"}', (string)$result->getBody());
    }

    public function testSendMessageUserNotFound()
    {
        $this->messageService->method('sendMessage')->willReturn([
            'status' => 404,
            'body' => json_encode(['error' => 'User not found'])
        ]);

        // Call the method
        $result = $this->messageController->sendMessage($this->request, $this->response);

        // Assertions
        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(404, $result->getStatusCode());
        $this->assertStringContainsString('error', (string)$result->getBody());
    }

    public function testListMessagesSuccess()
    {
        $this->messageService->method('listMessages')->willReturn([
            'status' => 200,
            'body' => json_encode([
                ['message' => 'Hello, group!', 'username' => 'testuser', 'created_at' => '2024-10-10 10:00:00']
            ])
        ]);
        // Call the method
        $result = $this->messageController->listMessages($this->request, $this->response);

        // Assertions
        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('[{"message":"Hello, group!","username":"testuser","created_at":"2024-10-10 10:00:00"}]', (string)$result->getBody());
    }

    public function testListMessagesUserNotFound()
    {
        $this->messageService->method('listMessages')->willReturn([
            'status' => 404,
            'body' => json_encode(['error' => 'User not found'])
        ]);

        $result = $this->messageController->listMessages($this->request, $this->response);

        // Assertions
        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(404, $result->getStatusCode());
        $this->assertStringContainsString('error', (string)$result->getBody());
    }
}
