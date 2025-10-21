<?php
// publish_test_message.php
/**
 * This script publishes a test message to an MQTT topic from the command line.
 * Useful for testing connectivity and debugging.
 */

require 'vendor/autoload.php';  // Include the MQTT library (e.g., phpMQTT)

use Bluerhinos\phpMQTT;

// MQTT broker connection settings
$host = 'mqtt.example.com';
$port = 1883;
$username = 'mqtt_user';
$password = 'mqtt_password';
$topic = 'test/topic';

// Create a new MQTT client instance
$mqtt = new phpMQTT($host, $port, "TestClient");

if ($mqtt->connect(true, NULL, $username, $password)) {
    $mqtt->publish($topic, 'Test message from CLI', 0);
    echo "Test message published to topic '$topic'.\n";
    $mqtt->close();
} else {
    echo "Failed to connect to MQTT broker.\n";
}
?>
