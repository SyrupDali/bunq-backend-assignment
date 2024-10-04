<?php

namespace App\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Models\Group;
use App\Models\Message;
use Respect\Validation\Validator as v;

class MessageController
{
    // Send a message to a group
    public function sendMessage(Request $request, Response $response, $args)
    {
        $groupId = $args['id'];
        $group = Group::find($groupId);

        if (!$group) {
            return $response->withStatus(404)->withJson(['error' => 'Group not found']);
        }

        $user = $request->getAttribute('user');

        // Check if user is a member of the group
        if (!$group->users()->where('user_id', $user->id)->exists()) {
            return $response->withStatus(403)->withJson(['error' => 'You are not a member of this group']);
        }

        $data = $request->getParsedBody();
        $messageText = $data['message'] ?? '';

        // message should not be empty or more than 1000 characters
        $messageValidator = v::stringType()->notEmpty()->length(1, 1000);
        if (!$messageValidator->validate($messageText)) {
            return $response->withStatus(400)->withJson(['error' => 'Invalid message']);
        }

        // Create message
        $message = Message::create([
            'group_id' => $group->id,
            'user_id' => $user->id,
            'message' => $messageText
        ]);

        return $response->withStatus(201)->withJson(['message' => 'Message sent', 'data' => $message]);
    }

    // List messages in a group
    public function listMessages(Request $request, Response $response, $args)
    {
        $groupId = $args['id'];
        $group = Group::find($groupId);

        if (!$group) {
            return $response->withStatus(404)->withJson(['error' => 'Group not found']);
        }
        // retrieve messages in time order
        $messages = $group->messages()->with('user')->orderBy('created_at', 'asc')->get();

        return $response->withJson(['messages' => $messages]);
    }
}
