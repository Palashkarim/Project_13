<?php
// bulk_import_devices.php
/**
 * This script imports devices in bulk from a CSV file.
 * The CSV should contain user_id, device_name, and device_type columns.
 */

// Database connection settings (example)
$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = 'password';
$dbName = 'iot_platform';

$pdo = new PDO("mysql:host=$dbHost;dbname=$dbName", $dbUser, $dbPass);

// Path to CSV file (you can adjust this as needed)
$csvFile = 'devices.csv';

if (!file_exists($csvFile)) {
    echo "CSV file not found!\n";
    exit(1);
}

$csv = array_map('str_getcsv', file($csvFile));  // Read CSV
array_walk($csv, function(&$a) use ($csv) {
    $a = array_combine($csv[0], $a);
});
array_shift($csv);  // Remove header

// Insert devices into the database
$sql = "INSERT INTO devices (user_id, device_name, device_type) VALUES (?, ?, ?)";
$stmt = $pdo->prepare($sql);

foreach ($csv as $device) {
    $stmt->execute([$device['user_id'], $device['device_name'], $device['device_type']]);
}

echo "Bulk devices imported successfully.\n";
?>
