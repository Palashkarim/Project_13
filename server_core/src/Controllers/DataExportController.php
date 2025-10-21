<?php
namespace Iot\Controllers;

class DataExportController extends BaseController {
  public function createJob(): array {
    $b = $this->req->body;
    // Validate date range against subscription export_window_days
    // Insert job in data_export_jobs with status 'queued'
    return ['status'=>'queued','job_id'=>123];
  }
  public function status(string $jobId): array {
    // return 'queued' | 'running' | 'done' | 'error'
    return ['job_id'=>(int)$jobId,'status'=>'done'];
  }
  public function download(string $jobId): void {
    // Stream file if job done; otherwise 404/409
  }
}