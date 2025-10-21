<?php
namespace Iot\Services;

/**
 * MqttService
 * Abstraction to publish messages. In containers, you can install mosquitto-clients
 * and shell out to `mosquitto_pub`, or integrate a PHP MQTT client library.
 * This class encapsulates either strategy so the rest of the code doesn't care.
 */
class MqttService {
  public function __construct(
    private string $host = 'mqtt',
    private int $port = 1883,
    private ?string $username = null,
    private ?string $password = null
  ) {}

  /**
   * Publish a message (best-effort, fire-and-forget).
   * WARNING: Shelling out is simple but not the most efficient.
   * Replace with a library call if you add a PHP MQTT client.
   */
  public function publish(string $topic, string $payload, int $qos = 0, bool $retain = false): bool {
    // If mosquitto_pub is available:
    $cmd = [
      'mosquitto_pub',
      '-h', escapeshellarg($this->host),
      '-p', (string)$this->port,
      '-t', escapeshellarg($topic),
      '-m', escapeshellarg($payload),
      '-q', (string)$qos,
    ];
    if ($retain) $cmd[] = '-r';
    if ($this->username) { $cmd[]='-u'; $cmd[]=escapeshellarg($this->username); }
    if ($this->password) { $cmd[]='-P'; $cmd[]=escapeshellarg($this->password); }

    $line = implode(' ', $cmd) . ' 2>/dev/null';
    @exec($line, $out, $code);
    return $code === 0;
  }
}