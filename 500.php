<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>系统错误 - 爱云科技 SSO</title>
    <link href="https://cdn.aiyun.top/assets/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        .error-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 60px 40px;
            max-width: 500px;
            width: 90%;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .error-code {
            font-size: 120px;
            font-weight: bold;
            background: linear-gradient(135deg, #f093fb, #f5576c);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            line-height: 1;
            margin-bottom: 20px;
        }
        .error-title {
            font-size: 24px;
            color: #333;
            margin-bottom: 15px;
        }
        .error-desc {
            color: #666;
            margin-bottom: 30px;
        }
        .trace-id {
            background: #f8f9fa;
            padding: 10px 20px;
            border-radius: 10px;
            font-family: monospace;
            font-size: 12px;
            color: #666;
            margin-bottom: 30px;
        }
        .btn-home {
            background: linear-gradient(135deg, #f093fb, #f5576c);
            border: none;
            padding: 12px 40px;
            border-radius: 25px;
            color: white;
            text-decoration: none;
            display: inline-block;
            transition: transform 0.3s;
        }
        .btn-home:hover {
            transform: translateY(-2px);
            color: white;
        }
    </style>
</head>
<body>
    <div class="error-card">
        <div class="error-code">500</div>
        <h1 class="error-title">系统开小差了</h1>
        <p class="error-desc">抱歉，服务器遇到了一些问题，请稍后重试</p>
        <?php if (defined('ERROR_TRACE_ID')): ?>
        <div class="trace-id">错误追踪 ID: <?= htmlspecialchars(ERROR_TRACE_ID) ?></div>
        <?php endif; ?>
        <a href="/" class="btn-home">返回首页</a>
    </div>
</body>
</html>
