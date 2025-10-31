<?php
namespace Iot\Controllers;

use Iot\Support\Request;

/**
 * Auth:
 * - login: verify credentials (stub), return JWT
 * - logout: client-side token discard; optionally add denylist table
 * - profile: returns current user basic info
 */
class AuthController extends BaseController {

  public function login(): array {
    $email = trim((string)($this->req->body['email'] ?? ''));
    $password = (string)($this->req->body['password'] ?? '');

    if ($email === '' || $password === '') {
      http_response_code(422);
      return ['error' => 'Email and password required'];
    }

    // TODO: Replace with real query & password verify (argon2id)
    $pdo = $this->pdo();
    $stmt = $pdo->prepare('SELECT id, email, password_hash, role FROM users WHERE email = :e LIMIT 1');
    $stmt->execute([':e' => $email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
      http_response_code(401);
      return ['error' => 'Invalid credentials'];
    }

    $token = $this->jwtSign(['uid' => (int)$user['id'], 'role' => $user['role']], 3600 * 8);

    return [
      'token' => $token,
      'user' => [
        'id' => (int)$user['id'],
        'email' => $user['email'],
        'role' => $user['role'],
      ]
    ];
  }

  public function logout(): array {
    // Stateless JWT: client just removes token.
    // If you need server-side revoke: insert token jti into a denylist table with exp.
    return ['status' => 'ok'];
  }

  public function profile(): array {
    $uid = $this->authUserIdOrFail();
    $pdo = $this->pdo();
    $stmt = $pdo->prepare('SELECT id, email, role, display_name, avatar_url FROM users WHERE id = :id');
    $stmt->execute([':id' => $uid]);
    $u = $stmt->fetch() ?: [];
    return ['user' => $u];
  }
}