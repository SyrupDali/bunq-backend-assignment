<?php

namespace App;

/**
 * SQLite connection
 */
class SQLiteConnection
{
    /**
     * PDO instance
     * @var \PDO|null 
     */
    private $pdo;

    /**
     * Return an instance of the PDO object that connects to the SQLite database
     * @return \PDO
     */
    public function connect()
    {
        if ($this->pdo == null) {
            try {
                $this->pdo = new \PDO("sqlite:" . Config::PATH_TO_SQLITE_FILE);
            } catch (\PDOException $e) {
                // Handle the exception here
                error_log('Connection error: ' . $e->getMessage());
                return null;
            }
        }
        return $this->pdo;
    }
}
