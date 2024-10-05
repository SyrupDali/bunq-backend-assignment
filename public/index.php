<?php

require __DIR__ . '/../vendor/autoload.php';

use App\SQLiteConnection;

// Establish a connection to the SQLite database
$pdo = (new SQLiteConnection())->connect();

if ($pdo != null) {
    echo 'Connected to the SQLite database successfully!';
} else {
    echo 'Whoops, could not connect to the SQLite database!';
}
