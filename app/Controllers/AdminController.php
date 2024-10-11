<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Services\AdminService;

class AdminController
{
    private $adminService;

    public function __construct(AdminService $adminService)
    {
        $this->adminService = $adminService;
    }

    public function clearAllEntries(Request $request, Response $response): Response
    {
        $result = $this->adminService->clearAllEntries();

        $response->getBody()->write($result['body']);
        return $response->withStatus($result['status'])->withHeader('Content-Type', 'application/json');
    }
}
