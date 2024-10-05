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
switch ($request_method) {
    case 'GET':
        // Example: Get all users
        if (isset($_GET['action']) && $_GET['action'] == 'get_users') {
            get_users($pdo);
        }
        else if (isset($_GET['action']) && $_GET['action'] == 'list_messages') {
            list_messages($pdo);
        }
        break;

    case 'POST':
        // Create a new user
        if (isset($_GET['action']) && $_GET['action'] == 'create_user') {
            create_user($pdo);
        }
        // Create a new group
        elseif (isset($_GET['action']) && $_GET['action'] == 'create_group') {
            create_group($pdo);
        }
        // Join a group
        elseif (isset($_GET['action']) && $_GET['action'] == 'join_group') {
            join_group($pdo);
        }
        // Send a message
        elseif (isset($_GET['action']) && $_GET['action'] == 'send_message') {
            send_message($pdo);
        }
        break;

    // Add other cases for PUT, DELETE as needed
    default:
        echo json_encode(["message" => "Invalid Request"]);
        break;
}

// Function to fetch all users
function get_users($pdo) {
    $stmt = $pdo->query("SELECT * FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($users);
}

// Function to create a new user
function create_user($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    $username = $input['username'] ?? null;

    if ($username) {
        $stmt = $pdo->prepare("INSERT INTO users (username) VALUES (:username)");
        if ($stmt->execute([':username' => $username])) {
            echo json_encode(["message" => "User created successfully"]);
        } else {
            echo json_encode(["message" => "Failed to create user"]);
        }
    } else {
        echo json_encode(["message" => "Invalid input"]);
    }
}

// Function to create a new group
function create_group($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    $group_name = $input['name'] ?? null;
    $created_by = $input['created_by'] ?? null;  // User ID of creator

    if ($group_name && $created_by) {
        $stmt = $pdo->prepare("INSERT INTO groups (name, created_by) VALUES (:name, :created_by)");
        if ($stmt->execute([':name' => $group_name, ':created_by' => $created_by])) {
            echo json_encode(["message" => "Group created successfully"]);
        } else {
            echo json_encode(["message" => "Failed to create group"]);
        }
    } else {
        echo json_encode(["message" => "Invalid input"]);
    }
}

// Function to join a group
function join_group($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    $user_id = $input['user_id'] ?? null;
    $group_id = $input['group_id'] ?? null;

    if ($user_id && $group_id) {
        $stmt = $pdo->prepare("INSERT INTO group_users (group_id, user_id) VALUES (:group_id, :user_id)");
        if ($stmt->execute([':group_id' => $group_id, ':user_id' => $user_id])) {
            echo json_encode(["message" => "Joined group successfully"]);
        } else {
            echo json_encode(["message" => "Failed to join group"]);
        }
    } else {
        echo json_encode(["message" => "Invalid input"]);
    }
}

// Function to send a message in a group
function send_message($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    $group_id = $input['group_id'] ?? null;
    $user_id = $input['user_id'] ?? null;
    $message = $input['message'] ?? null;

    if ($group_id && $user_id && $message) {
        $stmt = $pdo->prepare("INSERT INTO messages (group_id, user_id, message) VALUES (:group_id, :user_id, :message)");
        if ($stmt->execute([':group_id' => $group_id, ':user_id' => $user_id, ':message' => $message])) {
            echo json_encode(["message" => "Message sent successfully"]);
        } else {
            echo json_encode(["message" => "Failed to send message"]);
        }
    } else {
        echo json_encode(["message" => "Invalid input"]);
    }
}

// Function to list all messages in a group
function list_messages($pdo) {
    $group_id = $_GET['group_id'] ?? null;

    if ($group_id) {
        $stmt = $pdo->prepare("SELECT * FROM messages WHERE group_id = :group_id ORDER BY created_at ASC");
        $stmt->execute([':group_id' => $group_id]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($messages);
    } else {
        echo json_encode(["message" => "Group ID is required"]);
    }
}