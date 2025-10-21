<?php
namespace Iot\Config;

/**
 * Timescale/telemetry settings.
 */
return [
  // hypertable naming
  'telemetry_table' => 'telemetry',

  // rollup settings (if you enable TelemetryRollupService)
  'rollup' => [
    'enabled' => true,
    'hours'   => 24,   // roll up last 24h each run
  ],

  // retention (read via settings table at runtime; here just defaults)
  'retention_defaults' => [
    'basic_days' => 7,
    'pro_days'   => 30,
    'ent_days'   => 365,
  ],
];
