<?php
/**
 * 爱云科技 SSO 统一认证系统 - 安装向导
 * 
 * @package SSO_Install
 * @version 1.0.0
 */

// 检查是否已安装
if (file_exists(__DIR__ . '/install.lock')) {
    die('<div style="text-align:center;padding:50px;font-family:sans-serif;"><h1>🔒 系统已安装</h1><p>为了安全起见，安装程序已被锁定。</p><p>如需重新安装，请手动删除 <code>install/install.lock</code> 文件。</p></div>');
}

// 加载配置示例
$configExample = require __DIR__ . '/../includes/config.example.php';

// 初始化会话
session_start();

// 当前步骤
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$step = max(1, min(5, $step));

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'check_environment') {
        // 环境检测
        $checks = [
            'php_version' => [
                'name' => 'PHP 版本',
                'required' => true,
                'current' => PHP_VERSION,
                'pass' => version_compare(PHP_VERSION, '8.1.0', '>='),
                'message' => '需要 PHP 8.1.0 或更高版本'
            ],
            'pdo_mysql' => [
                'name' => 'PDO MySQL 扩展',
                'required' => true,
                'current' => extension_loaded('pdo_mysql') ? '已启用' : '未启用',
                'pass' => extension_loaded('pdo_mysql'),
                'message' => '需要启用 pdo_mysql 扩展'
            ],
            'curl' => [
                'name' => 'cURL 扩展',
                'required' => true,
                'current' => extension_loaded('curl') ? '已启用' : '未启用',
                'pass' => extension_loaded('curl'),
                'message' => '需要启用 curl 扩展'
            ],
            'openssl' => [
                'name' => 'OpenSSL 扩展',
                'required' => true,
                'current' => extension_loaded('openssl') ? '已启用' : '未启用',
                'pass' => extension_loaded('openssl'),
                'message' => '需要启用 openssl 扩展'
            ],
            'json' => [
                'name' => 'JSON 扩展',
                'required' => true,
                'current' => extension_loaded('json') ? '已启用' : '未启用',
                'pass' => extension_loaded('json'),
                'message' => '需要启用 json 扩展'
            ],
            'mbstring' => [
                'name' => 'MBString 扩展',
                'required' => true,
                'current' => extension_loaded('mbstring') ? '已启用' : '未启用',
                'pass' => extension_loaded('mbstring'),
                'message' => '需要启用 mbstring 扩展'
            ],
            'config_writable' => [
                'name' => '配置文件目录可写',
                'required' => true,
                'current' => is_writable(__DIR__ . '/../includes') ? '可写' : '不可写',
                'pass' => is_writable(__DIR__ . '/../includes'),
                'message' => '需要 includes 目录有写入权限'
            ],
            'database_writable' => [
                'name' => '数据库脚本可读',
                'required' => true,
                'current' => is_readable(__DIR__ . '/../database/init.sql') ? '可读' : '不可读',
                'pass' => is_readable(__DIR__ . '/../database/init.sql'),
                'message' => '需要 init.sql 文件可读'
            ]
        ];
        
        $_SESSION['env_checks'] = $checks;
        $allPass = !in_array(false, array_column($checks, 'pass'));
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $allPass,
            'checks' => $checks
        ]);
        exit;
    }
    
    if ($action === 'test_db_connection') {
        // 测试数据库连接
        $host = $_POST['db_host'] ?? 'localhost';
        $port = $_POST['db_port'] ?? '3306';
        $name = $_POST['db_name'] ?? '';
        $user = $_POST['db_user'] ?? '';
        $pass = $_POST['db_pass'] ?? '';
        
        try {
            $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            
            // 尝试创建数据库（如果不存在）
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `{$name}`");
            
            // 测试表创建权限
            $testTable = 'sso_install_test_' . time();
            $pdo->exec("CREATE TABLE {$testTable} (id INT)");
            $pdo->exec("DROP TABLE {$testTable}");
            
            echo json_encode(['success' => true, 'message' => '数据库连接成功']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => '数据库连接失败：' . $e->getMessage()]);
        }
        exit;
    }
    
    if ($action === 'install') {
        // 执行安装
        $dbConfig = [
            'host' => $_POST['db_host'] ?? 'localhost',
            'port' => $_POST['db_port'] ?? '3306',
            'name' => $_POST['db_name'] ?? '',
            'user' => $_POST['db_user'] ?? '',
            'pass' => $_POST['db_pass'] ?? '',
            'charset' => 'utf8mb4'
        ];
        
        $adminConfig = [
            'username' => $_POST['admin_username'] ?? 'admin',
            'password' => $_POST['admin_password'] ?? '',
            'email' => $_POST['admin_email'] ?? ''
        ];
        
        $siteConfig = [
            'sso_domain' => $_POST['sso_domain'] ?? 'https://sso.aiyun.top',
            'cdn_base' => $_POST['cdn_base'] ?? 'https://cdn.aiyun.top',
            'wx_appid' => $_POST['wx_appid'] ?? '',
            'wx_appsecret' => $_POST['wx_appsecret'] ?? '',
            'sms_user' => $_POST['sms_user'] ?? '',
            'sms_pass' => $_POST['sms_pass'] ?? ''
        ];
        
        try {
            // 1. 生成 config.php
            $configContent = "<?php\n";
            $configContent .= "/**\n * 爱云科技 SSO 统一认证系统 - 配置文件\n * Generated by Install Wizard on " . date('Y-m-d H:i:s') . "\n */\n\n";
            
            // 数据库配置
            $configContent .= "// 数据库配置\n";
            $configContent .= "define('DB_HOST', '" . addslashes($dbConfig['host']) . "');\n";
            $configContent .= "define('DB_PORT', '" . addslashes($dbConfig['port']) . "');\n";
            $configContent .= "define('DB_NAME', '" . addslashes($dbConfig['name']) . "');\n";
            $configContent .= "define('DB_USER', '" . addslashes($dbConfig['user']) . "');\n";
            $configContent .= "define('DB_PASS', '" . addslashes($dbConfig['pass']) . "');\n";
            $configContent .= "define('DB_CHARSET', '" . $dbConfig['charset'] . "');\n\n";
            
            // 微信配置
            $configContent .= "// 微信开放平台配置\n";
            $configContent .= "define('WX_APPID', '" . addslashes($siteConfig['wx_appid']) . "');\n";
            $configContent .= "define('WX_APPSECRET', '" . addslashes($siteConfig['wx_appsecret']) . "');\n";
            $configContent .= "define('WX_REDIRECT_URI', '" . addslashes($siteConfig['sso_domain']) . "/wechat/callback.php');\n\n";
            
            // 平台配置
            $configContent .= "// 平台配置\n";
            $configContent .= "define('SSO_DOMAIN', '" . addslashes($siteConfig['sso_domain']) . "');\n";
            $configContent .= "define('TOKEN_EXPIRE_MINUTES', 5);\n";
            $configContent .= "define('CDN_BASE', '" . addslashes($siteConfig['cdn_base']) . "');\n\n";
            
            // 短信配置
            $configContent .= "// 短信平台配置\n";
            $configContent .= "define('SMS_USER', '" . addslashes($siteConfig['sms_user']) . "');\n";
            $configContent .= "define('SMS_PASS', '" . addslashes($siteConfig['sms_pass']) . "');\n";
            $configContent .= "define('SMS_FEE_TYPE', '2'); // 2=行业套餐，3=政务套餐\n\n";
            
            // 安全配置
            $configContent .= "// 安全配置\n";
            $configContent .= "define('MAX_LOGIN_ATTEMPTS', 5);\n";
            $configContent .= "define('LOCKOUT_MINUTES', 15);\n";
            $configContent .= "define('CSRF_TOKEN_NAME', 'csrf_token');\n\n";
            
            // 允许重定向的域名白名单
            $configContent .= "// 允许重定向的域名白名单\n";
            $configContent .= "define('ALLOWED_REDIRECT_DOMAINS', json_encode([\n";
            $configContent .= "    'news.aiyun.top',\n";
            $configContent .= "    'aiyunkeji.com',\n";
            $configContent .= "    '8881314.com',\n";
            $configContent .= "    'xuyong.vip'\n";
            $configContent .= "]));";
            
            file_put_contents(__DIR__ . '/../includes/config.php', $configContent);
            
            // 2. 连接数据库并导入 SQL
            $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['name']};charset=utf8mb4";
            $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            
            // 读取并执行 SQL 文件
            $sqlFile = __DIR__ . '/../database/init.sql';
            $sqlContent = file_get_contents($sqlFile);
            
            // 分割 SQL 语句
            $statements = array_filter(array_map('trim', explode(';', $sqlContent)));
            
            foreach ($statements as $statement) {
                if (empty($statement) || strpos($statement, '--') === 0) {
                    continue;
                }
                try {
                    $pdo->exec($statement);
                } catch (PDOException $e) {
                    // 忽略已存在的表错误
                    if (strpos($e->getMessage(), 'already exists') === false) {
                        throw $e;
                    }
                }
            }
            
            // 3. 创建管理员账户
            $passwordHash = password_hash($adminConfig['password'], PASSWORD_BCRYPT, ['cost' => 12]);
            $stmt = $pdo->prepare("INSERT INTO users (username, password, email, nickname, role, status, created_at) VALUES (:username, :password, :email, :nickname, 'admin', 1, NOW())");
            $stmt->execute([
                ':username' => $adminConfig['username'],
                ':password' => $passwordHash,
                ':email' => $adminConfig['email'],
                ':nickname' => '超级管理员'
            ]);
            
            // 4. 创建安装锁文件
            file_put_contents(__DIR__ . '/install.lock', 'Installed on ' . date('Y-m-d H:i:s'));
            
            // 保存安装信息到 session
            $_SESSION['install_complete'] = true;
            $_SESSION['admin_username'] = $adminConfig['username'];
            $_SESSION['sso_domain'] = $siteConfig['sso_domain'];
            
            echo json_encode(['success' => true, 'message' => '安装成功']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => '安装失败：' . $e->getMessage()]);
        }
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>安装向导 - 爱云科技 SSO 统一认证系统</title>
    <link rel="stylesheet" href="../assets/css/install.css">
</head>
<body>
    <div class="install-container">
        <!-- 头部 -->
        <div class="install-header">
            <div class="install-logo">
                <img src="https://cdn.aiyun.top/assets/aiyun/aiyun-logo-black.png" alt="爱云科技">
            </div>
            <h1>SSO 统一认证系统</h1>
            <p>安装向导 v1.0.0</p>
        </div>
        
        <!-- 进度条 -->
        <div class="progress-bar">
            <div class="step <?= $step >= 1 ? 'active' : '' ?> <?= $step > 1 ? 'completed' : '' ?>">
                <div class="step-number">1</div>
                <div class="step-label">许可协议</div>
            </div>
            <div class="step <?= $step >= 2 ? 'active' : '' ?> <?= $step > 2 ? 'completed' : '' ?>">
                <div class="step-number">2</div>
                <div class="step-label">环境检测</div>
            </div>
            <div class="step <?= $step >= 3 ? 'active' : '' ?> <?= $step > 3 ? 'completed' : '' ?>">
                <div class="step-number">3</div>
                <div class="step-label">数据库配置</div>
            </div>
            <div class="step <?= $step >= 4 ? 'active' : '' ?> <?= $step > 4 ? 'completed' : '' ?>">
                <div class="step-number">4</div>
                <div class="step-label">管理员设置</div>
            </div>
            <div class="step <?= $step >= 5 ? 'active' : '' ?>">
                <div class="step-number">5</div>
                <div class="step-label">完成安装</div>
            </div>
        </div>
        
        <!-- 主体内容 -->
        <div class="install-body">
            <!-- 步骤 1: 许可协议 -->
            <?php if ($step == 1): ?>
            <div class="step-content active">
                <div class="alert alert-warning">
                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                    <span>请仔细阅读以下许可协议条款</span>
                </div>
                
                <div class="license-content">
                    <h3>爱云科技 SSO 统一认证系统 使用许可协议</h3>
                    <br>
                    <p><strong>第一条 许可范围</strong></p>
                    <p>本软件仅供企业或个人用户在遵守本协议条款的前提下使用。用户可以将本软件部署在自己的服务器上，用于内部系统的单点登录认证。</p>
                    <br>
                    <p><strong>第二条 限制条款</strong></p>
                    <p>1. 不得对本软件进行反向工程、反编译或反汇编。</p>
                    <p>2. 不得将本软件用于任何违法用途。</p>
                    <p>3. 不得移除或修改本软件中的版权标识。</p>
                    <p>4. 未经书面许可，不得将本软件用于商业销售或分发。</p>
                    <br>
                    <p><strong>第三条 免责声明</strong></p>
                    <p>本软件按"原样"提供，不提供任何形式的明示或暗示保证。爱云科技不对因使用本软件而导致的任何直接、间接、附带或后果性损害承担责任。</p>
                    <br>
                    <p><strong>第四条 技术支持</strong></p>
                    <p>免费用户可通过 GitHub Issues 获取社区支持。企业用户可享受专业技术支持服务。</p>
                    <br>
                    <p><strong>第五条 协议终止</strong></p>
                    <p>如用户违反本协议任何条款，爱云科技有权立即终止许可，用户必须停止使用并销毁所有副本。</p>
                    <br>
                    <p><strong>第六条 法律适用</strong></p>
                    <p>本协议受中华人民共和国法律管辖，任何争议应通过友好协商解决，协商不成的，提交爱云科技所在地人民法院诉讼解决。</p>
                </div>
                
                <form method="get">
                    <input type="hidden" name="step" value="2">
                    <div class="btn-group">
                        <button type="submit" class="btn btn-primary" id="agreeBtn" disabled>
                            <input type="checkbox" id="agreeCheck" style="margin-right:8px;" onclick="document.getElementById('agreeBtn').disabled=!this.checked">
                            我已阅读并同意以上条款
                        </button>
                    </div>
                </form>
            </div>
            <?php endif; ?>
            
            <!-- 步骤 2: 环境检测 -->
            <?php if ($step == 2): ?>
            <div class="step-content active">
                <div class="environment-check" id="envCheck">
                    <p style="text-align:center;color:#6b7280;">正在检测服务器环境...</p>
                </div>
                
                <form method="get" id="envForm" style="display:none;">
                    <input type="hidden" name="step" value="3">
                    <div class="btn-group">
                        <a href="?step=1" class="btn btn-secondary">上一步</a>
                        <button type="submit" class="btn btn-primary" id="envNextBtn" disabled>下一步</button>
                    </div>
                </form>
                
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    fetch('', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: 'action=check_environment'
                    })
                    .then(res => res.json())
                    .then(data => {
                        const container = document.getElementById('envCheck');
                        let html = '';
                        
                        data.checks.forEach(check => {
                            const statusClass = check.pass ? 'pass' : 'fail';
                            const icon = check.pass ? '✓' : '✗';
                            html += `
                                <div class="check-item">
                                    <span class="check-name">${check.name}</span>
                                    <div class="check-status ${statusClass}">
                                        <span>${icon}</span>
                                        <span>${check.current}</span>
                                    </div>
                                </div>
                            `;
                        });
                        
                        container.innerHTML = html;
                        
                        if (data.success) {
                            document.getElementById('envForm').style.display = 'block';
                            document.getElementById('envNextBtn').disabled = false;
                        } else {
                            container.innerHTML = '<div class="alert alert-error">环境检测未通过，请根据上述提示修复后刷新页面重试。</div>' + container.innerHTML;
                        }
                    });
                });
                </script>
            </div>
            <?php endif; ?>
            
            <!-- 步骤 3: 数据库配置 -->
            <?php if ($step == 3): ?>
            <div class="step-content active">
                <form id="dbForm">
                    <div class="form-group">
                        <label>数据库主机</label>
                        <input type="text" name="db_host" value="localhost" required>
                        <small>MySQL 服务器地址，通常为 localhost</small>
                    </div>
                    
                    <div class="form-group">
                        <label>数据库端口</label>
                        <input type="number" name="db_port" value="3306" required>
                        <small>MySQL 服务器端口，默认为 3306</small>
                    </div>
                    
                    <div class="form-group">
                        <label>数据库名称</label>
                        <input type="text" name="db_name" value="sso_aiyun_top" required>
                        <small>系统将自动创建该数据库（如果不存在）</small>
                    </div>
                    
                    <div class="form-group">
                        <label>数据库用户名</label>
                        <input type="text" name="db_user" value="sso_aiyun_top" required>
                    </div>
                    
                    <div class="form-group">
                        <label>数据库密码</label>
                        <input type="password" name="db_pass" required>
                    </div>
                    
                    <div id="dbTestResult"></div>
                    
                    <div class="btn-group">
                        <a href="?step=2" class="btn btn-secondary">上一步</a>
                        <button type="button" class="btn btn-secondary" onclick="testDbConnection()">测试连接</button>
                        <button type="submit" class="btn btn-primary" disabled id="dbNextBtn">下一步</button>
                    </div>
                </form>
                
                <script>
                function testDbConnection() {
                    const form = document.getElementById('dbForm');
                    const formData = new FormData(form);
                    formData.append('action', 'test_db_connection');
                    
                    document.getElementById('dbTestResult').innerHTML = '<p style="text-align:center;color:#6b7280;">正在测试数据库连接...</p>';
                    
                    fetch('', {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => res.json())
                    .then(data => {
                        const resultDiv = document.getElementById('dbTestResult');
                        if (data.success) {
                            resultDiv.innerHTML = '<div class="alert alert-success">✓ ' + data.message + '</div>';
                            document.getElementById('dbNextBtn').disabled = false;
                        } else {
                            resultDiv.innerHTML = '<div class="alert alert-error">✗ ' + data.message + '</div>';
                            document.getElementById('dbNextBtn').disabled = true;
                        }
                    });
                }
                
                document.getElementById('dbForm').addEventListener('submit', function(e) {
                    e.preventDefault();
                    const formData = new FormData(this);
                    formData.append('action', 'install');
                    
                    const btn = document.getElementById('dbNextBtn');
                    btn.disabled = true;
                    btn.innerHTML = '<span class="loading-spinner"></span> 正在安装...';
                    
                    fetch('', {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            window.location.href = '?step=5';
                        } else {
                            alert('安装失败：' + data.message);
                            btn.disabled = false;
                            btn.innerHTML = '下一步';
                        }
                    });
                });
                </script>
            </div>
            <?php endif; ?>
            
            <!-- 步骤 4: 管理员设置 (已在步骤 3 的表单中合并处理，这里简化) -->
            <?php if ($step == 4): ?>
            <div class="step-content active">
                <div class="alert alert-success">
                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    <span>数据库配置成功！请继续设置管理员账户。</span>
                </div>
                
                <form id="adminForm">
                    <div class="form-group">
                        <label>管理员用户名</label>
                        <input type="text" name="admin_username" value="admin" required>
                        <small>用于登录管理后台的用户名</small>
                    </div>
                    
                    <div class="form-group">
                        <label>管理员密码</label>
                        <input type="password" name="admin_password" required minlength="6">
                        <small>至少 6 个字符，建议使用强密码</small>
                    </div>
                    
                    <div class="form-group">
                        <label>管理员邮箱</label>
                        <input type="email" name="admin_email" required>
                        <small>用于接收系统通知</small>
                    </div>
                    
                    <hr style="margin:30px 0;border:none;border-top:1px solid #e5e7eb;">
                    
                    <h3 style="margin-bottom:20px;color:#1f2937;">站点配置</h3>
                    
                    <div class="form-group">
                        <label>SSO 域名</label>
                        <input type="url" name="sso_domain" value="https://sso.aiyun.top" required>
                    </div>
                    
                    <div class="form-group">
                        <label>CDN 域名</label>
                        <input type="url" name="cdn_base" value="https://cdn.aiyun.top" required>
                    </div>
                    
                    <div class="form-group">
                        <label>微信 AppID</label>
                        <input type="text" name="wx_appid" placeholder="可选，稍后可在后台配置">
                    </div>
                    
                    <div class="form-group">
                        <label>微信 AppSecret</label>
                        <input type="text" name="wx_appsecret" placeholder="可选，稍后可在后台配置">
                    </div>
                    
                    <div class="form-group">
                        <label>短信平台账号</label>
                        <input type="text" name="sms_user" placeholder="可选，稍后可在后台配置">
                    </div>
                    
                    <div class="form-group">
                        <label>短信平台密码</label>
                        <input type="password" name="sms_pass" placeholder="可选，稍后可在后台配置">
                    </div>
                    
                    <input type="hidden" name="db_host" value="<?= htmlspecialchars($_SESSION['db_host'] ?? 'localhost') ?>">
                    <input type="hidden" name="db_port" value="<?= htmlspecialchars($_SESSION['db_port'] ?? '3306') ?>">
                    <input type="hidden" name="db_name" value="<?= htmlspecialchars($_SESSION['db_name'] ?? '') ?>">
                    <input type="hidden" name="db_user" value="<?= htmlspecialchars($_SESSION['db_user'] ?? '') ?>">
                    <input type="hidden" name="db_pass" value="<?= htmlspecialchars($_SESSION['db_pass'] ?? '') ?>">
                    
                    <div class="btn-group">
                        <a href="?step=2" class="btn btn-secondary">上一步</a>
                        <button type="submit" class="btn btn-primary">开始安装</button>
                    </div>
                </form>
                
                <script>
                // 从上一个表单继承数据
                document.addEventListener('DOMContentLoaded', function() {
                    const prevForm = document.querySelector('#dbForm');
                    if (prevForm) {
                        const formData = new FormData(prevForm);
                        for (let [key, value] of formData.entries()) {
                            const input = document.querySelector(`[name="${key}"]`);
                            if (input && !input.hasAttribute('type') || input.type !== 'password') {
                                input.value = value;
                            }
                        }
                    }
                });
                
                document.getElementById('adminForm').addEventListener('submit', function(e) {
                    e.preventDefault();
                    const formData = new FormData(this);
                    formData.append('action', 'install');
                    
                    const btn = this.querySelector('button[type="submit"]');
                    btn.disabled = true;
                    btn.innerHTML = '<span class="loading-spinner"></span> 正在安装...';
                    
                    fetch('', {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            window.location.href = '?step=5';
                        } else {
                            alert('安装失败：' + data.message);
                            btn.disabled = false;
                            btn.innerHTML = '开始安装';
                        }
                    });
                });
                </script>
            </div>
            <?php endif; ?>
            
            <!-- 步骤 5: 完成安装 -->
            <?php if ($step == 5): ?>
            <div class="step-content active">
                <?php if (isset($_SESSION['install_complete']) && $_SESSION['install_complete']): ?>
                <div class="success-card">
                    <div class="success-icon">✓</div>
                    <h2>安装成功！</h2>
                    <p>爱云科技 SSO 统一认证系统已成功安装到您的服务器。</p>
                    
                    <div class="info-grid">
                        <div class="info-card">
                            <label>管理员账号</label>
                            <value><?= htmlspecialchars($_SESSION['admin_username']) ?></value>
                        </div>
                        <div class="info-card">
                            <label>系统域名</label>
                            <value><?= htmlspecialchars($_SESSION['sso_domain']) ?></value>
                        </div>
                    </div>
                    
                    <div class="alert alert-warning">
                        <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                        <span>为了安全，安装程序已被自动锁定。如需重新安装，请手动删除 install/install.lock 文件。</span>
                    </div>
                    
                    <div class="btn-group" style="justify-content:center;">
                        <a href="../index.php" class="btn btn-primary">前往登录页</a>
                        <a href="../admin/" class="btn btn-secondary">进入管理后台</a>
                    </div>
                </div>
                <?php else: ?>
                <div class="alert alert-error">
                    未找到安装信息，请返回重新安装。
                </div>
                <div class="btn-group">
                    <a href="?step=1" class="btn btn-primary">重新开始</a>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
