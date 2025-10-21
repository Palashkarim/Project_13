<?php
namespace Iot\Services;

use PDO;

/**
 * TimeseriesService
 * Low-level helpers around the telemetry table (Timescale hypertable).
 */
class TimeseriesService {
  public function __construct(private PDO $pdo) {}

  public function insertTelemetry(string $ts, int $userId, string $deviceId, string $metric, float $value, array $extra = []): bool {
    $st = $this->pdo->prepare('INSERT INTO telemetry(ts, user_id, device_id, metric, value, extra)
                               VALUES (:ts, :u, :d, :m, :v, :x)');
    return $st->execute([':ts'=>$ts, ':u'=>$userId, ':d'=>$deviceId, ':m'=>$metric, ':v'=>$value, ':x'=>json_encode($extra)]);
  }

  public function queryWindow(int $userId, string $fromTs, string $toTs, array $metrics = []): array {
    $sql = "SELECT ts, device_id, metric, value, extra FROM telemetry
            WHERE user_id=:u AND ts BETWEEN :f AND :t";
    $params = [':u'=>$userId, ':f'=>$fromTs, ':t'=>$toTs];
    if ($metrics) {
      $in = implode(',', array_fill(0, count($metrics), '?'));
      $sql .= " AND metric IN ($in)";
    }
    $sql .= " ORDER BY ts ASC";
    $st = $this->pdo->prepare($sql);
    $idx = 1;
    $st->bindValue(':u', $userId, PDO::PARAM_INT);
    $st->bindValue(':f', $fromTs);
    $st->bindValue(':t', $toTs);
    foreach ($metrics as $m) $st->bindValue($idx++, $m);
    $st->execute();
    return $st->fetchAll();
  }
}