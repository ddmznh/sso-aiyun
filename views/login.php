<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>登录 - 爱云科技 SSO 统一认证系统</title>
    <link href="https://cdn.aiyun.top/assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= CDN_BASE ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="logo-container">
                <img src="https://cdn.aiyun.top/assets/aiyun/aiyun-logo-black.png" class="logo-black" alt="爱云科技">
                <img src="https://cdn.aiyun.top/assets/aiyun/aiyun-logo-white.png" class="logo-white" alt="爱云科技">
            </div>
            
            <div class="login-tabs">
                <button class="tab-btn active" onclick="switchTab('password')">账号登录</button>
                <button class="tab-btn" onclick="switchTab('wechat')">微信扫码</button>
            </div>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($_SESSION['error']) ?>
                    <button type="button" class="close" data-dismiss="alert">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            
            <!-- 账号密码登录表单 -->
            <div id="password-panel" class="form-panel active">
                <form method="POST" action="wechat/callback.php?action=login" id="loginForm">
                    <div class="form-group">
                        <label for="username" class="form-label">用户名</label>
                        <input type="text" class="form-control" id="username" name="username" required placeholder="请输入用户名">
                    </div>
                    
                    <div class="form-group">
                        <label for="password" class="form-label">密码</label>
                        <input type="password" class="form-control" id="password" name="password" required placeholder="请输入密码">
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100 mb-3">登 录</button>
                </form>
            </div>
            
            <!-- 微信登录面板 -->
            <div id="wechat-panel" class="form-panel">
                <div class="wechat-login text-center p-3">
                    <a href="<?= $wechatService->getAuthorizeUrl() ?>" class="btn btn-success btn-lg mb-3">
                        💬 微信扫码登录
                    </a>
                    <p class="text-muted" style="font-size: 14px;">
                        使用微信扫码，快速安全登录
                    </p>
                </div>
            </div>
            
            <div class="footer text-center mt-4">
                <p class="text-muted" style="font-size: 12px;">
                    © 2024 爱云科技 SSO 统一认证系统 v<?= SSO_VERSION ?>
                </p>
            </div>
        </div>
    </div>
    
    <button id="themeToggle" class="theme-toggle" title="切换到深色模式">🌙</button>
    
    <script src="https://cdn.aiyun.top/assets/js/bootstrap.bundle.min.js"></script>
    <script src="<?= CDN_BASE ?>/assets/js/main.js"></script>
    <script>
        function switchTab(tab) {
            // 更新标签页状态
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');
            
            // 更新表单面板
            document.querySelectorAll('.form-panel').forEach(panel => {
                panel.classList.remove('active');
            });
            document.getElementById(tab + '-panel').classList.add('active');
        }
    </script>
</body>
</html>
