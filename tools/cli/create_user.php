<?php
// create_user.php
/**
 * This script creates a new user from the command line.
 * It takes the user's name, email, password, and assigns a default role.
 */

// Database connection settings (example)
$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = 'password';
$dbName = 'iot_platform';

$pdo = new PDO("mysql:host=$dbHost;dbname=$dbName", $dbUser, $dbPass);

// Get user input from command line arguments
if ($argc < 4) {
    echo "Usage: php create_user.php <username> <email> <password>\n";
    exit(1);
}

$username = $argv[1];
$email = $argv[2];
$password = password_hash($argv[3], PASSWORD_BCRYPT);  // Hash password

// Default role is 'user', this can be adjusted based on needs
$role = 'user';

$sql = "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)";
$stmt = $pdo->prepare($sql);
$stmt->execute([$username, $email, $password, $role]);

echo "User $username created successfully with role $role.\n";
?>