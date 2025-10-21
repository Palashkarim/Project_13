<?php
namespace Iot\Services;

/**
 * CompressionService
 * Utilities for compressing telemetry payloads/files.
 * - gzip/ungzip strings
 * - (optional) CBOR / Protobuf helpers can be added as needed
 */
class CompressionService {
  public function gzipString(string $data, int $level = 6): string {
    $gz = gzencode($data, $level);
    if ($gz === false) throw new \RuntimeException('gzip failed');
    return $gz;
  }

  public function gunzipString(string $data): string {
    $out = gzdecode($data);
    if ($out === false) throw new \RuntimeException('gunzip failed');
    return $out;
  }

  // Stubs for CBOR/Protobuf. Add libs if you adopt those formats.
  public function encodeCbor(array $payload): string {
    // TODO: integrate a CBOR library if required
    return json_encode($payload);
  }

  public function decodeCbor(string $bytes): array {
    // TODO: integrate a CBOR library if required
    $j = json_decode($bytes, true);
    return is_array($j) ? $j : [];
  }
}