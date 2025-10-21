<?php
namespace Iot\Middleware;

use PDO;
use Iot\Support\Request;

/**
 * RateLimiter
 * Lightweight, DB-backed fixed-window limiter (works without Redis).
 *
 * Schema (add once):
 *   CREATE TABLE IF NOT EXISTS rate_limits(
 *     key TEXT PRIMARY KEY,
 *     counter INT NOT NULL,
 *     window_start TIMESTAMPTZ NOT NULL
 *   );
 *
 * Policy:
 * - Keyed by IP + route (default). You can override with your own $key.
 * - Window size in seconds, max allowed hits per window.
 *
 * Usage:
 *   RateLimiter::check($pdo, $req, 60, 120); // 120 requests per 60s window
 */
class RateLimiter {
  public static function check(PDO $pdo, Request $req, int $windowSec = 60, int $max = 120, ?string $key = null): void {
    $ip   = $req->server['REMOTE_ADDR'] ?? '0.0.0.0';
    $path = $req->path();
    $k    = $key ?: "ip:{$ip}|path:{$path}";

    $pdo->beginTransaction();
    $sel = $pdo->prepare('SELECT counter, window_start FROM rate_limits WHERE key=:k FOR UPDATE');
    $sel->execute([':k' => $k]);
    $row = $sel->fetch();
    $now = new \DateTimeImmutable();
    if ($row) {
      $start = new \DateTimeImmutable($row['window_start']);
      $elapsed = $now->getTimestamp() - $start->getTimestamp();
      if ($elapsed >= $windowSec) {
        // reset window
        $upd = $pdo->prepare('UPDATE rate_limits SET counter=1, window_start=:ws WHERE key=:k');
        $upd->execute([':ws'=>$now->format('c'), ':k'=>$k]);
      } else {
        if ((int)$row['counter'] >= $max) {
          $pdo->commit();
          http_response_code(429);
          header('Retry-After: '. max(1, $windowSec - $elapsed));
          echo json_encode(['error'=>'Too Many Requests']);
          exit;
        }
        $upd = $pdo->prepare('UPDATE rate_limits SET counter=counter+1 WHERE key=:k');
        $upd->execute([':k'=>$k]);
      }
    } else {
      $ins = $pdo->prepare('INSERT INTO rate_limits(key,counter,window_start) VALUES(:k,1,:ws)');
      $ins->execute([':k'=>$k, ':ws'=>$now->format('c')]);
    }
    $pdo->commit();
  }
}
