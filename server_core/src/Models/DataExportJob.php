<?php
namespace Iot\Models;

use PDO;

/**
 * DataExportJob model
 * - data_export_jobs(id PK, user_id, format, from_ts, to_ts, status, file_path, created_at, updated_at)
 *
 * Lifecycle:
 *  create(queued) -> worker runs (running) -> file created (done) or error.
 */
class DataExportJob {
  public function __construct(private PDO $pdo) {}

  public function create(int $userId, string $format, string $fromTs, string $toTs): int {
    $st = $this->pdo->prepare("INSERT INTO data_export_jobs(user_id, format, from_ts, to_ts, status)
                               VALUES (:u,:f,:from,:to,'queued') RETURNING id");
    $st->execute([':u'=>$userId, ':f'=>$format, ':from'=>$fromTs, ':to'=>$toTs]);
    return (int)$st->fetchColumn();
  }

  public function get(int $jobId): ?array {
    $st = $this->pdo->prepare('SELECT * FROM data_export_jobs WHERE id=:id');
    $st->execute([':id'=>$jobId]);
    return $st->fetch() ?: null;
  }

  public function markRunning(int $jobId): bool {
    return $this->pdo->prepare("UPDATE data_export_jobs SET status='running', updated_at=now() WHERE id=:id")
      ->execute([':id'=>$jobId]);
  }

  public function markDone(int $jobId, string $filePath): bool {
    $st = $this->pdo->prepare("UPDATE data_export_jobs SET status='done', file_path=:p, updated_at=now() WHERE id=:id");
    return $st->execute([':p'=>$filePath, ':id'=>$jobId]);
  }

  public function markError(int $jobId): bool {
    return $this->pdo->prepare("UPDATE data_export_jobs SET status='error', updated_at=now() WHERE id=:id")
      ->execute([':id'=>$jobId]);
  }

  /** Worker helper: Atomically fetch next queued job and lock it */
  public function fetchNextQueuedForUpdate(): ?array {
    $this->pdo->beginTransaction();
    $st = $this->pdo->query("SELECT * FROM data_export_jobs WHERE status='queued' ORDER BY id ASC LIMIT 1 FOR UPDATE SKIP LOCKED");
    $row = $st->fetch();
    if (!$row) { $this->pdo->commit(); return null; }
    // caller should markRunning() and then commit/continue
    return $row;
  }

  public function commit(): void { $this->pdo->commit(); }
  public function rollBack(): void { $this->pdo->rollBack(); }
}
