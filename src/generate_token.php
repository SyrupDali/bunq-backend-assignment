<?php
// to identify users by tokens, we need to generate a token for each user
require __DIR__ . '/vendor/autoload.php';

use App\Models\User;
use Firebase\JWT\JWT;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Create a user (if not exists)
$username = 'testuser';
$user = User::firstOrCreate(['username' => $username]);

// Generate JWT
$payload = [
    'iss' => 'chat-app',
    'sub' => $user->id,
    'iat' => time(),
    'exp' => time() + 90 * 60 // 90 minutes
];

$jwt = JWT::encode($payload, $_ENV['JWT_SECRET'], 'HS256');

echo "JWT Token for user '{$username}':\n";
echo $jwt;
