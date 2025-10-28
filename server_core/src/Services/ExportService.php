<?php
namespace Iot\Services;

use Iot\Models\DataExportJob;
use PDO;

/**
 * ExportService
 * Creates export jobs and (optionally) performs synchronous generation.
 * In our architecture, a separate worker consumes queued jobs and writes files.
 */
class ExportService {
  private DataExportJob $jobs;

  public function __construct(private PDO $pdo, private string $exportDir = '/var/iot/storage/export_jobs') {
    $this->jobs = new DataExportJob($pdo);
    @mkdir($this->exportDir, 0750, true);
  }

  public function enqueue(int $userId, string $format, string $fromTs, string $toTs): int {
    return $this->jobs->create($userId, $format, $fromTs, $toTs);
  }

  /**
   * Synchronous export (use for small windows/testing).
   * Returns full path to the generated file or null.
   */
  public function generateNow(int $userId, string $format, string $fromTs, string $toTs): ?string {
    // Pull data
    $q = $this->pdo->prepare("SELECT ts, device_id, metric, value, extra FROM telemetry
                              WHERE user_id=:u AND ts BETWEEN :f AND :t ORDER BY ts ASC");
    $q->execute([':u'=>$userId, ':f'=>$fromTs, ':t'=>$toTs]);

    $file = $this->exportDir . '/manual_' . date('Ymd_His');
    if ($format === 'csv') {
      $file .= '.csv';
      $fp = fopen($file, 'w');
      fputcsv($fp, ['ts','device_id','metric','value','extra_json']);
      while ($row = $q->fetch()) {
        fputcsv($fp, [$row['ts'],$row['device_id'],$row['metric'],$row['value'], json_encode($row['extra'] ?? null)]);
      }
      fclose($fp);
      return $file;
    } elseif ($format === 'json') {
      $file .= '.json';
      $rows = [];
      while ($row = $q->fetch()) $rows[] = $row;
      file_put_contents($file, json_encode($rows));
      return $file;
    } else {
      // XLSX omitted to keep deps minimal; integrate PhpSpreadsheet if needed.
      return null;
    }
  }
}
