<?php
namespace Iot\Models;

use PDO;

/**
 * ServerBinding model
 * - server_bindings(user_id PK, mqtt_server_id, db_server_id)
 * Used for per-tenant load balancing across MQTT brokers and DB shards.
 */
class ServerBinding {
  public function __construct(private PDO $pdo) {}

  public function get(int $userId): ?array {
    $st = $this->pdo->prepare('SELECT user_id, mqtt_server_id, db_server_id FROM server_bindings WHERE user_id=:u');
    $st->execute([':u'=>$userId]);
    $row = $st->fetch();
    return $row ?: null;
  }

  public function assign(int $userId, string $mqttServerId, string $dbServerId): bool {
    $st = $this->pdo->prepare("
      INSERT INTO server_bindings(user_id, mqtt_server_id, db_server_id)
      VALUES (:u,:m,:d)
      ON CONFLICT (user_id) DO UPDATE SET mqtt_server_id=EXCLUDED.mqtt_server_id, db_server_id=EXCLUDED.db_server_id
    ");
    return $st->execute([':u'=>$userId, ':m'=>$mqttServerId, ':d'=>$dbServerId]);
  }
}
