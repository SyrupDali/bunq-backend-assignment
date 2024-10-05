<?php

require __DIR__ . '/../vendor/autoload.php';

use App\SQLiteConnection;

// Establish a connection to the SQLite database
$pdo = (new SQLiteConnection())->connect();

if ($pdo != null) {
    echo 'Connected to the SQLite database successfully!';
} else {
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
        break;

    case 'POST':
        // Example: Create a new user
        if (isset($_GET['action']) && $_GET['action'] == 'create_user') {
            create_user($pdo);
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