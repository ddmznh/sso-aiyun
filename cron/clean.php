<?php
/**
 * 定时清理脚本
 * 清理过期的 Token、日志和验证码
 * 建议配置 Crontab: 0 2 * * * php /path/to/cron/clean.php
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/classes/Database.php';

$db = Database::getInstance();

echo "=== SSO 系统数据清理任务 ===\n";
echo "开始时间：" . date('Y-m-d H:i:s') . "\n\n";

// 1. 清理过期的 access_token
$stmt = $db->exec("DELETE FROM sso_tokens WHERE expire_at < NOW()");
echo "✓ 清理过期 Access Token: {$stmt} 条\n";

// 2. 清理过期的 refresh_token
$stmt = $db->exec("DELETE FROM sso_refresh_tokens WHERE expire_at < NOW()");
echo "✓ 清理过期 Refresh Token: {$stmt} 条\n";

// 3. 清理过期的授权码
$stmt = $db->exec("DELETE FROM sso_auth_codes WHERE expire_at < NOW() OR used = 1");
echo "✓ 清理已使用/过期授权码：{$stmt} 条\n";

// 4. 清理 90 天前的登录日志
$stmt = $db->exec("DELETE FROM sso_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)");
echo "✓ 清理 90 天前日志：{$stmt} 条\n";

// 5. 清理过期的短信验证码
$stmt = $db->exec("DELETE FROM sms_codes WHERE expire_at < NOW() OR used = 1");
echo "✓ 清理过期短信验证码：{$stmt} 条\n";

// 6. 清理 IP 黑名单中过期的记录 (30 天自动解封)
$stmt = $db->exec("DELETE FROM ip_blacklist WHERE expire_at < NOW()");
echo "✓ 清理过期 IP 黑名单：{$stmt} 条\n";

echo "\n完成时间：" . date('Y-m-d H:i:s') . "\n";
echo "=== 清理任务完成 ===\n";
