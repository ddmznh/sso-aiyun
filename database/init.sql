-- 爱云科技 SSO 统一认证系统 - 数据库初始化脚本
-- 版本：1.0.0
-- 字符集：utf8mb4

-- 创建数据库（如果不存在）
CREATE DATABASE IF NOT EXISTS `sso_aiyun_top` 
DEFAULT CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `sso_aiyun_top`;

-- ===========================
-- 用户表
-- ===========================
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL COMMENT '用户名',
  `password` VARCHAR(255) DEFAULT NULL COMMENT '密码（bcrypt 加密）',
  `nickname` VARCHAR(100) DEFAULT NULL COMMENT '昵称',
  `email` VARCHAR(100) DEFAULT NULL COMMENT '邮箱',
  `avatar` VARCHAR(255) DEFAULT NULL COMMENT '头像 URL',
  `role` ENUM('admin', 'user', 'developer') DEFAULT 'user' COMMENT '角色',
  `status` TINYINT DEFAULT 1 COMMENT '状态：1-正常，0-禁用',
  `wechat_openid` VARCHAR(64) DEFAULT NULL COMMENT '微信 openid',
  `wechat_unionid` VARCHAR(64) DEFAULT NULL COMMENT '微信 unionid',
  `last_login_at` DATETIME DEFAULT NULL COMMENT '最后登录时间',
  `last_login_ip` VARCHAR(50) DEFAULT NULL COMMENT '最后登录 IP',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_username` (`username`),
  UNIQUE KEY `uk_email` (`email`),
  UNIQUE KEY `uk_wechat_openid` (`wechat_openid`),
  KEY `idx_wechat_unionid` (`wechat_unionid`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户表';

-- ===========================
-- 接入站点表
-- ===========================
DROP TABLE IF EXISTS `sso_clients`;
CREATE TABLE `sso_clients` (
  `client_id` VARCHAR(64) NOT NULL COMMENT '客户端 ID',
  `client_name` VARCHAR(100) NOT NULL COMMENT '站点名称',
  `client_secret` VARCHAR(64) NOT NULL COMMENT '客户端密钥',
  `redirect_uris` TEXT COMMENT '允许的重定向 URI（JSON 数组）',
  `logo_url` VARCHAR(255) DEFAULT NULL COMMENT '站点 Logo URL',
  `scopes` VARCHAR(255) DEFAULT 'userinfo' COMMENT '授权范围',
  `description` VARCHAR(500) DEFAULT NULL COMMENT '站点描述',
  `status` TINYINT DEFAULT 1 COMMENT '状态：1-启用，0-禁用',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`client_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='接入站点表';

-- ===========================
-- 访问令牌表
-- ===========================
DROP TABLE IF EXISTS `sso_tokens`;
CREATE TABLE `sso_tokens` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `token` VARCHAR(64) NOT NULL COMMENT '访问令牌',
  `user_id` INT UNSIGNED NOT NULL COMMENT '用户 ID',
  `client_id` VARCHAR(64) NOT NULL COMMENT '客户端 ID',
  `redirect_uri` VARCHAR(255) DEFAULT NULL COMMENT '重定向 URI',
  `expire_at` DATETIME NOT NULL COMMENT '过期时间',
  `used` TINYINT DEFAULT 0 COMMENT '是否已使用：0-未使用，1-已使用',
  `used_at` DATETIME DEFAULT NULL COMMENT '使用时间',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_token` (`token`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_client_id` (`client_id`),
  KEY `idx_expire_at` (`expire_at`),
  KEY `idx_used` (`used`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='访问令牌表';

-- ===========================
-- 登录日志表
-- ===========================
DROP TABLE IF EXISTS `sso_logs`;
CREATE TABLE `sso_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED DEFAULT NULL COMMENT '用户 ID',
  `client_id` VARCHAR(64) DEFAULT NULL COMMENT '客户端 ID',
  `login_type` ENUM('wechat', 'password') NOT NULL COMMENT '登录方式',
  `status` ENUM('success', 'failed') NOT NULL COMMENT '登录状态',
  `ip` VARCHAR(50) DEFAULT NULL COMMENT 'IP 地址',
  `user_agent` VARCHAR(500) DEFAULT NULL COMMENT 'User Agent',
  `error_msg` VARCHAR(500) DEFAULT NULL COMMENT '错误信息',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_client_id` (`client_id`),
  KEY `idx_login_type` (`login_type`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='登录日志表';

-- ===========================
-- 授权记录表
-- ===========================
DROP TABLE IF EXISTS `sso_authorizations`;
CREATE TABLE `sso_authorizations` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL COMMENT '用户 ID',
  `client_id` VARCHAR(64) NOT NULL COMMENT '客户端 ID',
  `scopes` VARCHAR(255) DEFAULT 'userinfo' COMMENT '授权范围',
  `granted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '授权时间',
  `revoked_at` DATETIME DEFAULT NULL COMMENT '撤销时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_client` (`user_id`, `client_id`),
  KEY `idx_client_id` (`client_id`),
  KEY `idx_revoked_at` (`revoked_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='授权记录表';

-- ===========================
-- 登录失败尝试表（用于防暴力破解）
-- ===========================
DROP TABLE IF EXISTS `login_attempts`;
CREATE TABLE `login_attempts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `identifier` VARCHAR(100) NOT NULL COMMENT '标识（用户名/IP）',
  `attempts` INT NOT NULL DEFAULT 0 COMMENT '失败次数',
  `locked_until` DATETIME DEFAULT NULL COMMENT '锁定截止时间',
  `last_attempt_at` DATETIME DEFAULT NULL COMMENT '最后尝试时间',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_identifier` (`identifier`),
  KEY `idx_locked_until` (`locked_until`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='登录失败尝试表';

-- ===========================
-- 系统配置表
-- ===========================
DROP TABLE IF EXISTS `system_settings`;
CREATE TABLE `system_settings` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `setting_key` VARCHAR(100) NOT NULL COMMENT '配置键',
  `setting_value` TEXT COMMENT '配置值',
  `setting_type` ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string' COMMENT '配置类型',
  `description` VARCHAR(255) DEFAULT NULL COMMENT '配置描述',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统配置表';

-- ===========================
-- 初始化数据
-- ===========================

-- 插入默认管理员账户（密码：admin123）
INSERT INTO `users` (`username`, `password`, `nickname`, `email`, `role`, `status`) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '系统管理员', 'admin@aiyun.top', 'admin', 1);

-- 插入示例站点
INSERT INTO `sso_clients` (`client_id`, `client_name`, `client_secret`, `redirect_uris`, `logo_url`, `description`, `status`) VALUES
('news_aiyun_top', '爱云资讯', 'sk_news_' || SUBSTRING(MD5(RAND()), 1, 32), '["https://news.aiyun.top/callback.php"]', 'https://cdn.aiyun.top/logos/news.png', '爱云科技资讯站点', 1),
('aiyunkeji_com', '爱云科技官网', 'sk_official_' || SUBSTRING(MD5(RAND()), 1, 32), '["https://aiyunkeji.com/sso/callback.php"]', 'https://cdn.aiyun.top/logos/official.png', '爱云科技官方网站', 1),
('8881314_com', '业务平台', 'sk_business_' || SUBSTRING(MD5(RAND()), 1, 32), '["https://8881314.com/auth/callback.php"]', 'https://cdn.aiyun.top/logos/business.png', '业务管理平台', 1),
('xuyong_vip', '个人站点', 'sk_personal_' || SUBSTRING(MD5(RAND()), 1, 32), '["https://xuyong.vip/login/callback.php"]', 'https://cdn.aiyun.top/logos/personal.png', '个人博客站点', 1);

-- 插入系统配置
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `setting_type`, `description`) VALUES
('site_name', '爱云科技 SSO 统一认证系统', 'string', '系统名称'),
('site_logo', 'https://cdn.aiyun.top/logos/sso-logo.png', 'string', '系统 Logo'),
('allow_register', '0', 'boolean', '是否允许用户注册'),
('require_email_verify', '0', 'boolean', '是否需要邮箱验证'),
('session_timeout', '30', 'number', 'Session 超时时间（分钟）'),
('password_min_length', '6', 'number', '密码最小长度'),
('enable_captcha', '0', 'boolean', '是否启用验证码');

-- ===========================
-- 视图：活跃用户统计
-- ===========================
DROP VIEW IF EXISTS `v_active_users_today`;
CREATE VIEW `v_active_users_today` AS
SELECT 
    COUNT(DISTINCT user_id) as active_users,
    SUM(CASE WHEN login_type = 'wechat' THEN 1 ELSE 0 END) as wechat_logins,
    SUM(CASE WHEN login_type = 'password' THEN 1 ELSE 0 END) as password_logins
FROM sso_logs
WHERE DATE(created_at) = CURDATE() AND status = 'success';

-- ===========================
-- 视图：站点登录统计
-- ===========================
DROP VIEW IF EXISTS `v_client_stats`;
CREATE VIEW `v_client_stats` AS
SELECT 
    c.client_id,
    c.client_name,
    c.status as client_status,
    COUNT(l.id) as total_logins,
    SUM(CASE WHEN l.status = 'success' THEN 1 ELSE 0 END) as success_logins,
    SUM(CASE WHEN l.status = 'failed' THEN 1 ELSE 0 END) as failed_logins,
    MAX(l.created_at) as last_login_at
FROM sso_clients c
LEFT JOIN sso_logs l ON c.client_id = l.client_id
GROUP BY c.client_id, c.client_name, c.status;

COMMIT;

-- ===========================
-- 短信验证码表（新增）
-- ===========================
DROP TABLE IF EXISTS `sms_codes`;
CREATE TABLE `sms_codes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `mobile` varchar(20) NOT NULL COMMENT '手机号码',
  `code` varchar(10) NOT NULL COMMENT '验证码',
  `ip` varchar(50) DEFAULT NULL COMMENT '请求 IP',
  `used` tinyint(1) DEFAULT 0 COMMENT '是否已使用',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `expire_at` datetime NOT NULL COMMENT '过期时间',
  PRIMARY KEY (`id`),
  KEY `idx_mobile` (`mobile`),
  KEY `idx_expire` (`expire_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='短信验证码表';

-- ===========================
-- IP 黑名单表（防短信炸弹）
-- ===========================
DROP TABLE IF EXISTS `ip_blacklist`;
CREATE TABLE `ip_blacklist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip` varchar(50) NOT NULL COMMENT 'IP 地址',
  `reason` varchar(255) DEFAULT NULL COMMENT '封禁原因',
  `expires_at` datetime DEFAULT NULL COMMENT '解封时间',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_ip` (`ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='IP 黑名单表';
