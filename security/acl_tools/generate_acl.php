<?php
// Render ACL templates for mosquitto (simple file output).
// Usage: php generate_acl.php userId output_file
if ($argc < 3) {
    echo "Usage: php generate_acl.php <userId> <output_acl_file>\n";
    exit(1);
}
$userId = $argv[1];
$outFile = $argv[2];

$templates = [
    "topic read" => "pattern read ten/{$userId}/dev/+/state\n",
    "topic tele" => "pattern read ten/{$userId}/dev/+/tele\n",
    "topic cmd"  => "pattern write ten/{$userId}/dev/+/cmd\n",
];

file_put_contents($outFile, implode("", $templates));
echo "Wrote ACL to $outFile\n";
