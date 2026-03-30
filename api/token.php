<?php
/**
 * API - Token 验证接口
 * 供第三方站点验证访问令牌是否有效
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

define('SSO_ROOT', dirname(__DIR__) . '/');
require_once SSO_ROOT . 'includes/config.php';
require_once SSO_ROOT . 'includes/classes/TokenService.php';
require_once SSO_ROOT . 'includes/middleware.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$tokenService = new TokenService();

// 获取 Token
$token = $_POST['access_token'] ?? '';
$client_id = $_POST['client_id'] ?? '';

if (!$token) {
    http_response_code(400);
    echo json_encode(['valid' => false, 'error' => '缺少访问令牌']);
    exit;
}

// 验证 Token
$tokenData = $tokenService->validate($token);

if (!$tokenData) {
    echo json_encode(['valid' => false, 'error' => '无效的访问令牌']);
    exit;
}

// 如果指定了 client_id，验证是否匹配
if ($client_id && $tokenData['client_id'] !== $client_id) {
    echo json_encode(['valid' => false, 'error' => '令牌与客户端不匹配']);
    exit;
}

echo json_encode([
    'valid' => true,
    'user_id' => $tokenData['user_id'],
    'client_id' => $tokenData['client_id'],
    'expires_at' => $tokenData['expire_at']
]);
