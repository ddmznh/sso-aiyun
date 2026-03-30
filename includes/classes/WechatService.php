<?php
/**
 * 爱云科技 SSO 统一认证系统 - 微信服务类
 * 
 * @package SSO_Aiyun
 * @version 1.0.0
 */

if (!defined('SSO_SYSTEM')) {
    exit('Direct access not allowed');
}

class WechatService {
    private $appId;
    private $appSecret;
    private $redirectUri;
    
    public function __construct() {
        $this->appId = WX_APPID;
        $this->appSecret = WX_APPSECRET;
        $this->redirectUri = WX_REDIRECT_URI;
    }
    
    /**
     * 获取微信授权 URL
     */
    public function getAuthorizeUrl($state = '') {
        $params = [
            'appid' => $this->appId,
            'redirect_uri' => urlencode($this->redirectUri),
            'response_type' => 'code',
            'scope' => 'snsapi_login',
            'state' => $state ?: uniqid()
        ];
        
        return 'https://open.weixin.qq.com/connect/qrconnect?' . http_build_query($params);
    }
    
    /**
     * 通过 code 获取 access_token 和用户信息
     */
    public function getAccessToken($code) {
        $url = 'https://api.weixin.qq.com/sns/oauth2/access_token';
        $params = [
            'appid' => $this->appId,
            'secret' => $this->appSecret,
            'code' => $code,
            'grant_type' => 'authorization_code'
        ];
        
        $result = $this->httpGet($url, $params);
        
        if (isset($result['errcode'])) {
            throw new Exception('微信 API 错误：' . $result['errmsg']);
        }
        
        return $result;
    }
    
    /**
     * 刷新 access_token
     */
    public function refreshAccessToken($refreshToken) {
        $url = 'https://api.weixin.qq.com/sns/oauth2/refresh_token';
        $params = [
            'appid' => $this->appId,
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken
        ];
        
        return $this->httpGet($url, $params);
    }
    
    /**
     * 获取用户信息
     */
    public function getUserInfo($accessToken, $openid) {
        $url = 'https://api.weixin.qq.com/sns/userinfo';
        $params = [
            'access_token' => $accessToken,
            'openid' => $openid,
            'lang' => 'zh_CN'
        ];
        
        return $this->httpGet($url, $params);
    }
    
    /**
     * 验证 access_token 是否有效
     */
    public function checkAccessToken($accessToken, $openid) {
        $url = 'https://api.weixin.qq.com/sns/auth';
        $params = [
            'access_token' => $accessToken,
            'openid' => $openid
        ];
        
        $result = $this->httpGet($url, $params);
        return isset($result['errmsg']) && $result['errmsg'] === 'ok';
    }
    
    /**
     * HTTP GET 请求
     */
    private function httpGet($url, $params = []) {
        $ch = curl_init();
        
        $fullUrl = $url;
        if (!empty($params)) {
            $fullUrl .= '?' . http_build_query($params);
        }
        
        curl_setopt($ch, CURLOPT_URL, $fullUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($error) {
            throw new Exception('CURL 错误：' . $error);
        }
        
        if ($httpCode !== 200) {
            throw new Exception('HTTP 错误：' . $httpCode);
        }
        
        $result = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('JSON 解析错误：' . json_last_error_msg());
        }
        
        return $result;
    }
    
    /**
     * HTTP POST 请求（JSON）
     */
    private function httpPost($url, $data) {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($error) {
            throw new Exception('CURL 错误：' . $error);
        }
        
        if ($httpCode !== 200) {
            throw new Exception('HTTP 错误：' . $httpCode);
        }
        
        $result = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('JSON 解析错误：' . json_last_error_msg());
        }
        
        return $result;
    }
}
