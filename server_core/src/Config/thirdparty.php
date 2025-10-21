<?php
namespace Iot\Config;

/**
 * Third-party service endpoints (AI engine, push notifications, etc.).
 */
return [
  'ai_engine' => [
    'url' => getenv('AI_ENGINE_URL') ?: 'http://ai_engine:8000',
    'timeout_sec' => 10
  ],
  'push' => [
    'vapid_public'  => getenv('PUSH_PUBLIC_KEY') ?: '',
    'vapid_private' => getenv('PUSH_PRIVATE_KEY') ?: '',
  ],
];
