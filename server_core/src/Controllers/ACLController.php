<?php
namespace Iot\Controllers;

class ACLController extends BaseController {
  public function preview(string $userId): array {
    // TODO: derive ACL from RBAC + widget/board topics
    return ['acl'=>[
      ['access'=>'read','topic'=>"ten/{$userId}/dev/+ /state"],
      ['access'=>'write','topic'=>"ten/{$userId}/dev/+ /cmd"],
    ]];
  }
  public function sync(string $userId): array {
    // TODO: write rendered ACL into mosquitto acl.conf (or DB plugin) & signal reload
    return ['status'=>'synced'];
  }
}
