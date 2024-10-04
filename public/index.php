<?php

require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use App\Dependencies;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$app = AppFactory::create();
// Verify the app instance
if (is_null($app)) {
    error_log("App instance is null.");
} else {
    error_log("App instance is created successfully.");
}
// Middleware
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
$errorMiddleware = $app->addErrorMiddleware($_ENV['APP_DEBUG'] === 'true', true, true);

// Register dependencies
try {
    (new Dependencies())($app);
} catch (\Exception $e) {
    echo "Error initializing dependencies: " . $e->getMessage();
    exit(1);
}

// Define Routes
(require __DIR__ . '/../src/Routes.php')($app);

$app->run();
