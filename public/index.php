<?php

require __DIR__ . '/../vendor/autoload.php';

use App\SQLiteConnection;

// Establish a connection to the SQLite database
$pdo = (new SQLiteConnection())->connect();

if ($pdo == null) {
    echo 'Whoops, could not connect to the SQLite database!';
}

// Handle incoming requests
$request_method = $_SERVER["REQUEST_METHOD"];
$request_uri = $_SERVER['REQUEST_URI'];

if ($request_method === 'GET') {
    if ($request_uri === '/users') {
        get_users($pdo); // Fetch all users
    } elseif ($request_uri === '/groups') {
        // This could be to fetch all groups (not shown in previous examples)
    } elseif ($request_uri === '/groups/messages') {
        list_messages($pdo); // List messages for a specific group
    }
    else {
        http_response_code(404);
        echo json_encode(["message" => "Invalid Request"]);
    }
} elseif ($request_method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if ($request_uri === '/users') {
        create_user($pdo, $input); // Create a new user
    } elseif ($request_uri === '/groups') {
        create_group($pdo, $input); // Create a new group
    } elseif ($request_uri === '/groups/join') {
        join_group($pdo, $input); // Join a specific group
    } elseif ($request_uri === '/groups/messages') {
        send_message($pdo, $input); // Send a message to a specific group
    }
    else {
        http_response_code(404);
        echo json_encode(["message" => "Invalid Request"]);
    }
} else {
    http_response_code(404);
    echo json_encode(["message" => "Invalid Request"]);
}

// Function to fetch all users
function get_users($pdo) {
    $stmt = $pdo->query("SELECT * FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($users);
}

function create_user($pdo, $input) {
    $username = $input['username'] ?? null;

    if (!$username) {
        error_response("Invalid input: username is required", 400);
    }

    // Check if the username already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username");
    $stmt->execute([':username' => $username]);
    if ($stmt->fetch()) {
        error_response("Username already exists", 400);
    }

    // Create the user
    $stmt = $pdo->prepare("INSERT INTO users (username) VALUES (:username)");
    if ($stmt->execute([':username' => $username])) {
        echo json_encode(["message" => "User created successfully", "user_id" => $pdo->lastInsertId(), "username" => $username]);
    } else {
        error_response("Failed to create user", 500);
    }
}


// Function to create a new group
function create_group($pdo, $input) {
    $group_name = $input['group_name'] ?? null;
    $username = $input['username'] ?? null;

    if (!$group_name || !$username) {
        error_response("Invalid input: both group_name and username are required", 400);
    }

    // Lookup the user ID from the username
    $user_id = get_user_id($pdo, $username);
    if (!$user_id) {
        error_response("User not found", 404);
    }

    // Check if the group already exists
    $stmt = $pdo->prepare("SELECT id FROM groups WHERE name = :group_name");
    $stmt->execute([':group_name' => $group_name]);
    if ($stmt->fetch()) {
        error_response("Group already exists", 409); // Conflict status
    }

    // Create the group
    $stmt = $pdo->prepare("INSERT INTO groups (name, created_by) VALUES (:group_name, :created_by)");
    if ($stmt->execute([':group_name' => $group_name, ':created_by' => $user_id])) {
        $group_id = $pdo->lastInsertId();
        echo json_encode([
            "message" => "Group created successfully",
            "group_id" => $group_id,
            "group_name" => $group_name,
            "created_by" => $username
        ]);

        // Add the creator to the group_users table
        $stmt = $pdo->prepare("INSERT INTO group_users (group_id, user_id) VALUES (:group_id, :user_id)");
        if (!$stmt->execute([':group_id' => $group_id, ':user_id' => $user_id])) {
            error_response("Group created but failed to add user to the group", 500);
        }
    } else {
        error_response("Failed to create group", 500);
    }
}



function join_group($pdo, $input) {
    $username = $input['username'] ?? null;
    $group_name = $input['group_name'] ?? null;

    if (!$username || !$group_name) {
        error_response("Invalid input: both username and group_name are required", 400);
    }

    // Lookup user ID and group ID
    $user_id = get_user_id($pdo, $username);
    $group_id = get_group_id($pdo, $group_name);

    if (!$user_id) {
        error_response("User not found", 404);
    }

    if (!$group_id) {
        error_response("Group not found", 404);
    }

    // Check if the user is already in the group
    $stmt = $pdo->prepare("SELECT * FROM group_users WHERE user_id = :user_id AND group_id = :group_id");
    $stmt->execute([':user_id' => $user_id, ':group_id' => $group_id]);
    if ($stmt->fetch()) {
        error_response("User already joined the group", 409);
    }

    // Add the user to the group
    $stmt = $pdo->prepare("INSERT INTO group_users (group_id, user_id) VALUES (:group_id, :user_id)");
    if ($stmt->execute([':group_id' => $group_id, ':user_id' => $user_id])) {
        echo json_encode(["message" => "Joined group successfully"]);
    } else {
        error_response("Failed to join group", 500);
    }
}


function send_message($pdo, $input) {
    $message = $input['message'] ?? null;
    $username = $input['username'] ?? null;
    $group_name = $input['group_name'] ?? null;

    if (!$message || !$username || !$group_name) {
        error_response("Invalid input: message, username, and group_name are required", 400);
    }

    // Lookup the user ID and group ID
    $user_id = get_user_id($pdo, $username);
    $group_id = get_group_id($pdo, $group_name);

    if (!$user_id) {
        error_response("User not found", 404);
    }

    if (!$group_id) {
        error_response("Group not found", 404);
    }

    // Check if the user is part of the group
    $stmt = $pdo->prepare("SELECT * FROM group_users WHERE group_id = :group_id AND user_id = :user_id");
    $stmt->execute([':group_id' => $group_id, ':user_id' => $user_id]);
    $is_in_group = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$is_in_group) {
        error_response("User is not part of this group", 403);
    }

    // Insert the message
    $stmt = $pdo->prepare("INSERT INTO messages (group_id, user_id, message) VALUES (:group_id, :user_id, :message)");
    if ($stmt->execute([':group_id' => $group_id, ':user_id' => $user_id, ':message' => $message])) {
        echo json_encode(["message" => "Message sent successfully"]);
    } else {
        error_response("Failed to send message", 500);
    }
}


function list_messages($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    $group_name = $input['group_name'] ?? null;
    $username = $input['username'] ?? null;

    if (!$group_name || !$username) {
        error_response("Invalid input: group_name and username are required", 400);
    }

    // Lookup the user ID and group ID
    $user_id = get_user_id($pdo, $username);
    $group_id = get_group_id($pdo, $group_name);

    if (!$user_id) {
        error_response("User not found", 404);
    }

    if (!$group_id) {
        error_response("Group not found", 404);
    }

    // Check if the user is part of the group
    $stmt = $pdo->prepare("SELECT * FROM group_users WHERE group_id = :group_id AND user_id = :user_id");
    $stmt->execute([':group_id' => $group_id, ':user_id' => $user_id]);
    $is_in_group = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$is_in_group) {
        error_response("User is not part of this group", 403);
    }

    // Fetch all messages from the group
    $stmt = $pdo->prepare("SELECT messages.message, users.username, messages.created_at 
                           FROM messages 
                           JOIN users ON messages.user_id = users.id
                           WHERE messages.group_id = :group_id 
                           ORDER BY messages.created_at ASC");
    $stmt->execute([':group_id' => $group_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($messages);
}


// Standardized error response helper
function error_response($message, $code) {
    http_response_code($code);
    echo json_encode(["error" => $message]);
    exit(); // Prevent further execution after sending the response
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