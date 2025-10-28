<?php
namespace Iot\Config;

/**
 * MQTT broker config (Mosquitto).
 */
return [
  'host'        => getenv('MQTT_HOST') ?: 'mqtt',
  'port'        => (int)(getenv('MQTT_PORT') ?: 1883),
  'tls_enabled' => filter_var(getenv('MQTT_TLS_ENABLED') ?: 'false', FILTER_VALIDATE_BOOL),
  'username'    => getenv('MQTT_USER') ?: null,
  'password'    => getenv('MQTT_PASSWORD') ?: null,
  // Topic namespace helper for tenants:
  'tenant_prefix' => 'ten', // results in ten/{userId}/dev/{deviceId}/...
];
