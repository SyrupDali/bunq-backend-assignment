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
    private $request;
    private $response;
    private $adminService;
    private $adminController;

    // This method is called before every test
    protected function setUp(): void
    {
        // Create a mock request
        $this->request = $this->createMock(ServerRequestInterface::class);
        // Use a real response object
        $this->response = new Response();

        // Create a mock for the AdminService
        $this->adminService = $this->createMock(AdminService::class);

        // Instantiate the controller with the mocked service
        $this->adminController = new AdminController($this->adminService);
    }

    public function testClearAll()
    {   
        $this->adminService->method('clearAllEntries')->willReturn([
            'status' => 200,
            'body' => json_encode(['message' => 'All entries cleared successfully'])
        ]);
        
        $result = $this->adminController->clearAllEntries($this->request, $this->response);

        // Assertions
        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('{"message":"All entries cleared successfully"}', (string)$result->getBody());
    }

    public function testClearAllError()
    {   
        $this->adminService->method('clearAllEntries')->willReturn([
            'status' => 500,
            'body' => json_encode(['error' => 'An unexpected error occurred'])
        ]);
        
        $result = $this->adminController->clearAllEntries($this->request, $this->response);

        // Assertions
        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(500, $result->getStatusCode());
        $this->assertStringContainsString('error', (string)$result->getBody());
    }
}
