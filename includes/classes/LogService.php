<?php
/**
 * 爱云科技 SSO 统一认证系统 - 日志服务类
 * 
 * @package SSO_Aiyun
 * @version 1.0.0
 */

if (!defined('SSO_SYSTEM')) {
    exit('Direct access not allowed');
}

class LogService {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * 记录登录日志
     */
    public function recordLogin($userId, $clientId, $loginType, $status, $errorMsg = null) {
        $sql = "INSERT INTO sso_logs (user_id, client_id, login_type, status, ip, user_agent, error_msg, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $params = [
            $userId,
            $clientId,
            $loginType,
            $status,
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            $errorMsg
        ];
        
        return $this->db->execute($sql, $params);
    }
    
    /**
     * 获取登录日志（分页）
     */
    public function getLogs($page = 1, $pageSize = 20, $filters = []) {
        $where = [];
        $params = [];
        
        if (!empty($filters['ip'])) {
            $where[] = "ip LIKE ?";
            $params[] = '%' . $filters['ip'] . '%';
        }
        
        if (isset($filters['login_type']) && $filters['login_type'] !== '') {
            $where[] = "login_type = ?";
            $params[] = $filters['login_type'];
        }
        
        if (isset($filters['status']) && $filters['status'] !== '') {
            $where[] = "status = ?";
            $params[] = $filters['status'];
        }
        
        if (isset($filters['client_id']) && $filters['client_id'] !== '') {
            $where[] = "client_id = ?";
            $params[] = $filters['client_id'];
        }
        
        if (!empty($filters['start_date'])) {
            $where[] = "created_at >= ?";
            $params[] = $filters['start_date'] . ' 00:00:00';
        }
        
        if (!empty($filters['end_date'])) {
            $where[] = "created_at <= ?";
            $params[] = $filters['end_date'] . ' 23:59:59';
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $offset = ($page - 1) * $pageSize;
        
        $sql = "SELECT l.*, u.username, u.nickname, c.client_name 
                FROM sso_logs l
                LEFT JOIN users u ON l.user_id = u.id
                LEFT JOIN sso_clients c ON l.client_id = c.client_id
                $whereClause
                ORDER BY l.created_at DESC
                LIMIT ? OFFSET ?";
        
        $params[] = $pageSize;
        $params[] = $offset;
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * 统计日志总数
     */
    public function countLogs($filters = []) {
        $where = [];
        $params = [];
        
        if (!empty($filters['ip'])) {
            $where[] = "ip LIKE ?";
            $params[] = '%' . $filters['ip'] . '%';
        }
        
        if (isset($filters['login_type']) && $filters['login_type'] !== '') {
            $where[] = "login_type = ?";
            $params[] = $filters['login_type'];
        }
        
        if (isset($filters['status']) && $filters['status'] !== '') {
            $where[] = "status = ?";
            $params[] = $filters['status'];
        }
        
        if (isset($filters['client_id']) && $filters['client_id'] !== '') {
            $where[] = "client_id = ?";
            $params[] = $filters['client_id'];
        }
        
        if (!empty($filters['start_date'])) {
            $where[] = "created_at >= ?";
            $params[] = $filters['start_date'] . ' 00:00:00';
        }
        
        if (!empty($filters['end_date'])) {
            $where[] = "created_at <= ?";
            $params[] = $filters['end_date'] . ' 23:59:59';
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $sql = "SELECT COUNT(*) as total FROM sso_logs $whereClause";
        $result = $this->db->fetchOne($sql, $params);
        
        return $result['total'] ?? 0;
    }
    
    /**
     * 获取今日统计数据
     */
    public function getTodayStats() {
        $today = date('Y-m-d');
        
        // 今日登录总数
        $sql1 = "SELECT COUNT(*) as total FROM sso_logs WHERE DATE(created_at) = ?";
        $total = $this->db->fetchOne($sql1, [$today])['total'] ?? 0;
        
        // 今日成功登录数
        $sql2 = "SELECT COUNT(*) as total FROM sso_logs WHERE DATE(created_at) = ? AND status = 'success'";
        $success = $this->db->fetchOne($sql2, [$today])['total'] ?? 0;
        
        // 今日失败登录数
        $sql3 = "SELECT COUNT(*) as total FROM sso_logs WHERE DATE(created_at) = ? AND status = 'failed'";
        $failed = $this->db->fetchOne($sql3, [$today])['total'] ?? 0;
        
        // 微信登录数
        $sql4 = "SELECT COUNT(*) as total FROM sso_logs WHERE DATE(created_at) = ? AND login_type = 'wechat'";
        $wechat = $this->db->fetchOne($sql4, [$today])['total'] ?? 0;
        
        // 密码登录数
        $sql5 = "SELECT COUNT(*) as total FROM sso_logs WHERE DATE(created_at) = ? AND login_type = 'password'";
        $password = $this->db->fetchOne($sql5, [$today])['total'] ?? 0;
        
        return [
            'total' => $total,
            'success' => $success,
            'failed' => $failed,
            'wechat' => $wechat,
            'password' => $password
        ];
    }
    
    /**
     * 获取失败登录尝试（用于安全告警）
     */
    public function getFailedAttempts($minutes = 60) {
        $sql = "SELECT ip, COUNT(*) as attempts, MAX(created_at) as last_attempt
                FROM sso_logs
                WHERE status = 'failed' AND created_at >= DATE_SUB(NOW(), INTERVAL ? MINUTE)
                GROUP BY ip
                HAVING attempts >= ?
                ORDER BY attempts DESC";
        
        return $this->db->fetchAll($sql, [$minutes, MAX_LOGIN_ATTEMPTS]);
    }
    
    /**
     * 导出日志为 CSV
     */
    public function exportToCsv($filters = []) {
        $logs = $this->getLogs(1, 10000, $filters);
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="sso_logs_' . date('Y-m-d_His') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // 添加 BOM 以支持 Excel 正确显示中文
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // 写入表头
        fputcsv($output, ['ID', '用户名', '昵称', '站点', '登录方式', '状态', 'IP 地址', 'User Agent', '错误信息', '时间']);
        
        // 写入数据
        foreach ($logs as $log) {
            fputcsv($output, [
                $log['id'],
                $log['username'] ?? '-',
                $log['nickname'] ?? '-',
                $log['client_name'] ?? '-',
                $log['login_type'],
                $log['status'],
                $log['ip'],
                $log['user_agent'],
                $log['error_msg'] ?? '-',
                $log['created_at']
            ]);
        }
        
        fclose($output);
        exit;
    }
}
