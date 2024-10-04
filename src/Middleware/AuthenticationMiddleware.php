<?php

namespace App\Middleware;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface; // Ensure you're using the PSR-15 RequestHandlerInterface
use Slim\Psr7\Response;

class AuthenticationMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $authHeader = $request->getHeaderLine('Authorization');
        
        if (!$authHeader) {
            return (new Response())->withStatus(401)->withBody("Authorization header not found.");
        }

        list($type, $token) = explode(' ', $authHeader);

        if ($type !== 'Bearer' || empty($token)) {
            return (new Response())->withStatus(401)->withBody("Invalid authorization format.");
        }

        try {
            // Decode the token
            $decoded = JWT::decode($token, new Key($_ENV['JWT_SECRET'], 'HS256'));
            $request = $request->withAttribute('user', $decoded); // Attach user info to request
        } catch (\Exception $e) {
            return (new Response())->withStatus(401)->withBody("Token is invalid: " . $e->getMessage());
        }

        return $handler->handle($request); // Proceed to the next middleware or route
    }
}
