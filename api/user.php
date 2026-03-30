<?php
/**
 * API - 用户信息接口
 * 供第三方站点调用，获取当前登录用户信息
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

define('SSO_ROOT', dirname(__DIR__) . '/');
require_once SSO_ROOT . 'includes/config.php';
require_once SSO_ROOT . 'includes/classes/Database.php';
require_once SSO_ROOT . 'includes/classes/TokenService.php';
require_once SSO_ROOT . 'includes/classes/UserService.php';
require_once SSO_ROOT . 'includes/middleware.php';

// 处理预检请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$tokenService = new TokenService();
$userService = new UserService();

// 从 Header 或 GET 参数获取 Token
$token = $_GET['access_token'] ?? '';
if (!$token) {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/Bearer\s+(.+)/i', $authHeader, $matches)) {
        $token = $matches[1];
    }
}

if (!$token) {
    http_response_code(401);
    echo json_encode(['error' => '缺少访问令牌', 'code' => 'MISSING_TOKEN']);
    exit;
}

// 验证 Token
$tokenData = $tokenService->validate($token);
if (!$tokenData) {
    http_response_code(401);
    echo json_encode(['error' => '无效的访问令牌', 'code' => 'INVALID_TOKEN']);
    exit;
}

// 获取用户信息
$user = $userService->getById($tokenData['user_id']);
if (!$user) {
    http_response_code(404);
    echo json_encode(['error' => '用户不存在', 'code' => 'USER_NOT_FOUND']);
    exit;
}

// 返回脱敏后的用户信息
$response = [
    'user_id' => $user['id'],
    'username' => $user['username'],
    'nickname' => $user['nickname'],
    'email' => $user['email'],
    'avatar' => $user['avatar'],
    'role' => $user['role'],
    'created_at' => $user['created_at']
];

echo json_encode(['success' => true, 'data' => $response]);
