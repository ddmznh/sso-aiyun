<?php
/**
 * 爱云科技 SSO - 通用辅助函数库
 */

if (!defined('SSO_ROOT')) {
    exit('Access Denied');
}

/**
 * 生成 CSRF Token
 */
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * 验证 CSRF Token
 */
function verifyCsrfToken($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * 刷新 CSRF Token
 */
function refreshCsrfToken() {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}

/**
 * 检查重定向 URL 是否在白名单中
 */
function isValidRedirectUrl($url) {
    global $ALLOWED_REDIRECT_DOMAINS;
    
    if (empty($url)) {
        return false;
    }
    
    $parsedUrl = parse_url($url);
    if (!isset($parsedUrl['host'])) {
        // 相对路径允许
        if (strpos($url, '/') === 0 && strpos($url, '//') !== 0) {
            return true;
        }
        return false;
    }
    
    $host = strtolower($parsedUrl['host']);
    
    foreach ($ALLOWED_REDIRECT_DOMAINS as $allowedDomain) {
        $allowedDomain = strtolower(trim($allowedDomain));
        if ($host === $allowedDomain || substr($host, -strlen('.' . $allowedDomain)) === '.' . $allowedDomain) {
            return true;
        }
    }
    
    return false;
}

/**
 * 获取客户端 IP 地址
 */
function getClientIp() {
    $ip = '';
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    }
    
    // 处理多个 IP 的情况（代理）
    if (strpos($ip, ',') !== false) {
        $ip = trim(explode(',', $ip)[0]);
    }
    
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
}

/**
 * 获取 User Agent
 */
function getUserAgent() {
    return substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 255);
}

/**
 * 安全的输出 HTML
 */
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * JSON 响应输出
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

/**
 * 成功响应
 */
function successResponse($message = '操作成功', $data = null) {
    jsonResponse([
        'code' => 0,
        'message' => $message,
        'data' => $data
    ]);
}

/**
 * 错误响应
 */
function errorResponse($message = '操作失败', $code = 1) {
    jsonResponse([
        'code' => $code,
        'message' => $message,
        'data' => null
    ], 400);
}

/**
 * 检查是否为 AJAX 请求
 */
function isAjaxRequest() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * 格式化时间
 */
function formatTime($timestamp, $format = 'Y-m-d H:i:s') {
    if (is_numeric($timestamp)) {
        return date($format, $timestamp);
    }
    return date($format, strtotime($timestamp));
}

/**
 * 友好的时间差显示
 */
function timeAgo($timestamp) {
    $time = is_numeric($timestamp) ? $timestamp : strtotime($timestamp);
    $diff = time() - $time;
    
    if ($diff < 60) {
        return '刚刚';
    } elseif ($diff < 3600) {
        return floor($diff / 60) . '分钟前';
    } elseif ($diff < 86400) {
        return floor($diff / 3600) . '小时前';
    } elseif ($diff < 604800) {
        return floor($diff / 86400) . '天前';
    } else {
        return formatTime($timestamp, 'Y-m-d');
    }
}

/**
 * 生成随机字符串
 */
function generateRandomString($length = 32, $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789') {
    $string = '';
    $max = strlen($characters) - 1;
    for ($i = 0; $i < $length; $i++) {
        $string .= $characters[random_int(0, $max)];
    }
    return $string;
}

/**
 * 导出 CSV 文件
 */
function exportCsv($filename, $headers, $data) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // 添加 BOM 以支持 Excel 正确识别 UTF-8
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
    
    fputcsv($output, $headers);
    
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit;
}

/**
 * 检查密码强度
 */
function checkPasswordStrength($password) {
    $strength = 0;
    $errors = [];
    
    if (strlen($password) < 6) {
        $errors[] = '密码长度至少为 6 位';
    } else {
        $strength++;
    }
    
    if (preg_match('/[a-z]/', $password)) {
        $strength++;
    } else {
        $errors[] = '密码需包含小写字母';
    }
    
    if (preg_match('/[A-Z]/', $password)) {
        $strength++;
    } else {
        $errors[] = '密码需包含大写字母';
    }
    
    if (preg_match('/[0-9]/', $password)) {
        $strength++;
    } else {
        $errors[] = '密码需包含数字';
    }
    
    if ($strength < 3) {
        return ['valid' => false, 'errors' => $errors];
    }
    
    return ['valid' => true, 'errors' => []];
}

/**
 * 清理输入数据
 */
function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    return trim(strip_tags($input));
}
