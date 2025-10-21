<?php
namespace Iot\Models;

use PDO;

/**
 * Firmware model
 * - firmwares(id PK, name, version, path, checksum, created_at)
 * Tracks available firmware files for OTA.
 */
class Firmware {
  public function __construct(private PDO $pdo) {}

  public function list(int $limit=100): array {
    $st = $this->pdo->prepare('SELECT id, name, version, path, checksum, created_at FROM firmwares ORDER BY id DESC LIMIT :l');
    $st->bindValue(':l', max(1, min(1000, $limit)), PDO::PARAM_INT);
    $st->execute();
    return $st->fetchAll();
  }

  public function add(string $name, string $version, string $path, ?string $checksum = null): int {
    $st = $this->pdo->prepare('INSERT INTO firmwares(name, version, path, checksum) VALUES (:n,:v,:p,:c) RETURNING id');
    $st->execute([':n'=>$name, ':v'=>$version, ':p'=>$path, ':c'=>$checksum]);
    return (int)$st->fetchColumn();
  }
}