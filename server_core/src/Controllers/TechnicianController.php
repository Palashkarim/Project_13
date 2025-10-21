<?php
namespace Iot\Controllers;

use ZipArchive;

/**
 * Technician flow:
 * - searchUser: find by email/ID
 * - techInsights: show topics/pins/QoS/LWT/server/db + ACL preview
 * - generateFirmware: build Arduino/C files with injected secrets → ZIP
 * - downloadFirmware: returns the generated ZIP (signed URL recommended in prod)
 */
class TechnicianController extends BaseController {

  public function searchUser(): array {
    $q = trim((string)($this->req->body['q'] ?? ''));
    if ($q === '') {
      http_response_code(422);
      return ['error' => 'q required'];
    }
    $pdo = $this->pdo();
    $stmt = $pdo->prepare('SELECT id, email, display_name, role FROM users WHERE email ILIKE :q OR CAST(id AS TEXT)=:qe LIMIT 20');
    $stmt->execute([':q' => "%$q%", ':qe' => $q]);
    $rows = $stmt->fetchAll();
    return ['results' => $rows];
  }

  public function techInsights(string $userId): array {
    // Build derived MQTT topic prefixes, pins, QoS, LWT, ACL preview
    // In production, pull from user/device/board/widget tables + server_bindings
    $tenant = "ten/{$userId}";
    $topics = [
      'cmd' => "{$tenant}/dev/{deviceId}/cmd",
      'tele' => "{$tenant}/dev/{deviceId}/tele",
      'state' => "{$tenant}/dev/{deviceId}/state",
      'lwt' => "{$tenant}/dev/{deviceId}/lwt",
    ];
    $dbShard = "db-shard-01"; // TODO: from server_bindings
    $mqttServer = "mqtt_01";  // TODO: from server_bindings
    $aclPreview = [
      ['access' => 'write', 'topic' => "{$tenant}/dev/+ /cmd"],
      ['access' => 'read',  'topic' => "{$tenant}/dev/+ /state"],
      ['access' => 'read',  'topic' => "{$tenant}/dev/+ /tele"],
      ['access' => 'read',  'topic' => "{$tenant}/dev/+ /lwt"],
    ];

    return [
      'user_id' => (int)$userId,
      'qos' => 1,
      'retain_states' => true,
      'lwt' => ['topic' => $topics['lwt'], 'payload' => 'offline', 'qos' => 1, 'retain' => true],
      'topics' => $topics,
      'pins_hint' => ['relay' => 5, 'sensor' => 34], // sample defaults
      'server_binding' => ['mqtt' => $mqttServer, 'db' => $dbShard],
      'acl_preview' => $aclPreview
    ];
  }

  public function generateFirmware(): array {
    $b = $this->req->body;

    $userId = (int)($b['user_id'] ?? 0);
    $hardware = (string)($b['hardware'] ?? 'esp32'); // esp32 | esp8266 | c_freertos
    $widgets = $b['widgets'] ?? []; // array of widget keys
    $wifiSsid = (string)($b['wifi_ssid'] ?? '');
    $wifiPass = (string)($b['wifi_password'] ?? '');
    $mqttUser = (string)($b['mqtt_user'] ?? '');
    $mqttPass = (string)($b['mqtt_password'] ?? '');
    $deviceId = trim((string)($b['device_id'] ?? 'D'.bin2hex(random_bytes(3))));

    if ($userId <= 0 || $wifiSsid === '' || $mqttUser === '' || $mqttPass === '') {
      http_response_code(422);
      return ['error' => 'user_id, wifi_ssid, mqtt_user, mqtt_password required'];
    }
    if (!in_array($hardware, ['esp32','esp8266','c_freertos'], true)) {
      http_response_code(422);
      return ['error' => 'unsupported hardware'];
    }

    // Pull server bindings, topics, pins from DB & templates:
    $tenantPrefix = "ten/{$userId}";
    $topics = [
      'cmd' => "{$tenantPrefix}/dev/{$deviceId}/cmd",
      'tele' => "{$tenantPrefix}/dev/{$deviceId}/tele",
      'state' => "{$tenantPrefix}/dev/{$deviceId}/state",
      'lwt' => "{$tenantPrefix}/dev/{$deviceId}/lwt",
    ];
    $mqttHost = $this->env('MQTT_HOST','mqtt');
    $mqttPort = (int)$this->env('MQTT_PORT','1883');
    $otaKey = bin2hex(random_bytes(16)); // per build secret

    // Prepare file contents from templates
    $basePath = __DIR__ . '/../Codegen/templates/' . $hardware;
    $mainTpl = file_get_contents($basePath . '/main.ino.tpl') ?: '';
    $secretsTpl = file_get_contents($basePath . '/secrets.h.tpl') ?: '';
    $topicsTpl = file_get_contents($basePath . '/mqtt_topics.h.tpl') ?: '';
    $pinsTpl = @file_get_contents($basePath . '/pins.h.tpl') ?: '';
    $otaTpl = @file_get_contents($basePath . '/ota.h.tpl') ?: '';

    // Replace tokens in templates (simple {{token}} replacement)
    $repl = [
      '{{WIFI_SSID}}' => addslashes($wifiSsid),
      '{{WIFI_PASS}}' => addslashes($wifiPass),
      '{{MQTT_HOST}}' => $mqttHost,
      '{{MQTT_PORT}}' => (string)$mqttPort,
      '{{MQTT_USER}}' => addslashes($mqttUser),
      '{{MQTT_PASS}}' => addslashes($mqttPass),
      '{{TOPIC_CMD}}' => $topics['cmd'],
      '{{TOPIC_TELE}}' => $topics['tele'],
      '{{TOPIC_STATE}}' => $topics['state'],
      '{{TOPIC_LWT}}' => $topics['lwt'],
      '{{DEVICE_ID}}' => $deviceId,
      '{{OTA_KEY}}' => $otaKey,
      '{{QOS}}' => '1'
    ];

    $render = fn(string $tpl) => str_replace(array_keys($repl), array_values($repl), $tpl);

    $main = $render($mainTpl);
    $secrets = $render($secretsTpl);
    $topicsH = $render($topicsTpl);
    $pinsH = $pinsTpl ?: "// pins selected by platform per widget\n";
    $otaH = $otaTpl ?: "// OTA helpers\n";

    // Package ZIP for download
    $buildId = 'b_' . bin2hex(random_bytes(6));
    $outDir = rtrim($this->env('FIRMWARE_BUILDS_DIR','/var/iot/storage/firmware_builds'), '/');
    @mkdir($outDir, 0750, true);
    $zipPath = $outDir . "/{$buildId}_{$hardware}_firmware.zip";

    $zip = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
      http_response_code(500);
      return ['error' => 'cannot create zip'];
    }
    $zip->addFromString('src/main.ino', $main);
    $zip->addFromString('src/secrets.h', $secrets);
    $zip->addFromString('src/mqtt_topics.h', $topicsH);
    $zip->addFromString('src/pins.h', $pinsH);
    $zip->addFromString('src/ota.h', $otaH);
    $zip->addFromString('README.txt', "Flash with Arduino IDE.\nDevice: {$deviceId}\nBuild: {$buildId}\n");
    $zip->close();

    // TODO: Insert firmware build record into DB (owner=userId, path=$zipPath, deviceId)
    return [
      'status' => 'built',
      'build_id' => $buildId,
      'hardware' => $hardware,
      'download_url' => "/api/technician/firmware/{$buildId}/download" // download handler below
    ];
  }

  public function downloadFirmware(string $buildId): void {
    $file = rtrim($this->env('FIRMWARE_BUILDS_DIR','/var/iot/storage/firmware_builds'), '/') . "/{$buildId}_esp32_firmware.zip";
    // Also support esp8266/c_freertos; try to find file by glob:
    if (!file_exists($file)) {
      $matches = glob(rtrim($this->env('FIRMWARE_BUILDS_DIR','/var/iot/storage/firmware_builds'), '/') . "/{$buildId}_*_firmware.zip");
      $file = $matches[0] ?? '';
    }
    if ($file === '' || !is_file($file)) {
      http_response_code(404);
      echo json_encode(['error' => 'build not found']);
      return;
    }
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . basename($file) . '"');
    header('Content-Length: ' . filesize($file));
    readfile($file);
  }
}
