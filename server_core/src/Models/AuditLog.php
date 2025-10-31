<?php
namespace Iot\Models;

use PDO;

/**
 * AuditLog model
 * - audit_logs(id PK, ts, actor_user_id, action, object_type, object_id, ip, meta JSONB)
 */
class AuditLog {
  public function __construct(private PDO $pdo) {}

  public function record(int $actorUserId, string $action, string $objectType, ?string $objectId, string $ip, array $meta = []): bool {
    $st = $this->pdo->prepare('INSERT INTO audit_logs(ts, actor_user_id, action, object_type, object_id, ip, meta)
                               VALUES (now(), :uid, :act, :otype, :oid, :ip, :m)');
    return $st->execute([
      ':uid'=>$actorUserId, ':act'=>$action, ':otype'=>$objectType, ':oid'=>$objectId, ':ip'=>$ip, ':m'=>json_encode($meta)
    ]);
  }

  public function tail(int $limit=200, ?int $userId=null, ?string $action=null): array {
    $sql = 'SELECT id, ts, actor_user_id, action, object_type, object_id, ip, meta FROM audit_logs WHERE 1=1';
    $params = [];
    if ($userId) { $sql .= ' AND actor_user_id=:u'; $params[':u'] = $userId; }
    if ($action) { $sql .= ' AND action=:a';        $params[':a'] = $action; }
    $sql .= ' ORDER BY ts DESC LIMIT :l';
    $st = $this->pdo->prepare($sql);
    foreach ($params as $k=>$v) $st->bindValue($k, $v);
    $st->bindValue(':l', max(1, min(2000, $limit)), PDO::PARAM_INT);
    $st->execute();
    return $st->fetchAll();
  }
}
