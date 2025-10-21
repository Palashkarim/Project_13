<?php
namespace Iot\Controllers;

class UserController extends BaseController {
  public function index(): array {
    // TODO: pagination, filters
    $rows = $this->pdo()->query('SELECT id, email, role, display_name FROM users ORDER BY id DESC LIMIT 100')->fetchAll();
    return ['users' => $rows];
  }
  public function create(): array {
    $b = $this->req->body;
    // TODO: validate, hash password (password_hash($pwd, PASSWORD_ARGON2ID))
    // TODO: insert into users
    return ['status' => 'created'];
  }
  public function show(string $id): array {
    $stmt = $this->pdo()->prepare('SELECT id, email, role, display_name FROM users WHERE id = :id');
    $stmt->execute([':id' => (int)$id]);
    $u = $stmt->fetch();
    if (!$u) { http_response_code(404); return ['error'=>'not found']; }
    return ['user'=>$u];
  }
  public function update(string $id): array {
    // TODO: update allowed fields; no username/email change for end-users per your policy
    return ['status'=>'updated'];
  }
  public function delete(string $id): array {
    // TODO: cascade deletes carefully (devices, bindings, etc.)
    return ['status'=>'deleted'];
  }
}