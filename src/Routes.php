<?php

use Slim\App;
use App\Controllers\GroupController;
use App\Controllers\MessageController;
use App\Controllers\UserController;
use App\Middleware\AuthenticationMiddleware;

// return the routes
return function (App $app) {
    $app->group('/api', function ($group) {
        // Group controller routes
        $group->post('/groups', GroupController::class . ':createGroup');
        $group->post('/groups/{id}/join', GroupController::class . ':joinGroup');
        $group->get('/groups', GroupController::class . ':listGroups');

        // User controller routes
        $group->post('/users', UserController::class . ':create');

        // Message controller routes
        $group->post('/groups/{id}/messages', MessageController::class . ':sendMessage');
        $group->get('/groups/{id}/messages', MessageController::class . ':listMessages');
    })->add(new AuthenticationMiddleware());
};
