<?php

namespace Tests\Services;

use App\Services\GroupService;
use App\Services\UserService; // Ensure the UserService is imported
use PHPUnit\Framework\TestCase;
use PDO;
use PDOStatement; // Import PDOStatement

class GroupServiceTest extends TestCase {
    private $mockPdo;
    private $mockUserService;
    private $groupService;

    protected function setUp(): void {
        $this->mockPdo = $this->createMock(PDO::class);
        $this->mockUserService = $this->createMock(UserService::class);
        
        // Instantiate the GroupService with the mocked PDO and UserService
        $this->groupService = new GroupService($this->mockPdo, $this->mockUserService);
    }

    public function testCreateGroupSuccess() {
        // Set up mock for user validation
        $this->mockUserService->method('getUserId')->willReturn(1);

        // Mock the first statement for group existence check
        $mockStatement = $this->createMock(PDOStatement::class);
        $mockStatement->method('execute')->willReturn(true);
        $mockStatement->method('fetch')->willReturn(null); // Simulate group does not exist

        // Mock the second statement for inserting into groups
        $mockStatement2 = $this->createMock(PDOStatement::class);
        $mockStatement2->method('execute')->willReturn(true);

        // Mock the third statement for inserting into group_users
        $mockStatement3 = $this->createMock(PDOStatement::class);
        $mockStatement3->method('execute')->willReturn(true);

        $this->mockPdo->method('prepare')
            ->willReturnCallback(function ($sql) use ($mockStatement, $mockStatement2, $mockStatement3) {
                if (strpos($sql, 'SELECT id FROM groups') !== false) {
                    return $mockStatement;
                } elseif (strpos($sql, 'INSERT INTO groups') !== false) {
                    return $mockStatement2;
                } elseif (strpos($sql, 'INSERT INTO group_users') !== false) {
                    return $mockStatement3;
                }
            });

        $this->mockPdo->method('lastInsertId')->willReturn("1"); // Simulate the last inserted ID
        // Call the service method
        $result = $this->groupService->createGroup(['group_name' => 'Test Group', 'username' => 'testuser']);

        $this->assertEquals(201, $result['status']);
        $this->assertEquals(json_encode([
            "message" => "Group created successfully",
            "group_id" => "1", // Assuming the ID of the newly created group is 1
            "group_name" => "Test Group",
            "created_by" => "testuser"
        ]), $result['body']);
    }

    public function testCreateGroupUserNotFound() {
        // Set up mock for user validation to return null
        $this->mockUserService->method('getUserId')->willReturn(null);

        $result = $this->groupService->createGroup(['group_name' => 'Test Group', 'username' => 'testuser']);

        $this->assertEquals(404, $result['status']);
        $this->assertEquals(json_encode(['error' => 'User not found']), $result['body']);
    }

    public function testCreateGroupAlreadyExists() {
        // Set up mock for user validation
        $this->mockUserService->method('getUserId')->willReturn(1);

        // Mock the PDO to simulate the group already existing
        $mockStatement = $this->createMock(PDOStatement::class);
        $mockStatement->method('execute')->willReturn(true);
        $mockStatement->method('fetch')->willReturn(['id' => 1]); // Simulate existing group

        $this->mockPdo->method('prepare')
            ->with($this->stringContains('SELECT id FROM groups WHERE name = :group_name'))
            ->willReturn($mockStatement);

        $result = $this->groupService->createGroup(['group_name' => 'Existing Group', 'username' => 'testuser']);

        $this->assertEquals(409, $result['status']);
        $this->assertEquals(json_encode(['error' => 'Group already exists']), $result['body']);
    }

    public function testCreateGroupInsertFail()
    {
        // Set up mock for user validation
        $this->mockUserService->method('getUserId')->willReturn(1);

        // Mock the PDO to simulate the group does not exist
        $groupNotExistsStatement = $this->createMock(PDOStatement::class);
        $groupNotExistsStatement->method('execute')->willReturn(true);
        $groupNotExistsStatement->method('fetch')->willReturn(false); // Simulate non-existing group

        // Mock the insert into groups to simulate failure
        $insertGroupStatement = $this->createMock(PDOStatement::class);
        $insertGroupStatement->method('execute')->willReturn(false); // Simulate failed insert

        $this->mockPdo->method('prepare')
            ->willReturnCallback(function ($sql) use ($groupNotExistsStatement, $insertGroupStatement) {
                if (strpos($sql, 'SELECT id FROM groups') !== false) {
                    return $groupNotExistsStatement;
                } elseif (strpos($sql, 'INSERT INTO groups') !== false) {
                    return $insertGroupStatement;
                }
            });
            
        $result = $this->groupService->createGroup(['group_name' => 'Test Group', 'username' => 'testuser']);

        $this->assertEquals(500, $result['status']);
        $this->assertEquals(json_encode(['error' => 'Failed to create group']), $result['body']);
    }
}
