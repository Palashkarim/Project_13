<?php
namespace Iot\Controllers;

/**
 * BillingController (simple)
 * - plans(): static or DB-backed list of plans
 * - usage(): per-user device/telemetry usage (for reporting/limits)
 */
class BillingController extends BaseController {

  public function plans(): array {
    // Could read from billing_plans table. Kept simple here.
    return [
      'plans'=>[
        ['key'=>'basic','name'=>'Basic','max_widgets'=>5,'max_boards'=>1,'retention_days'=>7],
        ['key'=>'pro','name'=>'Pro','max_widgets'=>25,'max_boards'=>5,'retention_days'=>30],
        ['key'=>'ent','name'=>'Enterprise','max_widgets'=>0,'max_boards'=>0,'retention_days'=>365]
      ]
    ];
  }

  public function usage(string $userId): array {
    $pdo = $this->pdo();
    // Example: count of devices, telemetry rows in last 30 days
    $d = $pdo->prepare('SELECT count(*)::int AS devices FROM devices WHERE user_id=:u');
    $d->execute([':u'=>(int)$userId]);
    $devices = (int)($d->fetch()['devices'] ?? 0);

    $t = $pdo->prepare("SELECT count(*)::bigint AS rows FROM telemetry WHERE user_id=:u AND ts > now() - interval '30 days'");
    $t->execute([':u'=>(int)$userId]);
    $rows = (int)($t->fetch()['rows'] ?? 0);

    return ['user_id'=>(int)$userId,'devices'=>$devices,'telemetry_rows_30d'=>$rows];
  }
}
