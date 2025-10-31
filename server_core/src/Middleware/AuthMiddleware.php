<?php
namespace Iot\Middleware;

use Iot\Services\JwtService;
use Iot\Support\Request;

/**
 * AuthMiddleware
 * - Verifies Bearer JWT from the Authorization header.
 * - Returns claims array on success, or sends 401 and exits on failure (if $required = true).
 *
 * Usage:
 *   $claims = AuthMiddleware::enforce($req, new JwtService(getenv('JWT_SECRET')), true);
 *   $uid    = (int)$claims['uid'];
 */
class AuthMiddleware {
  public static function enforce(Request $req, JwtService $jwt, bool $required = true): ?array {
    $token  = $req->bearerToken();
    $claims = $jwt->verify($token);
    if (!$claims && $required) {
      http_response_code(401);
      echo json_encode(['error' => 'Unauthorized']);
      exit;
    }
    return $claims ?: null;
  }
}
