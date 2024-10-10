<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Services\MessageService;

class MessageController
{
    private $messageService;

    public function __construct(MessageService $messageService)
    {
        $this->messageService = $messageService;
    }

    public function sendMessage(Request $request, Response $response): Response
    {
        // Retrieve the input from the request body
        $input = json_decode($request->getBody()->getContents(), true);
        $result = $this->messageService->sendMessage($input);
        
        // Set the response status and body based on the result
        $response->getBody()->write($result['body']);
        return $response->withStatus($result['status'])->withHeader('Content-Type', 'application/json');
    }

    public function listMessages(Request $request, Response $response): Response
    {
        $input = json_decode($request->getBody()->getContents(), true);
        $result = $this->messageService->listMessages($input);
        
        $response->getBody()->write($result['body']);
        return $response->withStatus($result['status'])->withHeader('Content-Type', 'application/json');
    }
}
