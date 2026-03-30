<?php
/**
 * 管理后台 - 驾驶舱
 */

define('SSO_ROOT', dirname(__DIR__) . '/');
require_once SSO_ROOT . 'includes/config.php';
require_once SSO_ROOT . 'includes/classes/Database.php';
require_once SSO_ROOT . 'includes/classes/UserService.php';
require_once SSO_ROOT . 'includes/classes/LogService.php';
require_once SSO_ROOT . 'includes/middleware.php';

$pageTitle = '驾驶舱';
require_once SSO_ROOT . 'admin/header.php';

$db = Database::getInstance();
$logService = new LogService();
$userService = new UserService();

// 统计数据
$todayStart = date('Y-m-d 00:00:00');
$totalUsers = $userService->getTotalCount();
$todayActiveUsers = $logService->getTodayActiveUsers();
$todayLogins = $logService->getLoginCountByDate(date('Y-m-d'));
$failedLogins = $logService->getFailedLoginCount($todayStart);

// 登录方式统计
$loginTypeStats = $logService->getLoginTypeStats($todayStart);
$wechatLogins = 0;
$passwordLogins = 0;
foreach ($loginTypeStats as $stat) {
    if ($stat['login_type'] === 'wechat') {
        $wechatLogins = $stat['count'];
    } elseif ($stat['login_type'] === 'password') {
        $passwordLogins = $stat['count'];
    }
}
$totalTodayLogins = $wechatLogins + $passwordLogins;
$wechatPercent = $totalTodayLogins > 0 ? round(($wechatLogins / $totalTodayLogins) * 100) : 0;
$passwordPercent = $totalTodayLogins > 0 ? round(($passwordLogins / $totalTodayLogins) * 100) : 0;

// 站点登录分布
$siteStats = $logService->getSiteLoginStats($todayStart);

// 最近日志
$recentLogs = $logService->getRecentLogs(10);
?>

<div class="content-card">
    <h2 style="margin-bottom: 20px; font-size: 18px;">📊 今日概览</h2>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
        <div style="padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 12px; color: white;">
            <div style="font-size: 14px; opacity: 0.9;">今日活跃用户</div>
            <div style="font-size: 32px; font-weight: 700; margin-top: 8px;"><?= $todayActiveUsers ?></div>
        </div>
        <div style="padding: 20px; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); border-radius: 12px; color: white;">
            <div style="font-size: 14px; opacity: 0.9;">今日登录次数</div>
            <div style="font-size: 32px; font-weight: 700; margin-top: 8px;"><?= $todayLogins ?></div>
        </div>
        <div style="padding: 20px; background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); border-radius: 12px; color: white;">
            <div style="font-size: 14px; opacity: 0.9;">总用户数</div>
            <div style="font-size: 32px; font-weight: 700; margin-top: 8px;"><?= $totalUsers ?></div>
        </div>
        <div style="padding: 20px; background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); border-radius: 12px; color: white;">
            <div style="font-size: 14px; opacity: 0.9;">失败尝试</div>
            <div style="font-size: 32px; font-weight: 700; margin-top: 8px;"><?= $failedLogins ?></div>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px;">
    <div class="content-card">
        <h3 style="margin-bottom: 16px; font-size: 16px;">📈 登录方式占比</h3>
        <div style="margin-bottom: 16px;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                <span>微信扫码</span>
                <span><?= $wechatPercent ?>%</span>
            </div>
            <div style="background: #e5e7eb; height: 8px; border-radius: 4px; overflow: hidden;">
                <div style="background: #10b981; width: <?= $wechatPercent ?>%; height: 100%; transition: width 0.3s;"></div>
            </div>
        </div>
        <div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                <span>账号密码</span>
                <span><?= $passwordPercent ?>%</span>
            </div>
            <div style="background: #e5e7eb; height: 8px; border-radius: 4px; overflow: hidden;">
                <div style="background: #4f46e5; width: <?= $passwordPercent ?>%; height: 100%; transition: width 0.3s;"></div>
            </div>
        </div>
    </div>
    
    <div class="content-card">
        <h3 style="margin-bottom: 16px; font-size: 16px;">⚠️ 安全告警</h3>
        <?php if ($failedLogins > 10): ?>
            <div style="padding: 12px; background: #fee2e2; border: 1px solid #fecaca; border-radius: 8px; color: #991b1b;">
                <strong>警告：</strong>今日失败登录尝试超过 <?= $failedLogins ?> 次，可能存在暴力破解风险。
            </div>
        <?php else: ?>
            <div style="padding: 12px; background: #d1fae5; border: 1px solid #a7f3d0; border-radius: 8px; color: #065f46;">
                ✅ 系统运行正常，无异常登录行为。
            </div>
        <?php endif; ?>
        
        <h4 style="margin: 20px 0 12px; font-size: 14px; color: var(--text-secondary);">接入站点状态</h4>
        <?php foreach ($siteStats as $stat): ?>
            <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f3f4f6;">
                <span><?= e($stat['client_name']) ?></span>
                <span class="badge badge-success"><?= $stat['count'] ?> 次登录</span>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="content-card">
    <h3 style="margin-bottom: 16px; font-size: 16px;">📜 最近登录记录</h3>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>用户</th>
                    <th>登录方式</th>
                    <th>站点</th>
                    <th>IP 地址</th>
                    <th>时间</th>
                    <th>状态</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentLogs as $log): ?>
                <tr>
                    <td><?= e($log['username'] ?? '未知') ?></td>
                    <td>
                        <span class="badge <?= $log['login_type'] === 'wechat' ? 'badge-success' : 'badge-warning' ?>">
                            <?= $log['login_type'] === 'wechat' ? '微信' : '密码' ?>
                        </span>
                    </td>
                    <td><?= e($log['client_name'] ?? 'SSO 中心') ?></td>
                    <td><?= e($log['ip']) ?></td>
                    <td><?= timeAgo($log['created_at']) ?></td>
                    <td>
                        <span class="badge <?= $log['status'] == 1 ? 'badge-success' : 'badge-danger' ?>">
                            <?= $log['status'] == 1 ? '成功' : '失败' ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div style="margin-top: 16px; text-align: center;">
        <a href="<?= SSO_DOMAIN ?>/admin/logs.php" class="btn btn-primary">查看全部日志</a>
    </div>
</div>

<?php require_once SSO_ROOT . 'admin/footer.php'; ?>
