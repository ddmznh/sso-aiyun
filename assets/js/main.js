/**
 * 爱云科技 SSO 统一认证系统 - 公共 JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    // 主题切换功能
    initThemeToggle();
    
    // 表单验证
    initFormValidation();
    
    // 自动隐藏 Alert
    initAutoHideAlerts();
    
    // 侧边栏切换 (移动端)
    initSidebarToggle();
});

// 主题切换
function initThemeToggle() {
    const themeToggle = document.getElementById('themeToggle');
    if (!themeToggle) return;
    
    // 检查本地存储的主题
    const currentTheme = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', currentTheme);
    updateThemeIcon(currentTheme);
    
    themeToggle.addEventListener('click', function() {
        const newTheme = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        updateThemeIcon(newTheme);
    });
}

function updateThemeIcon(theme) {
    const themeToggle = document.getElementById('themeToggle');
    if (!themeToggle) return;
    
    if (theme === 'dark') {
        themeToggle.innerHTML = '☀️';
        themeToggle.title = '切换到浅色模式';
    } else {
        themeToggle.innerHTML = '🌙';
        themeToggle.title = '切换到深色模式';
    }
}

// 表单验证
function initFormValidation() {
    const forms = document.querySelectorAll('.needs-validation');
    
    forms.forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            form.classList.add('was-validated');
        }, false);
    });
}

// 自动隐藏 Alert
function initAutoHideAlerts() {
    const alerts = document.querySelectorAll('.alert-dismissible');
    
    alerts.forEach(function(alert) {
        const timeout = alert.getAttribute('data-timeout') || 5000;
        
        setTimeout(function() {
            alert.classList.remove('show');
            setTimeout(function() {
                alert.remove();
            }, 150);
        }, timeout);
    });
}

// 侧边栏切换
function initSidebarToggle() {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
    }
}

// AJAX 提交表单
function submitFormAjax(formId, callback) {
    const form = document.getElementById(formId);
    if (!form) return;
    
    const formData = new FormData(form);
    const action = form.action || window.location.href;
    const method = form.method || 'POST';
    
    fetch(action, {
        method: method,
        body: formData,
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        if (callback) callback(data);
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('操作失败，请稍后重试', 'danger');
    });
}

// 显示提示消息
function showAlert(message, type = 'info') {
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    `;
    
    const container = document.querySelector('.alert-container') || document.querySelector('.content-wrapper');
    if (container) {
        container.insertAdjacentHTML('afterbegin', alertHtml);
        initAutoHideAlerts();
    }
}

// 确认对话框
function confirmAction(message, callback) {
    if (confirm(message)) {
        if (callback) callback();
    }
}

// 复制文本到剪贴板
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        showAlert('已复制到剪贴板', 'success');
    }, function() {
        showAlert('复制失败', 'danger');
    });
}

// 格式化时间
function formatTime(timestamp, format = 'YYYY-MM-DD HH:mm:ss') {
    const date = new Date(timestamp * 1000);
    
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    const seconds = String(date.getSeconds()).padStart(2, '0');
    
    return format
        .replace('YYYY', year)
        .replace('MM', month)
        .replace('DD', day)
        .replace('HH', hours)
        .replace('mm', minutes)
        .replace('ss', seconds);
}

// 密码强度检查
function checkPasswordStrength(password) {
    let strength = 0;
    
    if (password.length >= 8) strength++;
    if (password.match(/[a-z]/)) strength++;
    if (password.match(/[A-Z]/)) strength++;
    if (password.match(/[0-9]/)) strength++;
    if (password.match(/[^a-zA-Z0-9]/)) strength++;
    
    return strength;
}

// 实时密码强度显示
function initPasswordStrengthChecker(inputId, indicatorId) {
    const input = document.getElementById(inputId);
    const indicator = document.getElementById(indicatorId);
    
    if (!input || !indicator) return;
    
    input.addEventListener('input', function() {
        const strength = checkPasswordStrength(input.value);
        const colors = ['#e74a3b', '#f6c23e', '#36b9cc', '#1cc88a', '#1cc88a'];
        const labels = ['非常弱', '弱', '中等', '强', '非常强'];
        
        indicator.style.width = ((strength / 5) * 100) + '%';
        indicator.style.backgroundColor = colors[strength];
        indicator.setAttribute('data-label', labels[strength]);
    });
}
