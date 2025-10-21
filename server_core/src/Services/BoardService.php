<?php
namespace Iot\Services;

use Iot\Models\Board;
use Iot\Models\BoardWidget;
use PDO;

/**
 * BoardService
 * Orchestrates board CRUD + widget cloning using models.
 */
class BoardService {
  private Board $boards;
  private BoardWidget $widgets;

  public function __construct(PDO $pdo) {
    $this->boards  = new Board($pdo);
    $this->widgets = new BoardWidget($pdo);
  }

  public function createBoard(int $userId, string $name, string $color, int $createdBy, array $initialWidgets = []): int {
    $bid = $this->boards->create($userId, $name, $color, $createdBy);
    foreach ($initialWidgets as $w) {
      $this->widgets->add($bid, (string)$w['widget_key'], (array)($w['config_json'] ?? []), (int)($w['sort_order'] ?? 0));
    }
    return $bid;
  }

  public function cloneBoard(int $boardId, int $createdBy): ?int {
    $new = $this->boards->clone($boardId, $createdBy);
    if ($new === null) return null;
    $this->widgets->cloneFromBoard($boardId, $new);
    return $new;
  }

  public function deleteBoard(int $boardId): bool {
    $this->widgets->deleteByBoard($boardId);
    return $this->boards->delete($boardId);
  }

  public function listUserBoards(int $userId): array {
    return $this->boards->listByUser($userId);
  }
}