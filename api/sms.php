<?php
/**
 * 处理短信验证码相关请求
 * - 发送验证码（带图形验证码校验和频率限制）
 * - 验证验证码
 */

require_once '../includes/config.php';
require_once '../includes/classes/Database.php';
require_once '../includes/classes/SmsService.php';
require_once '../includes/helpers.php';

header('Content-Type: application/json');

// 只允许 POST 请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'error' => '非法请求']);
}

$action = $_POST['action'] ?? '';
$db = Database::getInstance();

if ($action === 'send_code') {
    // 发送验证码
    $mobile = $_POST['mobile'] ?? '';
    $captcha = $_POST['captcha'] ?? '';
    
    // 1. 验证手机号格式
    if (!preg_match('/^1[3-9]\d{9}$/', $mobile)) {
        json_response(['success' => false, 'error' => '请输入正确的手机号码']);
    }
    
    // 2. 验证图形验证码
    session_start();
    if (empty($captcha) || strtolower($captcha) !== ($_SESSION['captcha_code'] ?? '')) {
        json_response(['success' => false, 'error' => '图形验证码错误']);
    }
    // 清除已使用的验证码
    unset($_SESSION['captcha_code']);
    
    // 3. IP 频率限制（每小时最多 20 次）
    $ip = get_client_ip();
    $ipKey = 'sms_ip_limit_' . md5($ip);
    $ipCount = (int)redis_get($ipKey); // 假设有 Redis，否则用数据库
    if ($ipCount >= 20) {
        json_response(['success' => false, 'error' => '操作过于频繁，请稍后再试']);
    }
    
    // 4. 手机号频率限制（60 秒内只能发送一次）
    $mobileKey = 'sms_mobile_limit_' . $mobile;
    if (redis_exists($mobileKey)) {
        json_response(['success' => false, 'error' => '验证码已发送，请 60 秒后再试']);
    }
    
    // 5. 生成 6 位验证码
    $code = sprintf('%06d', mt_rand(0, 999999));
    $expireTime = date('Y-m-d H:i:s', strtotime('+5 minutes'));
    
    // 6. 保存到数据库
    $stmt = $db->prepare("INSERT INTO sms_codes (mobile, code, expire_at, ip) VALUES (?, ?, ?, ?)");
    $stmt->execute([$mobile, $code, $expireTime, $ip]);
    
    // 7. 发送短信
    $sms = new SmsService();
    $result = $sms->sendVerificationCode($mobile, $code);
    
    if ($result['success']) {
        // 设置频率限制（60 秒）
        redis_set($mobileKey, '1', 60);
        redis_incr($ipKey);
        redis_expire($ipKey, 3600);
        
        json_response([
            'success' => true, 
            'message' => '验证码已发送',
            'debug_id' => $result['message_id'] // 生产环境应移除
        ]);
    } else {
        json_response(['success' => false, 'error' => $result['error']]);
    }
    
} elseif ($action === 'verify_code') {
    // 验证验证码
    $mobile = $_POST['mobile'] ?? '';
    $code = $_POST['code'] ?? '';
    
    if (empty($mobile) || empty($code)) {
        json_response(['success' => false, 'error' => '参数不完整']);
    }
    
    // 查询未过期的验证码
    $stmt = $db->prepare("SELECT id FROM sms_codes WHERE mobile = ? AND code = ? AND used = 0 AND expire_at > NOW() ORDER BY id DESC LIMIT 1");
    $stmt->execute([$mobile, $code]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($row) {
        // 标记为已使用
        $update = $db->prepare("UPDATE sms_codes SET used = 1 WHERE id = ?");
        $update->execute([$row['id']]);
        json_response(['success' => true, 'message' => '验证成功']);
    } else {
        json_response(['success' => false, 'error' => '验证码错误或已过期']);
    }
    
} else {
    json_response(['success' => false, 'error' => '未知操作']);
}

/**
 * 简单的 Redis 模拟（如无 Redis，可用数据库或文件缓存替代）
 */
function redis_get($key) {
    // 实际项目中应使用 Redis 扩展
    return false;
}

function redis_set($key, $value, $ttl) {
    // 实际项目中应使用 Redis 扩展
    return true;
}

function redis_exists($key) {
    // 实际项目中应使用 Redis 扩展
    return false;
}

function redis_incr($key) {
    // 实际项目中应使用 Redis 扩展
    return true;
}

function redis_expire($key, $ttl) {
    // 实际项目中应使用 Redis 扩展
    return true;
}
