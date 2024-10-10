<?php

namespace Tests\Services;

use App\Services\MessageService;
use App\Services\UserService;
use App\Services\GroupService;
use PHPUnit\Framework\TestCase;
use PDO;
use PDOStatement;

class MessageServiceTest extends TestCase {
    private $mockPdo;
    private $mockUserService;
    private $mockGroupService;
    private $messageService;

    protected function setUp(): void {
        $this->mockPdo = $this->createMock(PDO::class);
        $this->mockUserService = $this->createMock(UserService::class);
        $this->mockGroupService = $this->createMock(GroupService::class);

        // Instantiate the MessageService with the mocked PDO, UserService, and GroupService
        $this->messageService = new MessageService($this->mockPdo, $this->mockUserService, $this->mockGroupService);
    }

    public function testSendMessageSuccess() {
        // Set up mock for user validation
        $this->mockUserService->method('getUserId')->willReturn(1);
        $this->mockGroupService->method('getGroupId')->willReturn(1);

        // Mock the statement for checking group membership
        $mockStatement = $this->createMock(PDOStatement::class);
        $mockStatement->method('execute')->willReturn(true);
        $mockStatement->method('fetch')->willReturn(['user_id' => 1]); // Simulate user is a member of the group

        // Mock the insert statement for sending a message
        $mockInsertStatement = $this->createMock(PDOStatement::class);
        $mockInsertStatement->method('execute')->willReturn(true);

        // Setup the mock PDO to return the correct statements
        $this->mockPdo->method('prepare')
            ->willReturnCallback(function ($sql) use ($mockStatement, $mockInsertStatement) {
                if (strpos($sql, 'SELECT * FROM group_users') !== false) {
                    return $mockStatement;
                } elseif (strpos($sql, 'INSERT INTO messages') !== false) {
                    return $mockInsertStatement;
                }
            });

        $result = $this->messageService->sendMessage([
            'group_name' => 'Test Group',
            'username' => 'testuser',
            'message' => 'Hello, group!'
        ]);

        $this->assertEquals(201, $result['status']);
        $this->assertEquals(json_encode(["message" => "Message sent successfully"]), $result['body']);
    }

    public function testSendMessageUserNotFound() {
        // Set up mock for user validation to return null
        $this->mockUserService->method('getUserId')->willReturn(null);

        $result = $this->messageService->sendMessage([
            'group_name' => 'Test Group',
            'username' => 'testuser',
            'message' => 'Hello, group!'
        ]);

        $this->assertEquals(404, $result['status']);
        $this->assertEquals(json_encode(['error' => 'User not found']), $result['body']);
    }

    public function testSendMessageGroupNotFound() {
        // Set up mock for user validation
        $this->mockUserService->method('getUserId')->willReturn(1);
        $this->mockGroupService->method('getGroupId')->willReturn(null); // Simulate group not found

        $result = $this->messageService->sendMessage([
            'group_name' => 'Test Group',
            'username' => 'testuser',
            'message' => 'Hello, group!'
        ]);

        $this->assertEquals(404, $result['status']);
        $this->assertEquals(json_encode(['error' => 'Group not found']), $result['body']);
    }

    public function testSendMessageUserNotMemberOfGroup() {
        // Set up mock for user validation
        $this->mockUserService->method('getUserId')->willReturn(1);
        $this->mockGroupService->method('getGroupId')->willReturn(1);

        // Mock the statement to simulate user not being a member of the group
        $mockStatement = $this->createMock(PDOStatement::class);
        $mockStatement->method('execute')->willReturn(true);
        $mockStatement->method('fetch')->willReturn(false); // User is not a member

        $this->mockPdo->method('prepare')
            ->willReturn($mockStatement);

        $result = $this->messageService->sendMessage([
            'group_name' => 'Test Group',
            'username' => 'testuser',
            'message' => 'Hello, group!'
        ]);

        $this->assertEquals(403, $result['status']);
        $this->assertEquals(json_encode(['error' => 'User is not a member of this group']), $result['body']);
    }

    public function testSendMessageInsertFail() {
        // Set up mock for user validation
        $this->mockUserService->method('getUserId')->willReturn(1);
        $this->mockGroupService->method('getGroupId')->willReturn(1);

        // Mock the statement for checking group membership
        $mockStatement = $this->createMock(PDOStatement::class);
        $mockStatement->method('execute')->willReturn(true);
        $mockStatement->method('fetch')->willReturn(['user_id' => 1]); // Simulate user is a member of the group

        // Mock the insert statement to simulate failure
        $mockInsertStatement = $this->createMock(PDOStatement::class);
        $mockInsertStatement->method('execute')->willThrowException(new \PDOException("Insert failed"));

        $this->mockPdo->method('prepare')
            ->willReturnCallback(function ($sql) use ($mockStatement, $mockInsertStatement) {
                if (strpos($sql, 'SELECT * FROM group_users') !== false) {
                    return $mockStatement;
                } elseif (strpos($sql, 'INSERT INTO messages') !== false) {
                    return $mockInsertStatement;
                }
            });

        $result = $this->messageService->sendMessage([
            'group_name' => 'Test Group',
            'username' => 'testuser',
            'message' => 'Hello, group!'
        ]);

        $this->assertEquals(500, $result['status']);
        $this->assertEquals(json_encode(['error' => 'Failed to send message: Insert failed']), $result['body']);
    }

    public function testListMessagesSuccess() {
        // Set up mock for user validation
        $this->mockUserService->method('getUserId')->willReturn(1);
        $this->mockGroupService->method('getGroupId')->willReturn(1);

        // Mock the statement for checking group membership
        $mockMembershipStatement = $this->createMock(PDOStatement::class);
        $mockMembershipStatement->method('execute')->willReturn(true);
        $mockMembershipStatement->method('fetch')->willReturn(['user_id' => 1]); // User is a member of the group

        // Mock the statement for fetching messages
        $mockMessageStatement = $this->createMock(PDOStatement::class);
        $mockMessageStatement->method('execute')->willReturn(true);
        $mockMessageStatement->method('fetchAll')->willReturn([
            ['message' => 'Hello, group!', 'username' => 'testuser', 'created_at' => '2024-10-10 10:00:00']
        ]);

        $this->mockPdo->method('prepare')
            ->willReturnCallback(function ($sql) use ($mockMembershipStatement, $mockMessageStatement) {
                if (strpos($sql, 'SELECT * FROM group_users') !== false) {
                    return $mockMembershipStatement;
                } elseif (strpos($sql, 'SELECT messages.message, users.username, messages.created_at') !== false) {
                    return $mockMessageStatement;
                }
            });

        $result = $this->messageService->listMessages([
            'group_name' => 'Test Group',
            'username' => 'testuser'
        ]);

        $this->assertEquals(200, $result['status']);
        $this->assertEquals(json_encode([
            ['message' => 'Hello, group!', 'username' => 'testuser', 'created_at' => '2024-10-10 10:00:00']
        ]), $result['body']);
    }

    public function testListMessagesUserNotFound() {
        // Set up mock for user validation to return null
        $this->mockUserService->method('getUserId')->willReturn(null);

        $result = $this->messageService->listMessages([
            'group_name' => 'Test Group',
            'username' => 'testuser'
        ]);

        $this->assertEquals(404, $result['status']);
    }
}