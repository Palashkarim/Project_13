<?php
namespace Iot\Services;

use PDO;

/**
 * NotificationService
 * Enqueue and deliver notifications across channels (web/email/sms/push).
 * For email/SMS/push, integrate your transport of choice. Here we stub them.
 */
class NotificationService {
  public function __construct(private PDO $pdo) {}

  public function enqueue(int $userId, string $message, string $channel='web'): int {
    $st = $this->pdo->prepare("INSERT INTO notifications(user_id, message, channel, status)
                               VALUES(:u,:m,:c,'queued') RETURNING id");
    $st->execute([':u'=>$userId, ':m'=>$message, ':c'=>$channel]);
    return (int)$st->fetchColumn();
  }

  public function markRead(int $id): bool {
    return $this->pdo->prepare("UPDATE notifications SET read_at=now(), status='read' WHERE id=:i")
      ->execute([':i'=>$id]);
  }

  public function deliverQueued(callable $emailSender=null, callable $smsSender=null, callable $pushSender=null): int {
    // Very simple worker: deliver queued and mark delivered
    $st = $this->pdo->query("SELECT id, user_id, message, channel FROM notifications WHERE status='queued' ORDER BY id ASC LIMIT 100 FOR UPDATE SKIP LOCKED");
    $rows = $st->fetchAll();
    $delivered = 0;

    foreach ($rows as $n) {
      $ok = false;
      switch ($n['channel']) {
        case 'email':
          $ok = $emailSender ? (bool)$emailSender($n) : true; break;
        case 'sms':
          $ok = $smsSender ? (bool)$smsSender($n) : true; break;
        case 'push':
          $ok = $pushSender ? (bool)$pushSender($n) : true; break;
        default:
          // web/in-app notifications treated as delivered immediately
          $ok = true;
      }
      $this->pdo->prepare("UPDATE notifications SET status=:s WHERE id=:i")
        ->execute([':s'=>$ok?'delivered':'error', ':i'=>$n['id']]);
      if ($ok) $delivered++;
    }
    return $delivered;
  }
}

