<?php

namespace App\Services;

use PDO;
use PDOException;

class GroupService
{
    private $pdo;
    private $userService;

    public function __construct(PDO $pdo, UserService $userService)
    {
        $this->pdo = $pdo;
        $this->userService = $userService;
    }

    public function getGroups()
    {
        try {
            $stmt = $this->pdo->query("SELECT * FROM groups");
            // I want the real username instead of the user_id
            $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($groups as &$group) {
                $stmt = $this->pdo->prepare("SELECT username FROM users WHERE id = :user_id");
                $stmt->execute([':user_id' => $group['created_by']]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                $group['created_by'] = $user['username'];
            }
            return [
                'status' => 200,
                'body' => json_encode($groups)
            ];
        } catch (PDOException $e) {
            return $this->errorResponse("Failed to fetch groups: " . $e->getMessage(), 500);
        }
    }

    public function createGroup($input)
    {
        $group_name = $input['group_name'] ?? null;
        $username = $input['username'] ?? null;

        if (!$group_name || !$username) {
            return $this->errorResponse("Invalid input: both group_name and username are required", 400);
        }

        $user_id = $this->userService->getUserId($username);
        if (!$user_id) {
            return $this->errorResponse("User not found", 404);
        }

        $stmt = $this->pdo->prepare("SELECT id FROM groups WHERE name = :group_name");
        $stmt->execute([':group_name' => $group_name]);
        if ($stmt->fetch()) {
            return $this->errorResponse("Group already exists", 409);
        }

        $stmt = $this->pdo->prepare("INSERT INTO groups (name, created_by) VALUES (:group_name, :created_by)");
        if ($stmt->execute([':group_name' => $group_name, ':created_by' => $user_id])) {
            $group_id = $this->pdo->lastInsertId();
            $stmt = $this->pdo->prepare("INSERT INTO group_users (group_id, user_id) VALUES (:group_id, :user_id)");
            $stmt->execute([':group_id' => $group_id, ':user_id' => $user_id]);

            return [
                'status' => 201,
                'body' => json_encode([
                    "message" => "Group created successfully",
                    "group_id" => $group_id,
                    "group_name" => $group_name,
                    "created_by" => $username
                ])
            ];
        } else {
            return $this->errorResponse("Failed to create group", 500);
        }
    }

    public function joinGroup($input)
    {
        $group_name = $input['group_name'] ?? null;
        $username = $input['username'] ?? null;

        if (!$group_name || !$username) {
            return $this->errorResponse("Invalid input: both group_name and username are required", 400);
        }

        $group_id = $this->getGroupId($group_name);
        if (!$group_id) {
            return $this->errorResponse("Group not found", 404);
        }

        $user_id = $this->userService->getUserId($username);
        if (!$user_id) {
            return $this->errorResponse("User not found", 404);
        }

        $stmt = $this->pdo->prepare("SELECT id FROM group_users WHERE group_id = :group_id AND user_id = :user_id");
        $stmt->execute([':group_id' => $group_id, ':user_id' => $user_id]);
        if ($stmt->fetch()) {
            return $this->errorResponse("User already in group", 409);
        }

        $stmt = $this->pdo->prepare("INSERT INTO group_users (group_id, user_id) VALUES (:group_id, :user_id)");
        if ($stmt->execute([':group_id' => $group_id, ':user_id' => $user_id])) {
            return [
                'status' => 201,
                'body' => json_encode([
                    "message" => "User joined group successfully",
                    "group_id" => $group_id,
                    "group_name" => $group_name,
                    "username" => $username
                ])
            ];
        } else {
            return $this->errorResponse("Failed to join group", 500);
        }
    }

    private function errorResponse($message, $code)
    {
        return [
            'status' => $code,
            'body' => json_encode(['error' => $message])
        ];
    }

    public function getGroupId($group_name)
    {
        $stmt = $this->pdo->prepare("SELECT id FROM groups WHERE name = :group_name");
        $stmt->execute([':group_name' => $group_name]);
        $group = $stmt->fetch(PDO::FETCH_ASSOC);
        return $group['id'] ?? null;
    }
}
