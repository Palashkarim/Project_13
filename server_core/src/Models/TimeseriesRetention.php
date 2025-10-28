<?php
namespace Iot\Models;

use PDO;

/**
 * TimeseriesRetention
 * - settings(key TEXT PK, value TEXT)
 * Keys example:
 *   retention.default_days
 *   retention.pro_days
 *   retention.ent_days
 *
 * Your cron job will enforce retention by dropping/compressing old chunks.
 */
class TimeseriesRetention {
  public function __construct(private PDO $pdo) {}

  public function getPolicy(): array {
    $st = $this->pdo->query("SELECT key, value FROM settings WHERE key LIKE 'retention.%'");
    $rows = $st->fetchAll();
    $out = [];
    foreach ($rows as $r) $out[$r['key']] = $r['value'];
    return $out;
  }

  public function setPolicy(array $kv): bool {
    $this->pdo->beginTransaction();
    $up = $this->pdo->prepare("INSERT INTO settings(key, value) VALUES (:k,:v)
                               ON CONFLICT (key) DO UPDATE SET value=EXCLUDED.value");
    foreach ($kv as $k=>$v) {
      if (!is_string($k)) continue;
      $up->execute([':k'=>$k, ':v'=>(string)$v]);
    }
    $this->pdo->commit();
    return true;
  }
}
