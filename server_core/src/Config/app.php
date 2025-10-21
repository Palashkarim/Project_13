<?php
namespace Iot\Config;

/**
 * Application config (non-secret).
 * Secrets should come from environment (.env) not from this file.
 */
return [
  'env'        => getenv('APP_ENV') ?: 'production',
  'debug'      => filter_var(getenv('APP_DEBUG') ?: 'false', FILTER_VALIDATE_BOOL),
  'app_url'    => getenv('APP_URL') ?: 'https://localhost',
  'log_level'  => getenv('LOG_LEVEL') ?: 'info',
  'log_channel'=> getenv('LOG_CHANNEL') ?: 'stdout',

  // Optional built-in metrics server port (if you add one)
  'prometheus' => [
    'enabled' => filter_var(getenv('PROM_ENABLE') ?: 'false', FILTER_VALIDATE_BOOL),
    'port'    => (int)(getenv('PROM_PORT') ?: 9100),
  ],
];