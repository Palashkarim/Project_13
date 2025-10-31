<?php
namespace Iot\Controllers;

/**
 * UserController
 * - index(): list users (admin/super_admin only recommended)
 * - create(): create user with role; password set by admin/technician
 * - show(), update(), delete()
 *
 * IMPORTANT POLICY (from your specs):
 * - End users cannot change username or self-register.
 * - Admin/Technician creates users and sends credentials.
 */
class UserController extends BaseController {

  public function index(): array {
    $this->authUserIdOrFail(); // add role check here if needed
    $pdo = $this->pdo();
    $rows = $pdo->query('SELECT id, email, role, display_name, avatar_url
                         FROM users ORDER BY id DESC LIMIT 200')->fetchAll();
    return ['users'=>$rows];
  }

  public function create(): array {
    $me = $this->authUserIdOrFail();
    $b = $this->req->body;

    $email = trim((string)($b['email'] ?? ''));
    $password = (string)($b['password'] ?? '');
    $role = (string)($b['role'] ?? 'super_user');
    $display = trim((string)($b['display_name'] ?? ''));

    if ($email==='' || $password==='' || $role==='') {
      http_response_code(422);
      return ['error'=>'email, password, role required'];
    }

    // Hash password (argon2id recommended)
    $hash = password_hash($password, PASSWORD_BCRYPT); // swap to ARGON2ID if ext available

    $pdo = $this->pdo();
    $stmt = $pdo->prepare('INSERT INTO users (email,password_hash,role,display_name)
                           VALUES (:e,:h,:r,:d) RETURNING id');
    $stmt->execute([':e'=>$email, ':h'=>$hash, ':r'=>$role, ':d'=>$display]);
    $id = (int)$stmt->fetchColumn();

    return ['status'=>'created','user_id'=>$id];
  }

  public function show(string $id): array {
    $this->authUserIdOrFail();
    $stmt = $this->pdo()->prepare('SELECT id, email, role, display_name, avatar_url
                                   FROM users WHERE id=:id');
    $stmt->execute([':id'=>(int)$id]);
    $u = $stmt->fetch();
    if (!$u) { http_response_code(404); return ['error'=>'not found']; }
    return ['user'=>$u];
  }

  public function update(string $id): array {
    $this->authUserIdOrFail();
    $b = $this->req->body;

    // Policy: end user cannot change username/email; admins can.
    $display = isset($b['display_name']) ? trim((string)$b['display_name']) : null;
    $avatar  = isset($b['avatar_url']) ? trim((string)$b['avatar_url']) : null;
    $role    = isset($b['role']) ? (string)$b['role'] : null;
    $pwd     = isset($b['password']) ? (string)$b['password'] : null;

    $fields = [];
    $params = [':id'=>(int)$id];
    if ($display !== null) { $fields[] = 'display_name = :d'; $params[':d']=$display; }
    if ($avatar  !== null) { $fields[] = 'avatar_url = :a';   $params[':a']=$avatar; }
    if ($role    !== null) { $fields[] = 'role = :r';         $params[':r']=$role; }
    if ($pwd     !== null) { $fields[] = 'password_hash = :h';$params[':h']=password_hash($pwd, PASSWORD_BCRYPT); }

    if (!$fields) return ['status'=>'noop'];
    $sql = 'UPDATE users SET '.implode(', ', $fields).', updated_at=now() WHERE id=:id';
    $this->pdo()->prepare($sql)->execute($params);
    return ['status'=>'updated'];
  }

  public function delete(string $id): array {
    $this->authUserIdOrFail();
    $this->pdo()->prepare('DELETE FROM users WHERE id=:id')->execute([':id'=>(int)$id]);
    return ['status'=>'deleted'];
  }
}
