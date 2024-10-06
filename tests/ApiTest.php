<?php

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;
use App\SQLiteConnection;
class ApiTest extends TestCase {
    private $pdo;
    private $client;
    
    protected function setUp(): void {
        $this->client = new Client(['base_uri' => 'http://localhost:8000']);
        // Setup SQLite in-memory database for testing
        $this->pdo = new PDO('sqlite::memory:');
        // Create necessary tables for testing
        $this->pdo->exec("
            CREATE TABLE users (id INTEGER PRIMARY KEY, username TEXT);
            CREATE TABLE groups (id INTEGER PRIMARY KEY, name TEXT, created_by INTEGER);
            CREATE TABLE group_users (group_id INTEGER, user_id INTEGER);
        ");

        // Seed with a test user
        $stmt = $this->pdo->prepare("INSERT INTO users (username) VALUES (:username)");
        $stmt->execute([':username' => 'testuser']);
        
        // Start a transaction
        $this->pdo->beginTransaction();
    }

    protected function tearDown(): void {
        // Roll back the transaction
        $this->pdo->rollBack();
    }

    public function testCreateGroupSuccess() {
        // First, create the user by making a request to the API
        $userInput = array(
            'username' => 'testuser1'
        );
        $userResponse = $this->client->request('POST', '/users', [
            'json' => $userInput
        ]);
        $this->assertEquals(200, $userResponse->getStatusCode(), 'Failed to create test user');
    
        // Now, attempt to create the group
        $groupInput = array(
            'group_name' => 'Test Group1',
            'username' => 'testuser1'
        );
        $groupResponse = $this->client->request('POST', '/groups', [
            'json' => $groupInput
        ]);
    
        $this->assertEquals(200, $groupResponse->getStatusCode());
        $groupData = json_decode($groupResponse->getBody(), true);
        $this->assertEquals('Group created successfully', $groupData['message']);
    }
    

    // Test case for group already exists
    public function testCreateGroupAlreadyExists() {
        // First, create the group
        $input = [
            'group_name' => 'Existing Group',
            'username' => 'testuser'
        ];
        $this->callApi('/groups', 'POST', $input);

        // Try to create the same group again
        $response = $this->callApi('/groups', 'POST', $input);

        $this->assertEquals(409, $response['status']);
        $this->assertEquals('Group already exists', $response['data']['message']);
    }

    // Test case for missing username
    public function testCreateGroupMissingUsername() {
        $input = [
            'group_name' => 'Group Without Username'
        ];
        $response = $this->callApi('/groups', 'POST', $input);

        $this->assertEquals(400, $response['status']);
        $this->assertEquals('Invalid input: both group_name and username are required', $response['data']['message']);
    }

    // Test case for missing group name
    public function testCreateGroupMissingGroupName() {
        $input = [
            'username' => 'testuser'
        ];
        $response = $this->callApi('/groups', 'POST', $input);

        $this->assertEquals(400, $response['status']);
        $this->assertEquals('Invalid input: both group_name and username are required', $response['data']['message']);
    }

    // Mock API call
    private function callApi($uri, $method, $data) {
        // Simulate a request to the API
        // You may want to adjust this according to your routing logic
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['REQUEST_URI'] = $uri;
        
        // Simulate input data
        file_put_contents('php://input', json_encode($data));

        ob_start(); // Start output buffering
        include __DIR__ . '/../api.php'; // Path to your API file
        $output = ob_get_clean(); // Get output

        return json_decode($output, true); // Return as array
    }
}
