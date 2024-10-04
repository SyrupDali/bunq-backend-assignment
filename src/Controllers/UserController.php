<?php

namespace App\Controllers;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Response;
use App\Models\User;

class UserController
{
    public function create(ServerRequestInterface $request): ResponseInterface
    {
        $data = json_decode($request->getBody()->getContents(), true);
        $username = $data['username'] ?? '';

        // Validate the input (e.g., check for uniqueness)
        if (empty($username)) {
            return (new Response())->withStatus(400)->withBody("Username cannot be empty.");
        }

        // Create the user in the database
        $user = User::create(['username' => $username]);

        // Generate JWT
        $payload = [
            'iat' => time(), // Issued at
            'exp' => time() + (60 * 60), // Expiration time (1 hour)
            'id' => $user->id, // User ID
            'username' => $username, // Payload data
        ];

        $jwt = JWT::encode($payload, $_ENV['JWT_SECRET'], 'HS256');

        // Return token in the response
        $response = new Response();
        $response->getBody()->write(json_encode(['token' => $jwt, 'user_id' => $user->id]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
    }
}

