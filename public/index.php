<?php

require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use DI\Container;
use Dotenv\Dotenv;
use App\Dependencies;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Create a Container
$container = new Container();

// Register dependencies in the container
$dependencies = new Dependencies();
$dependencies($container); // Pass the container to your dependencies

// Set the container for the AppFactory
AppFactory::setContainer($container);

// Create the Slim App
$app = AppFactory::create();

// Check if the app is created
if (is_null($app)) {
    error_log("App instance is null.");
} else {
    error_log("App instance is created successfully.");
}

// Check if the container is created
if (is_null($app->getContainer())) {
    error_log("Container is null.");
} else {
    error_log("Container is created successfully.");
}

// Middleware
$app->addBodyParsingMiddleware();  // Enable body parsing
$app->addRoutingMiddleware();      // Enable routing middleware

// Enable error middleware for debugging
$errorMiddleware = $app->addErrorMiddleware($_ENV['APP_DEBUG'] === 'true', true, true);

// Define Routes
(require __DIR__ . '/../src/Routes.php')($app);

// Run the application
$app->run();
