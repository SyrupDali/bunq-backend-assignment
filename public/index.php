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
        if(isset($_GET['action'])) {
            // Example: Get all users
            if ($_GET['action'] == 'get_users') {
                get_users($pdo);
            }
            // Example: List messages in a group
            elseif ($_GET['action'] == 'list_messages') {
                list_messages($pdo);
            }
        }
        break;

    case 'POST':
        if (isset($_GET['action'])) {
            // Example: Create a new user
            if ($_GET['action'] == 'create_user') {
                create_user($pdo);
            }
            // Example: Create a new group
            elseif ($_GET['action'] == 'create_group') {
                create_group($pdo);
            }
            // Example: Join a group
            elseif ($_GET['action'] == 'join_group') {
                join_group($pdo);
            }
            // Example: Send a message
            elseif ($_GET['action'] == 'send_message') {
                send_message($pdo);
            }
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
            echo json_encode(["message" => "User created successfully", "id" => $pdo->lastInsertId(), "username" => $username]);
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
    $group_name = $input['group_name'] ?? null;
    $username = $input['username'] ?? null;

    if ($group_name && $username) {
        // Lookup the user ID from the username
        $user_id = get_user_id($pdo, $username);
        if ($user_id) {
            // Insert the new group with the user ID
            $stmt = $pdo->prepare("INSERT INTO groups (name, created_by) VALUES (:group_name, :created_by)");
            if ($stmt->execute([':group_name' => $group_name, ':created_by' => $user_id])) {
                echo json_encode(["message" => "Group created successfully", "id" => $pdo->lastInsertId(), 
                                  "group_name" => $group_name, "created_by" => $username]);
            } else {
                echo json_encode(["message" => "Failed to create group"]);
            }
        } else {
            echo json_encode(["message" => "User not found"]);
        }
    } else {
        echo json_encode(["message" => "Invalid input"]);
    }
}

// Function to join a group
function join_group($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    $username = $input['username'] ?? null;
    $group_name = $input['group_name'] ?? null;
    if (!$username || !$group_name) {
        echo json_encode(["message" => "Invalid input"]);
        return;
    }
    $user_id = get_user_id($pdo, $username);
    $group_id = get_group_id($pdo, $group_name);
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

// Function to send a message to a group
function send_message($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    $message = $input['message'] ?? null;
    $username = $input['username'] ?? null;
    $group_name = $input['group_name'] ?? null;

    if ($message && $username && $group_name) {
        // Lookup the user ID and group ID
        $user_id = get_user_id($pdo, $username);
        $group_id = get_group_id($pdo, $group_name);

        if ($user_id && $group_id) {
            // Insert the message with the user ID and group ID
            $stmt = $pdo->prepare("INSERT INTO messages (group_id, user_id, message) VALUES (:group_id, :user_id, :message)");
            if ($stmt->execute([':group_id' => $group_id, ':user_id' => $user_id, ':message' => $message])) {
                echo json_encode(["message" => "Message sent successfully"]);
            } else {
                echo json_encode(["message" => "Failed to send message"]);
            }
        } else {
            echo json_encode(["message" => "User or group not found"]);
        }
    } else {
        echo json_encode(["message" => "Invalid input"]);
    }
}

function list_messages($pdo, $group_name, $username) {
    $input = json_decode(file_get_contents('php://input'), true);
    $group_name = $input['group_name'] ?? null;
    $username = $input['username'] ?? null;

    if (!$group_name || !$username) {
        echo json_encode(["message" => "Invalid input"]);
        return;
    }
    // Lookup the user ID and group ID
    $user_id = get_user_id($pdo, $username);
    $group_id = get_group_id($pdo, $group_name);

    if ($user_id && $group_id) {
        // Check if the user is part of the group
        $stmt = $pdo->prepare("SELECT * FROM group_users WHERE group_id = :group_id AND user_id = :user_id");
        $stmt->execute([':group_id' => $group_id, ':user_id' => $user_id]);
        $is_in_group = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($is_in_group) {
            // Fetch all messages from the group, including other users' messages
            $stmt = $pdo->prepare("SELECT messages.message, users.username, messages.created_at 
                                   FROM messages 
                                   JOIN users ON messages.user_id = users.id
                                   WHERE messages.group_id = :group_id 
                                   ORDER BY messages.created_at ASC");
            $stmt->execute([':group_id' => $group_id]);
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($messages);
        } else {
            echo json_encode(["message" => "User is not part of this group"]);
        }
    } else {
        echo json_encode(["message" => "User or group not found"]);
    }
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