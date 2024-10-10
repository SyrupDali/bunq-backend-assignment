<?php

require __DIR__ . '/../vendor/autoload.php';

use App\SQLiteConnection;
use App\Services\UserService;
use App\Services\GroupService;
use App\Services\MessageService;
use App\Services\AdminService;
use App\Controllers\UserController;
use App\Controllers\GroupController;
use App\Controllers\MessageController;
use App\Controllers\AdminController;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

$app = AppFactory::create();

// Establish a connection to the SQLite database
$pdo = (new SQLiteConnection())->connect();

if ($pdo === null) {
    die('Whoops, could not connect to the SQLite database!');
}

// Initialize services with the PDO connection
$userService = new UserService($pdo);
$groupService = new GroupService($pdo, $userService);
$messageService = new MessageService($pdo, $userService, $groupService);
$adminService = new AdminService($pdo);

// Initialize controllers with their respective services
$userController = new UserController($userService);
$groupController = new GroupController($groupService);
$messageController = new MessageController($messageService);
$adminController = new AdminController($adminService);

// Define routes for users
$app->post('/users', function (Request $request, Response $response) use ($userController) {
    return $userController->createUser($request, $response);
});

$app->get('/users', function (Request $request, Response $response) use ($userController) {
    return $userController->getUsers($request, $response);
});

// Define routes for groups
$app->post('/groups', function (Request $request, Response $response) use ($groupController) {
    return $groupController->createGroup($request, $response);
});

$app->get('/groups', function (Request $request, Response $response) use ($groupController) {
    return $groupController->getGroups($request, $response);
});

// Route for joining a group
$app->post('/groups/join', function (Request $request, Response $response) use ($groupController) {
    return $groupController->joinGroup($request, $response);
});

// Route for sending a message
$app->post('/messages', function (Request $request, Response $response) use ($messageController) {
    return $messageController->sendMessage($request, $response);
});

// Route for listing messages
$app->get('/messages', function (Request $request, Response $response) use ($messageController) {
    return $messageController->listMessages($request, $response);
});

// Route to clear all entries
$app->delete('/clear', function (Request $request, Response $response) use ($adminController) {
    return $adminController->clearAllEntries($request, $response);
});

// Run the application
$app->run();
