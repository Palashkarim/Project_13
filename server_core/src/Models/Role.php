<?php
namespace Iot\Models;

use PDO;

/**
 * Role model
 * - roles(role TEXT PK, scopes JSONB)
 * Scopes can be used to generate MQTT ACLs and UI permissions.
 */
class Role {
  public function __construct(private PDO $pdo) {}

  public function all(): array {
    return $this->pdo->query('SELECT role, scopes FROM roles ORDER BY role')->fetchAll();
  }

  public function upsert(string $role, array $scopes = []): bool {
    $st = $this->pdo->prepare("
      INSERT INTO roles(role, scopes) VALUES (:r, :s)
      ON CONFLICT (role) DO UPDATE SET scopes = EXCLUDED.scopes
    ");
    return $st->execute([':r'=>$role, ':s'=>json_encode($scopes)]);
  }
}
