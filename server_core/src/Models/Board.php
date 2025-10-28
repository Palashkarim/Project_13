<?php
namespace Iot\Models;

use PDO;

/**
 * Board model
 * - boards(id PK, user_id, name, color, created_by, created_at)
 */
class Board {
  public function __construct(private PDO $pdo) {}

  public function listByUser(int $userId): array {
    $st = $this->pdo->prepare('SELECT id, name, color, created_at FROM boards WHERE user_id=:u ORDER BY id DESC');
    $st->execute([':u'=>$userId]);
    return $st->fetchAll();
  }

  public function create(int $userId, string $name, string $color, int $createdBy): int {
    $st = $this->pdo->prepare('INSERT INTO boards(user_id, name, color, created_by) VALUES (:u,:n,:c,:by) RETURNING id');
    $st->execute([':u'=>$userId, ':n'=>$name, ':c'=>$color, ':by'=>$createdBy]);
    return (int)$st->fetchColumn();
  }

  public function clone(int $boardId, int $createdBy): ?int {
    // Caller should copy widgets using BoardWidget model
    $st = $this->pdo->prepare('SELECT user_id, name, color FROM boards WHERE id=:b');
    $st->execute([':b'=>$boardId]);
    $row = $st->fetch();
    if (!$row) return null;
    return $this->create((int)$row['user_id'], $row['name'].' (Copy)', $row['color'], $createdBy);
  }

  public function delete(int $boardId): bool {
    // Ensure board_widgets are deleted first or rely on ON DELETE CASCADE
    return $this->pdo->prepare('DELETE FROM boards WHERE id=:b')->execute([':b'=>$boardId]);
  }
}
