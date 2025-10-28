<?php
namespace Iot\Services;

use PDO;

/**
 * TelemetryRollupService
 * Periodic aggregation (hourly/daily rollups) to speed up analytics queries.
 * You can store rollups in dedicated tables (e.g., telemetry_hourly, telemetry_daily).
 */
class TelemetryRollupService {
  public function __construct(private PDO $pdo) {}

  /**
   * Example: roll up last N hours by metric into telemetry_hourly(user_id, h, metric, sum_value)
   * Create the table if you decide to use it:
   *   CREATE TABLE telemetry_hourly(user_id BIGINT, h TIMESTAMPTZ, metric TEXT, sum_value DOUBLE PRECISION, PRIMARY KEY(user_id, h, metric));
   */
  public function rollupLastHours(int $hours = 24): int {
    $hours = max(1, min(168, $hours));
    $sql = "
      INSERT INTO telemetry_hourly(user_id, h, metric, sum_value)
      SELECT user_id, date_trunc('hour', ts) AS h, metric, sum(value)
      FROM telemetry
      WHERE ts > now() - interval '{$hours} hours'
      GROUP BY user_id, date_trunc('hour', ts), metric
      ON CONFLICT (user_id, h, metric) DO UPDATE SET sum_value = EXCLUDED.sum_value
    ";
    return $this->pdo->exec($sql) ?: 0;
  }
}
