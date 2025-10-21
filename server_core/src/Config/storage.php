<?php
namespace Iot\Config;

/**
 * Storage & paths used by services.
 */
return [
  'root'              => getenv('STORAGE_PATH') ?: '/var/iot/storage',
  'firmware_builds'   => getenv('FIRMWARE_BUILDS_DIR') ?: '/var/iot/storage/firmware_builds',
  'export_jobs'       => getenv('EXPORT_JOBS_DIR') ?: '/var/iot/storage/export_jobs',
  'ota_signing_key'   => getenv('OTA_SIGNING_PRIVATE_KEY_PEM') ?: '/run/secrets/ota_signing_key',
  // Max upload size (enforced at Nginx/PHP too)
  'max_upload_bytes'  => 50 * 1024 * 1024
];