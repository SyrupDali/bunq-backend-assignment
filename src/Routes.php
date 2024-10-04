<?php

use Slim\App;
use App\Controllers\GroupController;
use App\Controllers\MessageController;
use App\Controllers\UserController;
use App\Middleware\AuthenticationMiddleware;

// return the routes
return function (App $app) {
    // User registration (no authentication required)
    $app->post('/api/users', UserController::class . ':create');

    // Group listing (no authentication required)
    $app->get('/api/groups', GroupController::class . ':listGroups');

    // Group and Message controller routes (authentication required)
    $app->group('/api', function ($group) {
        // Group controller routes
        $group->post('/groups', GroupController::class . ':createGroup');
        $group->post('/groups/{id}/join', GroupController::class . ':joinGroup');

        // Message controller routes
        $group->post('/groups/{id}/messages', MessageController::class . ':sendMessage');
        $group->get('/groups/{id}/messages', MessageController::class . ':listMessages');
    })->add(new AuthenticationMiddleware()); // Apply middleware to this group
};
