<?php
namespace Iot\Config;

/**
 * Database config (PostgreSQL/Timescale).
 */
return [
  'driver'   => 'pgsql',
  'host'     => getenv('DB_HOST') ?: 'db',
  'port'     => (int)(getenv('DB_PORT') ?: 5432),
  'name'     => getenv('DB_NAME') ?: 'iot_core',
  'user'     => getenv('DB_USER') ?: 'iot_user',
  'password' => getenv('DB_PASSWORD') ?: 'secret',
  'sslmode'  => getenv('DB_SSLMODE') ?: 'disable',
];