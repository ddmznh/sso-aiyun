<?php
/**
 * 用户中心 - 头部模板
 */
if (!defined('SSO_ROOT')) {
    exit('Access Denied');
}

requireLogin();

$currentUser = $userService->getById($_SESSION['user_id']);
$pageTitle = $pageTitle ?? '个人中心';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> - 爱云科技 SSO</title>
    <link rel="stylesheet" href="<?= CDN_BASE ?>/css/style.css">
    <style>
        :root {
            --primary-color: #4f46e5;
            --primary-hover: #4338ca;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --bg-color: #f9fafb;
            --card-bg: #ffffff;
            --text-primary: #1f2937;
            --border-color: #e5e7eb;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--bg-color);
            color: var(--text-primary);
            min-height: 100vh;
        }
        
        .navbar {
            background: var(--card-bg);
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            padding: 16px 24px;
        }
        
        .navbar-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .brand {
            font-size: 20px;
            font-weight: 700;
            color: var(--primary-color);
            text-decoration: none;
        }
        
        .nav-links {
            display: flex;
            gap: 24px;
            align-items: center;
        }
        
        .nav-links a {
            color: var(--text-primary);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }
        
        .nav-links a:hover, .nav-links a.active {
            color: var(--primary-color);
        }
        
        .container {
            max-width: 1000px;
            margin: 32px auto;
            padding: 0 24px;
        }
        
        .card {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 24px;
        }
        
        .flash-message {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 16px;
        }
        
        .flash-success {
            background: #d1fae5;
            color: #065f46;
        }
        
        .flash-error {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 500;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--primary-hover);
        }
        
        .btn-danger {
            background: var(--danger-color);
            color: white;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 15px;
        }
        
        .table-container {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 12px 16px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        
        th {
            background: #f9fafb;
            font-weight: 600;
            color: #6b7280;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .badge-success { background: #d1fae5; color: #065f46; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-content">
            <a href="<?= SSO_DOMAIN ?>" class="brand">🔐 爱云科技 SSO</a>
            <div class="nav-links">
                <a href="<?= SSO_DOMAIN ?>/user/" class="<?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">个人中心</a>
                <a href="<?= SSO_DOMAIN ?>/user/security.php" class="<?= basename($_SERVER['PHP_SELF']) == 'security.php' ? 'active' : '' ?>">安全设置</a>
                <a href="<?= SSO_DOMAIN ?>/user/authorizations.php" class="<?= basename($_SERVER['PHP_SELF']) == 'authorizations.php' ? 'active' : '' ?>">授权管理</a>
                <span style="color: var(--border-color);">|</span>
                <span><?= e($currentUser['nickname'] ?? $currentUser['username']) ?></span>
                <a href="<?= SSO_DOMAIN ?>/logout.php" class="btn btn-danger" style="padding: 6px 12px;">退出</a>
            </div>
        </div>
    </nav>
    
    <div class="container">
        <?php if ($successMsg = getFlashMessage('success')): ?>
            <div class="flash-message flash-success"><?= e($successMsg) ?></div>
        <?php endif; ?>
        
        <?php if ($errorMsg = getFlashMessage('error')): ?>
            <div class="flash-message flash-error"><?= e($errorMsg) ?></div>
        <?php endif; ?>
