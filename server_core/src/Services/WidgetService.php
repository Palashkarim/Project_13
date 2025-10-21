<?php
namespace Iot\Services;

use Iot\Models\UserWidgetAllow;
use PDO;

/**
 * WidgetService
 * Keeps a catalog (static or DB-backed) and manages per-user allowlist.
 */
class WidgetService {
  private UserWidgetAllow $allow;

  public function __construct(PDO $pdo) {
    $this->allow = new UserWidgetAllow($pdo);
  }

  /** Static widget catalog (sync with UI) */
  public function catalog(): array {
    return [
      ['key'=>'switch','name'=>'Switch','category'=>'control'],
      ['key'=>'slider','name'=>'Slider','category'=>'control'],
      ['key'=>'gauge','name'=>'Gauge','category'=>'monitor'],
      ['key'=>'chart','name'=>'Chart','category'=>'monitor'],
      ['key'=>'camera','name'=>'Camera Feed','category'=>'security'],
      ['key'=>'alarm_panel','name'=>'Alarm Panel','category'=>'security'],
      ['key'=>'env_monitor','name'=>'Environment Monitor','category'=>'monitor'],
      ['key'=>'power_dashboard','name'=>'Power Dashboard','category'=>'energy'],
      ['key'=>'production_tracker','name'=>'Production Tracker','category'=>'industrial'],
      ['key'=>'map_tracker','name'=>'Map / GPS','category'=>'fleet'],
      ['key'=>'ota','name'=>'OTA Management','category'=>'maintenance'],
      ['key'=>'device_health','name'=>'Device Health','category'=>'maintenance'],
      ['key'=>'scene_builder','name'=>'Scene Builder','category'=>'automation'],
      ['key'=>'rules_engine','name'=>'Rules Engine','category'=>'automation'],
      ['key'=>'billing','name'=>'Billing','category'=>'business'],
      ['key'=>'retention','name'=>'Retention','category'=>'data'],
      ['key'=>'device_simulator','name'=>'Device Simulator','category'=>'devtools']
    ];
  }

  public function setUserAllowed(int $userId, array $keys): bool {
    return $this->allow->setAllowed($userId, $keys);
  }

  public function getUserAllowed(int $userId): array {
    return $this->allow->getAllowed($userId);
  }
}
