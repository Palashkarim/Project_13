<?php
namespace Iot\Models;

use PDO;

/**
 * UserWidgetAllow model
 * - user_widget_allow(user_id, widget_key) PRIMARY KEY(user_id, widget_key)
 * The source of truth for the "checkbox matrix" per user.
 */
class UserWidgetAllow {
  public function __construct(private PDO $pdo) {}

  public function setAllowed(int $userId, array $keys): bool {
    $this->pdo->beginTransaction();
    $this->pdo->prepare('DELETE FROM user_widget_allow WHERE user_id=:u')->execute([':u'=>$userId]);
    if ($keys) {
      $ins = $this->pdo->prepare('INSERT INTO user_widget_allow(user_id, widget_key) VALUES (:u,:k)');
      foreach ($keys as $k) $ins->execute([':u'=>$userId, ':k'=>(string)$k]);
    }
    $this->pdo->commit();
    return true;
  }

  public function getAllowed(int $userId): array {
    $st = $this->pdo->prepare('SELECT widget_key FROM user_widget_allow WHERE user_id=:u ORDER BY widget_key');
    $st->execute([':u'=>$userId]);
    return array_column($st->fetchAll(), 'widget_key');
  }
}