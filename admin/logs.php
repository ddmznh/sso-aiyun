<?php
/**
 * 管理后台 - 日志管理
 */
define('SSO_ROOT', dirname(__DIR__) . '/');
require_once SSO_ROOT . 'includes/config.php';
require_once SSO_ROOT . 'includes/classes/Database.php';
require_once SSO_ROOT . 'includes/classes/LogService.php';
require_once SSO_ROOT . 'includes/middleware.php';

$logService = new LogService();
$db = Database::getInstance();
$pageTitle = '操作日志';

// 导出 CSV
if (isset($_GET['export'])) {
    $logs = $logService->getAllLogs(null, null, null, null, null, null, 10000);
    exportCsv('login_logs_' . date('YmdHis') . '.csv', 
        ['ID', '用户名', '登录方式', '站点', 'IP 地址', '状态', '错误信息', '时间'],
        array_map(function($log) {
            return [
                $log['id'],
                $log['username'] ?? '未知',
                $log['login_type'],
                $log['client_name'] ?? 'SSO 中心',
                $log['ip'],
                $log['status'] == 1 ? '成功' : '失败',
                $log['error_msg'] ?? '',
                formatTime($log['created_at'])
            ];
        }, $logs)
    );
}

// 筛选条件
$ipSearch = sanitizeInput($_GET['ip'] ?? '');
$statusFilter = $_GET['status'] ?? '';
$typeFilter = $_GET['type'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';

$logs = $logService->getAllLogs($ipSearch, $statusFilter, $typeFilter, $dateFrom, $dateTo, null, 50);

require_once SSO_ROOT . 'admin/header.php';
?>

<div class="content-card">
    <form method="GET" action="" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 16px; margin-bottom: 20px;">
        <input type="text" name="ip" placeholder="IP 地址" value="<?= e($ipSearch) ?>" class="form-control">
        <select name="status" class="form-control">
            <option value="">全部状态</option>
            <option value="1" <?= $statusFilter === '1' ? 'selected' : '' ?>>成功</option>
            <option value="0" <?= $statusFilter === '0' ? 'selected' : '' ?>>失败</option>
        </select>
        <select name="type" class="form-control">
            <option value="">全部类型</option>
            <option value="wechat" <?= $typeFilter === 'wechat' ? 'selected' : '' ?>>微信登录</option>
            <option value="password" <?= $typeFilter === 'password' ? 'selected' : '' ?>>密码登录</option>
        </select>
        <input type="date" name="date_from" value="<?= e($dateFrom) ?>" class="form-control">
        <input type="date" name="date_to" value="<?= e($dateTo) ?>" class="form-control">
        <button type="submit" class="btn btn-primary">筛选</button>
        <a href="?export=1" class="btn" style="background: #10b981; color: white;">导出 CSV</a>
    </form>
    
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>用户</th>
                    <th>登录方式</th>
                    <th>站点</th>
                    <th>IP 地址</th>
                    <th>状态</th>
                    <th>时间</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?= $log['id'] ?></td>
                    <td><?= e($log['username'] ?? '未知') ?></td>
                    <td><span class="badge badge-<?= $log['login_type'] === 'wechat' ? 'success' : 'warning' ?>"><?= $log['login_type'] === 'wechat' ? '微信' : '密码' ?></span></td>
                    <td><?= e($log['client_name'] ?? 'SSO 中心') ?></td>
                    <td><?= e($log['ip']) ?></td>
                    <td><span class="badge badge-<?= $log['status'] == 1 ? 'success' : 'danger' ?>"><?= $log['status'] == 1 ? '成功' : '失败' ?></span></td>
                    <td><?= formatTime($log['created_at']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once SSO_ROOT . 'admin/footer.php'; ?>
