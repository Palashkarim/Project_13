<?php
namespace Iot\Config;

/**
 * Outbound mail settings (DEV SMTP image or your MTA).
 */
return [
  'driver'   => getenv('MAIL_DRIVER') ?: 'smtp',
  'host'     => getenv('MAIL_HOST') ?: 'mail',
  'port'     => (int)(getenv('MAIL_PORT') ?: 25),
  'username' => getenv('MAIL_USERNAME') ?: null,
  'password' => getenv('MAIL_PASSWORD') ?: null,
  'from'     => [
    'address' => getenv('MAIL_FROM') ?: 'notifications@localhost',
    'name'    => getenv('MAIL_FROM_NAME') ?: 'IoT Platform',
  ],
];
