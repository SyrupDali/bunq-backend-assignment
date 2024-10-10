<?php

namespace App\Services;

use PDO;
use PDOException;

class MessageService {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function sendMessage(array $input) {
        $group_name = $input['group_name'] ?? null;
        $username = $input['username'] ?? null;
        $message = $input['message'] ?? null;

        // Validate input
        if (!$group_name || !$username || !$message) {
            return $this->errorResponse("Invalid input: group_name, username, and message are required", 400);
        }

        // Check if user exists
        $userService = new UserService($this->pdo);
        $user_id = $userService->getUserId($username);
        if (!$user_id) {
            return $this->errorResponse("User not found", 404);
        }

        // Check if the group exists
        $groupService = new GroupService($this->pdo);
        $group_id = $groupService->getGroupId($group_name);
        if (!$group_id) {
            return $this->errorResponse("Group not found", 404);
        }

        //Check if the user is a member of the group
        $stmt = $this->pdo->prepare("SELECT * FROM group_users WHERE group_id = :group_id AND user_id = :user_id");
        $stmt->execute([':group_id' => $group_id, ':user_id' => $user_id]);
        if (!$stmt->fetch()) {
            return $this->errorResponse("User is not a member of this group", 403);
        }

        // Insert the message
        $stmt = $this->pdo->prepare("INSERT INTO messages (group_id, user_id, message) VALUES (:group_id, :user_id, :message)");
        try {
            $stmt->execute([':group_id' => $group_id, ':user_id' => $user_id, ':message' => $message]);
            return [
                'status' => 201,
                'body' => json_encode(["message" => "Message sent successfully"])
            ];
        } catch (PDOException $e) {
            return $this->errorResponse("Failed to send message: " . $e->getMessage(), 500);
        }
    }

    public function listMessages($input) {
        $group_name = $input['group_name'] ?? null;
        $username = $input['username'] ?? null;

        // Validate input
        if (!$group_name || !$username) {
            return $this->errorResponse("Invalid input: group_name and username are required", 400);
        }

        // Check if user exists
        $userService = new UserService($this->pdo);
        $user_id = $userService->getUserId($username);
        if (!$user_id) {
            return $this->errorResponse("User not found", 404);
        }

        // Check if the group exists
        $groupService = new GroupService($this->pdo);
        $group_id = $groupService->getGroupId($group_name);
        if (!$group_id) {
            return $this->errorResponse("Group not found", 404);
        }

        // Check if the user is a member of the group
        $stmt = $this->pdo->prepare("SELECT * FROM group_users WHERE group_id = :group_id AND user_id = :user_id");
        $stmt->execute([':group_id' => $group_id, ':user_id' => $user_id]);
        if (!$stmt->fetch()) {
            return $this->errorResponse("User is not a member of this group", 403);
        }

        $stmt = $this->pdo->prepare("SELECT messages.message, users.username, messages.created_at 
                                FROM messages 
                                JOIN users ON messages.user_id = users.id
                                WHERE messages.group_id = :group_id 
                                ORDER BY messages.created_at ASC");
        try {
            $stmt->execute([':group_id' => $group_id]);
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return [
                'status' => 200,
                'body' => json_encode($messages)
            ];
        } catch (PDOException $e) {
            return $this->errorResponse("Failed to fetch messages: " . $e->getMessage(), 500);
        }
    }

    private function errorResponse($message, $code) {
        return [
            'status' => $code,
            'body' => json_encode(['error' => $message])
        ];
    }
}
