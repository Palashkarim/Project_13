<?php
// Small debug endpoint to preview ACL rules for a user.
// Run from CLI: php preview_acl.php <userId>
if ($argc < 2) {
    echo "Usage: php preview_acl.php <userId>\n";
    exit(1);
}
$userId = $argv[1];
echo "ACL Preview for user $userId\n";
echo "--------------------------------\n";
echo "user ten/{$userId} read\n";
echo "topic ten/{$userId}/dev/+/tele read\n";
echo "topic ten/{$userId}/dev/+/state read\n";
echo "topic ten/{$userId}/dev/+/cmd write\n";
