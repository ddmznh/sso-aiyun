<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>登录 - 爱云科技 SSO 统一认证系统</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 40px;
            width: 100%;
            max-width: 420px;
            backdrop-filter: blur(10px);
        }
        
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo h1 {
            color: #333;
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        .logo p {
            color: #666;
            font-size: 14px;
        }
        
        .login-tabs {
            display: flex;
            margin-bottom: 30px;
            border-bottom: 2px solid #e0e0e0;
        }
        
        .tab-btn {
            flex: 1;
            padding: 12px;
            background: none;
            border: none;
            font-size: 16px;
            cursor: pointer;
            color: #666;
            transition: all 0.3s;
        }
        
        .tab-btn.active {
            color: #667eea;
            border-bottom: 2px solid #667eea;
            margin-bottom: -2px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .submit-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        
        .wechat-login {
            text-align: center;
            padding: 30px 0;
        }
        
        .wechat-btn {
            display: inline-flex;
            align-items: center;
            padding: 14px 30px;
            background: #07c160;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
        }
        
        .wechat-btn:hover {
            background: #06ad56;
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(7, 193, 96, 0.4);
        }
        
        .wechat-icon {
            margin-right: 10px;
            font-size: 20px;
        }
        
        .form-panel {
            display: none;
        }
        
        .form-panel.active {
            display: block;
        }
        
        .error-msg {
            color: #f44336;
            font-size: 14px;
            margin-top: 5px;
            display: none;
        }
        
        .footer {
            text-align: center;
            margin-top: 30px;
            color: #999;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <h1>🔐 爱云科技 SSO</h1>
            <p>统一认证系统 - 一次登录，全站通行</p>
        </div>
        
        <div class="login-tabs">
            <button class="tab-btn active" onclick="switchTab('password')">账号登录</button>
            <button class="tab-btn" onclick="switchTab('wechat')">微信扫码</button>
        </div>
        
        <!-- 账号密码登录表单 -->
        <div id="password-panel" class="form-panel active">
            <form method="POST" action="wechat/callback.php?action=login" id="loginForm">
                <div class="form-group">
                    <label for="username">用户名</label>
                    <input type="text" id="username" name="username" required placeholder="请输入用户名">
                </div>
                
                <div class="form-group">
                    <label for="password">密码</label>
                    <input type="password" id="password" name="password" required placeholder="请输入密码">
                </div>
                
                <button type="submit" class="submit-btn">登 录</button>
            </form>
        </div>
        
        <!-- 微信登录面板 -->
        <div id="wechat-panel" class="form-panel">
            <div class="wechat-login">
                <a href="<?= $wechatService->getAuthorizeUrl() ?>" class="wechat-btn">
                    <span class="wechat-icon">💬</span>
                    微信扫码登录
                </a>
                <p style="margin-top: 20px; color: #999; font-size: 14px;">
                    使用微信扫码，快速安全登录
                </p>
            </div>
        </div>
        
        <div class="footer">
            <p>© 2024 爱云科技 SSO 统一认证系统 v<?= SSO_VERSION ?></p>
        </div>
    </div>
    
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
