<?php
namespace Iot\Models;

use PDO;

/**
 * Subscription model
 * - subscriptions(user_id PK, plan_key, expires_at, limits_json JSONB, status)
 */
class Subscription {
  public function __construct(private PDO $pdo) {}

  public function get(int $userId): ?array {
    $st = $this->pdo->prepare('SELECT user_id, plan_key, expires_at, limits_json, status FROM subscriptions WHERE user_id=:u');
    $st->execute([':u'=>$userId]);
    $row = $st->fetch();
    return $row ?: null;
  }

  public function assign(int $userId, string $planKey, string $expiresAt, array $limits = [], string $status='active'): bool {
    $st = $this->pdo->prepare("
      INSERT INTO subscriptions(user_id, plan_key, expires_at, limits_json, status)
      VALUES (:u,:k,:e,:l,:s)
      ON CONFLICT (user_id) DO UPDATE SET
        plan_key=EXCLUDED.plan_key, expires_at=EXCLUDED.expires_at, limits_json=EXCLUDED.limits_json, status=EXCLUDED.status
    ");
    return $st->execute([':u'=>$userId, ':k'=>$planKey, ':e'=>$expiresAt, ':l'=>json_encode($limits), ':s'=>$status]);
  }

  public function renew(int $userId, int $months): ?string {
    $this->pdo->beginTransaction();
    $cur = $this->pdo->prepare('SELECT expires_at FROM subscriptions WHERE user_id=:u FOR UPDATE');
    $cur->execute([':u'=>$userId]);
    $row = $cur->fetch();
    if (!$row) { $this->pdo->rollBack(); return null; }

    $base = new \DateTimeImmutable($row['expires_at'] ?? 'now');
    $new  = $base < new \DateTimeImmutable('now') ? new \DateTimeImmutable() : $base;
    $new  = $new->modify("+{$months} months")->format('Y-m-d H:i:s');

    $up = $this->pdo->prepare("UPDATE subscriptions SET expires_at=:e, status='active' WHERE user_id=:u");
    $up->execute([':e'=>$new, ':u'=>$userId]);
    $this->pdo->commit();
    return $new;
  }
}
