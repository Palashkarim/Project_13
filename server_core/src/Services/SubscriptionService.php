<?php
namespace Iot\Services;

use Iot\Models\Subscription;
use PDO;

/**
 * SubscriptionService
 * Manage plan assignment and simple status checks.
 */
class SubscriptionService {
  private Subscription $subs;

  public function __construct(PDO $pdo) {
    $this->subs = new Subscription($pdo);
  }

  public function plans(): array {
    return [
      ['key'=>'basic','name'=>'Basic','max_widgets'=>5,'max_boards'=>1,'retention_days'=>7],
      ['key'=>'pro','name'=>'Pro','max_widgets'=>25,'max_boards'=>5,'retention_days'=>30],
      ['key'=>'ent','name'=>'Enterprise','max_widgets'=>0,'max_boards'=>0,'retention_days'=>365]
    ];
  }

  public function assign(int $userId, string $planKey, int $months=12, array $overrides=[]): array {
    $expires = (new \DateTimeImmutable())->modify("+{$months} months")->format('Y-m-d H:i:s');
    $this->subs->assign($userId, $planKey, $expires, $overrides, 'active');
    return ['user_id'=>$userId,'plan_key'=>$planKey,'expires_at'=>$expires];
  }

  public function renew(int $userId, int $months=12): ?string {
    return $this->subs->renew($userId, $months);
  }

  public function status(int $userId): array {
    $s = $this->subs->get($userId);
    if (!$s) return ['status'=>'none'];
    $expired = (new \DateTimeImmutable($s['expires_at'])) < new \DateTimeImmutable('now');
    return ['plan_key'=>$s['plan_key'], 'expires_at'=>$s['expires_at'], 'status'=>$expired ? 'expired' : $s['status']];
  }
}