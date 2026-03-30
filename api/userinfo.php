<?php
/**
 * OAuth2.0 用户信息接口
 * 返回当前登录用户的基本信息
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/classes/Database.php';
require_once __DIR__ . '/../includes/classes/UserService.php';
require_once __DIR__ . '/../includes/classes/TokenService.php';

$db = Database::getInstance();
$userService = new UserService($db);
$tokenService = new TokenService($db);

// 获取 Authorization Header
$auth_header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$access_token = '';

if (preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
    $access_token = $matches[1];
} else {
    $access_token = $_GET['access_token'] ?? '';
}

if (empty($access_token)) {
    http_response_code(401);
    echo json_encode(['error' => 'unauthorized', 'message' => '缺少 access_token']);
    exit;
}

// 验证 token
$tokenData = $tokenService->verify($access_token);

if (!$tokenData['valid']) {
    http_response_code(401);
    echo json_encode(['error' => 'invalid_token', 'message' => $tokenData['message']]);
    exit;
}

// 获取用户信息
$user = $userService->getById($tokenData['user_id']);

if (!$user) {
    http_response_code(404);
    echo json_encode(['error' => 'user_not_found']);
    exit;
}

// 返回标准格式的用户信息
echo json_encode([
    'sub' => (string)$user['id'],
    'openid' => $user['wechat_openid'] ?? '',
    'unionid' => $user['wechat_unionid'] ?? '',
    'nickname' => $user['nickname'] ?? $user['username'],
    'username' => $user['username'],
    'email' => $user['email'] ?? '',
    'avatar' => $user['avatar'] ?? '',
    'gender' => $user['gender'] ?? 0,
    'mobile' => $user['mobile'] ?? '',
    'created_at' => $user['created_at']
]);
