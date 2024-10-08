<?php

namespace App;

/**
 * SQLite connection
 */
class SQLiteConnection {
    /**
     * PDO instance
     * @var \PDO|null 
     */
    private $pdo;

    /**
     * Return an instance of the PDO object that connects to the SQLite database
     * @return \PDO
     */
    public function connect($isTest = false) {
        if ($this->pdo == null) {
            $dbpath = ($isTest) ? Config::PATH_TO_SQLITE_FILE_TEST : Config::PATH_TO_SQLITE_FILE;
            try {
                $this->pdo = new \PDO("sqlite:" . $dbpath);
            } catch (\PDOException $e) {
                // Handle the exception here
                echo 'Connection failed: ' . $e->getMessage();
                return null;
            }
        }
        return $this->pdo;
    }
}
