<?php
namespace Iot\Models;

use PDO;

/**
 * User model
 * Policy notes (from spec):
 * - No self-registration; Admin/Technician creates users.
 * - End user cannot change username/email.
 * - Passwords must be hashed with bcrypt/argon2id.
 */
class User {
  public function __construct(private PDO $pdo) {}

  public function findById(int $id): ?array {
    $st = $this->pdo->prepare('SELECT id, email, role, display_name, avatar_url, created_at, updated_at FROM users WHERE id=:id');
    $st->execute([':id'=>$id]);
    $row = $st->fetch();
    return $row ?: null;
  }

  public function findByEmail(string $email): ?array {
    $st = $this->pdo->prepare('SELECT id, email, password_hash, role, display_name, avatar_url FROM users WHERE email=:e');
    $st->execute([':e'=>$email]);
    $row = $st->fetch();
    return $row ?: null;
  }

  public function list(int $limit = 200, int $offset = 0): array {
    $limit = max(1, min(1000, $limit));
    $st = $this->pdo->prepare('SELECT id, email, role, display_name, avatar_url FROM users ORDER BY id DESC LIMIT :lim OFFSET :off');
    $st->bindValue(':lim', $limit, PDO::PARAM_INT);
    $st->bindValue(':off', $offset, PDO::PARAM_INT);
    $st->execute();
    return $st->fetchAll();
  }

  public function create(string $email, string $passwordHash, string $role, ?string $displayName = null): int {
    $st = $this->pdo->prepare('INSERT INTO users(email, password_hash, role, display_name) VALUES (:e,:h,:r,:d) RETURNING id');
    $st->execute([':e'=>$email, ':h'=>$passwordHash, ':r'=>$role, ':d'=>$displayName]);
    return (int)$st->fetchColumn();
  }

  public function updateBasic(int $id, ?string $displayName, ?string $avatarUrl, ?string $role = null, ?string $passwordHash = null): bool {
    $fields = [];
    $params = [':id'=>$id];
    if ($displayName !== null) { $fields[] = 'display_name = :d'; $params[':d'] = $displayName; }
    if ($avatarUrl  !== null) { $fields[] = 'avatar_url = :a';   $params[':a'] = $avatarUrl; }
    if ($role       !== null) { $fields[] = 'role = :r';         $params[':r'] = $role; }
    if ($passwordHash!==null) { $fields[] = 'password_hash = :h';$params[':h'] = $passwordHash; }
    if (!$fields) return true;
    $sql = 'UPDATE users SET '.implode(', ', $fields).', updated_at=now() WHERE id=:id';
    return $this->pdo->prepare($sql)->execute($params);
  }

  public function delete(int $id): bool {
    return $this->pdo->prepare('DELETE FROM users WHERE id=:id')->execute([':id'=>$id]);
  }
}
