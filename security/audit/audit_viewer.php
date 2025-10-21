<?php
// audit_viewer.php
/**
 * This script displays the audit logs in a human-readable format.
 * The logs can be viewed directly from the browser.
 */

$logFile = '/path/to/audit_logs.csv';  // Path to the audit log file

function viewLogs($file) {
    if (!file_exists($file)) {
        echo "No audit logs found.";
        return;
    }

    $logs = file_get_contents($file);
    echo "<pre>$logs</pre>";
}

viewLogs($logFile);
?>