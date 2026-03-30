<?php
/**
 * OAuth2.0 Token 接口
 * 处理授权码换取 access_token
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/classes/Database.php';
require_once __DIR__ . '/../includes/classes/TokenService.php';

$db = Database::getInstance();
$tokenService = new TokenService($db);

// 只接受 POST 请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'method_not_allowed']);
    exit;
}

// 获取参数
$grant_type = $_POST['grant_type'] ?? '';
$client_id = $_POST['client_id'] ?? '';
$client_secret = $_POST['client_secret'] ?? '';
$code = $_POST['code'] ?? '';
$redirect_uri = $_POST['redirect_uri'] ?? '';

// 验证 grant_type
if ($grant_type !== 'authorization_code') {
    http_response_code(400);
    echo json_encode(['error' => 'unsupported_grant_type']);
    exit;
}

// 验证客户端
$stmt = $db->prepare("SELECT * FROM sso_clients WHERE client_id = ? AND status = 1");
$stmt->execute([$client_id]);
$client = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$client || $client['client_secret'] !== $client_secret) {
    http_response_code(401);
    echo json_encode(['error' => 'invalid_client']);
    exit;
}

// 验证授权码
$stmt = $db->prepare("SELECT * FROM sso_auth_codes WHERE code = ? AND client_id = ? AND expire_at > NOW() AND used = 0");
$stmt->execute([$code, $client_id]);
$auth_code = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$auth_code) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid_grant', 'message' => '授权码无效或已过期']);
    exit;
}

// 验证 redirect_uri
if ($redirect_uri && $redirect_uri !== $auth_code['redirect_uri']) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid_grant', 'message' => 'redirect_uri 不匹配']);
    exit;
}

// 生成 access_token 和 refresh_token
$access_token = $tokenService->generate($auth_code['user_id'], $client_id, $auth_code['scope']);
$refresh_token = bin2hex(random_bytes(32));
$expires_in = 3600; // 1 小时

// 保存 refresh_token
$stmt = $db->prepare("INSERT INTO sso_refresh_tokens (token, user_id, client_id, expire_at) VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 30 DAY))");
$stmt->execute([$refresh_token, $auth_code['user_id'], $client_id]);

// 标记授权码为已使用
$stmt = $db->prepare("UPDATE sso_auth_codes SET used = 1 WHERE code = ?");
$stmt->execute([$code]);

// 返回结果
echo json_encode([
    'access_token' => $access_token,
    'token_type' => 'Bearer',
    'expires_in' => $expires_in,
    'refresh_token' => $refresh_token,
    'scope' => $auth_code['scope']
]);
