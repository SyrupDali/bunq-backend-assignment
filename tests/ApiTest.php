<?php

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;
use App\SQLiteConnection;
use App\Config;
use App\Database;

class ApiTest extends TestCase {
    private static $pdo;
    private $client;

    // This method runs before each test
    protected function setUp(): void {
        $this->client = new Client(['base_uri' => 'http://localhost:8000']);
        // Seed with a test user
        $this->client->request('DELETE', '/clear');
    }

    // This method runs after each test
    protected function tearDown(): void {
        // Roll back the transaction
        $this->client->request('DELETE', '/clear');
    }

    private function seedTestUser($username) {
        $input = [
            'username' => $username
        ];
        $this->client->request('POST', '/users', [
            'json' => $input
        ]);
    }

    public function testCreateGroupSuccess() {
        // First, create the user by making a request to the API
        
        $this->seedTestUser('testuser');
        
        // Now, attempt to create the group
        $groupInput = array(
            'group_name' => 'Test Group1',
            'username' => 'testuser'
        );
        $groupResponse = $this->client->request('POST', '/groups', [
            'json' => $groupInput
        ]);
    
        $this->assertEquals(201, $groupResponse->getStatusCode());
        $groupData = json_decode($groupResponse->getBody(), true);
        $this->assertEquals('Group created successfully', $groupData['message']);
    }
    

    // Test case for group already exists
    public function testCreateGroupAlreadyExists() {
        $this->seedTestUser('testuser');
        // First, create the group
        $input = [
            'group_name' => 'Existing Group',
            'username' => 'testuser'
        ];
        $this->client->request('POST', '/groups', [
            'json' => $input
        ]);
        // Try to create the same group again
        try {
            $response = $this->client->request('POST', '/groups', [
                'json' => $input
            ]);
        } catch (GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse(); // Get the response from the exception
        }
    
        $this->assertEquals(409, $response->getStatusCode());
        $this->assertEquals('Group already exists', json_decode($response->getBody(), true)['error']);
    }

    // Test case for missing username
    public function testCreateGroupMissingUsername() {
        $input = [
            'group_name' => 'Group Without Username'
        ];
        try {
            $response = $this->client->request('POST', '/groups', [
                'json' => $input
            ]);
        } catch (GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse(); // Get the response from the exception
        }
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('Invalid input: both group_name and username are required', json_decode($response->getBody(), true)['error']);
    }

    // Test case for missing group name
    public function testCreateGroupMissingGroupName() {
        $input = [
            'username' => 'testuser'
        ];
        try {
            $response = $this->client->request('POST', '/groups', [
                'json' => $input
            ]);
        }
        catch (GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse(); // Get the response from the exception
        }
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('Invalid input: both group_name and username are required', json_decode($response->getBody(), true)['error']);
    }
}
