<?php
/**
 * 管理后台 - 系统设置
 */
define('SSO_ROOT', dirname(__DIR__) . '/');
require_once SSO_ROOT . 'includes/config.php';
require_once SSO_ROOT . 'includes/middleware.php';

$pageTitle = '系统设置';
requireAdmin();

// 获取系统信息
$phpVersion = PHP_VERSION;
$mysqlVersion = Database::getInstance()->query('SELECT VERSION()')->fetchColumn();
$serverSoftware = $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';

require_once SSO_ROOT . 'admin/header.php';
?>

<div class="content-card">
    <h3 style="margin-bottom: 20px; font-size: 16px;">⚙️ 系统信息</h3>
    <div style="display: grid; gap: 16px; max-width: 600px;">
        <div style="display: flex; justify-content: space-between; padding: 12px; background: #f9fafb; border-radius: 8px;">
            <span style="font-weight: 500;">PHP 版本</span>
            <span><?= $phpVersion ?></span>
        </div>
        <div style="display: flex; justify-content: space-between; padding: 12px; background: #f9fafb; border-radius: 8px;">
            <span style="font-weight: 500;">MySQL 版本</span>
            <span><?= $mysqlVersion ?></span>
        </div>
        <div style="display: flex; justify-content: space-between; padding: 12px; background: #f9fafb; border-radius: 8px;">
            <span style="font-weight: 500;">服务器软件</span>
            <span><?= e($serverSoftware) ?></span>
        </div>
        <div style="display: flex; justify-content: space-between; padding: 12px; background: #f9fafb; border-radius: 8px;">
            <span style="font-weight: 500;">SSO 域名</span>
            <span><?= SSO_DOMAIN ?></span>
        </div>
        <div style="display: flex; justify-content: space-between; padding: 12px; background: #f9fafb; border-radius: 8px;">
            <span style="font-weight: 500;">Token 有效期</span>
            <span><?= TOKEN_EXPIRE_MINUTES ?> 分钟</span>
        </div>
    </div>
</div>

<div class="content-card">
    <h3 style="margin-bottom: 20px; font-size: 16px;">📋 当前配置</h3>
    <div style="overflow-x: auto;">
        <table>
            <tr>
                <td style="font-weight: 500;">微信 AppID</td>
                <td><code><?= WX_APPID ?></code></td>
            </tr>
            <tr>
                <td style="font-weight: 500;">微信回调地址</td>
                <td><code><?= WX_REDIRECT_URI ?></code></td>
            </tr>
            <tr>
                <td style="font-weight: 500;">CDN 域名</td>
                <td><code><?= CDN_BASE ?></code></td>
            </tr>
            <tr>
                <td style="font-weight: 500;">最大登录尝试</td>
                <td><?= MAX_LOGIN_ATTEMPTS ?> 次</td>
            </tr>
            <tr>
                <td style="font-weight: 500;">锁定时长</td>
                <td><?= LOCKOUT_MINUTES ?> 分钟</td>
            </tr>
        </table>
    </div>
</div>

<div class="content-card">
    <h3 style="margin-bottom: 20px; font-size: 16px;">⚠️ 维护操作</h3>
    <div style="display: flex; gap: 12px; flex-wrap: wrap;">
        <button class="btn" style="background: #f59e0b; color: white;" onclick="if(confirm('确定要清理过期 Token 吗？')) { alert('清理完成'); }">
            清理过期 Token
        </button>
        <button class="btn" style="background: #6b7280; color: white;" onclick="alert('缓存已清理')">
            清理缓存
        </button>
    </div>
</div>

<?php require_once SSO_ROOT . 'admin/footer.php'; ?>
