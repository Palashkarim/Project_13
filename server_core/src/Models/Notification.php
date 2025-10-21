<?php
namespace Iot\Models;

use PDO;

/**
 * Notification model
 * - notifications(id PK, user_id, message, channel, status, created_at, read_at)
 * channel: web|email|sms|push
 */
class Notification {
  public function __construct(private PDO $pdo) {}

  public function enqueue(int $userId, string $message, string $channel='web'): int {
    $st = $this->pdo->prepare("INSERT INTO notifications(user_id, message, channel, status)
                               VALUES(:u,:m,:c,'queued') RETURNING id");
    $st->execute([':u'=>$userId, ':m'=>$message, ':c'=>$channel]);
    return (int)$st->fetchColumn();
  }

  public function listForUser(int $userId, int $limit=200): array {
    $st = $this->pdo->prepare('SELECT id, message, channel, status, created_at, read_at
                               FROM notifications WHERE user_id=:u ORDER BY id DESC LIMIT :l');
    $st->bindValue(':u', $userId, PDO::PARAM_INT);
    $st->bindValue(':l', max(1, min(1000, $limit)), PDO::PARAM_INT);
    $st->execute();
    return $st->fetchAll();
  }

  public function markRead(int $id): bool {
    return $this->pdo->prepare("UPDATE notifications SET read_at=now(), status='read' WHERE id=:i")
      ->execute([':i'=>$id]);
  }
}