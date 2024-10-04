<?php

namespace App\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Models\Group;
use App\Models\User;
use Respect\Validation\Validator as v;

class GroupController
{
    // Create a new group
    public function createGroup(Request $request, Response $response, $args)
    {
        $data = $request->getParsedBody();
        $name = $data['name'] ?? ''; // if name is not set, set it to empty string

        // Validate input
        $nameValidator = v::stringType()->notEmpty()->length(3, 50);
        if (!$nameValidator->validate($name)) {
            return $response->withStatus(400)->withJson(['error' => 'Invalid group name']);
        }

        // Check if group exists
        if (Group::where('name', $name)->exists()) {
            return $response->withStatus(400)->withJson(['error' => 'Group already exists']);
        }

        $user = $request->getAttribute('user');
        $group = Group::create([
            'name' => $name,
            'created_by' => $user->id
        ]);

        // Add creator to group
        $group->users()->attach($user->id);

        return $response->withStatus(201)->withJson(['message' => 'Group created', 'group' => $group]);
    }

    // Join a group
    public function joinGroup(Request $request, Response $response, $args)
    {
        $groupId = $args['id'];
        $group = Group::find($groupId);

        if (!$group) {
            return $response->withStatus(404)->withJson(['error' => 'Group not found']);
        }

        $user = $request->getAttribute('user');

        // Check if already a member
        if ($group->users()->where('user_id', $user->id)->exists()) {
            return $response->withStatus(400)->withJson(['error' => 'Already a member of the group']);
        }

        $group->users()->attach($user->id);

        return $response->withJson(['message' => 'Joined the group successfully']);
    }

    // List all groups
    public function listGroups(Request $request, Response $response, $args)
    {
        $groups = Group::all();
        return $response->withJson(['groups' => $groups]);
    }
}
