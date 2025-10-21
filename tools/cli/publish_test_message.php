<?php
// Publish test MQTT message - uses mosquitto_pub (system must have it)
// Usage: php publish_test_message.php topic message
if ($argc < 3) { echo "Usage: php publish_test_message.php topic message\n"; exit(1); }
$topic = $argv[1];
$msg = $argv[2];
$host = getenv('MQTT_HOST') ?: 'localhost';
$port = getenv('MQTT_PORT') ?: 1883;
$cmd = sprintf('mosquitto_pub -h %s -p %d -t %s -m %s', escapeshellarg($host), (int)$port, escapeshellarg($topic), escapeshellarg($msg));
exec($cmd, $out, $rc);
echo "Published: rc=$rc\n";
