<?php
/**
 * 爱云科技 SSO 统一认证系统 - 微信回调处理
 * 
 * @package SSO_Aiyun
 * @version 1.0.0
 */

// 定义系统常量
define('SSO_SYSTEM', true);

// 加载配置文件
require_once __DIR__ . '/../includes/config.php';

// 初始化服务类
$userService = new UserService();
$tokenService = new TokenService();
$logService = new LogService();
$wechatService = new WechatService();

// 获取动作参数
$action = $_GET['action'] ?? 'callback';

// 处理账号密码登录
if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    handlePasswordLogin($userService, $logService, $tokenService);
}

// 处理微信回调
if ($action === 'callback') {
    handleWechatCallback($wechatService, $userService, $logService, $tokenService);
}

/**
 * 处理账号密码登录
 */
function handlePasswordLogin($userService, $logService, $tokenService) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $redirectUri = $_GET['redirect_uri'] ?? '';
    $clientId = $_GET['client_id'] ?? '';
    
    if (empty($username) || empty($password)) {
        die('用户名和密码不能为空');
    }
    
    // 查找用户
    $user = $userService->findByUsername($username);
    
    if (!$user) {
        // 记录失败日志
        $logService->recordLogin(null, $clientId ?: 'unknown', 'password', 'failed', '用户不存在');
        die('用户名或密码错误');
    }
    
    // 检查用户状态
    if ($user['status'] != 1) {
        $logService->recordLogin($user['id'], $clientId ?: 'unknown', 'password', 'failed', '账户已被禁用');
        die('账户已被禁用，请联系管理员');
    }
    
    // 验证密码
    if (!$userService->verifyPassword($password, $user['password'])) {
        $logService->recordLogin($user['id'], $clientId ?: 'unknown', 'password', 'failed', '密码错误');
        die('用户名或密码错误');
    }
    
    // 登录成功
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['nickname'] = $user['nickname'];
    $_SESSION['role'] = $user['role'];
    
    // 更新最后登录时间
    $userService->updateLastLogin($user['id']);
    
    // 记录成功日志
    $logService->recordLogin($user['id'], $clientId ?: 'unknown', 'password', 'success');
    
    // 如果有重定向地址，生成 token 并跳转
    if (!empty($redirectUri) && !empty($clientId)) {
        generateAndRedirect($user['id'], $clientId, $redirectUri, $tokenService);
    } else {
        // 跳转到用户中心或首页
        header('Location: /index.php');
        exit;
    }
}

/**
 * 处理微信回调
 */
function handleWechatCallback($wechatService, $userService, $logService, $tokenService) {
    $code = $_GET['code'] ?? '';
    $state = $_GET['state'] ?? '';
    $redirectUri = $_SESSION['wechat_redirect_uri'] ?? '';
    $clientId = $_SESSION['wechat_client_id'] ?? '';
    
    // 清除 session 中的临时数据
    unset($_SESSION['wechat_redirect_uri']);
    unset($_SESSION['wechat_client_id']);
    
    if (empty($code)) {
        // 没有 code，可能是用户取消授权
        die('授权失败：未获取到授权码');
    }
    
    try {
        // 通过 code 获取 access_token
        $tokenInfo = $wechatService->getAccessToken($code);
        
        // 获取用户信息
        $userInfo = $wechatService->getUserInfo(
            $tokenInfo['access_token'],
            $tokenInfo['openid']
        );
        
        // 创建或更新用户
        $userId = $userService->createOrUpdateWechatUser([
            'openid' => $tokenInfo['openid'],
            'unionid' => $tokenInfo['unionid'] ?? '',
            'nickname' => $userInfo['nickname'],
            'headimgurl' => $userInfo['headimgurl'] ?? ''
        ]);
        
        // 获取用户信息
        $user = $userService->findById($userId);
        
        // 检查用户状态
        if ($user['status'] != 1) {
            $logService->recordLogin($user['id'], $clientId ?: 'unknown', 'wechat', 'failed', '账户已被禁用');
            die('账户已被禁用，请联系管理员');
        }
        
        // 设置 session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['nickname'] = $user['nickname'];
        $_SESSION['role'] = $user['role'];
        
        // 更新最后登录时间
        $userService->updateLastLogin($user['id']);
        
        // 记录成功日志
        $logService->recordLogin($user['id'], $clientId ?: 'unknown', 'wechat', 'success');
        
        // 如果有重定向地址，生成 token 并跳转
        if (!empty($redirectUri) && !empty($clientId)) {
            generateAndRedirect($user['id'], $clientId, $redirectUri, $tokenService);
        } else {
            // 跳转到用户中心或首页
            header('Location: /index.php');
            exit;
        }
        
    } catch (Exception $e) {
        $logService->recordLogin(null, $clientId ?: 'unknown', 'wechat', 'failed', $e->getMessage());
        die('微信登录失败：' . $e->getMessage());
    }
}

/**
 * 生成 token 并重定向
 */
function generateAndRedirect($userId, $clientId, $redirectUri, $tokenService) {
    global $ALLOWED_REDIRECT_DOMAINS;
    
    // 验证重定向域名是否在白名单中
    $parsedUrl = parse_url($redirectUri);
    $domain = $parsedUrl['host'] ?? '';
    
    if (!in_array($domain, $ALLOWED_REDIRECT_DOMAINS)) {
        die('非法的重定向地址');
    }
    
    // 生成 token
    $token = $tokenService->generateToken($userId, $clientId, $redirectUri);
    
    // 构建重定向 URL
    $separator = strpos($redirectUri, '?') !== false ? '&' : '?';
    $redirectUrl = $redirectUri . $separator . 'token=' . $token;
    
    header('Location: ' . $redirectUrl);
    exit;
}
