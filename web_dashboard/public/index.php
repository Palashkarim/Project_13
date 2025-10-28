<?php
declare(strict_types=1);

use Iot\Support\Request;
use Iot\Support\Response;
use Iot\Controllers\AuthController;
use Iot\Controllers\SubscriptionController;
use Iot\Controllers\BoardController;
use Iot\Controllers\WidgetController;
use Iot\Controllers\TechnicianController;
use Iot\Controllers\UserController;
use Iot\Controllers\RoleController;
use Iot\Controllers\DeviceController;
use Iot\Controllers\MqttController;
use Iot\Controllers\OtaController;
use Iot\Controllers\AnalyticsController;
use Iot\Controllers\NotificationController;
use Iot\Controllers\BillingController;
use Iot\Controllers\RetentionController;
use Iot\Controllers\SimulatorController;
use Iot\Controllers\SecurityController;
use Iot\Controllers\ServerBindingController;
use Iot\Controllers\ACLController;
use Iot\Controllers\DataExportController;

require __DIR__ . '/../vendor/autoload.php';

/**
 * Minimal, dependency-free bootstrap:
 * - Parses env vars
 * - Simple router based on METHOD + PATH_INFO
 * - Instantiates the right controller and action
 */

header('Content-Type: application/json; charset=utf-8');

$req = Request::fromGlobals();

// Very small, explicit route table. Add routes as needed.
$routes = [
  // Auth
  ['POST', '/api/login', [AuthController::class, 'login']],
  ['POST', '/api/logout', [AuthController::class, 'logout']],
  ['GET',  '/api/profile', [AuthController::class, 'profile']],

  // Subscriptions
  ['GET',  '/api/subscriptions', [SubscriptionController::class, 'listPlans']],
  ['GET',  '/api/subscriptions/{userId}', [SubscriptionController::class, 'getUserPlan']],
  ['POST', '/api/subscriptions/{userId}/assign', [SubscriptionController::class, 'assignPlan']],
  ['PUT',  '/api/subscriptions/{userId}/renew', [SubscriptionController::class, 'renewPlan']],
  ['GET',  '/api/subscriptions/{userId}/status', [SubscriptionController::class, 'status']],

  // Boards
  ['GET',  '/api/boards', [BoardController::class, 'index']],
  ['POST', '/api/boards', [BoardController::class, 'create']],
  ['POST', '/api/boards/{boardId}/clone', [BoardController::class, 'cloneBoard']],
  ['DELETE','/api/boards/{boardId}', [BoardController::class, 'delete']],

  // Widgets
  ['GET',  '/api/widgets', [WidgetController::class, 'catalog']],
  ['POST', '/api/users/{userId}/widgets', [WidgetController::class, 'setUserAllowedWidgets']],

  // Technician
  ['POST', '/api/technician/search-user', [TechnicianController::class, 'searchUser']],
  ['GET',  '/api/technician/insights/{userId}', [TechnicianController::class, 'techInsights']],
  ['POST', '/api/technician/codegen', [TechnicianController::class, 'generateFirmware']],
  ['GET',  '/api/technician/firmware/{buildId}/download', [TechnicianController::class, 'downloadFirmware']],

  // (Skeletons below; handlers exist with comments)
  ['GET',  '/api/users', [UserController::class, 'index']],
  ['POST', '/api/users', [UserController::class, 'create']],
  ['GET',  '/api/users/{id}', [UserController::class, 'show']],
  ['PUT',  '/api/users/{id}', [UserController::class, 'update']],
  ['DELETE','/api/users/{id}', [UserController::class, 'delete']],

  ['GET',  '/api/roles', [RoleController::class, 'index']],
  ['POST', '/api/roles', [RoleController::class, 'upsert']],

  ['GET',  '/api/devices', [DeviceController::class, 'index']],
  ['POST', '/api/devices', [DeviceController::class, 'register']],
  ['GET',  '/api/devices/{id}', [DeviceController::class, 'show']],
  ['DELETE','/api/devices/{id}', [DeviceController::class, 'delete']],

  ['POST', '/api/server-bindings/{userId}', [ServerBindingController::class, 'assign']],

  ['GET',  '/api/acl/{userId}', [ACLController::class, 'preview']],
  ['POST', '/api/acl/sync/{userId}', [ACLController::class, 'sync']],

  ['POST', '/api/exports', [DataExportController::class, 'createJob']],
  ['GET',  '/api/exports/{jobId}', [DataExportController::class, 'status']],
  ['GET',  '/api/exports/{jobId}/download', [DataExportController::class, 'download']]
];

// Router
$path = $req->path();
$method = $req->method();
foreach ($routes as [$m, $pattern, $handler]) {
  if ($m !== $method) continue;
  $params = Request::match($pattern, $path);
  if ($params === null) continue;

  [$class, $action] = $handler;
  $controller = new $class($req);
  try {
    $result = $controller->$action(...array_values($params));
    Response::json($result);
  } catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error', 'detail' => $e->getMessage()]);
  }
  exit;
}

// 404 if no route matched
http_response_code(404);
echo json_encode(['error' => 'Not Found', 'path' => $path, 'method' => $method]);