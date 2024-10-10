<?php

namespace App\Controllers;

use App\Services\GroupService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class GroupController {
    private $groupService;

    public function __construct(GroupService $groupService) {
        $this->groupService = $groupService;
    }

    public function getGroups(Request $request, Response $response) {
        $result = $this->groupService->getGroups();
        return $this->prepareResponse($response, $result);
    }

    public function createGroup(Request $request, Response $response) {
        $input = json_decode($request->getBody()->getContents(), true);
        $result = $this->groupService->createGroup($input);
        return $this->prepareResponse($response, $result);
    }

    public function joinGroup(Request $request, Response $response) {
        $input = json_decode($request->getBody()->getContents(), true);
        $result = $this->groupService->joinGroup($input);
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
