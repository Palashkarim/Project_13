<?php
namespace Iot\Models;

use PDO;

/**
 * Telemetry model (Timescale hypertable)
 * - telemetry(ts, user_id, device_id, metric, value, extra JSONB)
 */
class Telemetry {
  public function __construct(private PDO $pdo) {}

  public function insertRow(string $ts, int $userId, string $deviceId, string $metric, float $value, array $extra = []): bool {
    $st = $this->pdo->prepare('INSERT INTO telemetry(ts, user_id, device_id, metric, value, extra)
                               VALUES (:ts, :u, :d, :m, :v, :x)');
    return $st->execute([':ts'=>$ts, ':u'=>$userId, ':d'=>$deviceId, ':m'=>$metric, ':v'=>$value, ':x'=>json_encode($extra)]);
  }

  public function seriesAggregateEnergy(int $userId, string $since = '24 hours'): array {
    $st = $this->pdo->prepare("
      SELECT date_trunc('hour', ts) AS h, sum(value) AS kwh
      FROM telemetry
      WHERE user_id=:u AND metric='energy_kwh' AND ts > now() - interval '$since'
      GROUP BY 1 ORDER BY 1
    ");
    $st->execute([':u'=>$userId]);
    return $st->fetchAll();
  }

  public function faultsByDevice(int $userId): array {
    $st = $this->pdo->prepare("
      SELECT device_id, count(*) AS faults
      FROM telemetry
      WHERE user_id=:u AND metric='fault'
      GROUP BY device_id
      ORDER BY faults DESC
      LIMIT 50
    ");
    $st->execute([':u'=>$userId]);
    return $st->fetchAll();
  }
}
