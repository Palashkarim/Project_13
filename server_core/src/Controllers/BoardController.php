<?php
namespace Iot\Controllers;

/**
 * Boards group widgets visually (Room 1, Production Line 1, etc.)
 * - index?user_id=... : list boards for user
 * - create: name, color, user_id, widgets[]
 * - clone: duplicate board & its widgets
 * - delete: remove board
 */
class BoardController extends BaseController {

  public function index(): array {
    $userId = (int)($this->req->query['user_id'] ?? 0);
    if ($userId <= 0) {
      http_response_code(422);
      return ['error' => 'user_id required'];
    }
    $pdo = $this->pdo();
    $stmt = $pdo->prepare('SELECT id, name, color, created_at FROM boards WHERE user_id = :u ORDER BY id DESC');
    $stmt->execute([':u' => $userId]);
    $boards = $stmt->fetchAll();
    return ['boards' => $boards];
  }

  public function create(): array {
    $b = $this->req->body;
    $userId = (int)($b['user_id'] ?? 0);
    $name = trim((string)($b['name'] ?? ''));
    $color = trim((string)($b['color'] ?? '#3b82f6'));
    $widgets = $b['widgets'] ?? []; // [{widget_key, config_json, sort_order}, ...]

    if ($userId <= 0 || $name === '') {
      http_response_code(422);
      return ['error' => 'user_id and name required'];
    }

    $pdo = $this->pdo();
    $pdo->beginTransaction();

    $ins = $pdo->prepare('INSERT INTO boards (user_id, name, color, created_by) VALUES (:u, :n, :c, :by) RETURNING id');
    $ins->execute([':u' => $userId, ':n' => $name, ':c' => $color, ':by' => $this->authUserIdOrFail()]);
    $boardId = (int)$ins->fetchColumn();

    if (is_array($widgets)) {
      $bw = $pdo->prepare('INSERT INTO board_widgets (board_id, widget_key, config_json, sort_order) VALUES (:b, :k, :cfg, :s)');
      foreach ($widgets as $w) {
        $bw->execute([
          ':b' => $boardId,
          ':k' => (string)$w['widget_key'],
          ':cfg' => json_encode($w['config_json'] ?? new \stdClass()),
          ':s' => (int)($w['sort_order'] ?? 0),
        ]);
      }
    }

    $pdo->commit();
    return ['status' => 'created', 'board_id' => $boardId];
  }

  public function cloneBoard(string $boardId): array {
    $srcId = (int)$boardId;
    $pdo = $this->pdo();
    $pdo->beginTransaction();

    $stmt = $pdo->prepare('SELECT user_id, name, color FROM boards WHERE id = :id');
    $stmt->execute([':id' => $srcId]);
    $b = $stmt->fetch();
    if (!$b) {
      $pdo->rollBack();
      http_response_code(404);
      return ['error' => 'Board not found'];
    }

    $newName = $b['name'].' (Copy)';
    $ins = $pdo->prepare('INSERT INTO boards (user_id, name, color, created_by) VALUES (:u, :n, :c, :by) RETURNING id');
    $ins->execute([':u' => $b['user_id'], ':n' => $newName, ':c' => $b['color'], ':by' => $this->authUserIdOrFail()]);
    $newId = (int)$ins->fetchColumn();

    $wstmt = $pdo->prepare('SELECT widget_key, config_json, sort_order FROM board_widgets WHERE board_id = :b');
    $wstmt->execute([':b' => $srcId]);
    $widgets = $wstmt->fetchAll();

    $bw = $pdo->prepare('INSERT INTO board_widgets (board_id, widget_key, config_json, sort_order) VALUES (:b, :k, :cfg, :s)');
    foreach ($widgets as $w) {
      $bw->execute([
        ':b' => $newId,
        ':k' => $w['widget_key'],
        ':cfg' => $w['config_json'],
        ':s' => $w['sort_order'],
      ]);
    }

    $pdo->commit();
    return ['status' => 'cloned', 'board_id' => $newId];
  }

  public function delete(string $boardId): array {
    $pdo = $this->pdo();
    $pdo->beginTransaction();
    $pdo->prepare('DELETE FROM board_widgets WHERE board_id = :b')->execute([':b' => (int)$boardId]);
    $pdo->prepare('DELETE FROM boards WHERE id = :b')->execute([':b' => (int)$boardId]);
    $pdo->commit();
    return ['status' => 'deleted'];
  }
}
