<?php
/**
 * OAuth2.0 授权接口
 * 处理授权码模式：?response_type=code&client_id=xxx&redirect_uri=xxx&scope=xxx
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/middleware.php';
require_once __DIR__ . '/../includes/classes/Database.php';
require_once __DIR__ . '/../includes/classes/UserService.php';
require_once __DIR__ . '/../includes/classes/TokenService.php';

session_start();

$db = Database::getInstance();
$userService = new UserService($db);
$tokenService = new TokenService($db);

// 获取参数
$response_type = $_GET['response_type'] ?? '';
$client_id = $_GET['client_id'] ?? '';
$redirect_uri = $_GET['redirect_uri'] ?? '';
$scope = $_GET['scope'] ?? 'userinfo';
$state = $_GET['state'] ?? '';

// 验证 response_type
if ($response_type !== 'code') {
    die('Invalid response_type');
}

// 验证客户端
$stmt = $db->prepare("SELECT * FROM sso_clients WHERE client_id = ? AND status = 1");
$stmt->execute([$client_id]);
$client = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$client) {
    die('Invalid client_id');
}

// 验证 redirect_uri 是否在白名单中
$allowed_uris = explode(',', $client['redirect_uris']);
if (!in_array($redirect_uri, $allowed_uris)) {
    die('Invalid redirect_uri');
}

// 检查用户是否登录
if (!isset($_SESSION['user_id'])) {
    // 未登录，跳转到登录页，并记录回调参数
    $_SESSION['oauth_callback'] = [
        'client_id' => $client_id,
        'redirect_uri' => $redirect_uri,
        'scope' => $scope,
        'state' => $state
    ];
    header('Location: /index.php');
    exit;
}

// 用户已登录，如果是 POST 请求表示用户确认授权
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'allow') {
        // 生成授权码
        $auth_code = bin2hex(random_bytes(32));
        $expire_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));
        
        // 保存授权码到数据库
        $stmt = $db->prepare("INSERT INTO sso_auth_codes (code, user_id, client_id, scope, expire_at) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$auth_code, $_SESSION['user_id'], $client_id, $scope, $expire_at]);
        
        // 构建回调 URL
        $callback_url = $redirect_uri . (strpos($redirect_uri, '?') ? '&' : '?') . http_build_query([
            'code' => $auth_code,
            'state' => $state
        ]);
        
        header('Location: ' . $callback_url);
        exit;
    } else {
        // 用户拒绝授权
        $callback_url = $redirect_uri . (strpos($redirect_uri, '?') : '&') . http_build_query([
            'error' => 'access_denied',
            'state' => $state
        ]);
        header('Location: ' . $callback_url);
        exit;
    }
}

// 显示授权确认页面
$user = $userService->getById($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>授权确认 - 爱云科技 SSO</title>
    <link href="https://cdn.aiyun.top/assets/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .auth-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 40px;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .client-logo {
            width: 80px;
            height: 80px;
            border-radius: 15px;
            margin-bottom: 20px;
        }
        .scope-badge {
            display: inline-block;
            background: #e9ecef;
            padding: 5px 15px;
            border-radius: 20px;
            margin: 5px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="auth-card text-center">
        <?php if ($client['logo_url']): ?>
            <img src="<?= htmlspecialchars($client['logo_url']) ?>" alt="<?= htmlspecialchars($client['client_name']) ?>" class="client-logo">
        <?php endif; ?>
        
        <h2 class="mb-3"><?= htmlspecialchars($client['client_name']) ?></h2>
        <p class="text-muted mb-4">申请获取以下权限：</p>
        
        <div class="mb-4">
            <?php 
            $scopes = explode(',', $scope);
            $scope_names = ['userinfo' => '基本信息', 'email' => '邮箱', 'phone' => '手机号'];
            foreach ($scopes as $s): 
                $name = $scope_names[$s] ?? $s;
            ?>
                <span class="scope-badge"><?= htmlspecialchars($name) ?></span>
            <?php endforeach; ?>
        </div>
        
        <div class="alert alert-info">
            <small>作为 <strong><?= htmlspecialchars($user['nickname'] ?: $user['username']) ?></strong> 登录</small>
        </div>
        
        <form method="POST">
            <button type="submit" name="action" value="allow" class="btn btn-primary btn-lg w-100 mb-3">
                同意授权
            </button>
            <button type="submit" name="action" value="deny" class="btn btn-outline-secondary btn-lg w-100">
                拒绝
            </button>
        </form>
    </div>
</body>
</html>
