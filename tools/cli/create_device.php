<?php
// create_device.php
/**
 * This script creates a new device for a user from the command line.
 * It takes the user's ID, device name, and device type as input.
 */

// Database connection settings (example)
$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = 'password';
$dbName = 'iot_platform';

$pdo = new PDO("mysql:host=$dbHost;dbname=$dbName", $dbUser, $dbPass);

// Get user input from command line arguments
if ($argc < 4) {
    echo "Usage: php create_device.php <user_id> <device_name> <device_type>\n";
    exit(1);
}

$user_id = $argv[1];
$device_name = $argv[2];
$device_type = $argv[3];

// Insert the device into the database
$sql = "INSERT INTO devices (user_id, device_name, device_type) VALUES (?, ?, ?)";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id, $device_name, $device_type]);

echo "Device $device_name created successfully for user $user_id.\n";
?>