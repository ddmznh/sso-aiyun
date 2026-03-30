<?php
/**
 * 爱云科技 SSO 统一认证系统 - 用户服务类
 * 
 * @package SSO_Aiyun
 * @version 1.0.0
 */

if (!defined('SSO_SYSTEM')) {
    exit('Direct access not allowed');
}

class UserService {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * 根据用户名查找用户
     */
    public function findByUsername($username) {
        $sql = "SELECT * FROM users WHERE username = ? LIMIT 1";
        return $this->db->fetchOne($sql, [$username]);
    }
    
    /**
     * 根据邮箱查找用户
     */
    public function findByEmail($email) {
        $sql = "SELECT * FROM users WHERE email = ? LIMIT 1";
        return $this->db->fetchOne($sql, [$email]);
    }
    
    /**
     * 根据微信 openid 查找用户
     */
    public function findByWechatOpenid($openid) {
        $sql = "SELECT * FROM users WHERE wechat_openid = ? LIMIT 1";
        return $this->db->fetchOne($sql, [$openid]);
    }
    
    /**
     * 根据 ID 查找用户
     */
    public function findById($id) {
        $sql = "SELECT * FROM users WHERE id = ? LIMIT 1";
        return $this->db->fetchOne($sql, [$id]);
    }
    
    /**
     * 验证用户密码
     */
    public function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    /**
     * 加密密码
     */
    public function hashPassword($password) {
        return password_hash($password, PASSWORD_BCRYPT);
    }
    
    /**
     * 创建用户
     */
    public function createUser($data) {
        $sql = "INSERT INTO users (username, password, nickname, email, role, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())";
        
        $params = [
            $data['username'],
            $this->hashPassword($data['password']),
            $data['nickname'] ?? $data['username'],
            $data['email'] ?? null,
            $data['role'] ?? 'user',
            $data['status'] ?? 1
        ];
        
        return $this->db->insert($sql, $params);
    }
    
    /**
     * 通过微信创建或更新用户
     */
    public function createOrUpdateWechatUser($wechatInfo) {
        // 先检查是否已存在
        $user = $this->findByWechatOpenid($wechatInfo['openid']);
        
        if ($user) {
            // 更新用户信息
            $sql = "UPDATE users SET 
                    nickname = ?, 
                    avatar = ?, 
                    wechat_unionid = ?, 
                    updated_at = NOW() 
                    WHERE id = ?";
            $this->db->execute($sql, [
                $wechatInfo['nickname'],
                $wechatInfo['headimgurl'] ?? null,
                $wechatInfo['unionid'] ?? null,
                $user['id']
            ]);
            return $user['id'];
        }
        
        // 创建新用户
        $username = 'wx_' . substr($wechatInfo['openid'], -8);
        $sql = "INSERT INTO users (username, nickname, avatar, role, status, 
                wechat_openid, wechat_unionid, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        
        return $this->db->insert($sql, [
            $username,
            $wechatInfo['nickname'],
            $wechatInfo['headimgurl'] ?? null,
            'user',
            1,
            $wechatInfo['openid'],
            $wechatInfo['unionid'] ?? null
        ]);
    }
    
    /**
     * 绑定微信到现有用户
     */
    public function bindWechat($userId, $wechatInfo) {
        $sql = "UPDATE users SET 
                wechat_openid = ?, 
                wechat_unionid = ?, 
                nickname = COALESCE(NULLIF(?, ''), nickname),
                avatar = COALESCE(NULLIF(?, ''), avatar),
                updated_at = NOW() 
                WHERE id = ?";
        
        return $this->db->execute($sql, [
            $wechatInfo['openid'],
            $wechatInfo['unionid'] ?? null,
            $wechatInfo['nickname'],
            $wechatInfo['headimgurl'] ?? null,
            $userId
        ]);
    }
    
    /**
     * 更新用户登录时间
     */
    public function updateLastLogin($userId) {
        $sql = "UPDATE users SET last_login_at = NOW(), last_login_ip = ? WHERE id = ?";
        $this->db->execute($sql, [$_SERVER['REMOTE_ADDR'] ?? '', $userId]);
    }
    
    /**
     * 更新用户状态
     */
    public function updateStatus($userId, $status) {
        $sql = "UPDATE users SET status = ?, updated_at = NOW() WHERE id = ?";
        return $this->db->execute($sql, [$status, $userId]);
    }
    
    /**
     * 修改密码
     */
    public function changePassword($userId, $newPassword) {
        $sql = "UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?";
        return $this->db->execute($sql, [$this->hashPassword($newPassword), $userId]);
    }
    
    /**
     * 获取所有用户（分页）
     */
    public function getAllUsers($page = 1, $pageSize = 20, $filters = []) {
        $where = [];
        $params = [];
        
        if (!empty($filters['keyword'])) {
            $where[] = "(username LIKE ? OR nickname LIKE ? OR email LIKE ?)";
            $keyword = '%' . $filters['keyword'] . '%';
            $params[] = $keyword;
            $params[] = $keyword;
            $params[] = $keyword;
        }
        
        if (isset($filters['role']) && $filters['role'] !== '') {
            $where[] = "role = ?";
            $params[] = $filters['role'];
        }
        
        if (isset($filters['status']) && $filters['status'] !== '') {
            $where[] = "status = ?";
            $params[] = $filters['status'];
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $offset = ($page - 1) * $pageSize;
        
        $sql = "SELECT * FROM users $whereClause ORDER BY id DESC LIMIT ? OFFSET ?";
        $params[] = $pageSize;
        $params[] = $offset;
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * 统计用户总数
     */
    public function countUsers($filters = []) {
        $where = [];
        $params = [];
        
        if (!empty($filters['keyword'])) {
            $where[] = "(username LIKE ? OR nickname LIKE ? OR email LIKE ?)";
            $keyword = '%' . $filters['keyword'] . '%';
            $params[] = $keyword;
            $params[] = $keyword;
            $params[] = $keyword;
        }
        
        if (isset($filters['role']) && $filters['role'] !== '') {
            $where[] = "role = ?";
            $params[] = $filters['role'];
        }
        
        if (isset($filters['status']) && $filters['status'] !== '') {
            $where[] = "status = ?";
            $params[] = $filters['status'];
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $sql = "SELECT COUNT(*) as total FROM users $whereClause";
        $result = $this->db->fetchOne($sql, $params);
        
        return $result['total'] ?? 0;
    }
}
