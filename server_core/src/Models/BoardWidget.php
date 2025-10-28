<?php
namespace Iot\Models;

use PDO;

/**
 * BoardWidget model
 * - board_widgets(id PK, board_id, widget_key, config_json, sort_order)
 */
class BoardWidget {
  public function __construct(private PDO $pdo) {}

  public function listByBoard(int $boardId): array {
    $st = $this->pdo->prepare('SELECT id, widget_key, config_json, sort_order FROM board_widgets WHERE board_id=:b ORDER BY sort_order ASC, id ASC');
    $st->execute([':b'=>$boardId]);
    return $st->fetchAll();
  }

  public function add(int $boardId, string $widgetKey, array $config = [], int $sortOrder = 0): int {
    $st = $this->pdo->prepare('INSERT INTO board_widgets(board_id, widget_key, config_json, sort_order) VALUES (:b,:k,:c,:s) RETURNING id');
    $st->execute([':b'=>$boardId, ':k'=>$widgetKey, ':c'=>json_encode($config), ':s'=>$sortOrder]);
    return (int)$st->fetchColumn();
  }

  public function cloneFromBoard(int $srcBoardId, int $destBoardId): int {
    $widgets = $this->listByBoard($srcBoardId);
    $ins = $this->pdo->prepare('INSERT INTO board_widgets(board_id, widget_key, config_json, sort_order) VALUES (:b,:k,:c,:s)');
    $count = 0;
    foreach ($widgets as $w) {
      $ins->execute([':b'=>$destBoardId, ':k'=>$w['widget_key'], ':c'=>$w['config_json'], ':s'=>$w['sort_order']]);
      $count++;
    }
    return $count;
  }

  public function deleteByBoard(int $boardId): bool {
    return $this->pdo->prepare('DELETE FROM board_widgets WHERE board_id=:b')->execute([':b'=>$boardId]);
  }
}
