<?php
namespace Tests\Controllers;

use App\Controllers\AdminController;
use App\Services\AdminService; // Ensure the service is imported
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Request; // Import Slim's Request class
use Slim\Psr7\Response; // Import Slim's Response class

class AdminControllerTest extends TestCase
{
    public function testClearAll()
    {
        // Create a mock request
        $request = $this->createMock(ServerRequestInterface::class);

        // Create a mock response
        $response = new Response();

        // Create a mock for the AdminService
        $adminService = $this->createMock(AdminService::class);
        $adminService->method('clearAllEntries')->willReturn([
            'status' => 200,
            'body' => json_encode(['message' => 'All entries cleared successfully'])
        ]);

        // Instantiate the controller with the mocked service
        $controller = new AdminController($adminService);

        // Call the method
        $result = $controller->clearAllEntries($request, $response);

        // Assertions
        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('{"message":"All entries cleared successfully"}', (string)$result->getBody());
    }

    public function testClearAllError()
    {
        // Create a mock request
        $request = $this->createMock(ServerRequestInterface::class);
        $response = new Response();

        // Create a mock for the AdminService
        $adminService = $this->createMock(AdminService::class);
        $adminService->method('clearAllEntries')->willReturn([
            'status' => 500,
            'body' => json_encode(['error' => 'An unexpected error occurred'])
        ]);

        // Instantiate the controller with the mocked service
        $controller = new AdminController($adminService);

        // Call the method
        $result = $controller->clearAllEntries($request, $response);

        // Assertions
        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(500, $result->getStatusCode());
        $this->assertStringContainsString('error', (string)$result->getBody());
    }
}
