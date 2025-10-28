<?php
namespace Iot\Services;

use Iot\Models\AclRule;
use PDO;

/**
 * AclService
 * Generates topic ACLs from RBAC/tenant namespace and persists in DB.
 * Mosquitto can read from this table via plugin or you can render to a file elsewhere.
 */
class AclService {
  private AclRule $acl;

  public function __construct(PDO $pdo) {
    $this->acl = new AclRule($pdo);
  }

  public function syncBasicTenant(int $userId): array {
    $this->acl->setBasicTenantRules($userId);
    return $this->acl->listByUser($userId);
  }

  public function preview(int $userId): array {
    // mirror generation logic without persisting
    $tenant = "ten/{$userId}/dev/+";
    return [
      ['access'=>'read',  'topic'=>"$tenant/state", 'rw'=>'r'],
      ['access'=>'read',  'topic'=>"$tenant/tele",  'rw'=>'r'],
      ['access'=>'read',  'topic'=>"$tenant/lwt",   'rw'=>'r'],
      ['access'=>'write', 'topic'=>"$tenant/cmd",   'rw'=>'w'],
    ];
  }
}
