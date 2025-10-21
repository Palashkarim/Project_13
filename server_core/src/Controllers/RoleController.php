<?php
namespace Iot\Controllers;

class RoleController extends BaseController {
  public function index(): array {
    // TODO: fetch roles from roles table
    return ['roles' => ['super_admin','admin','technician','sales','super_user','sub_user']];
  }
  public function upsert(): array {
    // TODO: create/update roles & scopes; map to ACL generation
    return ['status'=>'ok'];
  }
}
