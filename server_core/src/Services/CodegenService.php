<?php
namespace Iot\Services;

use ZipArchive;

/**
 * CodegenService
 * Builds Arduino/C firmware packages tailored per user/device:
 * - Replaces tokens in template files with secrets, topics, etc.
 * - Outputs a ZIP with /src/*.ino|*.c and headers.
 *
 * Template directory layout:
 *   server_core/src/Codegen/templates/<hw>/
 *     main.ino.tpl  OR main.c.tpl
 *     secrets.h.tpl
 *     mqtt_topics.h.tpl
 *     pins.h.tpl
 *     ota.h.tpl
 */
class CodegenService {
  public function __construct(
    private string $templateRoot = __DIR__ . '/../Codegen/templates',
    private string $buildDir = '/var/iot/storage/firmware_builds',
    private string $mqttHost = 'mqtt',
    private int $mqttPort = 1883
  ) {}

  public function build(array $opts): array {
    $userId   = (int)($opts['user_id'] ?? 0);
    $hardware = (string)($opts['hardware'] ?? 'esp32');
    $wifiSsid = (string)($opts['wifi_ssid'] ?? '');
    $wifiPass = (string)($opts['wifi_password'] ?? '');
    $mqttUser = (string)($opts['mqtt_user'] ?? '');
    $mqttPass = (string)($opts['mqtt_password'] ?? '');
    $deviceId = trim((string)($opts['device_id'] ?? 'D'.bin2hex(random_bytes(3))));
    $qos      = (int)($opts['qos'] ?? 1);

    if ($userId<=0 || $wifiSsid==='' || $mqttUser==='' || $mqttPass==='') {
      throw new \InvalidArgumentException('user_id, wifi_ssid, mqtt_user, mqtt_password required');
    }
    if (!in_array($hardware, ['esp32','esp8266','c_freertos'], true)) {
      throw new \InvalidArgumentException('unsupported hardware');
    }

    $tenantPrefix = "ten/{$userId}";
    $topics = [
      'cmd'   => "{$tenantPrefix}/dev/{$deviceId}/cmd",
      'tele'  => "{$tenantPrefix}/dev/{$deviceId}/tele",
      'state' => "{$tenantPrefix}/dev/{$deviceId}/state",
      'lwt'   => "{$tenantPrefix}/dev/{$deviceId}/lwt",
    ];
    $otaKey = bin2hex(random_bytes(16));

    $basePath  = rtrim($this->templateRoot, '/').'/'.$hardware;
    $mainTpl   = @file_get_contents($basePath.'/main.ino.tpl') ?: @file_get_contents($basePath.'/main.c.tpl') ?: '';
    $secretsTpl= @file_get_contents($basePath.'/secrets.h.tpl') ?: '';
    $topicsTpl = @file_get_contents($basePath.'/mqtt_topics.h.tpl') ?: '';
    $pinsTpl   = @file_get_contents($basePath.'/pins.h.tpl') ?: '';
    $otaTpl    = @file_get_contents($basePath.'/ota.h.tpl') ?: '';

    if ($mainTpl==='') throw new \RuntimeException('missing firmware templates');

    $repl = [
      '{{WIFI_SSID}}'   => addslashes($wifiSsid),
      '{{WIFI_PASS}}'   => addslashes($wifiPass),
      '{{MQTT_HOST}}'   => $this->mqttHost,
      '{{MQTT_PORT}}'   => (string)$this->mqttPort,
      '{{MQTT_USER}}'   => addslashes($mqttUser),
      '{{MQTT_PASS}}'   => addslashes($mqttPass),
      '{{TOPIC_CMD}}'   => $topics['cmd'],
      '{{TOPIC_TELE}}'  => $topics['tele'],
      '{{TOPIC_STATE}}' => $topics['state'],
      '{{TOPIC_LWT}}'   => $topics['lwt'],
      '{{DEVICE_ID}}'   => $deviceId,
      '{{OTA_KEY}}'     => $otaKey,
      '{{QOS}}'         => (string)$qos
    ];
    $render = fn(string $tpl) => str_replace(array_keys($repl), array_values($repl), $tpl);

    $main   = $render($mainTpl);
    $secrets= $render($secretsTpl);
    $topicsH= $render($topicsTpl);
    $pinsH  = $pinsTpl ?: "// pins file\n";
    $otaH   = $otaTpl ?: "// ota helpers\n";

    @mkdir($this->buildDir, 0750, true);
    $buildId = 'b_'.bin2hex(random_bytes(6));
    $zipPath = rtrim($this->buildDir, '/')."/{$buildId}_{$hardware}_firmware.zip";

    $zip = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
      throw new \RuntimeException('cannot create zip');
    }
    $zip->addFromString('src/main.ino', $main);
    $zip->addFromString('src/secrets.h', $secrets);
    $zip->addFromString('src/mqtt_topics.h', $topicsH);
    $zip->addFromString('src/pins.h', $pinsH);
    $zip->addFromString('src/ota.h', $otaH);
    $zip->addFromString('README.txt',
      "Flash with Arduino IDE / PlatformIO.\nDevice: {$deviceId}\nBuild: {$buildId}\nUser: {$userId}\n");
    $zip->close();

    return [
      'build_id'     => $buildId,
      'device_id'    => $deviceId,
      'hardware'     => $hardware,
      'download_path'=> $zipPath
    ];
  }
}
