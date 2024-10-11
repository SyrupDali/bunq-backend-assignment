<?php

namespace Tests\Services;

use App\Services\UserService;
use PHPUnit\Framework\TestCase;
use PDO;
use PDOStatement; // Import PDOStatement

class UserServiceTest extends TestCase
{
    private $mockPdo;
    private $userService;

    protected function setUp(): void
    {
        // Create a mock for PDO
        $this->mockPdo = $this->createMock(PDO::class);

        // Instantiate the UserService with the mocked PDO
        $this->userService = new UserService($this->mockPdo);
    }

    public function testCreateUserSuccess()
    {
        // Mock the insert into users to simulate success
        $mockInsertStatement = $this->createMock(PDOStatement::class);
        $mockInsertStatement->method('execute')->willReturn(true); // Simulate successful insert

        $this->mockPdo->method('prepare')
            ->willReturn($mockInsertStatement);

        // Mock lastInsertId to return the ID of the new user
        $this->mockPdo->method('lastInsertId')->willReturn('1');

        // Call the service method
        $result = $this->userService->createUser(['username' => 'testuser', 'email' => 'testuser@example.com']);

        // Assertions
        $this->assertEquals(201, $result['status']);
        $this->assertEquals(json_encode([
            'message' => 'User created successfully',
            'user_id' => '1',
            "username" => "testuser",
        ]), $result['body']);
    }

    public function testCreateUserAlreadyExists()
    {
        // Mock the statement to simulate user already exists
        $mockCheckStatement = $this->createMock(PDOStatement::class);
        $mockCheckStatement->method('execute')->willReturn(true);
        $mockCheckStatement->method('fetch')->willReturn(['id' => 1]); // Simulate existing user

        $this->mockPdo->method('prepare')
            ->with($this->stringContains('SELECT id FROM users WHERE username = :username'))
            ->willReturn($mockCheckStatement);

        // Call the service method
        $result = $this->userService->createUser(['username' => 'existinguser', 'email' => 'existinguser@example.com']);

        // Assertions
        $this->assertEquals(409, $result['status']);
        $this->assertEquals(json_encode(['error' => 'Username already exists']), $result['body']);
    }

    public function testCreateUserInsertFail()
    {
        // Mock the check for existing user to return false
        $mockCheckStatement = $this->createMock(PDOStatement::class);
        $mockCheckStatement->method('execute')->willReturn(true);
        $mockCheckStatement->method('fetch')->willReturn(false); // Simulate user does not exist

        // Mock the insert into users to simulate failure
        $mockInsertStatement = $this->createMock(PDOStatement::class);
        $mockInsertStatement->method('execute')->willReturn(false); // Simulate failed insert

        $this->mockPdo->method('prepare')
            ->willReturnCallback(function ($sql) use ($mockCheckStatement, $mockInsertStatement) {
                if (strpos($sql, 'SELECT id FROM users') !== false) {
                    return $mockCheckStatement;
                } elseif (strpos($sql, 'INSERT INTO users') !== false) {
                    return $mockInsertStatement;
                }
            });

        // Call the service method
        $result = $this->userService->createUser(['username' => 'newuser', 'email' => 'newuser@example.com']);

        // Assertions
        $this->assertEquals(500, $result['status']);
        $this->assertEquals(json_encode(['error' => 'Failed to create user']), $result['body']);
    }

    public function testGetUsersSuccess()
    {
        // Mock the statement for fetching users
        $mockStatement = $this->createMock(PDOStatement::class);
        $mockStatement->method('fetchAll')->willReturn([
            ['id' => 1, 'username' => 'testuser1', 'email' => 'testuser1@example.com'],
            ['id' => 2, 'username' => 'testuser2', 'email' => 'testuser2@example.com'],
        ]); // Simulate multiple users

        $this->mockPdo->method('query')->willReturn($mockStatement);

        // Call the service method
        $result = $this->userService->getUsers();

        // Assertions
        $this->assertEquals(200, $result['status']);
        $this->assertEquals(json_encode([
            ['id' => 1, 'username' => 'testuser1', 'email' => 'testuser1@example.com'],
            ['id' => 2, 'username' => 'testuser2', 'email' => 'testuser2@example.com'],
        ]), $result['body']);
    }


    public function testGetUsersFail()
    {
        // Mock the query method to throw a PDOException
        $this->mockPdo->method('query')->willThrowException(new \PDOException('Database error'));

        // Call the service method
        $result = $this->userService->getUsers();

        // Assertions
        $this->assertEquals(500, $result['status']);
        $this->assertStringContainsString('Failed to fetch users', $result['body']);
    }
}
