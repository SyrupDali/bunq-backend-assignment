<?php

namespace Tests\Controllers;

use App\Controllers\GroupController;
use App\Services\GroupService;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Request; // Import Slim's Request class
use Slim\Psr7\Response; // Import Slim's Response class

class GroupControllerTest extends TestCase {
    public function testCreateGroupSuccess() {
        // Create a mock request
        $request = $this->createMock(ServerRequestInterface::class);
        $response = new Response();

        // Create a mock for the GroupService
        $groupService = $this->createMock(GroupService::class);
        $groupService->method('createGroup')->willReturn([
            'status' => 201,
            'body' => json_encode(['message' => 'Group created successfully'])
        ]);

        // Instantiate the controller with the mocked service
        $controller = new GroupController($groupService);

        // Call the method
        $result = $controller->createGroup($request, $response);

        // Assertions
        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(201, $result->getStatusCode());
        $this->assertEquals('{"message":"Group created successfully"}', (string)$result->getBody());
    }

    public function testCreateGroupAlreadyExists() {
        // Create a mock request
        $request = $this->createMock(ServerRequestInterface::class);
        $response = new Response();

        // Create a mock for the GroupService
        $groupService = $this->createMock(GroupService::class);
        $groupService->method('createGroup')->willReturn([
            'status' => 409,
            'body' => json_encode(['error' => 'Group already exists'])
        ]);

        // Instantiate the controller with the mocked service
        $controller = new GroupController($groupService);

        // Call the method
        $result = $controller->createGroup($request, $response);

        // Assertions
        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(409, $result->getStatusCode());
        $this->assertStringContainsString('error', (string)$result->getBody());
    }
}
