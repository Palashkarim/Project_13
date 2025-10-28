<?php
namespace Iot\Services;

use PDO;

/**
 * OtaService
 * Tracks OTA firmware catalog and device desired versions.
 * The actual binary hosting is via Nginx or file server; DB stores metadata.
 */
class OtaService {
  public function __construct(private PDO $pdo) {}

  public function listFirmwares(int $limit=100): array {
    $st = $this->pdo->prepare('SELECT id, name, version, path, checksum, created_at FROM firmwares ORDER BY id DESC LIMIT :l');
    $st->bindValue(':l', max(1, min(1000, $limit)), PDO::PARAM_INT);
    $st->execute();
    return $st->fetchAll();
  }

  public function addFirmware(string $name, string $version, string $path, ?string $checksum=null): int {
    $st = $this->pdo->prepare('INSERT INTO firmwares(name, version, path, checksum) VALUES (:n,:v,:p,:c) RETURNING id');
    $st->execute([':n'=>$name, ':v'=>$version, ':p'=>$path, ':c'=>$checksum]);
    return (int)$st->fetchColumn();
  }

  public function setDeviceDesiredVersion(string $deviceId, string $version): bool {
    $st = $this->pdo->prepare('UPDATE devices SET desired_version=:v WHERE device_id=:d');
    return $st->execute([':v'=>$version, ':d'=>$deviceId]);
  }

  public function getDeviceDesiredVersion(string $deviceId): ?string {
    $st = $this->pdo->prepare('SELECT desired_version FROM devices WHERE device_id=:d');
    $st->execute([':d'=>$deviceId]);
    $row = $st->fetch();
    return $row['desired_version'] ?? null;
  }
}
