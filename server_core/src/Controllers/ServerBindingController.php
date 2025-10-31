<?php
namespace Iot\Controllers;

class ServerBindingController extends BaseController {
  public function assign(string $userId): array {
    $mqtt = (string)($this->req->body['mqtt_server_id'] ?? '');
    $db   = (string)($this->req->body['db_server_id'] ?? '');
    if ($mqtt === '' || $db === '') { http_response_code(422); return ['error'=>'mqtt_server_id & db_server_id required']; }
    // TODO: upsert into server_bindings (user_id, mqtt_server_id, db_server_id)
    return ['status'=>'assigned'];
  }
}
