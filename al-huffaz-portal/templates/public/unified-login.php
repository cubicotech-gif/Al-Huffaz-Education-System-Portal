<?php
/**
 * Unified Login Page Template
 *
 * Single login page for Admin, Staff, and Sponsors
 * System automatically detects role and redirects to correct dashboard
 *
 * Usage: Create a WordPress page and use shortcode [alhuffaz_unified_login]
 *
 * @package AlHuffaz
 */

use AlHuffaz\Core\Helpers;

defined('ABSPATH') || exit;

// If already logged in, redirect based on role
if (is_user_logged_in()) {
    $user = wp_get_current_user();

    // Admin or Staff → Admin Portal
    if (in_array('alhuffaz_admin', $user->roles) ||
        in_array('alhuffaz_staff', $user->roles) ||
        in_array('administrator', $user->roles)) {
        wp_redirect(Helpers::get_admin_portal_url());
        exit;
    }

    // Sponsor → Check if approved
    if (in_array('alhuffaz_sponsor', $user->roles)) {
        $status = get_user_meta($user->ID, 'account_status', true);
        if ($status === 'approved' || empty($status)) {
            wp_redirect(Helpers::get_sponsor_dashboard_url());
            exit;
        }
    }
}
?>

<div class="alhuffaz-unified-login-page">
    <div class="alhuffaz-login-container">
        <div class="alhuffaz-login-header">
            <img src="https://portal.alhuffazeducationsystem.com/wp-content/uploads/2026/01/cropped-AlHuffaz-Logo-1.png" alt="<?php bloginfo('name'); ?>" class="alhuffaz-login-logo">
            <h1><?php echo get_option('alhuffaz_portal_title', __('Al-Huffaz Portal', 'al-huffaz-portal')); ?></h1>
            <p><?php _e('Login to access your dashboard', 'al-huffaz-portal'); ?></p>
        </div>

        <?php
        // Show messages based on URL parameters
        if (isset($_GET['login']) && $_GET['login'] === 'failed') {
            echo '<div class="alhuffaz-login-message error">
                <i class="fas fa-exclamation-circle"></i>
                <span>' . __('Invalid username or password. Please try again.', 'al-huffaz-portal') . '</span>
            </div>';
        }

        if (isset($_GET['login']) && $_GET['login'] === 'pending') {
            echo '<div class="alhuffaz-login-message warning">
                <i class="fas fa-clock"></i>
                <span>' . __('Your account is pending approval. You will receive an email once approved.', 'al-huffaz-portal') . '</span>
            </div>';
        }

        if (isset($_GET['registered']) && $_GET['registered'] === 'success') {
            echo '<div class="alhuffaz-login-message success">
                <i class="fas fa-check-circle"></i>
                <span>' . __('Registration successful! Your account is pending approval. You will receive an email once approved.', 'al-huffaz-portal') . '</span>
            </div>';
        }

        if (isset($_GET['approved']) && $_GET['approved'] === 'yes') {
            echo '<div class="alhuffaz-login-message success">
                <i class="fas fa-check-circle"></i>
                <span>' . __('Your account has been approved! You can now login.', 'al-huffaz-portal') . '</span>
            </div>';
        }

        if (isset($_GET['logout']) && $_GET['logout'] === 'success') {
            echo '<div class="alhuffaz-login-message info">
                <i class="fas fa-info-circle"></i>
                <span>' . __('You have been logged out successfully.', 'al-huffaz-portal') . '</span>
            </div>';
        }

        if (isset($_GET['access']) && $_GET['access'] === 'denied') {
            echo '<div class="alhuffaz-login-message error">
                <i class="fas fa-ban"></i>
                <span>' . __('Access denied. Please login with valid credentials.', 'al-huffaz-portal') . '</span>
            </div>';
        }

        // WordPress login form
        $args = array(
            'echo'           => true,
            'redirect'       => home_url('/login/'), // Will be handled by login_redirect filter
            'form_id'        => 'alhuffaz-unified-loginform',
            'label_username' => __('Email or Username', 'al-huffaz-portal'),
            'label_password' => __('Password', 'al-huffaz-portal'),
            'label_remember' => __('Remember Me', 'al-huffaz-portal'),
            'label_log_in'   => __('Login', 'al-huffaz-portal'),
            'remember'       => true,
            'value_remember' => true,
        );
        wp_login_form($args);
        ?>

        <div class="alhuffaz-login-links">
            <a href="<?php echo wp_lostpassword_url(home_url('/login/')); ?>">
                <i class="fas fa-key"></i> <?php _e('Forgot Password?', 'al-huffaz-portal'); ?>
            </a>
        </div>

        <div class="alhuffaz-login-separator">
            <span><?php _e('OR', 'al-huffaz-portal'); ?></span>
        </div>

        <div class="alhuffaz-login-register">
            <p><?php _e("Don't have an account?", 'al-huffaz-portal'); ?></p>
            <a href="<?php echo home_url('/register/'); ?>" class="alhuffaz-btn-register">
                <i class="fas fa-user-plus"></i> <?php _e('Register as Sponsor', 'al-huffaz-portal'); ?>
            </a>
            <p class="alhuffaz-register-note">
                <small><?php _e('Sponsors can browse and sponsor students', 'al-huffaz-portal'); ?></small>
            </p>
        </div>
    </div>
</div>

<style>
.alhuffaz-unified-login-page {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 100vh;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 20px;
}

.alhuffaz-login-container {
    background: white;
    padding: 40px;
    border-radius: 16px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    max-width: 450px;
    width: 100%;
    animation: slideUp 0.4s ease-out;
}

@keyframes slideUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.alhuffaz-login-header {
    text-align: center;
    margin-bottom: 30px;
}

.alhuffaz-login-logo {
    width: 80px;
    height: 80px;
    margin-bottom: 20px;
    object-fit: contain;
}

.alhuffaz-login-header h1 {
    margin: 0 0 10px 0;
    font-size: 28px;
    color: #1d1d1f;
}

.alhuffaz-login-header p {
    margin: 0;
    color: #666;
    font-size: 14px;
}

.alhuffaz-login-message {
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 14px;
}

.alhuffaz-login-message i {
    font-size: 18px;
}

.alhuffaz-login-message.success {
    background: #d1fae5;
    color: #065f46;
    border: 1px solid #6ee7b7;
}

.alhuffaz-login-message.error {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fca5a5;
}

.alhuffaz-login-message.warning {
    background: #fef3c7;
    color: #92400e;
    border: 1px solid #fcd34d;
}

.alhuffaz-login-message.info {
    background: #dbeafe;
    color: #1e40af;
    border: 1px solid #93c5fd;
}

#alhuffaz-unified-loginform {
    margin: 20px 0;
}

#alhuffaz-unified-loginform label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
    color: #374151;
    font-size: 14px;
}

#alhuffaz-unified-loginform input[type="text"],
#alhuffaz-unified-loginform input[type="password"] {
    width: 100%;
    padding: 12px 16px;
    margin-bottom: 16px;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    font-size: 16px;
    transition: all 0.3s;
    box-sizing: border-box;
}

#alhuffaz-unified-loginform input[type="text"]:focus,
#alhuffaz-unified-loginform input[type="password"]:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

#alhuffaz-unified-loginform .login-remember {
    margin-bottom: 16px;
}

#alhuffaz-unified-loginform input[type="checkbox"] {
    margin-right: 8px;
}

#alhuffaz-unified-loginform .login-remember label {
    display: inline;
    font-weight: normal;
    font-size: 14px;
}

#alhuffaz-unified-loginform input[type="submit"] {
    width: 100%;
    padding: 14px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}

#alhuffaz-unified-loginform input[type="submit"]:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
}

.alhuffaz-login-links {
    text-align: center;
    margin: 20px 0;
}

.alhuffaz-login-links a {
    color: #667eea;
    text-decoration: none;
    font-size: 14px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.alhuffaz-login-links a:hover {
    text-decoration: underline;
}

.alhuffaz-login-separator {
    text-align: center;
    margin: 30px 0;
    position: relative;
}

.alhuffaz-login-separator::before,
.alhuffaz-login-separator::after {
    content: '';
    position: absolute;
    top: 50%;
    width: 40%;
    height: 1px;
    background: #e5e7eb;
}

.alhuffaz-login-separator::before { left: 0; }
.alhuffaz-login-separator::after { right: 0; }

.alhuffaz-login-separator span {
    background: white;
    padding: 0 15px;
    color: #9ca3af;
    font-size: 12px;
    font-weight: 600;
}

.alhuffaz-login-register {
    text-align: center;
    padding: 20px;
    background: #f9fafb;
    border-radius: 8px;
    margin: 20px 0;
}

.alhuffaz-login-register p {
    margin: 0 0 12px 0;
    color: #6b7280;
}

.alhuffaz-btn-register {
    display: inline-block;
    padding: 12px 24px;
    background: #10b981;
    color: white !important;
    text-decoration: none !important;
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.3s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.alhuffaz-btn-register:hover {
    background: #059669;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
}

.alhuffaz-register-note {
    margin-top: 8px !important;
    font-size: 12px;
    color: #9ca3af;
}

/* Responsive */
@media (max-width: 640px) {
    .alhuffaz-login-container {
        padding: 30px 20px;
    }

    .alhuffaz-login-header h1 {
        font-size: 24px;
    }
}
</style>
