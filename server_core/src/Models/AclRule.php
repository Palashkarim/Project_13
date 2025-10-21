<?php
namespace Iot\Models;

use PDO;

/**
 * AclRule model
 * - acl_rules(id PK, user_id, access('read'|'write'), topic_pattern, rw('r'|'w'|'rw'))
 * Typically generated from RBAC and tenant topic namespace.
 */
class AclRule {
  public function __construct(private PDO $pdo) {}

  public function setBasicTenantRules(int $userId): bool {
    $tenant = "ten/{$userId}/dev/+";
    $this->pdo->beginTransaction();
    $this->pdo->prepare('DELETE FROM acl_rules WHERE user_id=:u')->execute([':u'=>$userId]);
    $ins = $this->pdo->prepare('INSERT INTO acl_rules(user_id, access, topic_pattern, rw) VALUES (:u,:a,:t,:rw)');
    $ins->execute([':u'=>$userId, ':a'=>'read',  ':t'=>"$tenant/state", ':rw'=>'r']);
    $ins->execute([':u'=>$userId, ':a'=>'read',  ':t'=>"$tenant/tele",  ':rw'=>'r']);
    $ins->execute([':u'=>$userId, ':a'=>'read',  ':t'=>"$tenant/lwt",   ':rw'=>'r']);
    $ins->execute([':u'=>$userId, ':a'=>'write', ':t'=>"$tenant/cmd",   ':rw'=>'w']);
    $this->pdo->commit();
    return true;
  }

  public function listByUser(int $userId): array {
    $st = $this->pdo->prepare('SELECT id, access, topic_pattern, rw FROM acl_rules WHERE user_id=:u ORDER BY id ASC');
    $st->execute([':u'=>$userId]);
    return $st->fetchAll();
  }
}
