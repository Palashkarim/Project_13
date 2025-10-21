<?php
// webhook_receiver.php
/**
 * This script receives webhook notifications from external services.
 * It validates the payload, logs the incoming data, and can trigger specific actions based on the data.
 */

// Configure your secret key for security (shared with the external service)
define('SECRET_KEY', 'your_secret_key');

// Get the raw POST data
$data = file_get_contents('php://input');

// Get the signature sent by the external service for validation
$signature = $_SERVER['HTTP_X_SIGNATURE'] ?? '';

// Validate the signature
$expected_signature = hash_hmac('sha256', $data, SECRET_KEY);

if ($signature !== $expected_signature) {
    header('HTTP/1.1 403 Forbidden');
    echo 'Invalid signature';
    exit;
}

// Parse the incoming JSON payload
$payload = json_decode($data, true);

// Process the webhook (e.g., update database, trigger actions)
if ($payload['event'] === 'device_status_update') {
    $device_id = $payload['device_id'];
    $status = $payload['status'];

    // Example: update device status in the database
    $dbHost = 'localhost';
    $dbUser = 'root';
    $dbPass = 'password';
    $dbName = 'iot_platform';

    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName", $dbUser, $dbPass);

    $sql = "UPDATE devices SET status = ? WHERE device_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$status, $device_id]);

    echo 'Device status updated successfully.';
} else {
    echo 'Unrecognized event type.';
}
?>
