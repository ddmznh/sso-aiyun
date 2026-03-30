<?php
/**
 * CORS 跨域资源共享中间件
 * 动态允许已注册的 OAuth2 客户端跨域访问
 */

class CORSMiddleware {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * 处理 CORS 请求
     */
    public function handle() {
        // 获取 Origin
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        
        if (empty($origin)) {
            return;
        }
        
        // 解析 origin 域名
        $parsed = parse_url($origin);
        $origin_host = $parsed['host'] ?? '';
        
        if (empty($origin_host)) {
            return;
        }
        
        // 从数据库查询允许的域名
        $allowed_origins = $this->getAllowedOrigins();
        
        // 检查是否在白名单中
        foreach ($allowed_origins as $allowed) {
            if ($this->matchOrigin($origin_host, $allowed)) {
                header("Access-Control-Allow-Origin: {$origin}");
                header('Access-Control-Allow-Credentials: true');
                header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
                header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
                header('Access-Control-Max-Age: 86400');
                
                // 处理预检请求
                if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
                    http_response_code(204);
                    exit;
                }
                
                return;
            }
        }
        
        // 不在白名单，不设置 CORS 头
    }
    
    /**
     * 获取所有允许的源
     */
    private function getAllowedOrigins() {
        static $origins = null;
        
        if ($origins !== null) {
            return $origins;
        }
        
        $stmt = $this->db->query("SELECT redirect_uris FROM sso_clients WHERE status = 1");
        $uris = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $origins = [];
        foreach ($uris as $uri_string) {
            $uri_list = explode(',', $uri_string);
            foreach ($uri_list as $uri) {
                $uri = trim($uri);
                if (!empty($uri)) {
                    $parsed = parse_url($uri);
                    if (isset($parsed['host'])) {
                        $origins[] = $parsed['host'];
                    }
                }
            }
        }
        
        // 添加主域名
        $origins[] = 'sso.aiyun.top';
        $origins[] = 'aiyun.top';
        
        return array_unique($origins);
    }
    
    /**
     * 匹配源（支持通配符子域名）
     */
    private function matchOrigin($origin, $allowed) {
        if ($origin === $allowed) {
            return true;
        }
        
        // 支持 *.example.com 格式
        if (strpos($allowed, '*.') === 0) {
            $suffix = substr($allowed, 1); // .example.com
            if (substr($origin, -strlen($suffix)) === $suffix) {
                return true;
            }
        }
        
        return false;
    }
}

// 快捷函数
function init_cors($db) {
    $cors = new CORSMiddleware($db);
    $cors->handle();
}
