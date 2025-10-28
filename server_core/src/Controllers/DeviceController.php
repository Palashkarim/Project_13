<?php
namespace Iot\Controllers;

class DeviceController extends BaseController {
  public function index(): array {
    // TODO: list devices (filter by user_id)
    return ['devices'=>[]];
  }
  public function register(): array {
    // TODO: insert device with topics & bindings
    return ['status'=>'registered'];
  }
  public function show(string $id): array {
    // TODO: device details & last seen
    return ['device'=>['id'=>$id]];
  }
  public function delete(string $id): array {
    // TODO: soft delete & revoke credentials
    return ['status'=>'deleted'];
  }
}
