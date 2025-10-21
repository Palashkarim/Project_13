<?php
namespace Iot\Middleware;

use PDO;

/**
 * PlanLimitGuard
 * Enforces subscription-based limits for:
 * - Board count per user
 * - Widget count per board
 * - Data export time window (how far back a user may export)
 *
 * Schema:
 *   subscriptions(user_id PK, plan_key, expires_at, limits_json JSONB, status)
 *   boards(id, user_id, ...)
 *   board_widgets(id, board_id, ...)
 *
 * Limits priority: per-user overrides in limits_json > plan defaults.
 *
 * Usage examples:
 *   PlanLimitGuard::enforceBoardCap($pdo, $userId);
 *   PlanLimitGuard::enforceWidgetCap($pdo, $boardId, $userId);
 *   PlanLimitGuard::enforceExportWindow($pdo, $userId, $fromTs);
 */
class PlanLimitGuard {

  /** Throws 402/403 if the plan is expired or missing. Returns the limits array on success. */
  public static function getEffectiveLimits(PDO $pdo, int $userId): array {
    $st = $pdo->prepare('SELECT plan_key, expires_at, limits_json FROM subscriptions WHERE user_id=:u');
    $st->execute([':u'=>$userId]);
    $row = $st->fetch();
    if (!$row) {
      http_response_code(402);
      echo json_encode(['error'=>'No active subscription']);
      exit;
    }

    $expired = (new \DateTimeImmutable($row['expires_at'])) < new \DateTimeImmutable('now');
    if ($expired) {
      http_response_code(402);
      echo json_encode(['error'=>'Subscription expired']);
      exit;
    }

    // Defaults per plan (mirror the SubscriptionService)
    $defaults = match ($row['plan_key']) {
      'basic' => ['max_widgets'=>5,  'max_boards'=>1, 'retention_days'=>7,   'export_window_days'=>30],
      'pro'   => ['max_widgets'=>25, 'max_boards'=>5, 'retention_days'=>30,  'export_window_days'=>90],
      'ent'   => ['max_widgets'=>0,  'max_boards'=>0, 'retention_days'=>365, 'export_window_days'=>365],
      default => ['max_widgets'=>5,  'max_boards'=>1, 'retention_days'=>7,   'export_window_days'=>30],
    };

    $overrides = $row['limits_json'] ? json_decode($row['limits_json'], true) : [];
    if (!is_array($overrides)) $overrides = [];
    return array_merge($defaults, $overrides);
  }

  /** Enforce max boards per user. */
  public static function enforceBoardCap(PDO $pdo, int $userId): void {
    $limits = self::getEffectiveLimits($pdo, $userId);
    $maxBoards = (int)($limits['max_boards'] ?? 0); // 0 == unlimited
    if ($maxBoards === 0) return;

    $st = $pdo->prepare('SELECT count(*)::int AS c FROM boards WHERE user_id=:u');
    $st->execute([':u'=>$userId]);
    $cnt = (int)$st->fetch()['c'];
    if ($cnt >= $maxBoards) {
      http_response_code(403);
      echo json_encode(['error'=>'Board limit reached','limit'=>$maxBoards]);
      exit;
    }
  }

  /** Enforce max widgets on a board (looks up the board owner’s plan). */
  public static function enforceWidgetCap(PDO $pdo, int $boardId, int $userId): void {
    $limits = self::getEffectiveLimits($pdo, $userId);
    $maxWidgets = (int)($limits['max_widgets'] ?? 0); // 0 == unlimited
    if ($maxWidgets === 0) return;

    $st = $pdo->prepare('SELECT count(*)::int AS c FROM board_widgets WHERE board_id=:b');
    $st->execute([':b'=>$boardId]);
    $cnt = (int)$st->fetch()['c'];
    if ($cnt >= $maxWidgets) {
      http_response_code(403);
      echo json_encode(['error'=>'Widget limit reached for this board','limit'=>$maxWidgets]);
      exit;
    }
  }

  /**
   * Enforce export window: earliest allowed "from" timestamp based on plan.
   * Typically used before creating a DataExportJob.
   */
  public static function enforceExportWindow(PDO $pdo, int $userId, string $fromTs): void {
    $limits = self::getEffectiveLimits($pdo, $userId);
    $window = (int)($limits['export_window_days'] ?? 30);
    if ($window <= 0) return;

    $earliest = (new \DateTimeImmutable())->modify("-{$window} days");
    $from     = new \DateTimeImmutable($fromTs);

    if ($from < $earliest) {
      http_response_code(403);
      echo json_encode([
        'error' => 'Export window exceeded',
        'export_window_days' => $window,
        'earliest_allowed_from' => $earliest->format('c')
      ]);
      exit;
    }
  }
}
