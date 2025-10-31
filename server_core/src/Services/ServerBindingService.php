<?php
namespace Iot\Services;

use Iot\Models\ServerBinding;
use PDO;

/**
 * ServerBindingService
 * Assign per-user MQTT/DB servers for load balancing.
 */
class ServerBindingService {
  private ServerBinding $sb;

  public function __construct(PDO $pdo) {
    $this->sb = new ServerBinding($pdo);
  }

  public function assign(int $userId, string $mqttServerId, string $dbServerId): bool {
    return $this->sb->assign($userId, $mqttServerId, $dbServerId);
  }

  public function get(int $userId): ?array {
    return $this->sb->get($userId);
  }
}
