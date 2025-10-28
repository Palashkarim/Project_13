<?php
namespace Iot\Models;

use PDO;

/**
 * BillingPlan (optional if you store plans in DB)
 * - billing_plans(key TEXT PK, name, limits_json JSONB)
 */
class BillingPlan {
  public function __construct(private PDO $pdo) {}

  public function list(): array {
    return $this->pdo->query('SELECT key, name, limits_json FROM billing_plans ORDER BY key')->fetchAll();
  }

  public function upsert(string $key, string $name, array $limits): bool {
    $st = $this->pdo->prepare("INSERT INTO billing_plans(key, name, limits_json)
                               VALUES(:k,:n,:l)
                               ON CONFLICT (key) DO UPDATE SET name=EXCLUDED.name, limits_json=EXCLUDED.limits_json");
    return $st->execute([':k'=>$key, ':n'=>$name, ':l'=>json_encode($limits)]);
  }
}
