<?php
/**
 * 管理后台 - 站点管理
 */
define('SSO_ROOT', dirname(__DIR__) . '/');
require_once SSO_ROOT . 'includes/config.php';
require_once SSO_ROOT . 'includes/classes/Database.php';
require_once SSO_ROOT . 'includes/middleware.php';

$db = Database::getInstance();
$pageTitle = '站点管理';

// 处理操作
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfPost();
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $clientName = sanitizeInput($_POST['client_name'] ?? '');
        $homepage = sanitizeInput($_POST['homepage'] ?? '');
        $redirectUris = sanitizeInput($_POST['redirect_uris'] ?? '');
        
        if ($clientName) {
            $clientId = 'client_' . generateRandomString(16);
            $clientSecret = generateRandomString(32);
            
            $stmt = $db->prepare("INSERT INTO sso_clients (client_id, client_secret, client_name, homepage, redirect_uris) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$clientId, $clientSecret, $clientName, $homepage, $redirectUris]);
            
            setFlashMessage('success', '站点添加成功，Client ID: ' . $clientId);
        }
    } elseif ($action === 'toggle_status') {
        $clientId = $_POST['client_id'] ?? '';
        $stmt = $db->prepare("UPDATE sso_clients SET status = NOT status WHERE client_id = ?");
        $stmt->execute([$clientId]);
        setFlashMessage('success', '站点状态已更新');
    }
}

// 获取所有站点
$stmt = $db->query("SELECT * FROM sso_clients ORDER BY created_at DESC");
$sites = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once SSO_ROOT . 'admin/header.php';
?>

<div class="content-card">
    <h3 style="margin-bottom: 20px; font-size: 16px;">➕ 添加新站点</h3>
    <form method="POST" action="" style="display: grid; gap: 16px; max-width: 600px;">
        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
        <input type="hidden" name="action" value="add">
        
        <div>
            <label style="display: block; margin-bottom: 8px; font-weight: 500;">站点名称 *</label>
            <input type="text" name="client_name" class="form-control" required placeholder="例如：爱云资讯">
        </div>
        
        <div>
            <label style="display: block; margin-bottom: 8px; font-weight: 500;">官方网站</label>
            <input type="url" name="homepage" class="form-control" placeholder="https://example.com">
        </div>
        
        <div>
            <label style="display: block; margin-bottom: 8px; font-weight: 500;">回调地址（多个用逗号分隔）*</label>
            <input type="text" name="redirect_uris" class="form-control" required placeholder="https://news.aiyun.top/auth/callback">
        </div>
        
        <button type="submit" class="btn btn-primary">添加站点</button>
    </form>
</div>

<div class="content-card">
    <h3 style="margin-bottom: 20px; font-size: 16px;">📋 接入站点列表</h3>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>站点名称</th>
                    <th>Client ID</th>
                    <th>Client Secret</th>
                    <th>回调地址</th>
                    <th>状态</th>
                    <th>创建时间</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sites as $site): ?>
                <tr>
                    <td><strong><?= e($site['client_name']) ?></strong></td>
                    <td><code style="background: #f3f4f6; padding: 2px 6px; border-radius: 4px; font-size: 12px;"><?= e($site['client_id']) ?></code></td>
                    <td><code style="background: #f3f4f6; padding: 2px 6px; border-radius: 4px; font-size: 12px;"><?= e($site['client_secret']) ?></code></td>
                    <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?= e($site['redirect_uris']) ?></td>
                    <td><span class="badge badge-<?= $site['status'] == 1 ? 'success' : 'danger' ?>"><?= $site['status'] == 1 ? '启用' : '禁用' ?></span></td>
                    <td><?= formatTime($site['created_at'], 'Y-m-d') ?></td>
                    <td>
                        <form method="POST" action="" style="display: inline;">
                            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                            <input type="hidden" name="client_id" value="<?= e($site['client_id']) ?>">
                            <input type="hidden" name="action" value="toggle_status">
                            <button type="submit" class="btn" style="padding: 4px 8px; font-size: 12px; background: <?= $site['status'] == 1 ? '#f59e0b' : '#10b981' ?>; color: white;">
                                <?= $site['status'] == 1 ? '禁用' : '启用' ?>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once SSO_ROOT . 'admin/footer.php'; ?>
