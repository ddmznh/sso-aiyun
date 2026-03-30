<?php
/**
 * 爱云科技 SSO - 中间件层
 * 权限验证、安全拦截
 */

if (!defined('SSO_ROOT')) {
    exit('Access Denied');
}

require_once SSO_ROOT . 'includes/helpers.php';

/**
 * 检查用户是否已登录
 */
function requireLogin() {
    if (empty($_SESSION['user_id'])) {
        if (isAjaxRequest()) {
            errorResponse('请先登录', 401);
        }
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: ' . SSO_DOMAIN);
        exit;
    }
}

/**
 * 检查是否为管理员
 */
function requireAdmin() {
    requireLogin();
    
    $userService = new UserService();
    $user = $userService->getById($_SESSION['user_id']);
    
    if (!$user || $user['role'] !== 'admin') {
        if (isAjaxRequest()) {
            errorResponse('无权访问', 403);
        }
        $_SESSION['flash_error'] = '您没有权限访问管理后台';
        header('Location: ' . SSO_DOMAIN . '/user/');
        exit;
    }
    
    return $user;
}

/**
 * 检查是否为开发者
 */
function requireDeveloper() {
    requireLogin();
    
    $userService = new UserService();
    $user = $userService->getById($_SESSION['user_id']);
    
    if (!$user || !in_array($user['role'], ['admin', 'developer'])) {
        if (isAjaxRequest()) {
            errorResponse('无权访问', 403);
        }
        $_SESSION['flash_error'] = '您没有权限执行此操作';
        header('Location: ' . SSO_DOMAIN . '/user/');
        exit;
    }
    
    return $user;
}

/**
 * 验证 CSRF Token（用于表单提交）
 */
function verifyCsrfPost() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        
        if (!verifyCsrfToken($token)) {
            if (isAjaxRequest()) {
                errorResponse('CSRF 验证失败，请刷新页面重试', 403);
            }
            $_SESSION['flash_error'] = '安全验证失败，请刷新页面重试';
            header('Location: ' . $_SERVER['HTTP_REFERER'] ?? SSO_DOMAIN);
            exit;
        }
    }
}

/**
 * 检查登录失败锁定
 */
function checkLoginLockout($identifier) {
    global $MAX_LOGIN_ATTEMPTS, $LOCKOUT_MINUTES;
    
    $lockoutKey = 'login_attempts_' . md5($identifier);
    $attempts = $_SESSION[$lockoutKey]['count'] ?? 0;
    $lockoutTime = $_SESSION[$lockoutKey]['time'] ?? 0;
    
    if ($attempts >= $MAX_LOGIN_ATTEMPTS && (time() - $lockoutTime) < ($LOCKOUT_MINUTES * 60)) {
        $remainingTime = ceil(($LOCKOUT_MINUTES * 60 - (time() - $lockoutTime)) / 60);
        return [
            'locked' => true,
            'remaining_minutes' => $remainingTime
        ];
    }
    
    // 如果锁定期已过，重置计数
    if ($attempts >= $MAX_LOGIN_ATTEMPTS && (time() - $lockoutTime) >= ($LOCKOUT_MINUTES * 60)) {
        unset($_SESSION[$lockoutKey]);
    }
    
    return ['locked' => false];
}

/**
 * 记录登录失败尝试
 */
function recordLoginAttempt($identifier, $success = false) {
    global $MAX_LOGIN_ATTEMPTS, $LOCKOUT_MINUTES;
    
    $lockoutKey = 'login_attempts_' . md5($identifier);
    
    if ($success) {
        // 登录成功，清除失败记录
        unset($_SESSION[$lockoutKey]);
        return;
    }
    
    // 登录失败，增加计数
    if (!isset($_SESSION[$lockoutKey])) {
        $_SESSION[$lockoutKey] = ['count' => 0, 'time' => time()];
    }
    
    $_SESSION[$lockoutKey]['count']++;
    $_SESSION[$lockoutKey]['time'] = time();
    
    $attempts = $_SESSION[$lockoutKey]['count'];
    
    if ($attempts >= $MAX_LOGIN_ATTEMPTS) {
        $remainingTime = $LOCKOUT_MINUTES * 60;
        return [
            'locked' => true,
            'remaining_minutes' => $LOCKOUT_MINUTES
        ];
    }
    
    return [
        'locked' => false,
        'remaining_attempts' => $MAX_LOGIN_ATTEMPTS - $attempts
    ];
}

/**
 * 设置 Flash 消息
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash_' . $type] = $message;
}

/**
 * 获取并清除 Flash 消息
 */
function getFlashMessage($type) {
    $key = 'flash_' . $type;
    $message = $_SESSION[$key] ?? null;
    unset($_SESSION[$key]);
    return $message;
}

/**
 * 检查站点访问权限
 */
function checkSiteAccess($clientId, $userId) {
    $db = Database::getInstance();
    
    // 检查站点是否存在且启用
    $stmt = $db->prepare("SELECT * FROM sso_clients WHERE client_id = ? AND status = 1");
    $stmt->execute([$clientId]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$client) {
        return ['allowed' => false, 'reason' => '站点不存在或已禁用'];
    }
    
    // 检查用户是否已授权
    $stmt = $db->prepare("SELECT * FROM sso_authorizations WHERE user_id = ? AND client_id = ? AND revoked_at IS NULL");
    $stmt->execute([$userId, $clientId]);
    $auth = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$auth) {
        return ['allowed' => false, 'reason' => '未授权', 'require_auth' => true];
    }
    
    return ['allowed' => true, 'scopes' => explode(',', $auth['scopes'])];
}
