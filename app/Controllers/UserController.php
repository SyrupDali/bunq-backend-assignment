<?php

namespace App\Controllers;

use App\Services\UserService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class UserController {
    private $userService;

    public function __construct(UserService $userService) {
        $this->userService = $userService;
    }

    public function getUsers(Request $request, Response $response) {
        $result = $this->userService->getUsers();
        return $this->prepareResponse($response, $result);
    }

    public function createUser(Request $request, Response $response) {
        $input = json_decode($request->getBody()->getContents(), true);
        $result = $this->userService->createUser($input);
        return $this->prepareResponse($response, $result);
    }

    private function prepareResponse(Response $response, $result) {
        if (isset($result['status'])) {
            $response->getBody()->write($result['body']);
            return $response->withStatus($result['status'])->withHeader('Content-Type', 'application/json');
        }
        $response->getBody()->write(json_encode(["error" => "An unexpected error occurred."]));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
}
