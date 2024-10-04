<?php

namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthenticationMiddleware
{
    public function __invoke(Request $request, Response $response, callable $next)
    {
        $authHeader = $request->getHeader('Authorization');

        if (!$authHeader) {
            return $response->withStatus(401)->withJson(['error' => 'Authorization header missing']);
        }

        $token = str_replace('Bearer ', '', $authHeader[0]);

        try {
            $decoded = JWT::decode($token, new Key($_ENV['JWT_SECRET'], 'HS256'));
            $user = User::find($decoded->sub);
            if (!$user) {
                throw new \Exception('User not found');
            }
            // Add user to request attributes
            $request = $request->withAttribute('user', $user);
        } catch (\Exception $e) {
            return $response->withStatus(401)->withJson(['error' => 'Invalid token']);
        }

        return $next($request, $response);
    }
}
