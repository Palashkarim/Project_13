<?php
namespace Iot\Controllers;

/**
 * Widget catalog + per-user allow matrix (checkbox UI source of truth)
 */
class WidgetController extends BaseController {

  public function catalog(): array {
    // Could also come from DB table "widgets"
    return [
      'widgets' => [
        ['key' => 'switch', 'name' => 'Switch', 'category' => 'control'],
        ['key' => 'slider', 'name' => 'Slider', 'category' => 'control'],
        ['key' => 'gauge', 'name' => 'Gauge', 'category' => 'monitor'],
        ['key' => 'chart', 'name' => 'Chart', 'category' => 'monitor'],
        ['key' => 'camera', 'name' => 'Camera Feed', 'category' => 'security'],
        ['key' => 'alarm_panel', 'name' => 'Alarm Panel', 'category' => 'security'],
        ['key' => 'env_monitor', 'name' => 'Environment Monitor', 'category' => 'monitor'],
        ['key' => 'power_dashboard', 'name' => 'Power Dashboard', 'category' => 'energy'],
        ['key' => 'production_tracker', 'name' => 'Production Tracker', 'category' => 'industrial'],
        ['key' => 'map_tracker', 'name' => 'Map / GPS', 'category' => 'fleet'],
        ['key' => 'ota', 'name' => 'OTA Management', 'category' => 'maintenance'],
        ['key' => 'device_health', 'name' => 'Device Health', 'category' => 'maintenance'],
        ['key' => 'scene_builder', 'name' => 'Scene Builder', 'category' => 'automation'],
        ['key' => 'rules_engine', 'name' => 'Rules Engine', 'category' => 'automation'],
        ['key' => 'billing', 'name' => 'Billing', 'category' => 'business'],
        ['key' => 'retention', 'name' => 'Retention', 'category' => 'data'],
        ['key' => 'device_simulator', 'name' => 'Device Simulator', 'category' => 'devtools']
      ]
    ];
  }

  public function setUserAllowedWidgets(string $userId): array {
    $list = $this->req->body['allowed'] ?? []; // ['switch','gauge',...]
    if (!is_array($list)) {
      http_response_code(422);
      return ['error' => 'allowed must be an array of widget keys'];
    }
    $pdo = $this->pdo();
    $pdo->beginTransaction();
    $pdo->prepare('DELETE FROM user_widget_allow WHERE user_id = :u')->execute([':u' => (int)$userId]);
    $ins = $pdo->prepare('INSERT INTO user_widget_allow (user_id, widget_key) VALUES (:u, :k)');
    foreach ($list as $k) {
      $ins->execute([':u' => (int)$userId, ':k' => (string)$k]);
    }
    $pdo->commit();
    return ['status' => 'ok', 'user_id' => (int)$userId, 'allowed' => array_values($list)];
  }
}
