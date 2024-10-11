<?php

namespace App\Services;

use PDO;
use PDOException;

class AdminService
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function clearAllEntries()
    {
        try {
            // Start a transaction
            $this->pdo->beginTransaction();

            // Example of clearing tables; adjust according to your schema
            $this->pdo->exec("DELETE FROM messages");
            $this->pdo->exec("DELETE FROM group_users");
            $this->pdo->exec("DELETE FROM groups");
            $this->pdo->exec("DELETE FROM users");
            $this->pdo->exec("DELETE FROM sqlite_sequence");

            // Commit the transaction
            $this->pdo->commit();

            return [
                'status' => 200,
                'body' => json_encode(['message' => 'All entries cleared successfully'])
            ];
        } catch (PDOException $e) {
            // Roll back the transaction in case of error
            $this->pdo->rollBack();
            return [
                'status' => 500,
                'body' => json_encode(['error' => 'Failed to clear entries: ' . $e->getMessage()])
            ];
        }
    }
}
