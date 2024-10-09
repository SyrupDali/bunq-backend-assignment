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

    // Test for creating a user
    public function testCreateUserSuccess() {
        $input = ['username' => 'newuser'];
        $response = $this->client->request('POST', '/users', [
            'json' => $input
        ]);

        $this->assertEquals(201, $response->getStatusCode());
        $responseData = json_decode($response->getBody(), true);
        $this->assertEquals('User created successfully', $responseData['message']);
    }

    // Test for creating a user with an existing username
    public function testCreateUserAlreadyExists() {
        $this->seedTestUser('existinguser');

        $input = ['username' => 'existinguser'];
        try {
            $response = $this->client->request('POST', '/users', [
                'json' => $input
            ]);
        } catch (GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse(); // Get the response from the exception
        }

        $this->assertEquals(409, $response->getStatusCode());
        $this->assertEquals('Username already exists', json_decode($response->getBody(), true)['error']);
    }

    // Test for joining a group successfully
    public function testJoinGroupSuccess() {
        $this->seedTestUser('testuser');
        
        // Create a group to join
        $groupInput = [
            'group_name' => 'Test Group',
            'username' => 'testuser'
        ];
        $this->client->request('POST', '/groups', [
            'json' => $groupInput
        ]);
        
        $this->seedTestUser('anotheruser');
        // Now join the group
        $joinInput = [
            'username' => 'anotheruser',
            'group_name' => 'Test Group'
        ];
        $response = $this->client->request('POST', '/groups/join', [
            'json' => $joinInput
        ]);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals('User joined group successfully', json_decode($response->getBody(), true)['message']);
    }

    // Test for joining a non-existent group
    public function testJoinNonExistentGroup() {
        $this->seedTestUser('testuser');
        
        $joinInput = [
            'username' => 'testuser',
            'group_name' => 'Non-existent Group'
        ];
        
        try {
            $response = $this->client->request('POST', '/groups/join', [
                'json' => $joinInput
            ]);
        } catch (GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse(); // Get the response from the exception
        }

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals('Group not found', json_decode($response->getBody(), true)['error']);
    }

    // Test for sending a message successfully
    public function testSendMessageSuccess() {
        $this->seedTestUser('testuser');
        
        // Create a group to send messages to
        $groupInput = [
            'group_name' => 'Test Group',
            'username' => 'testuser'
        ];
        $this->client->request('POST', '/groups', [
            'json' => $groupInput
        ]);
        
        $this->seedTestUser('anotheruser');
        // Join the group
        $joinInput = [
            'username' => 'anotheruser',
            'group_name' => 'Test Group'
        ];
        $this->client->request('POST', '/groups/join', [
            'json' => $joinInput
        ]);
        
        // Send a message
        $messageInput = [
            'message' => 'Hello Group!',
            'username' => 'anotheruser',
            'group_name' => 'Test Group'
        ];
        $response = $this->client->request('POST', '/groups/messages', [
            'json' => $messageInput
        ]);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals('Message sent successfully', json_decode($response->getBody(), true)['message']);
    }

    // Test for sending a message when user is not in the group
    public function testSendMessageUserNotInGroup() {
        $this->seedTestUser('testuser');

        // Create a group but do not join
        $groupInput = [
            'group_name' => 'Test Group',
            'username' => 'testuser'
        ];
        $this->client->request('POST', '/groups', [
            'json' => $groupInput
        ]);
        
        $this->seedTestUser('anotheruser');
        // Attempt to send a message
        $messageInput = [
            'message' => 'Hello Group!',
            'username' => 'anotheruser',
            'group_name' => 'Test Group'
        ];
        
        try {
            $response = $this->client->request('POST', '/groups/messages', [
                'json' => $messageInput
            ]);
        } catch (GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse(); // Get the response from the exception
        }

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEquals('User is not part of this group', json_decode($response->getBody(), true)['error']);
    }

    // Test for listing messages successfully
    public function testListMessagesSuccess() {
        $this->seedTestUser('testuser');

        // Create and join the group
        $groupInput = [
            'group_name' => 'Test Group',
            'username' => 'testuser'
        ];
        $this->client->request('POST', '/groups', [
            'json' => $groupInput
        ]);

        $this->seedTestUser('anotheruser');
        $joinInput = [
            'username' => 'anotheruser',
            'group_name' => 'Test Group'
        ];
        $this->client->request('POST', '/groups/join', [
            'json' => $joinInput
        ]);
        
        // Send a message
        $messageInput = [
            'message' => 'Hello Group!',
            'username' => 'testuser',
            'group_name' => 'Test Group'
        ];
        $this->client->request('POST', '/groups/messages', [
            'json' => $messageInput
        ]);
        
        // Now list the messages
        $listInput = [
            'group_name' => 'Test Group',
            'username' => 'anotheruser'
        ];
        $response = $this->client->request('GET', '/groups/messages', [
            'json' => $listInput
        ]);

        $this->assertEquals(200, $response->getStatusCode());
        $messages = json_decode($response->getBody(), true);
        $this->assertCount(1, $messages);
        $this->assertEquals('Hello Group!', $messages[0]['message']);
    }

    // Test for listing messages when user is not in the group
    public function testListMessagesUserNotInGroup() {
        $this->seedTestUser('testuser');

        // Create a group but do not join
        $groupInput = [
            'group_name' => 'Test Group',
            'username' => 'testuser'
        ];
        $this->client->request('POST', '/groups', [
            'json' => $groupInput
        ]);

        $this->seedTestUser('anotheruser');
        // Attempt to list messages
        $listInput = [
            'group_name' => 'Test Group',
            'username' => 'anotheruser'
        ];
        
        try {
            $response = $this->client->request('GET', '/groups/messages', [
                'json' => $listInput
            ]);
        } catch (GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse(); // Get the response from the exception
        }

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEquals('User is not part of this group', json_decode($response->getBody(), true)['error']);
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
