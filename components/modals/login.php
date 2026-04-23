<?php
$current_user = wp_get_current_user();
$is_logged_in = is_user_logged_in();
$login_error = '';
$error_msg = isset($_GET['login_error']) ? sanitize_key(wp_unslash($_GET['login_error'])) : '';

if ($error_msg) {
    $error_messages = array(
        'invalid' => '用户名或密码错误',
        'logout' => '您已成功退出登录',
        'expired' => '登录已过期，请重新登录',
        'fail' => '登录失败，请重试',
        'protected' => '该页面需要登录后访问',
        'empty' => '请输入用户名和密码',
    );
    $login_error = isset($error_messages[$error_msg]) ? $error_messages[$error_msg] : '登录失败，请重试';
}

$current_url = remove_query_arg(array('login_error', 'loggedout'), timellow_current_url());
$logout_redirect = add_query_arg('login_error', 'logout', $current_url);
$logout_url = wp_logout_url($logout_redirect);
$user_role = '';
if (!empty($current_user->roles)) {
    $user_role = (string) $current_user->roles[0];
}
?>

<div class="login-modal" x-cloak
     x-data="{ loginModalShow: false }"
     x-show="loginModalShow"
     x-transition.opacity.duration.300ms
     @click.self="loginModalShow = false">
    <div class="login-container" x-transition.scale.duration.300ms>
        <!-- 弹框标题 -->
        <div class="login-modal-header">
            <div class="login-modal-title"><?php echo $is_logged_in ? '👤 用户信息' : '🔐 登录'; ?></div>
            <button type="button" class="login-modal-close" @click="loginModalShow = false">×</button>
        </div>

        <!-- 错误/成功提示 -->
        <?php if (!empty($login_error)) : ?>
            <?php
            $is_success = (strpos($login_error, '成功') !== false) || (strpos($login_error, '退出登录') !== false);
            $alert_class = $is_success ? 'login-success-alert' : 'login-error-alert';
            ?>
            <div id="loginAlertMessage" class="<?php echo esc_attr($alert_class); ?>" style="display: none;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="20" height="20" stroke-width="1.5" stroke="currentColor">
                    <?php if ($is_success) : ?>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    <?php else : ?>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                    <?php endif; ?>
                </svg>
                <span><?php echo esc_html($login_error); ?></span>
                <?php if (!$is_success) : ?>
                    <button type="button" class="login-error-close" onclick="document.getElementById('loginAlertMessage').style.display='none'">×</button>
                <?php endif; ?>
            </div>

            <script>
            (function() {
                // 自动显示登录弹窗和错误提示
                const modal = document.querySelector('.login-modal');
                const alert = document.getElementById('loginAlertMessage');

                if (modal && alert) {
                    // 获取 Alpine.js 数据
                    setTimeout(() => {
                        const alpineData = Alpine.$data(modal);
                        if (alpineData) {
                            alpineData.loginModalShow = true;

                            // 显示错误提示（带动画）
                            setTimeout(() => {
                                alert.style.display = 'flex';
                                alert.style.animation = 'slideInDown 0.3s ease-out';

                                // 5秒后自动隐藏错误提示
                                setTimeout(() => {
                                    alert.style.animation = 'fadeOut 0.2s ease-in';
                                    setTimeout(() => {
                                        alert.style.display = 'none';
                                    }, 200);
                                }, 5000);
                            }, 100);
                        }
                    }, 100);
                }
            })();
            </script>
        <?php endif; ?>

        <?php if ($is_logged_in) : ?>
            <!-- 已登录状态：显示用户信息 -->
            <div class="user-info-panel">
                <div class="user-info-avatar">
                    <img src="<?php echo timellow_escape_img_src(timellow_get_wp_avatar_url($current_user->user_email, 80)); ?>" alt="<?php echo esc_attr($current_user->display_name); ?>">
                </div>
                <div class="user-info-details">
                    <div class="user-info-item">
                        <span class="user-info-label">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="16" height="16" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                            </svg>
                            昵称：
                        </span>
                        <span class="user-info-value"><?php echo esc_html($current_user->display_name); ?></span>
                    </div>
                    <div class="user-info-item">
                        <span class="user-info-label">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="16" height="16" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                            </svg>
                            邮箱：
                        </span>
                        <span class="user-info-value"><?php echo esc_html($current_user->user_email); ?></span>
                    </div>
                    <?php if ($current_user->user_url) : ?>
                    <div class="user-info-item">
                        <span class="user-info-label">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="16" height="16" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" />
                            </svg>
                            网址：
                        </span>
                        <span class="user-info-value">
                            <a href="<?php echo esc_url($current_user->user_url); ?>" target="_blank" rel="noopener noreferrer">
                                <?php echo esc_html($current_user->user_url); ?>
                            </a>
                        </span>
                    </div>
                    <?php endif; ?>
                    <div class="user-info-item">
                        <span class="user-info-label">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="16" height="16" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                            </svg>
                            权限组：
                        </span>
                        <span class="user-info-value user-group-<?php echo esc_attr($user_role); ?>">
                            <?php
                            $group_names = array(
                                'administrator' => '管理员',
                                'editor' => '编辑',
                                'contributor' => '贡献者',
                                'subscriber' => '订阅者',
                                'author' => '作者',
                            );
                            echo isset($group_names[$user_role]) ? esc_html($group_names[$user_role]) : esc_html($user_role);
                            ?>
                        </span>
                    </div>
                </div>
                <div class="user-info-actions">
                    <?php if (current_user_can('edit_posts')) : ?>
                    <a href="<?php echo esc_url(admin_url()); ?>" class="user-info-btn user-info-btn-admin" target="_blank">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="16" height="16" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        </svg>
                        管理后台
                    </a>
                    <?php endif; ?>
                    <a href="<?php echo esc_url($logout_url); ?>" class="user-info-btn user-info-btn-logout">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="16" height="16" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9l-3 3m0 0 3 3m-3-3h12.75" />
                        </svg>
                        退出登录
                    </a>
                </div>
            </div>
        <?php else : ?>
            <!-- 未登录状态：显示登录表单 -->
            <form action="<?php echo esc_url(wp_login_url()); ?>" method="post" name="login" role="form" class="login-form" id="loginForm">
                <!-- 客户端验证错误提示 -->
                <div id="validationError" class="login-error-alert" style="display: none;">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="20" height="20" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                    </svg>
                    <span id="validationErrorText"></span>
                    <button type="button" class="login-error-close" onclick="document.getElementById('validationError').style.display='none'">×</button>
                </div>
                <div class="login-form-group">
                    <label for="name" class="login-form-label">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="16" height="16" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                        </svg>
                        用户名
                    </label>
                    <input type="text" id="name" name="log" value="" placeholder="请输入用户名" class="login-form-input" autocomplete="username" autofocus required>
                </div>

                <div class="login-form-group">
                    <label for="password" class="login-form-label">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="16" height="16" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                        </svg>
                        密码
                    </label>
                    <input type="password" id="password" name="pwd" class="login-form-input" placeholder="请输入密码" autocomplete="current-password" required>
                </div>

                <div class="login-form-remember">
                    <label for="remember" class="login-remember-label">
                        <input type="checkbox" name="rememberme" class="login-checkbox" value="forever" id="remember" />
                        <span>下次自动登录</span>
                    </label>
                </div>

                <div class="login-form-submit">
                    <button type="submit" class="login-submit-btn" id="loginSubmitBtn">
                        <svg id="loginIcon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="18" height="18" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
                        </svg>
                        <svg id="loginSpinner" class="login-spinner" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="18" height="18" style="display: none;">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span id="loginBtnText">登录</span>
                    </button>
                    <input type="hidden" name="redirect_to" value="<?php echo esc_url($current_url); ?>">
                </div>
            </form>

            <!-- 表单验证脚本 -->
            <script>
            (function() {
                const form = document.getElementById('loginForm');
                const submitBtn = document.getElementById('loginSubmitBtn');
                const btnText = document.getElementById('loginBtnText');
                const loginIcon = document.getElementById('loginIcon');
                const loginSpinner = document.getElementById('loginSpinner');
                const validationError = document.getElementById('validationError');
                const validationErrorText = document.getElementById('validationErrorText');

                if (form) {
                    form.addEventListener('submit', function(e) {
                        const username = document.getElementById('name').value.trim();
                        const password = document.getElementById('password').value;

                        // 验证用户名
                        if (username.length < 2) {
                            e.preventDefault();
                            showValidationError('用户名至少需要2个字符');
                            return false;
                        }

                        // 验证密码
                        if (password.length < 6) {
                            e.preventDefault();
                            showValidationError('密码至少需要6个字符');
                            return false;
                        }

                        // 验证通过，显示loading状态
                        submitBtn.disabled = true;
                        submitBtn.classList.add('loading');
                        loginIcon.style.display = 'none';
                        loginSpinner.style.display = 'inline-block';
                        btnText.textContent = '登录中...';
                    });
                }

                function showValidationError(message) {
                    validationErrorText.textContent = message;
                    validationError.style.display = 'flex';
                    validationError.style.animation = 'slideInDown 0.3s ease-out';

                    // 3秒后自动隐藏
                    setTimeout(() => {
                        validationError.style.animation = 'fadeOut 0.2s ease-in';
                        setTimeout(() => {
                            validationError.style.display = 'none';
                        }, 200);
                    }, 3000);
                }
            })();
            </script>
        <?php endif; ?>
    </div>
</div>
