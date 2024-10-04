<?php

namespace App\Controllers;

use App\Models\User;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class UserController
{
    public function create(Request $request, Response $response)
    {
        $data = json_decode($request->getBody()->getContents(), true);

        // Validate input
        if (!isset($data['username']) || empty($data['username'])) {
            return $response->withStatus(400)->withJson(['error' => 'Username is required.']);
        }

        // Create the user
        $user = User::create([
            'username' => $data['username'],
        ]);

        return $response->withStatus(201)->withJson([
            'message' => 'User created successfully.',
            'user' => $user
        ]);
    }
}
