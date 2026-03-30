<?php
/**
 * 爱云科技 SSO 统一认证系统 - Token 服务类
 * 
 * @package SSO_Aiyun
 * @version 1.0.0
 */

if (!defined('SSO_SYSTEM')) {
    exit('Direct access not allowed');
}

class TokenService {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * 生成访问令牌
     */
    public function generateToken($userId, $clientId, $redirectUri) {
        $token = bin2hex(random_bytes(32));
        $expireAt = date('Y-m-d H:i:s', time() + TOKEN_EXPIRE_MINUTES * 60);
        
        $sql = "INSERT INTO sso_tokens (token, user_id, client_id, redirect_uri, expire_at, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())";
        
        $this->db->execute($sql, [$token, $userId, $clientId, $redirectUri, $expireAt]);
        
        return $token;
    }
    
    /**
     * 验证令牌
     */
    public function validateToken($token) {
        $sql = "SELECT t.*, u.username, u.nickname, u.email, u.role, u.status as user_status
                FROM sso_tokens t
                LEFT JOIN users u ON t.user_id = u.id
                WHERE t.token = ? AND t.expire_at > NOW() AND t.used = 0
                LIMIT 1";
        
        return $this->db->fetchOne($sql, [$token]);
    }
    
    /**
     * 使用令牌（标记为已使用）
     */
    public function useToken($token) {
        $sql = "UPDATE sso_tokens SET used = 1, used_at = NOW() WHERE token = ?";
        return $this->db->execute($sql, [$token]);
    }
    
    /**
     * 删除令牌
     */
    public function revokeToken($token) {
        $sql = "DELETE FROM sso_tokens WHERE token = ?";
        return $this->db->execute($sql, [$token]);
    }
    
    /**
     * 清理过期令牌
     */
    public function cleanupExpiredTokens() {
        $sql = "DELETE FROM sso_tokens WHERE expire_at < NOW()";
        return $this->db->execute($sql);
    }
    
    /**
     * 获取用户的所有有效令牌
     */
    public function getUserTokens($userId) {
        $sql = "SELECT t.*, c.client_name 
                FROM sso_tokens t
                LEFT JOIN sso_clients c ON t.client_id = c.client_id
                WHERE t.user_id = ? AND t.expire_at > NOW() AND t.used = 0
                ORDER BY t.created_at DESC";
        
        return $this->db->fetchAll($sql, [$userId]);
    }
    
    /**
     * 撤销用户的所有令牌
     */
    public function revokeUserTokens($userId) {
        $sql = "UPDATE sso_tokens SET used = 1, used_at = NOW() WHERE user_id = ? AND used = 0";
        return $this->db->execute($sql, [$userId]);
    }
}
