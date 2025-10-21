<?php
namespace Iot\Services;

/**
 * JwtService (HS256)
 * Minimal JWT utility for signing and verifying tokens.
 * Keep the secret long (>= 32 bytes).
 */
class JwtService {
  public function __construct(private string $secret) {}

  public function sign(array $claims, ?int $ttlSec = 28800): string {
    $header = ['alg'=>'HS256','typ'=>'JWT'];
    $now = time();
    $claims['iat'] = $claims['iat'] ?? $now;
    $claims['exp'] = $claims['exp'] ?? ($now + ($ttlSec ?? 3600));

    $h = rtrim(strtr(base64_encode(json_encode($header)), '+/', '-_'), '=');
    $p = rtrim(strtr(base64_encode(json_encode($claims)), '+/', '-_'), '=');
    $sig = hash_hmac('sha256', "$h.$p", $this->secret, true);
    $s = rtrim(strtr(base64_encode($sig), '+/', '-_'), '=');

    return "$h.$p.$s";
  }

  public function verify(?string $token): ?array {
    if (!$token) return null;
    $parts = explode('.', $token);
    if (count($parts) !== 3) return null;
    [$h, $p, $s] = $parts;
    $sig = hash_hmac('sha256', "$h.$p", $this->secret, true);
    $calc = rtrim(strtr(base64_encode($sig), '+/', '-_'), '=');
    if (!hash_equals($calc, $s)) return null;

    $claims = json_decode(base64_decode(strtr($p,'-_','+/')), true);
    if (!is_array($claims)) return null;
    if (isset($claims['exp']) && time() > (int)$claims['exp']) return null;
    return $claims;
  }
}
