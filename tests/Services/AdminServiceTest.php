<?php
namespace Tests\Services;

use App\Services\AdminService;
use PDO;
use PDOException;
use PHPUnit\Framework\TestCase;

class AdminServiceTest extends TestCase
{
    private $pdo;
    private $adminService;

    protected function setUp(): void
    {
        // Mock the PDO object
        $this->pdo = $this->createMock(PDO::class);

        // Instantiate AdminService with the mocked PDO
        $this->adminService = new AdminService($this->pdo);
    }

    public function testClearAllEntriesSuccess()
    {
        // Mock the PDO methods used in clearAllEntries
        $this->pdo->expects($this->once())->method('beginTransaction');
        
        // Expect exec to be called 5 times, each returning an integer (rows affected)
        $this->pdo->expects($this->exactly(5))->method('exec')->willReturn(1);
        
        $this->pdo->expects($this->once())->method('commit');

        // Call the method and assert the response
        $result = $this->adminService->clearAllEntries();
        $this->assertEquals(200, $result['status']);
        $this->assertStringContainsString('All entries cleared successfully', $result['body']);
    }

    public function testClearAllEntriesFailure(){
        // Simulate a failure during the first delete query
        $this->pdo->expects($this->once())->method('beginTransaction');
        
        // Expect exec to throw an exception on the first call
        $this->pdo->expects($this->once())->method('exec')
            ->will($this->throwException(new PDOException('Simulated failure')));

        $this->pdo->expects($this->once())->method('rollBack');

        // Call the method and assert the error response
        $result = $this->adminService->clearAllEntries();
        $this->assertEquals(500, $result['status']);
        $this->assertStringContainsString('Failed to clear entries', $result['body']);
    }
}
