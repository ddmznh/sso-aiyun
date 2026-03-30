<?php
/**
 * 用户中心 - 授权管理
 */
define('SSO_ROOT', dirname(__DIR__) . '/');
require_once SSO_ROOT . 'includes/config.php';
require_once SSO_ROOT . 'includes/classes/Database.php';
require_once SSO_ROOT . 'includes/classes/UserService.php';
require_once SSO_ROOT . 'includes/middleware.php';

$db = Database::getInstance();
$userService = new UserService();
$pageTitle = '授权管理';

// 处理取消授权
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['client_id'])) {
    verifyCsrfPost();
    
    $clientId = $_POST['client_id'];
    $userId = $_SESSION['user_id'];
    
    $stmt = $db->prepare("UPDATE sso_authorizations SET revoked_at = NOW() WHERE user_id = ? AND client_id = ?");
    $stmt->execute([$userId, $clientId]);
    
    setFlashMessage('success', '已取消对该应用的授权');
}

// 获取用户的授权列表
$userId = $_SESSION['user_id'];
$stmt = $db->prepare("
    SELECT a.*, c.client_name, c.logo_url, c.homepage 
    FROM sso_authorizations a
    JOIN sso_clients c ON a.client_id = c.client_id
    WHERE a.user_id = ? AND a.revoked_at IS NULL
    ORDER BY a.created_at DESC
");
$stmt->execute([$userId]);
$authorizations = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once SSO_ROOT . 'user/header.php';
?>

<div class="card">
    <h2 style="margin-bottom: 20px; font-size: 18px;">🔐 已授权的应用</h2>
    
    <?php if (empty($authorizations)): ?>
        <div style="padding: 32px; text-align: center; color: #6b7280;">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin: 0 auto 16px; opacity: 0.5;">
                <circle cx="12" cy="12" r="10"/>
                <line x1="12" y1="8" x2="12" y2="12"/>
                <line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            <p>暂无授权的应用</p>
            <p style="font-size: 14px; margin-top: 8px;">当您登录第三方应用时，授权记录将显示在这里</p>
        </div>
    <?php else: ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>应用名称</th>
                        <th>授权时间</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($authorizations as $auth): ?>
                    <tr>
                        <td>
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <?php if ($auth['logo_url']): ?>
                                    <img src="<?= e($auth['logo_url']) ?>" alt="" style="width: 32px; height: 32px; border-radius: 6px;">
                                <?php endif; ?>
                                <strong><?= e($auth['client_name']) ?></strong>
                            </div>
                        </td>
                        <td><?= formatTime($auth['created_at']) ?></td>
                        <td>
                            <form method="POST" action="" style="display: inline;" onsubmit="return confirm('确定要取消对该应用的授权吗？')">
                                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                <input type="hidden" name="client_id" value="<?= e($auth['client_id']) ?>">
                                <button type="submit" class="btn btn-danger" style="padding: 6px 12px; font-size: 14px;">取消授权</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once SSO_ROOT . 'user/footer.php'; ?>
