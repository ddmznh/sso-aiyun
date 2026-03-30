<?php
/**
 * 全网智能通讯平台 SMS 服务类
 * 实现短信发送、余额查询、状态报告获取等功能
 */

class SmsService {
    private $apiUrl = 'https://dxsdk.028lk.com:8082/Api/';
    private $loginName;
    private $pwd;
    private $feeType = '2'; // 2:行业套餐, 3:政务套餐

    public function __construct() {
        global $config;
        $this->loginName = $config['sms_user'] ?? '';
        $this->pwd = $config['sms_pass'] ?? '';
        $this->feeType = $config['sms_fee_type'] ?? '2';
    }

    /**
     * 发送短信
     * @param string $mobile 手机号码
     * @param string $content 短信内容（含模板变量）
     * @param string $signName 签名（可选）
     * @return array ['success'=>bool, 'message_id'=>string, 'error'=>string]
     */
    public function sendSms($mobile, $content, $signName = '') {
        if (empty($this->loginName) || empty($this->pwd)) {
            return ['success' => false, 'error' => '短信平台账号未配置'];
        }

        // 内容编码：URLEncode + UTF-8
        $encodedContent = urlencode($content);
        $encodedSign = !empty($signName) ? urlencode($signName) : '';

        $params = [
            'LoginName' => $this->loginName,
            'Pwd' => $this->pwd,
            'FeeType' => $this->feeType,
            'Mobile' => $mobile,
            'Content' => $encodedContent,
            'SignName' => $encodedSign,
            'TimingDate' => '',
            'ExtCode' => ''
        ];

        $url = $this->apiUrl . 'SendSms?' . http_build_query($params);
        
        $result = $this->httpGet($url);
        
        if ($result === false) {
            return ['success' => false, 'error' => '网络请求失败'];
        }

        // 解析返回值 OK|消息编码 或 错误码
        if (strpos($result, 'OK|') === 0) {
            $parts = explode('|', $result);
            return [
                'success' => true,
                'message_id' => $parts[1] ?? '',
                'error' => ''
            ];
        } else {
            $errorCode = intval($result);
            $errorMsg = $this->getErrorMessage($errorCode);
            return ['success' => false, 'error' => $errorMsg . " (代码:$errorCode)"];
        }
    }

    /**
     * 发送验证码短信
     * @param string $mobile 手机号
     * @param string $code 验证码
     * @return array
     */
    public function sendVerificationCode($mobile, $code) {
        // 使用模板：您的验证码为[var]，5 分钟内有效
        $template = "您的验证码为{$code}，5 分钟内有效，请勿向他人泄露。";
        return $this->sendSms($mobile, $template, '【爱云科技】');
    }

    /**
     * 查询余额
     * @param int $getType 1:B 套餐，2:A 套餐，3:彩信，4:语音
     * @return array ['success'=>bool, 'balance'=>int, 'error'=>string]
     */
    public function getBalance($getType = 1) {
        if (empty($this->loginName) || empty($this->pwd)) {
            return ['success' => false, 'error' => '短信平台账号未配置'];
        }

        $params = [
            'LoginName' => $this->loginName,
            'Pwd' => $this->pwd,
            'GetType' => $getType
        ];

        $url = $this->apiUrl . 'GetBalance?' . http_build_query($params);
        $result = $this->httpGet($url);

        if ($result === false) {
            return ['success' => false, 'error' => '网络请求失败'];
        }

        $balance = intval($result);
        if ($balance >= 0) {
            return ['success' => true, 'balance' => $balance, 'error' => ''];
        } else {
            return ['success' => false, 'balance' => 0, 'error' => $this->getErrorMessage($balance)];
        }
    }

    /**
     * 获取短信状态报告
     * @param string $messageId 消息编码，0 表示批量获取
     * @return array
     */
    public function getSmsReport($messageId = '0') {
        $params = [
            'LoginName' => $this->loginName,
            'Pwd' => $this->pwd,
            'MessageID' => $messageId
        ];

        $url = $this->apiUrl . 'GetSmsReport?' . http_build_query($params);
        $result = $this->httpGet($url);

        if (empty($result) || $result === '空') {
            return ['success' => true, 'reports' => []];
        }

        if (strpos($result, '-') === 0) {
            return ['success' => false, 'error' => $this->getErrorMessage(intval($result))];
        }

        // 解析格式：消息编码$$$手机号码$$$报告标志$$$报告内容$$$报告时间|||
        $reports = [];
        $items = explode('|||', trim($result, '|'));
        foreach ($items as $item) {
            if (empty($item)) continue;
            $parts = explode('$$$', $item);
            if (count($parts) >= 5) {
                $reports[] = [
                    'message_id' => $parts[0],
                    'mobile' => $parts[1],
                    'status' => $parts[2] == '1' ? 'success' : 'failed',
                    'report_content' => $parts[3],
                    'report_time' => $parts[4]
                ];
            }
        }

        return ['success' => true, 'reports' => $reports];
    }

    /**
     * HTTP GET 请求
     */
    private function httpGet($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode != 200) {
            return false;
        }

        return $result;
    }

    /**
     * 获取错误码描述
     */
    private function getErrorMessage($code) {
        $errors = [
            -1 => '账户或密码错误',
            -2 => '未知错误，网络波动',
            -3 => '账户配置不正确',
            -4 => '在禁止发送的时间段',
            -5 => '余额不足',
            -6 => '定时时间小于当前系统时间',
            -7 => '用户签名错误',
            -8 => '发送号码数量超出限制',
            -9 => '发送字数超出限制',
            -10 => '产品不存在',
            -11 => '内容不能再存在签名',
            -12 => '传入参数不正确',
            -13 => '手机号校验不正确',
            -14 => '内容中存在黑字典关键字',
            -15 => '定时时间格式错误',
            -16 => '扩展号格式不正确',
            -17 => '子号池全被占用',
            -18 => '扩展号被投票问卷占用',
            -19 => '计费类型选择不正确',
            -21 => '语音模板显号未设置',
            -22 => '语音模板编码不存在',
            -23 => '语音模板不存在',
            -24 => '语音模板参数不匹配',
            -31 => '彩信帧的内容不完整',
            -100 => '发送失败',
            -101 => '调用频率过快',
            -103 => 'IP 未导白',
            -111 => '个性短信入口错误',
            -200 => '网络连接失败'
        ];
        return $errors[$code] ?? '未知错误';
    }
}
