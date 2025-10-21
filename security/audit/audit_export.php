
<?php
// audit_export.php
/**
 * This script exports the audit logs in CSV format.
 * It pulls the logs from the database and generates a CSV file for download.
 */

$logFile = '/path/to/audit_logs.csv';  // Path to store the export file
$headers = ["Timestamp", "User", "Action", "Details"];

// Example audit log data (replace with actual log data retrieval logic)
$auditLogs = [
    ["2025-10-01 12:00:00", "admin", "login", "Successful login from 192.168.1.100"],
    ["2025-10-01 12:05:00", "user1", "data_export", "Exported IoT device data"],
];

function exportLogs($file, $headers, $logs) {
    $fp = fopen($file, 'w');
    fputcsv($fp, $headers);

    foreach ($logs as $log) {
        fputcsv($fp, $log);
    }

    fclose($fp);
}

exportLogs($logFile, $headers, $auditLogs);

echo "Audit logs exported to $logFile\n";
?>