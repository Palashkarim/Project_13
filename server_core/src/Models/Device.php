<?php
namespace Iot\Models;

use PDO;

/**
 * Device model
 * - devices(id PK, user_id, device_id, hw_type, mqtt_username, mqtt_password, last_seen, meta JSONB)
 */
class Device {
  public function __construct(private PDO $pdo) {}

  public function listAll(int $limit=200, int $offset=0): array {
    $st = $this->pdo->prepare('SELECT * FROM devices ORDER BY id DESC LIMIT :l OFFSET :o');
    $st->bindValue(':l', max(1, min(1000,$limit)), PDO::PARAM_INT);
    $st->bindValue(':o', max(0, $offset), PDO::PARAM_INT);
    $st->execute();
    return $st->fetchAll();
  }

  public function listByUser(int $userId, int $limit=200, int $offset=0): array {
    $st = $this->pdo->prepare('SELECT * FROM devices WHERE user_id=:u ORDER BY id DESC LIMIT :l OFFSET :o');
    $st->bindValue(':u', $userId, PDO::PARAM_INT);
    $st->bindValue(':l', max(1, min(1000,$limit)), PDO::PARAM_INT);
    $st->bindValue(':o', max(0, $offset), PDO::PARAM_INT);
    $st->execute();
    return $st->fetchAll();
  }

  public function register(int $userId, string $deviceId, string $hwType='esp32', ?string $mqttUser=null, ?string $mqttPass=null, array $meta=[]): int {
    $st = $this->pdo->prepare('INSERT INTO devices(user_id, device_id, hw_type, mqtt_username, mqtt_password, meta)
                               VALUES(:u,:d,:h,:mu,:mp,:m) RETURNING id');
    $st->execute([':u'=>$userId, ':d'=>$deviceId, ':h'=>$hwType, ':mu'=>$mqttUser, ':mp'=>$mqttPass, ':m'=>json_encode($meta)]);
    return (int)$st->fetchColumn();
  }

  public function find(int $id): ?array {
    $st = $this->pdo->prepare('SELECT * FROM devices WHERE id=:id');
    $st->execute([':id'=>$id]);
    return $st->fetch() ?: null;
  }

  public function updateMeta(int $id, array $meta): bool {
    $st = $this->pdo->prepare('UPDATE devices SET meta=:m WHERE id=:id');
    return $st->execute([':m'=>json_encode($meta), ':id'=>$id]);
  }

  public function delete(int $id): bool {
    return $this->pdo->prepare('DELETE FROM devices WHERE id=:id')->execute([':id'=>$id]);
  }
}
