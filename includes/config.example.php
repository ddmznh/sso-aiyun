<?php
/**
 * 爱云科技 SSO 统一认证系统 - 配置文件模板
 * 
 * @package SSO_Aiyun
 * @version 1.0.0
 * 
 * 使用说明：
 * 1. 复制此文件为 config.php
 * 2. 修改下面的配置项为实际值
 * 3. 切勿将 config.php 提交到公共仓库
 */

// 防止直接访问
if (!defined('SSO_SYSTEM')) {
    exit('Direct access not allowed');
}

// ===========================
// 数据库配置
// ===========================
define('DB_HOST', 'localhost');
define('DB_NAME', 'sso_aiyun_top');
define('DB_USER', '数据库用户名');
define('DB_PASS', '数据库密码');
define('DB_CHARSET', 'utf8mb4');

// ===========================
// 微信开放平台配置
// ===========================
define('WX_APPID', '微信 APPID');
define('WX_APPSECRET', '微信 APPSECRET');
define('WX_REDIRECT_URI', 'https://sso.aiyun.top/wechat/callback.php');

// ===========================
// 平台配置
// ===========================
define('SSO_DOMAIN', 'https://sso.aiyun.top');
define('TOKEN_EXPIRE_MINUTES', 5); // Token 有效期（分钟）
define('CDN_BASE', 'https://cdn.aiyun.top');

// ===========================
// 短信平台配置（全网智能通讯平台）
// ===========================
$config['sms_user'] = '短信平台账号';      // 企业登录名
$config['sms_pass'] = '短信平台密码';      // 企业账号密码
$config['sms_fee_type'] = '2';            // 计费套餐：2-行业套餐，3-政务套餐

// ===========================
// 安全配置
// ===========================
define('MAX_LOGIN_ATTEMPTS', 5); // 最大登录失败次数
define('LOCKOUT_MINUTES', 15);   // 锁定时间（分钟）

// ===========================
// 允许重定向的域名白名单
// ===========================
$ALLOWED_REDIRECT_DOMAINS = [
    'news.aiyun.top',
    'aiyunkeji.com',
    '8881314.com',
    'xuyong.vip',
];

// ===========================
// 会话配置
// ===========================
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.use_strict_mode', 1);
ini_set('session.gc_maxlifetime', TOKEN_EXPIRE_MINUTES * 60);

// 启动 Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ===========================
// 时区设置
// ===========================
date_default_timezone_set('Asia/Shanghai');

// ===========================
// 错误报告（生产环境应关闭）
// ===========================
error_reporting(E_ALL);
ini_set('display_errors', 0); // 生产环境设为 0
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/error.log');

// ===========================
// 全局常量
// ===========================
define('SSO_VERSION', '1.0.0');
define('SSO_ROOT', __DIR__);
define('SSO_URL', SSO_DOMAIN);
