<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>登录成功 - 爱云科技 SSO</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .success-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 50px 40px;
            text-align: center;
            max-width: 450px;
            width: 100%;
        }
        
        .success-icon {
            font-size: 80px;
            margin-bottom: 20px;
        }
        
        h1 {
            color: #333;
            font-size: 24px;
            margin-bottom: 15px;
        }
        
        .user-info {
            background: #f5f7fa;
            border-radius: 12px;
            padding: 20px;
            margin: 25px 0;
            text-align: left;
        }
        
        .info-row {
            display: flex;
            padding: 10px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            color: #666;
            width: 80px;
            font-weight: 500;
        }
        
        .info-value {
            color: #333;
            flex: 1;
        }
        
        .avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            margin-bottom: 15px;
            border: 3px solid #667eea;
        }
        
        .actions {
            margin-top: 25px;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 30px;
            margin: 5px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: #f0f0f0;
            color: #333;
        }
        
        .btn-secondary:hover {
            background: #e0e0e0;
        }
        
        .footer {
            margin-top: 30px;
            color: #999;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="success-container">
        <div class="success-icon">✅</div>
        <h1>登录成功</h1>
        <p style="color: #666;">欢迎回来，<?= htmlspecialchars($user['nickname'] ?? $user['username']) ?>！</p>
        
        <?php if (!empty($user['avatar'])): ?>
            <img src="<?= htmlspecialchars($user['avatar']) ?>" alt="头像" class="avatar">
        <?php endif; ?>
        
        <div class="user-info">
            <div class="info-row">
                <span class="info-label">用户名</span>
                <span class="info-value"><?= htmlspecialchars($user['username']) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">昵称</span>
                <span class="info-value"><?= htmlspecialchars($user['nickname'] ?? '-') ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">角色</span>
                <span class="info-value">
                    <?php
                    $roleNames = ['admin' => '管理员', 'user' => '普通用户', 'developer' => '开发者'];
                    echo $roleNames[$user['role']] ?? $user['role'];
                    ?>
                </span>
            </div>
            <div class="info-row">
                <span class="info-label">状态</span>
                <span class="info-value">
                    <?php if ($user['status'] == 1): ?>
                        <span style="color: #07c160;">● 正常</span>
                    <?php else: ?>
                        <span style="color: #f44336;">● 禁用</span>
                    <?php endif; ?>
                </span>
            </div>
        </div>
        
        <div class="actions">
            <a href="/user/index.php" class="btn btn-primary">进入用户中心</a>
            <?php if ($user['role'] === 'admin'): ?>
                <a href="/admin/index.php" class="btn btn-primary">管理后台</a>
            <?php endif; ?>
            <a href="/index.php?action=logout" class="btn btn-secondary">退出登录</a>
        </div>
        
        <div class="footer">
            <p>© 2024 爱云科技 SSO 统一认证系统 v<?= SSO_VERSION ?></p>
        </div>
    </div>
</body>
</html>
