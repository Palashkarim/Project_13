<?php
namespace Iot\Controllers;

/**
 * Manages plan templates + per-user assigned plan;
 * Enforces plan limits elsewhere via PlanLimitGuard middleware.
 */
class SubscriptionController extends BaseController {

  public function listPlans(): array {
    // TODO: load from billing_plans table
    return [
      'plans' => [
        ['key' => 'basic', 'name' => 'Basic', 'max_widgets' => 5, 'max_boards' => 1, 'retention_days' => 7],
        ['key' => 'pro', 'name' => 'Pro', 'max_widgets' => 25, 'max_boards' => 5, 'retention_days' => 30],
        ['key' => 'ent', 'name' => 'Enterprise', 'max_widgets' => 0, 'max_boards' => 0, 'retention_days' => 365], // 0 = unlimited
      ]
    ];
  }

  public function getUserPlan(string $userId): array {
    $pdo = $this->pdo();
    $stmt = $pdo->prepare('SELECT plan_key, expires_at, limits_json, status FROM subscriptions WHERE user_id = :uid LIMIT 1');
    $stmt->execute([':uid' => (int)$userId]);
    $row = $stmt->fetch();
    if (!$row) {
      http_response_code(404);
      return ['error' => 'No subscription'];
    }
    $row['limits'] = $row['limits_json'] ? json_decode($row['limits_json'], true) : null;
    unset($row['limits_json']);
    return $row;
  }

  public function assignPlan(string $userId): array {
    $body = $this->req->body;
    $planKey = (string)($body['plan_key'] ?? '');
    $months = (int)($body['months'] ?? 12);
    if ($planKey === '' || $months <= 0) {
      http_response_code(422);
      return ['error' => 'plan_key and months required'];
    }

    $expires = (new \DateTimeImmutable())->modify("+{$months} months")->format('Y-m-d H:i:s');
    $limits = [
      'max_widgets' => $body['max_widgets'] ?? null,
      'max_boards' => $body['max_boards'] ?? null,
      'retention_days' => $body['retention_days'] ?? null,
      'export_window_days' => $body['export_window_days'] ?? 30,
    ];

    $pdo = $this->pdo();
    $stmt = $pdo->prepare("
      INSERT INTO subscriptions (user_id, plan_key, expires_at, limits_json, status)
      VALUES (:u, :k, :e, :l, 'active')
      ON CONFLICT (user_id) DO UPDATE SET
        plan_key = EXCLUDED.plan_key,
        expires_at = EXCLUDED.expires_at,
        limits_json = EXCLUDED.limits_json,
        status = 'active'");
    $stmt->execute([
      ':u' => (int)$userId,
      ':k' => $planKey,
      ':e' => $expires,
      ':l' => json_encode($limits),
    ]);

    return ['status' => 'assigned', 'user_id' => (int)$userId, 'plan_key' => $planKey, 'expires_at' => $expires];
  }

  public function renewPlan(string $userId): array {
    $months = (int)($this->req->body['months'] ?? 12);
    if ($months <= 0) {
      http_response_code(422);
      return ['error' => 'months must be > 0'];
    }
    $pdo = $this->pdo();
    $pdo->beginTransaction();
    $stmt = $pdo->prepare('SELECT expires_at FROM subscriptions WHERE user_id = :u FOR UPDATE');
    $stmt->execute([':u' => (int)$userId]);
    $row = $stmt->fetch();
    if (!$row) {
      $pdo->rollBack();
      http_response_code(404);
      return ['error' => 'No subscription'];
    }
    $base = new \DateTimeImmutable($row['expires_at'] ?? 'now');
    $newExp = $base < new \DateTimeImmutable('now') ? new \DateTimeImmutable() : $base;
    $newExp = $newExp->modify("+{$months} months")->format('Y-m-d H:i:s');

    $up = $pdo->prepare('UPDATE subscriptions SET expires_at = :e, status = \'active\' WHERE user_id = :u');
    $up->execute([':e' => $newExp, ':u' => (int)$userId]);
    $pdo->commit();
    return ['status' => 'renewed', 'expires_at' => $newExp];
  }

  public function status(string $userId): array {
    $pdo = $this->pdo();
    $stmt = $pdo->prepare('SELECT plan_key, expires_at, status FROM subscriptions WHERE user_id = :u');
    $stmt->execute([':u' => (int)$userId]);
    $row = $stmt->fetch();
    if (!$row) { return ['status' => 'none']; }

    $expired = (new \DateTimeImmutable($row['expires_at'])) < new \DateTimeImmutable('now');
    return [
      'plan_key' => $row['plan_key'],
      'expires_at' => $row['expires_at'],
      'status' => $expired ? 'expired' : $row['status']
    ];
  }
}
