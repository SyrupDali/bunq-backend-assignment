<?php

require __DIR__ . '/../vendor/autoload.php';

use App\SQLiteConnection;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

$app = AppFactory::create();
// Establish a connection to the SQLite database
$pdo = (new SQLiteConnection())->connect();

if ($pdo == null) {
    die('Whoops, could not connect to the SQLite database!');
}

// Function to fetch all users
function get_users($pdo) {
    try {
        $stmt = $pdo->query("SELECT * FROM users");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return [
            'status' => 200, // Set the status code for OK
            'body' => json_encode($users)
        ];
    } catch (PDOException $e) {
        return error_response("Failed to fetch users: " . $e->getMessage(), 500);
    }
}

// Function to create a new user
function create_user($pdo, $input) {
    $username = $input['username'] ?? null;

    if (!$username) {
        return error_response("Invalid input: username is required", 400);
    }

    // Check if the username already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username");
    $stmt->execute([':username' => $username]);
    if ($stmt->fetch()) {
        return error_response("Username already exists", 409);
    }

    // Create the user
    $stmt = $pdo->prepare("INSERT INTO users (username) VALUES (:username)");
    if ($stmt->execute([':username' => $username])) {
        return [
            'status' => 201, // Set the status code for created
            'body' => json_encode([
                "message" => "User created successfully",
                "user_id" => $pdo->lastInsertId(),
                "username" => $username
            ])
        ];
    } else {
        return error_response("Failed to create user", 500);
    }
}


// Function to fetch all groups
function get_groups($pdo) {
    try {
        $stmt = $pdo->query("SELECT * FROM groups");
        $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return [
            'status' => 200, // Set the status code for OK
            'body' => json_encode($groups)
        ];
    } catch (PDOException $e) {
        return error_response("Failed to fetch groups: " . $e->getMessage(), 500);
    }
}

// Function to create a new group
function create_group($pdo, $input) {
    $group_name = $input['group_name'] ?? null;
    $username = $input['username'] ?? null;

    if (!$group_name || !$username) {
        return error_response("Invalid input: both group_name and username are required", 400);
    }

    // Lookup the user ID from the username
    $user_id = get_user_id($pdo, $username);
    if (!$user_id) {
        return error_response("User not found", 404);
    }

    // Check if the group already exists
    $stmt = $pdo->prepare("SELECT id FROM groups WHERE name = :group_name");
    $stmt->execute([':group_name' => $group_name]);
    if ($stmt->fetch()) {
        return error_response("Group already exists", 409);
    }

    // Create the group
    $stmt = $pdo->prepare("INSERT INTO groups (name, created_by) VALUES (:group_name, :created_by)");
    if ($stmt->execute([':group_name' => $group_name, ':created_by' => $user_id])) {
        $group_id = $pdo->lastInsertId();
        
        // Add the creator to the group_users table
        $stmt = $pdo->prepare("INSERT INTO group_users (group_id, user_id) VALUES (:group_id, :user_id)");
        $stmt->execute([':group_id' => $group_id, ':user_id' => $user_id]);

        return [
            'status' => 201, // Set the status code for created
            'body' => json_encode([
                "message" => "Group created successfully",
                "group_id" => $group_id,
                "group_name" => $group_name,
                "created_by" => $username
            ])
        ];
    } else {
        return error_response("Failed to create group", 500);
    }
}

// Function to join a group
function join_group($pdo, $input) {
    $username = $input['username'] ?? null;
    $group_name = $input['group_name'] ?? null;

    if (!$username || !$group_name) {
        return error_response("Invalid input: both username and group_name are required", 400);
    }

    // Lookup user ID and group ID
    $user_id = get_user_id($pdo, $username);
    $group_id = get_group_id($pdo, $group_name);

    if (!$user_id) {
        return error_response("User not found", 404);
    }

    if (!$group_id) {
        return error_response("Group not found", 404);
    }

    // Check if the user is already in the group
    $stmt = $pdo->prepare("SELECT * FROM group_users WHERE user_id = :user_id AND group_id = :group_id");
    $stmt->execute([':user_id' => $user_id, ':group_id' => $group_id]);
    if ($stmt->fetch()) {
        return error_response("User already joined the group", 409);
    }

    // Add the user to the group
    $stmt = $pdo->prepare("INSERT INTO group_users (group_id, user_id) VALUES (:group_id, :user_id)");
    if ($stmt->execute([':group_id' => $group_id, ':user_id' => $user_id])) {
        return [
            'status' => 201, // Set the status code for created
            'body' => json_encode([
                "message" => "User joined group successfully"
            ])
        ];
    } else {
        return error_response("Failed to join group", 500);
    }
}

// Function to send a message
function send_message($pdo, $input) {
    $message = $input['message'] ?? null;
    $username = $input['username'] ?? null;
    $group_name = $input['group_name'] ?? null;

    if (!$message || !$username || !$group_name) {
        return error_response("Invalid input: message, username, and group_name are required", 400);
    }

    // Lookup the user ID and group ID
    $user_id = get_user_id($pdo, $username);
    $group_id = get_group_id($pdo, $group_name);

    if (!$user_id) {
        return error_response("User not found", 404);
    }

    if (!$group_id) {
        return error_response("Group not found", 404);
    }

    // Check if the user is part of the group
    $stmt = $pdo->prepare("SELECT * FROM group_users WHERE group_id = :group_id AND user_id = :user_id");
    $stmt->execute([':group_id' => $group_id, ':user_id' => $user_id]);
    if (!$stmt->fetch()) {
        return error_response("User is not part of this group", 403);
    }

    // Insert the message
    $stmt = $pdo->prepare("INSERT INTO messages (group_id, user_id, message) VALUES (:group_id, :user_id, :message)");
    if ($stmt->execute([':group_id' => $group_id, ':user_id' => $user_id, ':message' => $message])) {
        return [
            'status' => 201, // Set the status code for created
            'body' => json_encode([
                "message" => "Message sent successfully"
            ])
        ];
    } else {
        return error_response("Failed to send message", 500);
    }
}

// Function to list messages
function list_messages($pdo, $input) {
    $group_name = $input['group_name'] ?? null;
    $username = $input['username'] ?? null;

    if (!$group_name || !$username) {
        return error_response("Invalid input: group_name and username are required", 400);
    }

    // Lookup the user ID and group ID
    $user_id = get_user_id($pdo, $username);
    $group_id = get_group_id($pdo, $group_name);

    if (!$user_id) {
        return error_response("User not found", 404);
    }

    if (!$group_id) {
        return error_response("Group not found", 404);
    }

    // Check if the user is part of the group
    $stmt = $pdo->prepare("SELECT * FROM group_users WHERE group_id = :group_id AND user_id = :user_id");
    $stmt->execute([':group_id' => $group_id, ':user_id' => $user_id]);
    if (!$stmt->fetch()) {
        return error_response("User is not part of this group", 403);
    }

    // Fetch all messages from the group
    $stmt = $pdo->prepare("SELECT messages.message, users.username, messages.created_at 
                           FROM messages 
                           JOIN users ON messages.user_id = users.id
                           WHERE messages.group_id = :group_id 
                           ORDER BY messages.created_at ASC");
    $stmt->execute([':group_id' => $group_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return [
        'status' => 200, // Set the status code for OK
        'body' => json_encode($messages)
    ];
}

// Function to clear all entries
function clear_entries($pdo) {
    try {
        // Disable foreign key checks to avoid constraint errors
        $pdo->exec("PRAGMA foreign_keys = OFF;");

        // Clear data from each table
        $pdo->exec("DELETE FROM messages");
        $pdo->exec("DELETE FROM group_users");
        $pdo->exec("DELETE FROM users");
        $pdo->exec("DELETE FROM groups");
        $pdo->exec("DELETE FROM sqlite_sequence");

        // Enable foreign key checks again
        $pdo->exec("PRAGMA foreign_keys = ON;");

        return [
            'status' => 200, // Set the status code for OK
            'body' => json_encode(["message" => "All entries cleared successfully"])
        ];
    } catch (PDOException $e) {
        return error_response("Failed to clear entries: " . $e->getMessage(), 500);
    }
}

// Standardized error response helper
function error_response($message, $code) {
    return [
        'status' => $code,
        'body' => json_encode(['error' => $message])
    ];
}

// Helper function to get user ID from username
function get_user_id($pdo, $username) {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username");
    $stmt->execute([':username' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    return $user['id'] ?? null;
}

// Helper function to get group ID from group name
function get_group_id($pdo, $group_name) {
    $stmt = $pdo->prepare("SELECT id FROM groups WHERE name = :group_name");
    $stmt->execute([':group_name' => $group_name]);
    $group = $stmt->fetch(PDO::FETCH_ASSOC);
    return $group['id'] ?? null;
}

// Define routes
$app->get('/users', function (Request $request, Response $response) use ($pdo) {
    $result = get_users($pdo);
    if (isset($result['status'])) {
        $response->getBody()->write($result['body']);
        return $response->withStatus($result['status'])->withHeader('Content-Type', 'application/json');
    }
    $response->getBody()->write(json_encode(["error" => "An unexpected error occurred."]));
    return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
});

$app->post('/users', function (Request $request, Response $response) use ($pdo) {
    $input = json_decode($request->getBody()->getContents(), true);
    $result = create_user($pdo, $input);
    if (isset($result['status'])) {
        $response->getBody()->write($result['body']);
        return $response->withStatus($result['status'])->withHeader('Content-Type', 'application/json');
    }
    $response->getBody()->write(json_encode(["error" => "An unexpected error occurred."]));
    return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
});

$app->get('/groups', function (Request $request, Response $response) use ($pdo) {
    $result = get_groups($pdo);
    if (isset($result['status'])) {
        $response->getBody()->write($result['body']);
        return $response->withStatus($result['status'])->withHeader('Content-Type', 'application/json');
    }
    $response->getBody()->write(json_encode(["error" => "An unexpected error occurred."]));
    return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
});

$app->post('/groups', function (Request $request, Response $response) use ($pdo) {
    $input = json_decode($request->getBody()->getContents(), true);
    $result = create_group($pdo, $input);
    if (isset($result['status'])) {
        $response->getBody()->write($result['body']);
        return $response->withStatus($result['status'])->withHeader('Content-Type', 'application/json');
    }
    $response->getBody()->write(json_encode(["error" => "An unexpected error occurred."]));
    return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
});

$app->post('/groups/join', function (Request $request, Response $response) use ($pdo) {
    $input = json_decode($request->getBody()->getContents(), true);
    $result = join_group($pdo, $input);
    if (isset($result['status'])) {
        $response->getBody()->write($result['body']);
        return $response->withStatus($result['status'])->withHeader('Content-Type', 'application/json');
    }
    $response->getBody()->write(json_encode(["error" => "An unexpected error occurred."]));
    return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
});

$app->post('/groups/messages', function (Request $request, Response $response) use ($pdo) {
    $input = json_decode($request->getBody()->getContents(), true);
    $result = send_message($pdo, $input);
    if (isset($result['status'])) {
        $response->getBody()->write($result['body']);
        return $response->withStatus($result['status'])->withHeader('Content-Type', 'application/json');
    }
    $response->getBody()->write(json_encode(["error" => "An unexpected error occurred."]));
    return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
});

$app->get('/groups/messages', function (Request $request, Response $response) use ($pdo) {
    $input = json_decode($request->getBody()->getContents(), true);
    $result = list_messages($pdo, $input);
    if (isset($result['status'])) {
        $response->getBody()->write($result['body']);
        return $response->withStatus($result['status'])->withHeader('Content-Type', 'application/json');
    }
    $response->getBody()->write(json_encode(["error" => "An unexpected error occurred."]));
    return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
});

$app->delete('/clear', function (Request $request, Response $response) use ($pdo) {
    $result = clear_entries($pdo);
    if (isset($result['status'])) {
        $response->getBody()->write($result['body']);
        return $response->withStatus($result['status'])->withHeader('Content-Type', 'application/json');
    }
    $response->getBody()->write(json_encode(["error" => "An unexpected error occurred."]));
    return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
});

// Run the application
$app->run();