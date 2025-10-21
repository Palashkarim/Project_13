<?php
namespace Iot\Middleware;

use PDO;
use Iot\Support\Request;

/**
 * ApiLogger
 * Writes minimal audit trail for each request (success path).
 *
 * Schema (add once):
 *   CREATE TABLE IF NOT EXISTS audit_logs(
 *     id BIGSERIAL PRIMARY KEY,
 *     ts TIMESTAMPTZ NOT NULL DEFAULT now(),
 *     actor_user_id BIGINT,
 *     action TEXT,
 *     object_type TEXT,
 *     object_id TEXT,
 *     ip TEXT,
 *     meta JSONB
 *   );
 *
 * Usage:
 *   ApiLogger::log($pdo, $req, $uid ?? null, 'GET /api/devices', 'device', null, ['status'=>200]);
 */
class ApiLogger {
  public static function log(PDO $pdo, Request $req, ?int $actorUserId, string $action, ?string $objectType=null, ?string $objectId=null, array $meta=[]): void {
    $ip = $req->server['REMOTE_ADDR'] ?? '0.0.0.0';
    $ua = $req->headers['User-Agent'] ?? '';
    $meta = array_merge(['ua'=>$ua, 'method'=>$req->method(), 'path'=>$req->path()], $meta);

    $st = $pdo->prepare('INSERT INTO audit_logs(actor_user_id, action, object_type, object_id, ip, meta)
                         VALUES (:uid,:act,:otype,:oid,:ip,:m)');
    $st->execute([
      ':uid'=>$actorUserId, ':act'=>$action, ':otype'=>$objectType, ':oid'=>$objectId, ':ip'=>$ip, ':m'=>json_encode($meta)
    ]);
  }
}
