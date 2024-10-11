<?php

namespace Tests\Controllers;

use App\Controllers\GroupController;
use App\Services\GroupService;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Response; // Import Slim's Response class

class GroupControllerTest extends TestCase
{
    private $request;
    private $response;
    private $groupService;
    private $groupController;

    // This method is called before every test
    protected function setUp(): void
    {
        // Create a mock request
        $this->request = $this->createMock(ServerRequestInterface::class);
        // Use a real response object
        $this->response = new Response();

        // Create a mock for the GroupService
        $this->groupService = $this->createMock(GroupService::class);

        // Instantiate the controller with the mocked service
        $this->groupController = new GroupController($this->groupService);
    }

    public function testCreateGroupSuccess()
    {
        $this->groupService->method('createGroup')->willReturn([
            'status' => 201,
            'body' => json_encode(['message' => 'Group created successfully'])
        ]);
        // Call the method
        $result = $this->groupController->createGroup($this->request, $this->response);

        // Assertions
        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(201, $result->getStatusCode());
        $this->assertEquals('{"message":"Group created successfully"}', (string)$result->getBody());
    }

    public function testCreateGroupAlreadyExists()
    {
        $this->groupService->method('createGroup')->willReturn([
            'status' => 409,
            'body' => json_encode(['error' => 'Group already exists'])
        ]);

        $result = $this->groupController->createGroup($this->request, $this->response);

        // Assertions
        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(409, $result->getStatusCode());
        $this->assertStringContainsString('error', (string)$result->getBody());
    }
}
