<?php
namespace Iot\Services;

/**
 * BackupService
 * Simple wrappers to dump/restore DB and archive storage.
 * In production, prefer dedicated backup jobs outside the app container.
 */
class BackupService {
  public function __construct(
    private string $pgHost,
    private string $pgPort,
    private string $pgDb,
    private string $pgUser,
    private string $pgPass,
    private string $backupDir = '/var/iot/storage/backups'
  ) {}

  public function dumpDatabase(): ?string {
    @mkdir($this->backupDir, 0750, true);
    $file = $this->backupDir.'/db_'.date('Ymd_His').'.sql.gz';

    $env = sprintf('PGPASSWORD=%s', escapeshellarg($this->pgPass));
    $cmd = sprintf(
      '%s pg_dump -h %s -p %d -U %s %s | gzip -9 > %s',
      $env,
      escapeshellarg($this->pgHost),
      (int)$this->pgPort,
      escapeshellarg($this->pgUser),
      escapeshellarg($this->pgDb),
      escapeshellarg($file)
    );
    @exec($cmd, $out, $code);
    return $code === 0 ? $file : null;
  }

  public function archiveFolder(string $sourcePath, ?string $name=null): ?string {
    if (!is_dir($sourcePath)) return null;
    @mkdir($this->backupDir, 0750, true);
    $name = $name ?: 'storage_'.date('Ymd_His').'.tar.gz';
    $dest = $this->backupDir.'/'.$name;
    $cmd = sprintf('tar -czf %s -C %s .', escapeshellarg($dest), escapeshellarg(rtrim($sourcePath,'/')));
    @exec($cmd, $out, $code);
    return $code === 0 ? $dest : null;
  }
}
