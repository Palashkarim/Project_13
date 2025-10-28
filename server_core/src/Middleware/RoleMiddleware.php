
<?php
namespace Iot\Middleware;

/**
 * RoleMiddleware
 * - Checks that the authenticated user role is in an allowed set.
 *
 * Usage:
 *   RoleMiddleware::enforce($claims, ['super_admin','admin','technician']);
 */
class RoleMiddleware {
  public static function enforce(?array $claims, array $allowedRoles): void {
    $role = $claims['role'] ?? null;
    if (!$role || !in_array($role, $allowedRoles, true)) {
      http_response_code(403);
      echo json_encode(['error' => 'Forbidden: insufficient role']);
      exit;
    }
  }
}
