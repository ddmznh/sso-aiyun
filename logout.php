<?php
/**
 * 登出接口
 */
define('SSO_ROOT', __DIR__ . '/');
require_once SSO_ROOT . 'includes/config.php';
require_once SSO_ROOT . 'includes/classes/Database.php';
require_once SSO_ROOT . 'includes/classes/TokenService.php';

// 销毁 Session
session_start();
session_destroy();

// 清除 Cookie
setcookie(session_name(), '', time() - 3600, '/', '', false, true);

// 重定向
$redirect = $_GET['redirect'] ?? '';
if (isValidRedirectUrl($redirect)) {
    header('Location: ' . $redirect);
} else {
    header('Location: ' . SSO_DOMAIN);
}
exit;
