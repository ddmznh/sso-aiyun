<?php
/**
 * 爱云科技 SSO 统一认证系统 - 主入口文件
 * 
 * @package SSO_Aiyun
 * @version 1.0.0
 */

// 定义系统常量
define('SSO_SYSTEM', true);

// 加载配置文件
require_once __DIR__ . '/includes/config.php';

// 初始化服务类
$db = Database::getInstance();
$userService = new UserService();
$tokenService = new TokenService();
$logService = new LogService();
$wechatService = new WechatService();

// 获取请求参数
$action = $_GET['action'] ?? 'login';
$redirectUri = $_GET['redirect_uri'] ?? '';
$clientId = $_GET['client_id'] ?? '';

// 检查是否已登录
$isLoggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);

// 处理登出
if ($action === 'logout') {
    session_destroy();
    header('Location: ' . SSO_DOMAIN);
    exit;
}

// 如果已登录且有重定向地址，生成 token 并跳转
if ($isLoggedIn && !empty($redirectUri) && !empty($clientId)) {
    // 验证重定向域名是否在白名单中
    $parsedUrl = parse_url($redirectUri);
    $domain = $parsedUrl['host'] ?? '';
    
    if (!in_array($domain, $ALLOWED_REDIRECT_DOMAINS)) {
        die('非法的重定向地址');
    }
    
    // 生成 token
    $token = $tokenService->generateToken($_SESSION['user_id'], $clientId, $redirectUri);
    
    // 构建重定向 URL
    $separator = strpos($redirectUri, '?') !== false ? '&' : '?';
    $redirectUrl = $redirectUri . $separator . 'token=' . $token;
    
    header('Location: ' . $redirectUrl);
    exit;
}

// 如果已登录，显示用户信息页面
if ($isLoggedIn) {
    $user = $userService->findById($_SESSION['user_id']);
    include __DIR__ . '/views/logged_in.php';
    exit;
}

// 显示登录页面
include __DIR__ . '/views/login.php';
