<?php
/**
 * API - OAuth2.0 授权回调接口
 * 第三方站点通过此接口完成授权流程
 */

header('Content-Type: application/json; charset=utf-8');

define('SSO_ROOT', dirname(__DIR__) . '/');
require_once SSO_ROOT . 'includes/config.php';
require_once SSO_ROOT . 'includes/classes/Database.php';
require_once SSO_ROOT . 'includes/classes/TokenService.php';
require_once SSO_ROOT . 'includes/classes/UserService.php';
require_once SSO_ROOT . 'includes/middleware.php';

$tokenService = new TokenService();
$userService = new UserService();
$db = Database::getInstance();

// 获取参数
$client_id = $_GET['client_id'] ?? $_POST['client_id'] ?? '';
$client_secret = $_POST['client_secret'] ?? '';
$code = $_GET['code'] ?? $_POST['code'] ?? '';
$redirect_uri = $_GET['redirect_uri'] ?? $_POST['redirect_uri'] ?? '';
$grant_type = $_GET['grant_type'] ?? $_POST['grant_type'] ?? '';

if ($grant_type === 'authorization_code' && $code) {
    // 授权码模式：验证并换取 Token
    $stmt = $db->query("SELECT * FROM sso_tokens WHERE token = ? AND expire_at > NOW()", [$code]);
    $tokenRecord = $stmt->fetch();
    
    if (!$tokenRecord) {
        http_response_code(400);
        echo json_encode(['error' => 'invalid_grant', 'error_description' => '授权码无效或已过期']);
        exit;
    }
    
    // 验证客户端
    $stmt = $db->query("SELECT * FROM sso_clients WHERE client_id = ? AND status = 1", [$client_id]);
    $client = $stmt->fetch();
    
    if (!$client || $client['client_secret'] !== $client_secret) {
        http_response_code(401);
        echo json_encode(['error' => 'invalid_client', 'error_description' => '客户端认证失败']);
        exit;
    }
    
    // 验证 redirect_uri
    if ($redirect_uri !== $tokenRecord['redirect_uri']) {
        http_response_code(400);
        echo json_encode(['error' => 'redirect_uri_mismatch', 'error_description' => '回调地址不匹配']);
        exit;
    }
    
    // 生成正式访问令牌
    $accessToken = $tokenService->generate($tokenRecord['user_id'], $client_id, $redirect_uri);
    
    // 删除一次性授权码
    $db->execute("DELETE FROM sso_tokens WHERE token = ?", [$code]);
    
    echo json_encode([
        'access_token' => $accessToken,
        'token_type' => 'Bearer',
        'expires_in' => TOKEN_EXPIRE_MINUTES * 60
    ]);
    
} elseif ($grant_type === 'client_credentials') {
    // 客户端凭证模式（用于服务端调用）
    $stmt = $db->query("SELECT * FROM sso_clients WHERE client_id = ? AND client_secret = ? AND status = 1", [$client_id, $client_secret]);
    $client = $stmt->fetch();
    
    if (!$client) {
        http_response_code(401);
        echo json_encode(['error' => 'invalid_client', 'error_description' => '客户端认证失败']);
        exit;
    }
    
    // 生成客户端令牌（无用户关联）
    $accessToken = bin2hex(random_bytes(32));
    $expireAt = date('Y-m-d H:i:s', strtotime('+' . TOKEN_EXPIRE_MINUTES . ' minutes'));
    
    $db->execute("INSERT INTO sso_tokens (token, client_id, expire_at) VALUES (?, ?, ?)", 
        [$accessToken, $client_id, $expireAt]);
    
    echo json_encode([
        'access_token' => $accessToken,
        'token_type' => 'Bearer',
        'expires_in' => TOKEN_EXPIRE_MINUTES * 60
    ]);
    
} else {
    http_response_code(400);
    echo json_encode(['error' => 'unsupported_grant_type', 'error_description' => '不支持的授权类型']);
}
