<?php
/**
 * 用户中心 - 安全设置
 */
define('SSO_ROOT', dirname(__DIR__) . '/');
require_once SSO_ROOT . 'includes/config.php';
require_once SSO_ROOT . 'includes/classes/Database.php';
require_once SSO_ROOT . 'includes/classes/UserService.php';
require_once SSO_ROOT . 'includes/middleware.php';

$userService = new UserService();
$pageTitle = '安全设置';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfPost();
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'change_password') {
        $oldPassword = $_POST['old_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if (empty($oldPassword) || empty($newPassword)) {
            setFlashMessage('error', '请填写完整密码信息');
        } elseif ($newPassword !== $confirmPassword) {
            setFlashMessage('error', '两次输入的新密码不一致');
        } else {
            $result = $userService->changePassword($_SESSION['user_id'], $oldPassword, $newPassword);
            
            if ($result === true) {
                setFlashMessage('success', '密码修改成功');
            } elseif ($result === 'invalid_old') {
                setFlashMessage('error', '原密码错误');
            } else {
                setFlashMessage('error', '修改失败，请重试');
            }
        }
    } elseif ($action === 'unbind_wechat') {
        $result = $userService->unbindWechat($_SESSION['user_id']);
        if ($result) {
            setFlashMessage('success', '微信账号已解绑');
        } else {
            setFlashMessage('error', '解绑失败');
        }
    }
}

$user = $userService->getById($_SESSION['user_id']);
require_once SSO_ROOT . 'user/header.php';
?>

<div class="card">
    <h2 style="margin-bottom: 20px; font-size: 18px;">🔐 修改密码</h2>
    <form method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
        <input type="hidden" name="action" value="change_password">
        
        <div class="form-group">
            <label>当前密码</label>
            <input type="password" name="old_password" class="form-control" required>
        </div>
        
        <div class="form-group">
            <label>新密码</label>
            <input type="password" name="new_password" class="form-control" required minlength="6">
        </div>
        
        <div class="form-group">
            <label>确认新密码</label>
            <input type="password" name="confirm_password" class="form-control" required minlength="6">
        </div>
        
        <button type="submit" class="btn btn-primary">修改密码</button>
    </form>
</div>

<div class="card">
    <h2 style="margin-bottom: 20px; font-size: 18px;">💬 微信绑定</h2>
    <?php if ($user['wechat_openid']): ?>
        <div style="padding: 16px; background: #d1fae5; border-radius: 8px; margin-bottom: 16px;">
            ✅ 已绑定微信账号
        </div>
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            <input type="hidden" name="action" value="unbind_wechat">
            <button type="submit" class="btn btn-danger" onclick="return confirm('确定要解绑微信账号吗？')">解绑微信</button>
        </form>
    <?php else: ?>
        <div style="padding: 16px; background: #fef3c7; border-radius: 8px; margin-bottom: 16px;">
            ⚠️ 尚未绑定微信账号，绑定后可使用微信扫码登录
        </div>
        <a href="<?= $wechatService->getAuthUrl() ?>" class="btn btn-primary">
            绑定微信账号
        </a>
    <?php endif; ?>
</div>

<?php require_once SSO_ROOT . 'user/footer.php'; ?>
