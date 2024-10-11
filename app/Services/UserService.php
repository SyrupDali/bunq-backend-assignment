<?php

namespace App\Services;

use PDO;
use PDOException;

class UserService
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getUsers()
    {
        try {
            $stmt = $this->pdo->query("SELECT * FROM users");
            return [
                'status' => 200,
                'body' => json_encode($stmt->fetchAll(PDO::FETCH_ASSOC))
            ];
        } catch (PDOException $e) {
            return $this->errorResponse("Failed to fetch users: " . $e->getMessage(), 500);
        }
    }

    public function createUser($input)
    {
        $username = $input['username'] ?? null;

        if (!$username) {
            return $this->errorResponse("Invalid input: username is required", 400);
        }

        // Check if the username already exists
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE username = :username");
        $stmt->execute([':username' => $username]);
        if ($stmt->fetch()) {
            return $this->errorResponse("Username already exists", 409);
        }

        // Create the user
        $stmt = $this->pdo->prepare("INSERT INTO users (username) VALUES (:username)");
        if ($stmt->execute([':username' => $username])) {
            return [
                'status' => 201,
                'body' => json_encode([
                    "message" => "User created successfully",
                    "user_id" => $this->pdo->lastInsertId(),
                    "username" => $username
                ])
            ];
        } else {
            return $this->errorResponse("Failed to create user", 500);
        }
    }

    private function errorResponse($message, $code)
    {
        return [
            'status' => $code,
            'body' => json_encode(['error' => $message])
        ];
    }

    public function getUserId($username)
    {
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE username = :username");
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user['id'] ?? null;
    }
}
