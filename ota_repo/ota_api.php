<?php
// Simple PHP endpoint for OTA firmware listing and metadata serving.
// This file assumes it's placed in a web root that can serve the firmware files.
declare(strict_types=1);

$baseDir = __DIR__ . '/firmwares';
header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? 'list';

if ($action === 'list') {
    $files = array_values(array_filter(scandir($baseDir), function($f){
        return is_file($baseDir . '/' . $f) && preg_match('/\.bin$/i', $f);
    }));
    $out = [];
    foreach ($files as $f) {
        $metaFile = __DIR__ . '/metadata/' . pathinfo($f, PATHINFO_FILENAME) . '.json';
        $meta = null;
        if (file_exists($metaFile)) {
            $meta = json_decode(file_get_contents($metaFile), true);
        }
        $out[] = ['file' => $f, 'metadata' => $meta];
    }
    echo json_encode(['firmwares' => $out], JSON_PRETTY_PRINT);
    exit;
}

if ($action === 'meta' && isset($_GET['file'])) {
    $file = basename($_GET['file']);
    $metaFile = __DIR__ . '/metadata/' . pathinfo($file, PATHINFO_FILENAME) . '.json';
    if (!file_exists($metaFile)) {
        http_response_code(404);
        echo json_encode(['error' => 'not found']);
        exit;
    }
    echo file_get_contents($metaFile);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'invalid_action']);
