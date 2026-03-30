# 🚀 爱云科技 SSO 统一认证系统 - 快速部署指南

## 📋 环境要求

- **PHP**: >= 8.1
- **MySQL**: >= 5.7
- **Web Server**: Nginx / Apache
- **扩展**: PDO, OpenSSL, JSON, Session

---

## 🔧 快速部署 (5 分钟)

### 1️⃣ 克隆代码

```bash
git clone -b sso.aiyun.top https://github.com/ddmznh/web_soft.git
cd web_soft
```

### 2️⃣ 配置文件

```bash
# 复制配置模板
cp includes/config.example.php includes/config.php

# 编辑配置文件
vim includes/config.php
```

**需要修改的配置项：**
```php
// 数据库配置
DB_HOST     = 'localhost'
DB_NAME     = 'sso_aiyun_top'
DB_USER     = 'sso_aiyun_top'
DB_PASS     = '你的数据库密码'

// 微信开放平台配置
WX_APPID         = 'wx64908b2279037121'
WX_APPSECRET     = '你的微信密钥'
WX_REDIRECT_URI  = 'https://sso.aiyun.top/wechat/callback.php'

// 平台配置
SSO_DOMAIN       = 'https://sso.aiyun.top'
CDN_BASE         = 'https://cdn.aiyun.top'
```

### 3️⃣ 导入数据库

```bash
mysql -u DB_USER -p DB_NAME < database/init.sql
```

### 4️⃣ 设置目录权限

```bash
chmod -R 755 /path/to/web_soft
chown -R www:www /path/to/web_soft
```

### 5️⃣ 配置 Web 服务器

#### Nginx 配置示例

```nginx
server {
    listen 80;
    server_name sso.aiyun.top;
    root /path/to/web_soft;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/tmp/php-cgi.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\. {
        deny all;
    }
}
```

#### Apache 配置示例

```apache
<VirtualHost *:80>
    ServerName sso.aiyun.top
    DocumentRoot /path/to/web_soft
    
    <Directory /path/to/web_soft>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/sso_error.log
    CustomLog ${APACHE_LOG_DIR}/sso_access.log combined
</VirtualHost>
```

启用 `.htaccess`:
```bash
a2enmod rewrite
systemctl restart apache2
```

---

## ✅ 验证安装

### 访问管理后台
- URL: `https://sso.aiyun.top/admin/`
- 默认管理员：`admin` / `admin123`
- **请立即修改默认密码！**

### 访问用户中心
- URL: `https://sso.aiyun.top/user/`

### 测试 API 接口
```bash
curl https://sso.aiyun.top/api/user.php?access_token=YOUR_TOKEN
```

---

## 🎯 角色说明

| 角色 | 权限 | 可访问页面 |
|------|------|-----------|
| **admin** | 管理员 | 管理后台全部功能 + 用户中心 |
| **developer** | 开发者 | 站点管理 + 用户中心 |
| **user** | 普通用户 | 仅用户中心 |

---

## 🔐 安全建议

1. **修改默认密码**: 首次登录后立即修改 admin 账户密码
2. **启用 HTTPS**: 生产环境必须使用 SSL 证书
3. **定期备份**: 每天备份数据库和配置文件
4. **日志监控**: 定期检查 `logs/` 目录下的操作日志
5. **更新依赖**: 及时更新 PHP 和 MySQL 到最新安全版本

---

## 🛠️ 常见问题

### Q: 无法连接数据库
A: 检查 `config.php` 中的数据库配置，确保 MySQL 服务运行正常。

### Q: 微信扫码登录失败
A: 检查微信开放平台配置的回调地址是否与 `WX_REDIRECT_URI` 一致。

### Q: 页面显示空白
A: 开启错误日志：在 `config.php` 中设置 `DEBUG_MODE = true` 查看详细错误。

### Q: Session 无法保存
A: 确保 PHP Session 目录有写权限，或改用 Redis 存储 Session。

---

## 📞 技术支持

- GitHub: https://github.com/ddmznh/web_soft
- 分支：`sso.aiyun.top`
- 文档：查看项目根目录 README.md

---

## 📄 许可证

MIT License © 2024 爱云科技
