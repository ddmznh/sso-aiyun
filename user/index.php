<?php
/**
 * 用户中心 - 个人中心
 */
define('SSO_ROOT', dirname(__DIR__) . '/');
require_once SSO_ROOT . 'includes/config.php';
require_once SSO_ROOT . 'includes/classes/Database.php';
require_once SSO_ROOT . 'includes/classes/UserService.php';
require_once SSO_ROOT . 'includes/middleware.php';

$userService = new UserService();
$pageTitle = '个人中心';

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfPost();
    
    $nickname = sanitizeInput($_POST['nickname'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    
    if (empty($nickname)) {
        setFlashMessage('error', '昵称不能为空');
    } else {
        $result = $userService->updateProfile($_SESSION['user_id'], [
            'nickname' => $nickname,
            'email' => $email
        ]);
        
        if ($result) {
            setFlashMessage('success', '个人资料更新成功');
        } else {
            setFlashMessage('error', '更新失败，请重试');
        }
    }
}

$user = $userService->getById($_SESSION['user_id']);
require_once SSO_ROOT . 'user/header.php';
?>

<div class="card">
    <h2 style="margin-bottom: 20px; font-size: 18px;">👤 个人信息</h2>
    <form method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
        
        <div class="form-group">
            <label>用户名</label>
            <input type="text" class="form-control" value="<?= e($user['username']) ?>" disabled>
            <small style="color: #6b7280;">用户名不可修改</small>
        </div>
        
        <div class="form-group">
            <label>昵称</label>
            <input type="text" name="nickname" class="form-control" value="<?= e($user['nickname']) ?>" required>
        </div>
        
        <div class="form-group">
            <label>邮箱</label>
            <input type="email" name="email" class="form-control" value="<?= e($user['email']) ?>">
        </div>
        
        <div class="form-group">
            <label>角色</label>
            <input type="text" class="form-control" value="<?= e($user['role']) ?>" disabled>
        </div>
        
        <div class="form-group">
            <label>注册时间</label>
            <input type="text" class="form-control" value="<?= formatTime($user['created_at']) ?>" disabled>
        </div>
        
        <button type="submit" class="btn btn-primary">保存修改</button>
    </form>
</div>

<div class="card">
    <h3 style="margin-bottom: 16px; font-size: 16px;">🔗 账号绑定</h3>
    <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #e5e7eb;">
        <div>
            <strong>微信账号</strong>
            <div style="color: #6b7280; font-size: 14px; margin-top: 4px;">
                <?= $user['wechat_openid'] ? '已绑定' : '未绑定' ?>
            </div>
        </div>
        <a href="<?= SSO_DOMAIN ?>/user/security.php" class="btn btn-primary">
            <?= $user['wechat_openid'] ? '解绑' : '绑定' ?>
        </a>
    </div>
</div>

<?php require_once SSO_ROOT . 'user/footer.php'; ?>
