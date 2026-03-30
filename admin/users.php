<?php
/**
 * 管理后台 - 用户管理
 */
define('SSO_ROOT', dirname(__DIR__) . '/');
require_once SSO_ROOT . 'includes/config.php';
require_once SSO_ROOT . 'includes/classes/Database.php';
require_once SSO_ROOT . 'includes/classes/UserService.php';
require_once SSO_ROOT . 'includes/middleware.php';

$userService = new UserService();
$db = Database::getInstance();
$pageTitle = '用户管理';

// 处理操作
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfPost();
    
    $action = $_POST['action'] ?? '';
    $userId = $_POST['user_id'] ?? 0;
    
    if ($action === 'toggle_status' && $userId) {
        $user = $userService->getById($userId);
        if ($user) {
            $newStatus = $user['status'] == 1 ? 0 : 1;
            $userService->update($userId, ['status' => $newStatus]);
            setFlashMessage('success', '用户状态已更新');
        }
    } elseif ($action === 'reset_password' && $userId) {
        $newPassword = generateRandomString(8);
        $userService->resetPassword($userId, $newPassword);
        setFlashMessage('success', '密码已重置为：' . $newPassword);
    } elseif ($action === 'delete' && $userId) {
        if ($userId != $_SESSION['user_id']) {
            $userService->delete($userId);
            setFlashMessage('success', '用户已删除');
        } else {
            setFlashMessage('error', '不能删除自己');
        }
    }
}

// 搜索和分页
$search = sanitizeInput($_GET['search'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;

$users = $userService->getAll($search, $page, $perPage);
$totalUsers = $userService->getTotalCount($search);
$totalPages = ceil($totalUsers / $perPage);

require_once SSO_ROOT . 'admin/header.php';
?>

<div class="content-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <form method="GET" action="" style="display: flex; gap: 12px;">
            <input type="text" name="search" placeholder="搜索用户名/昵称/邮箱" value="<?= e($search) ?>" class="form-control" style="width: 300px;">
            <button type="submit" class="btn btn-primary">搜索</button>
        </form>
    </div>
    
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>用户名</th>
                    <th>昵称</th>
                    <th>邮箱</th>
                    <th>角色</th>
                    <th>微信绑定</th>
                    <th>状态</th>
                    <th>注册时间</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= $user['id'] ?></td>
                    <td><?= e($user['username']) ?></td>
                    <td><?= e($user['nickname']) ?></td>
                    <td><?= e($user['email']) ?></td>
                    <td><span class="badge badge-<?= $user['role'] === 'admin' ? 'danger' : 'success' ?>"><?= e($user['role']) ?></span></td>
                    <td><?= $user['wechat_openid'] ? '✅' : '-' ?></td>
                    <td><span class="badge badge-<?= $user['status'] == 1 ? 'success' : 'danger' ?>"><?= $user['status'] == 1 ? '正常' : '禁用' ?></span></td>
                    <td><?= formatTime($user['created_at'], 'Y-m-d') ?></td>
                    <td>
                        <form method="POST" action="" style="display: inline;">
                            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                            <input type="hidden" name="action" value="toggle_status">
                            <button type="submit" class="btn" style="padding: 4px 8px; font-size: 12px; background: <?= $user['status'] == 1 ? '#f59e0b' : '#10b981' ?>; color: white;">
                                <?= $user['status'] == 1 ? '禁用' : '启用' ?>
                            </button>
                        </form>
                        <form method="POST" action="" style="display: inline;">
                            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                            <input type="hidden" name="action" value="reset_password">
                            <button type="submit" class="btn" style="padding: 4px 8px; font-size: 12px; background: #4f46e5; color: white;" onclick="return confirm('确定要重置该用户密码吗？')">重置密码</button>
                        </form>
                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                        <form method="POST" action="" style="display: inline;" onsubmit="return confirm('确定要删除该用户吗？')">
                            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                            <input type="hidden" name="action" value="delete">
                            <button type="submit" class="btn btn-danger" style="padding: 4px 8px; font-size: 12px;">删除</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <?php if ($totalPages > 1): ?>
    <div style="margin-top: 20px; display: flex; justify-content: center; gap: 8px;">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>" class="btn" style="padding: 8px 16px; background: <?= $i === $page ? '#4f46e5' : '#f3f4f6' ?>; color: <?= $i === $page ? 'white' : '#1f2937' ?>;"><?= $i ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>

<?php require_once SSO_ROOT . 'admin/footer.php'; ?>
