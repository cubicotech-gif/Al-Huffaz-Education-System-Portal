<?php
/**
 * Sponsor Dashboard Template
 * Al-Huffaz Education System Portal
 *
 * Modern Top-Navigation Design
 * Inspired by Stripe, Linear, Notion dashboards
 */

use AlHuffaz\Frontend\Sponsor_Dashboard;
use AlHuffaz\Core\Helpers;
use AlHuffaz\Core\Roles;

if (!defined('ABSPATH')) exit;

// CRITICAL: Disable all caching for sponsor dashboard
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: 0');

// Check if user is logged in
if (!is_user_logged_in()) {
    ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
    .sp-login-page {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 40px 20px;
        font-family: 'Inter', sans-serif;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    .sp-login-card {
        background: white;
        border-radius: 24px;
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
        padding: 48px;
        max-width: 420px;
        width: 100%;
        text-align: center;
    }
    .sp-login-header i { font-size: 64px; background: linear-gradient(135deg, #667eea, #764ba2); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin-bottom: 20px; display: block; }
    .sp-login-header h2 { margin: 0 0 8px 0; font-size: 28px; font-weight: 700; color: #1a1a2e; }
    .sp-login-header p { margin: 0 0 32px 0; color: #6b7280; font-size: 15px; }
    .sp-login-form label { display: block; font-weight: 600; color: #374151; margin-bottom: 8px; font-size: 14px; text-align: left; }
    .sp-login-form input[type="text"], .sp-login-form input[type="password"] {
        width: 100%; padding: 14px 16px; border: 2px solid #e5e7eb; border-radius: 12px;
        font-size: 15px; margin-bottom: 20px; font-family: 'Inter', sans-serif; box-sizing: border-box; transition: all 0.2s;
    }
    .sp-login-form input:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1); }
    .sp-login-form input[type="submit"] {
        width: 100%; padding: 16px; background: linear-gradient(135deg, #667eea, #764ba2); color: white;
        border: none; border-radius: 12px; font-size: 16px; font-weight: 600; cursor: pointer;
        font-family: 'Inter', sans-serif; transition: transform 0.2s, box-shadow 0.2s;
    }
    .sp-login-form input[type="submit"]:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4); }
    .sp-login-footer { margin-top: 32px; padding-top: 24px; border-top: 1px solid #e5e7eb; }
    .sp-login-footer p { margin: 0 0 16px 0; color: #6b7280; font-size: 14px; }
    .sp-login-btn { display: inline-flex; align-items: center; gap: 8px; padding: 14px 28px; border-radius: 12px;
        font-weight: 600; font-size: 15px; text-decoration: none; background: #f3f4f6; color: #374151; transition: all 0.2s; }
    .sp-login-btn:hover { background: #e5e7eb; }
    </style>
    <div class="sp-login-page">
        <div class="sp-login-card">
            <div class="sp-login-header">
                <i class="fas fa-hand-holding-heart"></i>
                <h2><?php _e('Sponsor Portal', 'al-huffaz-portal'); ?></h2>
                <p><?php _e('Login to access your sponsor dashboard', 'al-huffaz-portal'); ?></p>
            </div>
            <div class="sp-login-form">
                <?php wp_login_form(array('redirect' => get_permalink(), 'form_id' => 'sp-login-form',
                    'label_username' => __('Email or Username', 'al-huffaz-portal'), 'label_password' => __('Password', 'al-huffaz-portal'),
                    'label_remember' => __('Remember Me', 'al-huffaz-portal'), 'label_log_in' => __('Sign In', 'al-huffaz-portal'))); ?>
            </div>
            <div class="sp-login-footer">
                <p><?php _e('Not a sponsor yet?', 'al-huffaz-portal'); ?></p>
                <a href="<?php echo home_url('/become-a-sponsor'); ?>" class="sp-login-btn">
                    <i class="fas fa-heart"></i> <?php _e('Become a Sponsor', 'al-huffaz-portal'); ?>
                </a>
            </div>
        </div>
    </div>
    <?php
    return;
}

$user = wp_get_current_user();
$user_id = $user->ID;
$data = Sponsor_Dashboard::get_dashboard_data($user_id);

// Get sponsor profile data
$sponsor_post = get_posts(array(
    'post_type' => 'alhuffaz_sponsor',
    'posts_per_page' => 1,
    'meta_query' => array(array('key' => '_sponsor_user_id', 'value' => $user_id)),
));
$sponsor_id = !empty($sponsor_post) ? $sponsor_post[0]->ID : 0;

// User approval status
$is_user_approved = true;
if (function_exists('um_user') && function_exists('um_is_user_approved')) {
    $is_user_approved = um_is_user_approved($user_id);
}

$sponsor_status = $is_user_approved ? 'approved' : 'pending';
$sponsor_phone = $sponsor_id ? get_post_meta($sponsor_id, '_sponsor_phone', true) : get_user_meta($user_id, 'phone', true);
$sponsor_country = $sponsor_id ? get_post_meta($sponsor_id, '_sponsor_country', true) : get_user_meta($user_id, 'country', true);
$sponsor_whatsapp = $sponsor_id ? get_post_meta($sponsor_id, '_sponsor_whatsapp', true) : '';
$member_since = date_i18n(get_option('date_format'), strtotime($user->user_registered));

// CRITICAL FIX: Get available students for sponsorship using correct meta keys
$available_students = get_posts(array(
    'post_type' => 'student',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'meta_query' => array(
        'relation' => 'AND',
        array('key' => 'donation_eligible', 'value' => 'yes'),
        array(
            'relation' => 'OR',
            array('key' => 'already_sponsored', 'value' => 'yes', 'compare' => '!='),
            array('key' => 'already_sponsored', 'compare' => 'NOT EXISTS'),
        ),
    ),
));

$portal_url = get_permalink();
$nonce = wp_create_nonce('alhuffaz_public_nonce');
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

<style>
/* ============================================
   SPONSOR PORTAL - MODERN TOP NAV DESIGN
   Inspired by Stripe, Linear, Notion
   ============================================ */

/* CSS Variables */
.sp-portal {
    --sp-primary: #6366f1;
    --sp-primary-dark: #4f46e5;
    --sp-primary-light: #eef2ff;
    --sp-success: #10b981;
    --sp-success-light: #d1fae5;
    --sp-warning: #f59e0b;
    --sp-warning-light: #fef3c7;
    --sp-danger: #ef4444;
    --sp-danger-light: #fee2e2;
    --sp-text: #111827;
    --sp-text-secondary: #6b7280;
    --sp-border: #e5e7eb;
    --sp-bg: #f9fafb;
    --sp-card: #ffffff;
    --sp-header-bg: #ffffff;
}

/* CSS Reset */
.sp-portal,
.sp-portal *,
.sp-portal *::before,
.sp-portal *::after {
    box-sizing: border-box !important;
    margin: 0;
    padding: 0;
}

.sp-portal a { text-decoration: none; color: inherit; }
.sp-portal ul, .sp-portal ol { list-style: none; }
.sp-portal button, .sp-portal input, .sp-portal select, .sp-portal textarea { font-family: inherit; }

/* Main Container */
.sp-portal {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
    background: var(--sp-bg) !important;
    min-height: 100vh !important;
    color: var(--sp-text) !important;
    line-height: 1.5 !important;
    font-size: 14px !important;
    -webkit-font-smoothing: antialiased;
}

/* ==================== TOP HEADER ==================== */
.sp-header {
    background: var(--sp-header-bg) !important;
    border-bottom: 1px solid var(--sp-border) !important;
    position: sticky !important;
    top: 0 !important;
    z-index: 100 !important;
}

body.admin-bar .sp-portal .sp-header {
    top: 32px !important;
}
@media screen and (max-width: 782px) {
    body.admin-bar .sp-portal .sp-header {
        top: 46px !important;
    }
}

.sp-header-inner {
    max-width: 1400px !important;
    margin: 0 auto !important;
    padding: 0 24px !important;
}

.sp-header-top {
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    height: 64px !important;
}

.sp-logo {
    display: flex !important;
    align-items: center !important;
    gap: 12px !important;
    font-size: 18px !important;
    font-weight: 700 !important;
    color: var(--sp-text) !important;
}

.sp-logo i {
    font-size: 24px !important;
    color: var(--sp-primary) !important;
}

.sp-user-menu {
    display: flex !important;
    align-items: center !important;
    gap: 16px !important;
}

.sp-user-info {
    display: flex !important;
    align-items: center !important;
    gap: 12px !important;
    padding: 8px 16px !important;
    background: var(--sp-bg) !important;
    border-radius: 100px !important;
    cursor: pointer !important;
    transition: all 0.2s !important;
}

.sp-user-info:hover {
    background: var(--sp-border) !important;
}

.sp-avatar {
    width: 36px !important;
    height: 36px !important;
    border-radius: 50% !important;
    background: linear-gradient(135deg, var(--sp-primary), var(--sp-primary-dark)) !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    font-size: 14px !important;
    font-weight: 700 !important;
    color: white !important;
}

.sp-user-name {
    font-weight: 600 !important;
    font-size: 14px !important;
}

.sp-status-badge {
    display: inline-flex !important;
    align-items: center !important;
    gap: 4px !important;
    padding: 4px 10px !important;
    border-radius: 100px !important;
    font-size: 11px !important;
    font-weight: 600 !important;
}

.sp-status-badge.approved {
    background: var(--sp-success-light) !important;
    color: #065f46 !important;
}

.sp-status-badge.pending {
    background: var(--sp-warning-light) !important;
    color: #92400e !important;
}

.sp-logout-btn {
    display: flex !important;
    align-items: center !important;
    gap: 6px !important;
    padding: 8px 16px !important;
    background: transparent !important;
    border: 1px solid var(--sp-border) !important;
    border-radius: 8px !important;
    font-size: 13px !important;
    font-weight: 500 !important;
    color: var(--sp-text-secondary) !important;
    cursor: pointer !important;
    transition: all 0.2s !important;
}

.sp-logout-btn:hover {
    background: var(--sp-danger-light) !important;
    border-color: var(--sp-danger) !important;
    color: var(--sp-danger) !important;
}

/* ==================== NOTIFICATIONS ==================== */
.sp-notification-bell {
    position: relative !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    width: 40px !important;
    height: 40px !important;
    background: transparent !important;
    border: 1px solid var(--sp-border) !important;
    border-radius: 8px !important;
    color: var(--sp-text-secondary) !important;
    cursor: pointer !important;
    transition: all 0.2s !important;
}

.sp-notification-bell:hover {
    background: var(--sp-bg) !important;
    color: var(--sp-primary) !important;
}

.sp-notification-bell i {
    font-size: 18px !important;
}

.sp-notification-badge {
    position: absolute !important;
    top: -4px !important;
    right: -4px !important;
    min-width: 20px !important;
    height: 20px !important;
    padding: 0 6px !important;
    background: var(--sp-danger) !important;
    color: white !important;
    font-size: 11px !important;
    font-weight: 600 !important;
    border-radius: 10px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
}

.sp-notification-panel {
    position: absolute !important;
    top: 70px !important;
    right: 24px !important;
    width: 420px !important;
    max-height: 500px !important;
    background: white !important;
    border-radius: 12px !important;
    box-shadow: 0 8px 32px rgba(0,0,0,0.12) !important;
    z-index: 9999 !important;
    overflow: hidden !important;
}

.sp-notification-header {
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    padding: 16px 20px !important;
    border-bottom: 1px solid var(--sp-border) !important;
}

.sp-notification-header h3 {
    margin: 0 !important;
    font-size: 16px !important;
    font-weight: 700 !important;
    color: var(--sp-text) !important;
}

.sp-mark-all-read {
    display: flex !important;
    align-items: center !important;
    gap: 6px !important;
    padding: 6px 12px !important;
    background: transparent !important;
    border: none !important;
    border-radius: 6px !important;
    font-size: 12px !important;
    font-weight: 500 !important;
    color: var(--sp-primary) !important;
    cursor: pointer !important;
    transition: all 0.2s !important;
}

.sp-mark-all-read:hover {
    background: var(--sp-primary-light) !important;
}

.sp-notification-list {
    max-height: 420px !important;
    overflow-y: auto !important;
}

.sp-notification-loading {
    padding: 40px 20px !important;
    text-align: center !important;
    color: var(--sp-text-secondary) !important;
    font-size: 14px !important;
}

.sp-notification-empty {
    padding: 40px 20px !important;
    text-align: center !important;
    color: var(--sp-text-secondary) !important;
    font-size: 14px !important;
}

.sp-notification-item {
    display: flex !important;
    gap: 12px !important;
    padding: 16px 20px !important;
    border-bottom: 1px solid var(--sp-border) !important;
    cursor: pointer !important;
    transition: all 0.2s !important;
}

.sp-notification-item:hover {
    background: var(--sp-bg) !important;
}

.sp-notification-item.unread {
    background: #eff6ff !important;
}

.sp-notification-icon {
    flex-shrink: 0 !important;
    width: 40px !important;
    height: 40px !important;
    border-radius: 8px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    font-size: 16px !important;
}

.sp-notification-icon.success {
    background: var(--sp-success-light) !important;
    color: #065f46 !important;
}

.sp-notification-icon.info {
    background: var(--sp-primary-light) !important;
    color: var(--sp-primary-dark) !important;
}

.sp-notification-icon.warning {
    background: var(--sp-warning-light) !important;
    color: #92400e !important;
}

.sp-notification-content {
    flex: 1 !important;
    min-width: 0 !important;
}

.sp-notification-title {
    font-size: 14px !important;
    font-weight: 600 !important;
    color: var(--sp-text) !important;
    margin-bottom: 4px !important;
}

.sp-notification-message {
    font-size: 13px !important;
    color: var(--sp-text-secondary) !important;
    line-height: 1.5 !important;
    margin-bottom: 6px !important;
}

.sp-notification-time {
    font-size: 11px !important;
    color: var(--sp-text-muted) !important;
}

/* ==================== NAVIGATION TABS ==================== */
.sp-nav {
    display: flex !important;
    gap: 4px !important;
    padding-bottom: 0 !important;
    overflow-x: auto !important;
    scrollbar-width: none !important;
}

.sp-nav::-webkit-scrollbar { display: none; }

.sp-nav-item {
    display: flex !important;
    align-items: center !important;
    gap: 8px !important;
    padding: 12px 16px !important;
    font-size: 14px !important;
    font-weight: 500 !important;
    color: var(--sp-text-secondary) !important;
    border-bottom: 2px solid transparent !important;
    cursor: pointer !important;
    transition: all 0.2s !important;
    white-space: nowrap !important;
    background: transparent !important;
    border-top: none !important;
    border-left: none !important;
    border-right: none !important;
}

.sp-nav-item:hover {
    color: var(--sp-text) !important;
    background: var(--sp-bg) !important;
}

.sp-nav-item.active {
    color: var(--sp-primary) !important;
    border-bottom-color: var(--sp-primary) !important;
}

.sp-nav-item i {
    font-size: 16px !important;
}

.sp-nav-badge {
    background: var(--sp-primary) !important;
    color: white !important;
    padding: 2px 8px !important;
    border-radius: 100px !important;
    font-size: 11px !important;
    font-weight: 700 !important;
}

.sp-nav-badge.warning {
    background: var(--sp-warning) !important;
}

/* Mobile Menu Toggle */
.sp-menu-toggle {
    display: none !important;
    align-items: center !important;
    justify-content: center !important;
    width: 40px !important;
    height: 40px !important;
    background: var(--sp-bg) !important;
    border: none !important;
    border-radius: 8px !important;
    font-size: 20px !important;
    color: var(--sp-text) !important;
    cursor: pointer !important;
}

/* ==================== MAIN CONTENT ==================== */
.sp-main {
    max-width: 1400px !important;
    margin: 0 auto !important;
    padding: 32px 24px !important;
}

/* Panels */
.sp-panel {
    display: none !important;
}

.sp-panel.active {
    display: block !important;
    animation: spFadeIn 0.3s ease !important;
}

@keyframes spFadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Page Title */
.sp-page-title {
    font-size: 28px !important;
    font-weight: 700 !important;
    color: var(--sp-text) !important;
    margin: 0 0 8px 0 !important;
}

.sp-page-subtitle {
    font-size: 15px !important;
    color: var(--sp-text-secondary) !important;
    margin: 0 0 24px 0 !important;
}

/* ==================== STATS GRID ==================== */
.sp-stats {
    display: grid !important;
    grid-template-columns: repeat(4, 1fr) !important;
    gap: 20px !important;
    margin-bottom: 32px !important;
}

.sp-stat {
    background: var(--sp-card) !important;
    border-radius: 16px !important;
    padding: 24px !important;
    border: 1px solid var(--sp-border) !important;
    transition: all 0.2s !important;
}

.sp-stat:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05) !important;
    transform: translateY(-2px) !important;
}

.sp-stat-header {
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    margin-bottom: 12px !important;
}

.sp-stat-icon {
    width: 48px !important;
    height: 48px !important;
    border-radius: 12px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    font-size: 20px !important;
}

.sp-stat-icon.blue { background: #dbeafe !important; color: #1e40af !important; }
.sp-stat-icon.green { background: #d1fae5 !important; color: #065f46 !important; }
.sp-stat-icon.orange { background: #fef3c7 !important; color: #92400e !important; }
.sp-stat-icon.purple { background: #ede9fe !important; color: #5b21b6 !important; }

.sp-stat-value {
    font-size: 32px !important;
    font-weight: 700 !important;
    color: var(--sp-text) !important;
    margin-bottom: 4px !important;
}

.sp-stat-label {
    font-size: 13px !important;
    color: var(--sp-text-secondary) !important;
}

/* ==================== CARDS ==================== */
.sp-card {
    background: var(--sp-card) !important;
    border-radius: 16px !important;
    border: 1px solid var(--sp-border) !important;
    margin-bottom: 24px !important;
    overflow: hidden !important;
}

.sp-card-header {
    padding: 20px 24px !important;
    border-bottom: 1px solid var(--sp-border) !important;
    display: flex !important;
    justify-content: space-between !important;
    align-items: center !important;
}

.sp-card-title {
    font-size: 16px !important;
    font-weight: 600 !important;
    margin: 0 !important;
    display: flex !important;
    align-items: center !important;
    gap: 10px !important;
    color: var(--sp-text) !important;
}

.sp-card-title i {
    color: var(--sp-primary) !important;
}

.sp-card-body {
    padding: 24px !important;
}

/* ==================== STUDENT CARDS GRID ==================== */
.sp-students-grid {
    display: grid !important;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)) !important;
    gap: 24px !important;
}

.sp-student-card {
    background: var(--sp-card) !important;
    border-radius: 16px !important;
    border: 1px solid var(--sp-border) !important;
    overflow: hidden !important;
    transition: all 0.3s !important;
}

.sp-student-card:hover {
    box-shadow: 0 8px 24px rgba(99, 102, 241, 0.15) !important;
    transform: translateY(-4px) !important;
    border-color: var(--sp-primary) !important;
}

.sp-student-header {
    padding: 20px !important;
    display: flex !important;
    align-items: center !important;
    gap: 16px !important;
    border-bottom: 1px solid var(--sp-border) !important;
}

.sp-student-photo {
    width: 64px !important;
    height: 64px !important;
    border-radius: 50% !important;
    object-fit: cover !important;
    flex-shrink: 0 !important;
    border: 3px solid var(--sp-primary-light) !important;
}

.sp-student-placeholder {
    width: 64px !important;
    height: 64px !important;
    border-radius: 50% !important;
    background: linear-gradient(135deg, var(--sp-primary), var(--sp-primary-dark)) !important;
    color: white !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    font-size: 24px !important;
    font-weight: 700 !important;
    flex-shrink: 0 !important;
}

.sp-student-info {
    flex: 1 !important;
    min-width: 0 !important;
}

.sp-student-info h3 {
    margin: 0 0 8px 0 !important;
    font-size: 16px !important;
    font-weight: 600 !important;
    color: var(--sp-text) !important;
    white-space: nowrap !important;
    overflow: hidden !important;
    text-overflow: ellipsis !important;
}

.sp-student-badges {
    display: flex !important;
    gap: 8px !important;
    flex-wrap: wrap !important;
}

.sp-badge {
    padding: 4px 10px !important;
    border-radius: 100px !important;
    font-size: 11px !important;
    font-weight: 600 !important;
}

.sp-badge-grade { background: var(--sp-primary-light) !important; color: var(--sp-primary-dark) !important; }
.sp-badge-category { background: var(--sp-success-light) !important; color: #065f46 !important; }

.sp-student-body {
    padding: 20px !important;
}

.sp-fee-grid {
    display: grid !important;
    grid-template-columns: repeat(3, 1fr) !important;
    gap: 12px !important;
    margin-bottom: 16px !important;
}

.sp-fee-item {
    text-align: center !important;
    padding: 12px !important;
    background: var(--sp-bg) !important;
    border-radius: 10px !important;
}

.sp-fee-label {
    font-size: 11px !important;
    color: var(--sp-text-secondary) !important;
    margin-bottom: 4px !important;
}

.sp-fee-value {
    font-size: 15px !important;
    font-weight: 600 !important;
    color: var(--sp-text) !important;
}

.sp-student-footer {
    padding: 16px 20px !important;
    background: var(--sp-bg) !important;
    border-top: 1px solid var(--sp-border) !important;
    display: flex !important;
    gap: 12px !important;
}

/* ==================== BUTTONS ==================== */
.sp-btn {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 8px !important;
    padding: 12px 20px !important;
    border-radius: 10px !important;
    font-weight: 600 !important;
    font-size: 14px !important;
    cursor: pointer !important;
    transition: all 0.2s !important;
    border: none !important;
    font-family: 'Inter', sans-serif !important;
    white-space: nowrap !important;
}

.sp-btn-primary {
    background: var(--sp-primary) !important;
    color: white !important;
}

.sp-btn-primary:hover {
    background: var(--sp-primary-dark) !important;
    transform: translateY(-1px) !important;
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3) !important;
}

.sp-btn-secondary {
    background: var(--sp-bg) !important;
    color: var(--sp-text) !important;
    border: 1px solid var(--sp-border) !important;
}

.sp-btn-secondary:hover {
    background: var(--sp-border) !important;
}

.sp-btn-success {
    background: var(--sp-success) !important;
    color: white !important;
}

.sp-btn-success:hover {
    background: #059669 !important;
}

.sp-btn-block {
    width: 100% !important;
}

.sp-btn-sm {
    padding: 8px 14px !important;
    font-size: 13px !important;
}

/* ==================== ALERTS ==================== */
.sp-alert {
    padding: 16px 20px !important;
    border-radius: 12px !important;
    margin-bottom: 24px !important;
    display: flex !important;
    align-items: flex-start !important;
    gap: 12px !important;
}

.sp-alert-warning {
    background: var(--sp-warning-light) !important;
    color: #92400e !important;
    border: 1px solid #fde68a !important;
}

.sp-alert-success {
    background: var(--sp-success-light) !important;
    color: #065f46 !important;
    border: 1px solid #a7f3d0 !important;
}

.sp-alert i {
    font-size: 20px !important;
    flex-shrink: 0 !important;
    margin-top: 2px !important;
}

.sp-alert-content strong {
    display: block !important;
    margin-bottom: 4px !important;
}

/* ==================== PROFILE GRID ==================== */
.sp-profile-grid {
    display: grid !important;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)) !important;
    gap: 16px !important;
}

.sp-profile-item {
    display: flex !important;
    align-items: center !important;
    gap: 16px !important;
    padding: 16px !important;
    background: var(--sp-bg) !important;
    border-radius: 12px !important;
}

.sp-profile-icon {
    width: 48px !important;
    height: 48px !important;
    border-radius: 12px !important;
    background: white !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    color: var(--sp-primary) !important;
    font-size: 18px !important;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05) !important;
    flex-shrink: 0 !important;
}

.sp-profile-label {
    font-size: 12px !important;
    color: var(--sp-text-secondary) !important;
    margin-bottom: 2px !important;
}

.sp-profile-value {
    font-size: 15px !important;
    font-weight: 600 !important;
    color: var(--sp-text) !important;
}

/* ==================== TABLES ==================== */
.sp-table-wrap {
    overflow-x: auto !important;
}

.sp-table {
    width: 100% !important;
    border-collapse: collapse !important;
}

.sp-table th,
.sp-table td {
    padding: 14px 16px !important;
    text-align: left !important;
    border-bottom: 1px solid var(--sp-border) !important;
}

.sp-table thead th {
    background: var(--sp-bg) !important;
    font-weight: 600 !important;
    font-size: 12px !important;
    text-transform: uppercase !important;
    letter-spacing: 0.5px !important;
    color: var(--sp-text-secondary) !important;
}

.sp-table tbody tr:hover {
    background: var(--sp-bg) !important;
}

.sp-status {
    display: inline-flex !important;
    align-items: center !important;
    gap: 6px !important;
    padding: 6px 12px !important;
    border-radius: 100px !important;
    font-size: 12px !important;
    font-weight: 600 !important;
}

.sp-status.approved { background: var(--sp-success-light) !important; color: #065f46 !important; }
.sp-status.pending { background: var(--sp-warning-light) !important; color: #92400e !important; }
.sp-status.rejected { background: var(--sp-danger-light) !important; color: #991b1b !important; }

/* ==================== FORMS ==================== */
.sp-form-group {
    margin-bottom: 20px !important;
}

.sp-form-label {
    display: block !important;
    font-weight: 600 !important;
    margin-bottom: 8px !important;
    font-size: 14px !important;
    color: var(--sp-text) !important;
}

.sp-form-input,
.sp-form-select {
    width: 100% !important;
    padding: 12px 16px !important;
    border: 2px solid var(--sp-border) !important;
    border-radius: 10px !important;
    font-size: 14px !important;
    font-family: 'Inter', sans-serif !important;
    transition: all 0.2s !important;
    background: white !important;
    color: var(--sp-text) !important;
}

.sp-form-input:focus,
.sp-form-select:focus {
    outline: none !important;
    border-color: var(--sp-primary) !important;
    box-shadow: 0 0 0 4px var(--sp-primary-light) !important;
}

.sp-form-row {
    display: grid !important;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)) !important;
    gap: 16px !important;
}

.sp-form-actions {
    display: flex !important;
    gap: 12px !important;
    justify-content: flex-end !important;
    margin-top: 24px !important;
}

/* ==================== INFO GRID ==================== */
.sp-info-grid {
    display: grid !important;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)) !important;
    gap: 20px !important;
}

.sp-info-item {
    display: flex !important;
    flex-direction: column !important;
    gap: 4px !important;
}

.sp-info-label {
    font-size: 13px !important;
    color: var(--sp-text-secondary) !important;
    font-weight: 500 !important;
}

.sp-info-value {
    font-size: 16px !important;
    color: var(--sp-text) !important;
    font-weight: 600 !important;
}

/* ==================== EMPTY STATE ==================== */
.sp-empty {
    text-align: center !important;
    padding: 60px 20px !important;
    color: var(--sp-text-secondary) !important;
}

.sp-empty i {
    font-size: 64px !important;
    opacity: 0.3 !important;
    margin-bottom: 16px !important;
    display: block !important;
}

.sp-empty h3 {
    margin: 0 0 8px 0 !important;
    font-size: 18px !important;
    color: var(--sp-text) !important;
}

.sp-empty p {
    margin: 0 0 20px 0 !important;
}

/* ==================== PAYMENT PLANS ==================== */
.sp-plan-title {
    font-size: 14px !important;
    font-weight: 600 !important;
    color: var(--sp-text) !important;
    margin: 0 0 12px 0 !important;
    text-align: center !important;
}

.sp-plan-grid {
    display: grid !important;
    grid-template-columns: repeat(2, 1fr) !important;
    gap: 10px !important;
}

.sp-plan-btn {
    display: flex !important;
    flex-direction: column !important;
    align-items: center !important;
    padding: 16px 12px !important;
    background: var(--sp-bg) !important;
    border: 2px solid var(--sp-border) !important;
    border-radius: 12px !important;
    cursor: pointer !important;
    transition: all 0.2s !important;
    font-family: 'Inter', sans-serif !important;
    position: relative !important;
}

.sp-plan-btn:hover {
    border-color: var(--sp-primary) !important;
    background: var(--sp-primary-light) !important;
}

.sp-plan-btn.featured {
    background: var(--sp-primary-light) !important;
    border-color: var(--sp-primary) !important;
}

.sp-plan-badge {
    position: absolute !important;
    top: -8px !important;
    right: -8px !important;
    background: var(--sp-success) !important;
    color: white !important;
    font-size: 9px !important;
    font-weight: 700 !important;
    padding: 3px 8px !important;
    border-radius: 100px !important;
}

.sp-plan-duration {
    font-size: 13px !important;
    font-weight: 600 !important;
    color: var(--sp-text) !important;
    margin-bottom: 4px !important;
}

.sp-plan-amount {
    font-size: 18px !important;
    font-weight: 700 !important;
    color: var(--sp-primary) !important;
}

/* ==================== MODAL ==================== */
.sp-modal {
    display: none !important;
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    width: 100% !important;
    height: 100% !important;
    background: rgba(0, 0, 0, 0.5) !important;
    z-index: 1000 !important;
    align-items: center !important;
    justify-content: center !important;
    padding: 20px !important;
}

.sp-modal.open {
    display: flex !important;
}

.sp-modal-content {
    background: white !important;
    border-radius: 20px !important;
    max-width: 520px !important;
    width: 100% !important;
    max-height: 90vh !important;
    overflow-y: auto !important;
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25) !important;
}

.sp-modal-header {
    padding: 20px 24px !important;
    border-bottom: 1px solid var(--sp-border) !important;
    display: flex !important;
    justify-content: space-between !important;
    align-items: center !important;
}

.sp-modal-header h3 {
    margin: 0 !important;
    font-size: 18px !important;
    font-weight: 600 !important;
    display: flex !important;
    align-items: center !important;
    gap: 10px !important;
    color: var(--sp-text) !important;
}

.sp-modal-header h3 i {
    color: var(--sp-primary) !important;
}

.sp-modal-close {
    background: none !important;
    border: none !important;
    font-size: 24px !important;
    cursor: pointer !important;
    color: var(--sp-text-secondary) !important;
    padding: 4px !important;
    line-height: 1 !important;
    transition: color 0.2s !important;
}

.sp-modal-close:hover {
    color: var(--sp-danger) !important;
}

.sp-modal-body {
    padding: 24px !important;
}

.sp-modal-footer {
    padding: 16px 24px !important;
    border-top: 1px solid var(--sp-border) !important;
    display: flex !important;
    gap: 12px !important;
    justify-content: flex-end !important;
}

/* ==================== TOAST ==================== */
.sp-toast {
    position: fixed !important;
    bottom: 24px !important;
    right: 24px !important;
    padding: 16px 24px !important;
    background: var(--sp-text) !important;
    color: white !important;
    border-radius: 12px !important;
    font-weight: 500 !important;
    z-index: 9999 !important;
    display: none !important;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.2) !important;
    animation: spSlideUp 0.3s ease !important;
}

.sp-toast.success { background: var(--sp-success) !important; }
.sp-toast.error { background: var(--sp-danger) !important; }

@keyframes spSlideUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

/* ==================== RESPONSIVE ==================== */
@media (max-width: 1024px) {
    .sp-stats {
        grid-template-columns: repeat(2, 1fr) !important;
    }
}

@media (max-width: 768px) {
    .sp-header-inner {
        padding: 0 16px !important;
    }

    .sp-menu-toggle {
        display: flex !important;
    }

    .sp-nav {
        display: none !important;
        position: absolute !important;
        top: 100% !important;
        left: 0 !important;
        right: 0 !important;
        background: white !important;
        flex-direction: column !important;
        padding: 16px !important;
        border-bottom: 1px solid var(--sp-border) !important;
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1) !important;
    }

    .sp-nav.open {
        display: flex !important;
    }

    .sp-nav-item {
        border-bottom: none !important;
        border-radius: 8px !important;
        padding: 12px 16px !important;
    }

    .sp-nav-item.active {
        background: var(--sp-primary-light) !important;
    }

    .sp-user-name {
        display: none !important;
    }

    .sp-main {
        padding: 24px 16px !important;
    }

    .sp-stats {
        grid-template-columns: 1fr !important;
    }

    .sp-students-grid {
        grid-template-columns: 1fr !important;
    }

    .sp-page-title {
        font-size: 24px !important;
    }

    .sp-fee-grid {
        grid-template-columns: 1fr !important;
    }

    .sp-plan-grid {
        grid-template-columns: 1fr !important;
    }
}

@media (max-width: 480px) {
    .sp-stat {
        padding: 16px !important;
    }

    .sp-stat-value {
        font-size: 24px !important;
    }

    .sp-profile-grid {
        grid-template-columns: 1fr !important;
    }
}
</style>

<div class="sp-portal">
    <!-- ==================== HEADER ==================== -->
    <header class="sp-header">
        <div class="sp-header-inner">
            <div class="sp-header-top">
                <div class="sp-logo">
                    <i class="fas fa-graduation-cap"></i>
                    <span>Sponsor Portal</span>
                </div>

                <div class="sp-user-menu">
                    <!-- Notification Bell -->
                    <button class="sp-notification-bell" onclick="toggleNotifications()" id="notificationBell">
                        <i class="fas fa-bell"></i>
                        <span class="sp-notification-badge" id="notificationBadge" style="display: none;">0</span>
                    </button>

                    <div class="sp-user-info">
                        <div class="sp-avatar"><?php echo strtoupper(substr($user->display_name, 0, 1)); ?></div>
                        <span class="sp-user-name"><?php echo esc_html($user->display_name); ?></span>
                        <span class="sp-status-badge <?php echo $sponsor_status; ?>">
                            <i class="fas fa-<?php echo $sponsor_status === 'approved' ? 'check-circle' : 'clock'; ?>"></i>
                            <?php echo ucfirst($sponsor_status); ?>
                        </span>
                    </div>
                    <a href="<?php echo wp_logout_url(home_url()); ?>" class="sp-logout-btn">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </div>

                <!-- Notification Dropdown Panel -->
                <div class="sp-notification-panel" id="notificationPanel" style="display: none;">
                    <div class="sp-notification-header">
                        <h3><?php _e('Notifications', 'al-huffaz-portal'); ?></h3>
                        <button class="sp-mark-all-read" onclick="markAllNotificationsRead()" id="markAllReadBtn">
                            <i class="fas fa-check-double"></i> <?php _e('Mark all read', 'al-huffaz-portal'); ?>
                        </button>
                    </div>
                    <div class="sp-notification-list" id="notificationList">
                        <div class="sp-notification-loading">
                            <i class="fas fa-spinner fa-spin"></i> <?php _e('Loading notifications...', 'al-huffaz-portal'); ?>
                        </div>
                    </div>
                </div>

                <button class="sp-menu-toggle" onclick="toggleMobileNav()">
                    <i class="fas fa-bars"></i>
                </button>
            </div>

            <!-- Navigation Tabs -->
            <nav class="sp-nav" id="spNav">
                <button class="sp-nav-item active" data-panel="dashboard">
                    <i class="fas fa-home"></i>
                    <span><?php _e('Dashboard', 'al-huffaz-portal'); ?></span>
                </button>
                <button class="sp-nav-item" data-panel="profile">
                    <i class="fas fa-user"></i>
                    <span><?php _e('My Profile', 'al-huffaz-portal'); ?></span>
                </button>
                <button class="sp-nav-item" data-panel="my-students">
                    <i class="fas fa-user-graduate"></i>
                    <span><?php _e('My Students', 'al-huffaz-portal'); ?></span>
                    <?php if (count($data['sponsorships']) > 0): ?>
                    <span class="sp-nav-badge"><?php echo count($data['sponsorships']); ?></span>
                    <?php endif; ?>
                </button>
                <button class="sp-nav-item" data-panel="available-students">
                    <i class="fas fa-hand-holding-heart"></i>
                    <span><?php _e('Sponsor a Student', 'al-huffaz-portal'); ?></span>
                    <?php if (count($available_students) > 0): ?>
                    <span class="sp-nav-badge"><?php echo count($available_students); ?></span>
                    <?php endif; ?>
                </button>
                <button class="sp-nav-item" data-panel="payments">
                    <i class="fas fa-credit-card"></i>
                    <span><?php _e('Make Payment', 'al-huffaz-portal'); ?></span>
                </button>
                <button class="sp-nav-item" data-panel="history">
                    <i class="fas fa-history"></i>
                    <span><?php _e('Payment History', 'al-huffaz-portal'); ?></span>
                    <?php if ($data['pending_payments'] > 0): ?>
                    <span class="sp-nav-badge warning"><?php echo $data['pending_payments']; ?></span>
                    <?php endif; ?>
                </button>
            </nav>
        </div>
    </header>

    <!-- ==================== MAIN CONTENT ==================== -->
    <main class="sp-main">
        <!-- ==================== DASHBOARD PANEL ==================== -->
        <div class="sp-panel active" id="panel-dashboard">
            <h1 class="sp-page-title"><?php printf(__('Welcome back, %s!', 'al-huffaz-portal'), esc_html($user->first_name ?: $user->display_name)); ?></h1>
            <p class="sp-page-subtitle"><?php _e('Here\'s an overview of your sponsorship activity.', 'al-huffaz-portal'); ?></p>

            <?php if (isset($_GET['payment_submitted']) && $_GET['payment_submitted'] === 'success'): ?>
            <div class="sp-alert sp-alert-success" id="payment-success-alert" style="animation: slideInDown 0.5s;">
                <i class="fas fa-check-circle"></i>
                <div class="sp-alert-content">
                    <strong><?php _e('Payment Submitted Successfully!', 'al-huffaz-portal'); ?></strong>
                    <span><?php _e('Your sponsorship payment has been received! Our team will verify your payment within 24-48 hours. You can track your payment status in the "Payment History" section below. Once verified, the student will appear in your "My Students" section.', 'al-huffaz-portal'); ?></span>
                </div>
                <button class="sp-alert-close" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
            </div>
            <?php endif; ?>

            <?php if ($sponsor_status === 'pending'): ?>
            <div class="sp-alert sp-alert-warning">
                <i class="fas fa-clock"></i>
                <div class="sp-alert-content">
                    <strong><?php _e('Account Pending Verification', 'al-huffaz-portal'); ?></strong>
                    <span><?php _e('Your sponsor account is being reviewed. You will be able to sponsor students once approved.', 'al-huffaz-portal'); ?></span>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($data['pending_sponsorships'])): ?>
            <div class="sp-alert sp-alert-info">
                <i class="fas fa-hourglass-half"></i>
                <div class="sp-alert-content">
                    <strong><?php _e('Pending Verification', 'al-huffaz-portal'); ?> (<?php echo count($data['pending_sponsorships']); ?>)</strong>
                    <span><?php _e('The following payments are awaiting verification. Students will appear in your "My Students" section once payments are verified by our team (usually within 24-48 hours).', 'al-huffaz-portal'); ?></span>
                </div>
            </div>

            <div class="sp-card" style="margin-bottom: 24px;">
                <div class="sp-card-header">
                    <h3 class="sp-card-title"><i class="fas fa-clock"></i> <?php _e('Pending Payments', 'al-huffaz-portal'); ?></h3>
                </div>
                <div class="sp-card-body">
                    <?php foreach ($data['pending_sponsorships'] as $pending): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 16px; background: #fef3c7; border-radius: 12px; border-left: 4px solid #f59e0b; margin-bottom: 12px;">
                        <div>
                            <h5 style="margin: 0 0 4px 0; font-size: 15px; font-weight: 600; color: #78350f;"><?php echo esc_html($pending['student_name']); ?></h5>
                            <p style="margin: 0; font-size: 13px; color: #92400e;">
                                <i class="fas fa-money-bill-wave"></i> <?php echo Helpers::format_currency($pending['amount']); ?> (<?php echo ucfirst($pending['type']); ?>)
                                • <i class="fas fa-calendar"></i> <?php echo esc_html($pending['submitted_at']); ?>
                            </p>
                        </div>
                        <span class="sp-badge sp-badge-warning"><i class="fas fa-clock"></i> <?php _e('Pending', 'al-huffaz-portal'); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="sp-stats">
                <div class="sp-stat">
                    <div class="sp-stat-header">
                        <div class="sp-stat-icon blue"><i class="fas fa-user-graduate"></i></div>
                    </div>
                    <div class="sp-stat-value"><?php echo intval($data['students_count']); ?></div>
                    <div class="sp-stat-label"><?php _e('Students Sponsored', 'al-huffaz-portal'); ?></div>
                </div>
                <div class="sp-stat">
                    <div class="sp-stat-header">
                        <div class="sp-stat-icon green"><i class="fas fa-donate"></i></div>
                    </div>
                    <div class="sp-stat-value"><?php echo esc_html($data['total_contributed']); ?></div>
                    <div class="sp-stat-label"><?php _e('Total Contributed', 'al-huffaz-portal'); ?></div>
                </div>
                <div class="sp-stat">
                    <div class="sp-stat-header">
                        <div class="sp-stat-icon orange"><i class="fas fa-hourglass-half"></i></div>
                    </div>
                    <div class="sp-stat-value"><?php echo intval($data['pending_payments']); ?></div>
                    <div class="sp-stat-label"><?php _e('Pending Payments', 'al-huffaz-portal'); ?></div>
                </div>
                <div class="sp-stat">
                    <div class="sp-stat-header">
                        <div class="sp-stat-icon purple"><i class="fas fa-heart"></i></div>
                    </div>
                    <div class="sp-stat-value"><?php echo count($available_students); ?></div>
                    <div class="sp-stat-label"><?php _e('Available to Sponsor', 'al-huffaz-portal'); ?></div>
                </div>
            </div>

            <?php if (!empty($data['sponsorships'])): ?>
            <!-- Financial Summary Breakdown -->
            <div class="sp-card" style="margin-bottom: 24px;">
                <div class="sp-card-header">
                    <h3 class="sp-card-title"><i class="fas fa-calculator"></i> <?php _e('Financial Summary', 'al-huffaz-portal'); ?></h3>
                </div>
                <div class="sp-card-body">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
                        <div style="background: linear-gradient(135deg, #dbeafe, #bfdbfe); padding: 20px; border-radius: 12px; border-left: 4px solid #3b82f6;">
                            <div style="font-size: 13px; color: #1e40af; margin-bottom: 6px; font-weight: 600;"><?php _e('Monthly Donations', 'al-huffaz-portal'); ?></div>
                            <div style="font-size: 26px; font-weight: 800; color: #1e3a8a;"><?php echo esc_html($data['monthly_total']); ?></div>
                        </div>

                        <div style="background: linear-gradient(135deg, #d1fae5, #a7f3d0); padding: 20px; border-radius: 12px; border-left: 4px solid #10b981;">
                            <div style="font-size: 13px; color: #065f46; margin-bottom: 6px; font-weight: 600;"><?php _e('Quarterly Donations', 'al-huffaz-portal'); ?></div>
                            <div style="font-size: 26px; font-weight: 800; color: #064e3b;"><?php echo esc_html($data['quarterly_total']); ?></div>
                        </div>

                        <div style="background: linear-gradient(135deg, #fed7aa, #fdba74); padding: 20px; border-radius: 12px; border-left: 4px solid #f59e0b;">
                            <div style="font-size: 13px; color: #92400e; margin-bottom: 6px; font-weight: 600;"><?php _e('Yearly Donations', 'al-huffaz-portal'); ?></div>
                            <div style="font-size: 26px; font-weight: 800; color: #78350f;"><?php echo esc_html($data['yearly_total']); ?></div>
                        </div>

                        <div style="background: linear-gradient(135deg, #e0e7ff, #c7d2fe); padding: 20px; border-radius: 12px; border-left: 4px solid #6366f1;">
                            <div style="font-size: 13px; color: #4338ca; margin-bottom: 6px; font-weight: 600;"><?php _e('Total Contributions', 'al-huffaz-portal'); ?></div>
                            <div style="font-size: 26px; font-weight: 800; color: #3730a3;"><?php echo esc_html($data['total_contributed']); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="sp-card">
                <div class="sp-card-header">
                    <h3 class="sp-card-title"><i class="fas fa-users"></i> <?php _e('Your Sponsored Students', 'al-huffaz-portal'); ?></h3>
                    <button class="sp-btn sp-btn-sm sp-btn-secondary" onclick="showPanel('my-students')"><?php _e('View All', 'al-huffaz-portal'); ?></button>
                </div>
                <div class="sp-card-body">
                    <div class="sp-students-grid">
                        <?php foreach (array_slice($data['sponsorships'], 0, 3) as $s): ?>
                        <div class="sp-student-card">
                            <div class="sp-student-header">
                                <?php if (!empty($s['student_photo'])): ?>
                                    <img src="<?php echo esc_url($s['student_photo']); ?>" class="sp-student-photo" alt="">
                                <?php else: ?>
                                    <div class="sp-student-placeholder"><?php echo strtoupper(substr($s['student_name'], 0, 1)); ?></div>
                                <?php endif; ?>
                                <div class="sp-student-info">
                                    <h3><?php echo esc_html($s['student_name']); ?></h3>
                                    <div class="sp-student-badges">
                                        <?php if (!empty($s['grade'])): ?><span class="sp-badge sp-badge-grade"><?php echo esc_html($s['grade']); ?></span><?php endif; ?>
                                        <?php if (!empty($s['category'])): ?><span class="sp-badge sp-badge-category"><?php echo esc_html($s['category']); ?></span><?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="sp-student-footer">
                                <a href="<?php echo get_permalink($s['student_id']); ?>" class="sp-btn sp-btn-primary sp-btn-block sp-btn-sm">
                                    <i class="fas fa-eye"></i> <?php _e('View Profile', 'al-huffaz-portal'); ?>
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="sp-card">
                <div class="sp-card-body">
                    <div class="sp-empty">
                        <i class="fas fa-heart"></i>
                        <h3><?php _e('Start Your Sponsorship Journey', 'al-huffaz-portal'); ?></h3>
                        <p><?php _e('Browse available students and make a difference in their education today.', 'al-huffaz-portal'); ?></p>
                        <button class="sp-btn sp-btn-primary" onclick="showPanel('available-students')">
                            <i class="fas fa-hand-holding-heart"></i> <?php _e('Browse Students', 'al-huffaz-portal'); ?>
                        </button>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- ==================== PROFILE PANEL ==================== -->
        <div class="sp-panel" id="panel-profile">
            <h1 class="sp-page-title"><?php _e('My Profile', 'al-huffaz-portal'); ?></h1>
            <p class="sp-page-subtitle"><?php _e('Your personal information and sponsorship summary.', 'al-huffaz-portal'); ?></p>

            <div class="sp-card">
                <div class="sp-card-header">
                    <h3 class="sp-card-title"><i class="fas fa-user-circle"></i> <?php _e('Personal Information', 'al-huffaz-portal'); ?></h3>
                </div>
                <div class="sp-card-body">
                    <div class="sp-profile-grid">
                        <div class="sp-profile-item">
                            <div class="sp-profile-icon"><i class="fas fa-user"></i></div>
                            <div>
                                <div class="sp-profile-label"><?php _e('Full Name', 'al-huffaz-portal'); ?></div>
                                <div class="sp-profile-value"><?php echo esc_html($user->display_name); ?></div>
                            </div>
                        </div>
                        <div class="sp-profile-item">
                            <div class="sp-profile-icon"><i class="fas fa-envelope"></i></div>
                            <div>
                                <div class="sp-profile-label"><?php _e('Email Address', 'al-huffaz-portal'); ?></div>
                                <div class="sp-profile-value"><?php echo esc_html($user->user_email); ?></div>
                            </div>
                        </div>
                        <div class="sp-profile-item">
                            <div class="sp-profile-icon"><i class="fas fa-phone"></i></div>
                            <div>
                                <div class="sp-profile-label"><?php _e('Phone Number', 'al-huffaz-portal'); ?></div>
                                <div class="sp-profile-value"><?php echo esc_html($sponsor_phone ?: '-'); ?></div>
                            </div>
                        </div>
                        <div class="sp-profile-item">
                            <div class="sp-profile-icon"><i class="fab fa-whatsapp"></i></div>
                            <div>
                                <div class="sp-profile-label"><?php _e('WhatsApp', 'al-huffaz-portal'); ?></div>
                                <div class="sp-profile-value"><?php echo esc_html($sponsor_whatsapp ?: '-'); ?></div>
                            </div>
                        </div>
                        <div class="sp-profile-item">
                            <div class="sp-profile-icon"><i class="fas fa-globe"></i></div>
                            <div>
                                <div class="sp-profile-label"><?php _e('Country', 'al-huffaz-portal'); ?></div>
                                <div class="sp-profile-value"><?php echo esc_html($sponsor_country ?: '-'); ?></div>
                            </div>
                        </div>
                        <div class="sp-profile-item">
                            <div class="sp-profile-icon"><i class="fas fa-calendar"></i></div>
                            <div>
                                <div class="sp-profile-label"><?php _e('Member Since', 'al-huffaz-portal'); ?></div>
                                <div class="sp-profile-value"><?php echo esc_html($member_since); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="sp-card">
                <div class="sp-card-header">
                    <h3 class="sp-card-title"><i class="fas fa-chart-bar"></i> <?php _e('Sponsorship Summary', 'al-huffaz-portal'); ?></h3>
                </div>
                <div class="sp-card-body">
                    <div class="sp-profile-grid">
                        <div class="sp-profile-item">
                            <div class="sp-profile-icon"><i class="fas fa-user-graduate"></i></div>
                            <div>
                                <div class="sp-profile-label"><?php _e('Total Students Sponsored', 'al-huffaz-portal'); ?></div>
                                <div class="sp-profile-value"><?php echo intval($data['students_count']); ?></div>
                            </div>
                        </div>
                        <div class="sp-profile-item">
                            <div class="sp-profile-icon"><i class="fas fa-donate"></i></div>
                            <div>
                                <div class="sp-profile-label"><?php _e('Total Amount Contributed', 'al-huffaz-portal'); ?></div>
                                <div class="sp-profile-value"><?php echo esc_html($data['total_contributed']); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==================== MY STUDENTS PANEL ==================== -->
        <div class="sp-panel" id="panel-my-students">
            <h1 class="sp-page-title"><?php _e('My Sponsored Students', 'al-huffaz-portal'); ?></h1>
            <p class="sp-page-subtitle"><?php _e('Students you are currently sponsoring.', 'al-huffaz-portal'); ?></p>

            <?php if (empty($data['sponsorships'])): ?>
            <div class="sp-card">
                <div class="sp-card-body">
                    <div class="sp-empty">
                        <i class="fas fa-users"></i>
                        <h3><?php _e('No Sponsored Students Yet', 'al-huffaz-portal'); ?></h3>
                        <p><?php _e('Start sponsoring a student to see them here.', 'al-huffaz-portal'); ?></p>
                        <button class="sp-btn sp-btn-primary" onclick="showPanel('available-students')">
                            <i class="fas fa-hand-holding-heart"></i> <?php _e('Browse Students', 'al-huffaz-portal'); ?>
                        </button>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="sp-students-grid">
                <?php foreach ($data['sponsorships'] as $s):
                    $student_id = $s['student_id'];
                    // CRITICAL FIX: Get all fee components
                    $monthly_fee = floatval(get_post_meta($student_id, 'monthly_tuition_fee', true)) ?: 0;
                    $course_fee = floatval(get_post_meta($student_id, 'course_fee', true)) ?: 0;
                    $uniform_fee = floatval(get_post_meta($student_id, 'uniform_fee', true)) ?: 0;
                    $annual_fee = floatval(get_post_meta($student_id, 'annual_fee', true)) ?: 0;
                    $admission_fee = floatval(get_post_meta($student_id, 'admission_fee', true)) ?: 0;
                    $one_time_fees = $course_fee + $uniform_fee + $annual_fee + $admission_fee;
                ?>
                <div class="sp-student-card">
                    <div class="sp-student-header">
                        <?php if (!empty($s['student_photo'])): ?>
                            <img src="<?php echo esc_url($s['student_photo']); ?>" class="sp-student-photo" alt="">
                        <?php else: ?>
                            <div class="sp-student-placeholder"><?php echo strtoupper(substr($s['student_name'], 0, 1)); ?></div>
                        <?php endif; ?>
                        <div class="sp-student-info">
                            <h3><?php echo esc_html($s['student_name']); ?></h3>
                            <div class="sp-student-badges">
                                <?php if (!empty($s['grade'])): ?><span class="sp-badge sp-badge-grade"><?php echo esc_html($s['grade']); ?></span><?php endif; ?>
                                <?php if (!empty($s['category'])): ?><span class="sp-badge sp-badge-category"><?php echo esc_html($s['category']); ?></span><?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="sp-student-body">
                        <div class="sp-fee-grid">
                            <div class="sp-fee-item">
                                <div class="sp-fee-label"><?php _e('Monthly Fee', 'al-huffaz-portal'); ?></div>
                                <div class="sp-fee-value">PKR <?php echo number_format($monthly_fee); ?></div>
                            </div>
                            <div class="sp-fee-item">
                                <div class="sp-fee-label"><?php _e('One-Time Fees', 'al-huffaz-portal'); ?></div>
                                <div class="sp-fee-value">PKR <?php echo number_format($one_time_fees); ?></div>
                            </div>
                            <div class="sp-fee-item">
                                <div class="sp-fee-label"><?php _e('Your Plan', 'al-huffaz-portal'); ?></div>
                                <div class="sp-fee-value"><?php echo esc_html(ucfirst($s['type'])); ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="sp-student-footer">
                        <a href="<?php echo get_permalink($student_id); ?>" class="sp-btn sp-btn-primary sp-btn-sm" style="flex: 1;">
                            <i class="fas fa-eye"></i> <?php _e('View Profile', 'al-huffaz-portal'); ?>
                        </a>
                        <button class="sp-btn sp-btn-danger sp-btn-sm" onclick="cancelSponsorship(<?php echo $s['id']; ?>, '<?php echo esc_js($s['student_name']); ?>')" style="flex: 1;">
                            <i class="fas fa-times-circle"></i> <?php _e('Cancel', 'al-huffaz-portal'); ?>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- ==================== AVAILABLE STUDENTS PANEL ==================== -->
        <div class="sp-panel" id="panel-available-students">
            <h1 class="sp-page-title"><?php _e('Available Students', 'al-huffaz-portal'); ?></h1>
            <p class="sp-page-subtitle"><?php _e('Choose a student to sponsor and make a difference in their education.', 'al-huffaz-portal'); ?></p>

            <?php if (empty($available_students)): ?>
            <div class="sp-card">
                <div class="sp-card-body">
                    <div class="sp-empty">
                        <i class="fas fa-search"></i>
                        <h3><?php _e('No Students Available', 'al-huffaz-portal'); ?></h3>
                        <p><?php _e('All students are currently sponsored. Check back later!', 'al-huffaz-portal'); ?></p>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="sp-students-grid">
                <?php foreach ($available_students as $student):
                    $student_id = $student->ID;
                    $photo_id = get_post_meta($student_id, 'student_photo', true);
                    $photo_url = $photo_id ? wp_get_attachment_image_url($photo_id, 'medium') : '';
                    $grade = get_post_meta($student_id, 'grade_level', true);
                    $category = get_post_meta($student_id, 'islamic_studies_category', true);

                    // CRITICAL FIX: Get all fee components for proper calculation
                    $monthly_fee = floatval(get_post_meta($student_id, 'monthly_tuition_fee', true)) ?: 0;
                    $course_fee = floatval(get_post_meta($student_id, 'course_fee', true)) ?: 0;
                    $uniform_fee = floatval(get_post_meta($student_id, 'uniform_fee', true)) ?: 0;
                    $annual_fee = floatval(get_post_meta($student_id, 'annual_fee', true)) ?: 0;
                    $admission_fee = floatval(get_post_meta($student_id, 'admission_fee', true)) ?: 0;

                    // Calculate one-time fees total (paid once, not monthly)
                    $one_time_fees = $course_fee + $uniform_fee + $annual_fee + $admission_fee;

                    // Calculate correct sponsorship amounts
                    $amount_1month = $monthly_fee;  // Just monthly tuition
                    $amount_3months = $monthly_fee * 3;  // 3 months of tuition
                    $amount_6months = $monthly_fee * 6;  // 6 months of tuition
                    $amount_yearly = ($monthly_fee * 12) + $one_time_fees;  // Full year + one-time fees
                ?>
                <div class="sp-student-card">
                    <div class="sp-student-header">
                        <?php if ($photo_url): ?>
                            <img src="<?php echo esc_url($photo_url); ?>" class="sp-student-photo" alt="">
                        <?php else: ?>
                            <div class="sp-student-placeholder"><?php echo strtoupper(substr($student->post_title, 0, 1)); ?></div>
                        <?php endif; ?>
                        <div class="sp-student-info">
                            <h3><?php echo esc_html($student->post_title); ?></h3>
                            <div class="sp-student-badges">
                                <?php if ($grade): ?><span class="sp-badge sp-badge-grade"><?php echo esc_html(strtoupper($grade)); ?></span><?php endif; ?>
                                <?php if ($category): ?><span class="sp-badge sp-badge-category"><?php echo esc_html(ucfirst($category)); ?></span><?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="sp-student-body">
                        <div class="sp-fee-grid">
                            <div class="sp-fee-item">
                                <div class="sp-fee-label"><?php _e('Monthly Tuition', 'al-huffaz-portal'); ?></div>
                                <div class="sp-fee-value">PKR <?php echo number_format($monthly_fee); ?>/mo</div>
                            </div>
                            <div class="sp-fee-item">
                                <div class="sp-fee-label"><?php _e('One-Time Fees', 'al-huffaz-portal'); ?></div>
                                <div class="sp-fee-value">PKR <?php echo number_format($one_time_fees); ?></div>
                            </div>
                            <div class="sp-fee-item">
                                <div class="sp-fee-label"><?php _e('Yearly Total', 'al-huffaz-portal'); ?></div>
                                <div class="sp-fee-value">PKR <?php echo number_format($amount_yearly); ?></div>
                            </div>
                        </div>
                        <p class="sp-plan-title"><?php _e('Choose Sponsorship Plan', 'al-huffaz-portal'); ?></p>
                        <div class="sp-plan-grid">
                            <button type="button" class="sp-plan-btn" onclick="openSponsorModal(<?php echo $student_id; ?>, '<?php echo esc_js($student->post_title); ?>', 1, <?php echo $amount_1month; ?>)">
                                <span class="sp-plan-duration"><?php _e('1 Month', 'al-huffaz-portal'); ?></span>
                                <span class="sp-plan-amount">PKR <?php echo number_format($amount_1month); ?></span>
                            </button>
                            <button type="button" class="sp-plan-btn" onclick="openSponsorModal(<?php echo $student_id; ?>, '<?php echo esc_js($student->post_title); ?>', 3, <?php echo $amount_3months; ?>)">
                                <span class="sp-plan-duration"><?php _e('3 Months', 'al-huffaz-portal'); ?></span>
                                <span class="sp-plan-amount">PKR <?php echo number_format($amount_3months); ?></span>
                            </button>
                            <button type="button" class="sp-plan-btn" onclick="openSponsorModal(<?php echo $student_id; ?>, '<?php echo esc_js($student->post_title); ?>', 6, <?php echo $amount_6months; ?>)">
                                <span class="sp-plan-duration"><?php _e('6 Months', 'al-huffaz-portal'); ?></span>
                                <span class="sp-plan-amount">PKR <?php echo number_format($amount_6months); ?></span>
                            </button>
                            <button type="button" class="sp-plan-btn featured" onclick="openSponsorModal(<?php echo $student_id; ?>, '<?php echo esc_js($student->post_title); ?>', 12, <?php echo $amount_yearly; ?>)">
                                <span class="sp-plan-badge"><?php _e('Best Value', 'al-huffaz-portal'); ?></span>
                                <span class="sp-plan-duration"><?php _e('12 Months', 'al-huffaz-portal'); ?></span>
                                <span class="sp-plan-amount">PKR <?php echo number_format($amount_yearly); ?></span>
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- ==================== MAKE PAYMENT PANEL ==================== -->
        <div class="sp-panel" id="panel-payments">
            <h1 class="sp-page-title"><?php _e('Make a Payment', 'al-huffaz-portal'); ?></h1>
            <p class="sp-page-subtitle"><?php _e('Submit your payment proof for verification.', 'al-huffaz-portal'); ?></p>

            <?php if (empty($data['sponsorships'])): ?>
            <div class="sp-card">
                <div class="sp-card-body">
                    <div class="sp-empty">
                        <i class="fas fa-credit-card"></i>
                        <h3><?php _e('No Sponsorships Yet', 'al-huffaz-portal'); ?></h3>
                        <p><?php _e('You need to sponsor a student before making payments.', 'al-huffaz-portal'); ?></p>
                        <button class="sp-btn sp-btn-primary" onclick="showPanel('available-students')">
                            <i class="fas fa-hand-holding-heart"></i> <?php _e('Browse Students', 'al-huffaz-portal'); ?>
                        </button>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="sp-card">
                <div class="sp-card-header">
                    <h3 class="sp-card-title"><i class="fas fa-upload"></i> <?php _e('Submit Payment Proof', 'al-huffaz-portal'); ?></h3>
                </div>
                <div class="sp-card-body">
                    <form id="paymentForm" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="alhuffaz_submit_payment">
                        <input type="hidden" name="nonce" value="<?php echo $nonce; ?>">

                        <div class="sp-form-row">
                            <div class="sp-form-group">
                                <label class="sp-form-label"><?php _e('Select Student', 'al-huffaz-portal'); ?></label>
                                <select name="student_id" class="sp-form-select" required>
                                    <option value=""><?php _e('Choose a student...', 'al-huffaz-portal'); ?></option>
                                    <?php foreach ($data['sponsorships'] as $s): ?>
                                    <option value="<?php echo $s['student_id']; ?>"><?php echo esc_html($s['student_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="sp-form-group">
                                <label class="sp-form-label"><?php _e('Amount (PKR)', 'al-huffaz-portal'); ?></label>
                                <input type="number" name="amount" class="sp-form-input" placeholder="Enter amount" required>
                            </div>
                        </div>

                        <div class="sp-form-row">
                            <div class="sp-form-group">
                                <label class="sp-form-label"><?php _e('Payment Method', 'al-huffaz-portal'); ?></label>
                                <select name="payment_method" class="sp-form-select" required>
                                    <option value=""><?php _e('Select method...', 'al-huffaz-portal'); ?></option>
                                    <option value="bank_transfer"><?php _e('Bank Transfer', 'al-huffaz-portal'); ?></option>
                                    <option value="easypaisa"><?php _e('Easypaisa', 'al-huffaz-portal'); ?></option>
                                    <option value="jazzcash"><?php _e('JazzCash', 'al-huffaz-portal'); ?></option>
                                </select>
                            </div>
                            <div class="sp-form-group">
                                <label class="sp-form-label"><?php _e('Transaction Reference', 'al-huffaz-portal'); ?></label>
                                <input type="text" name="transaction_ref" class="sp-form-input" placeholder="Transaction ID or reference">
                            </div>
                        </div>

                        <div class="sp-form-group">
                            <label class="sp-form-label"><?php _e('Payment Proof (Screenshot)', 'al-huffaz-portal'); ?></label>
                            <input type="file" name="payment_proof" class="sp-form-input" accept="image/*" required>
                        </div>

                        <button type="submit" class="sp-btn sp-btn-success">
                            <i class="fas fa-paper-plane"></i> <?php _e('Submit Payment', 'al-huffaz-portal'); ?>
                        </button>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- ==================== PAYMENT PROOF PANEL ==================== -->
        <div class="sp-panel" id="panel-payment-proof">
            <h1 class="sp-page-title"><?php _e('Submit Payment Proof', 'al-huffaz-portal'); ?></h1>
            <p class="sp-page-subtitle"><?php _e('Please provide payment details and proof to complete your sponsorship.', 'al-huffaz-portal'); ?></p>

            <div class="sp-card">
                <div class="sp-card-header">
                    <h3 class="sp-card-title"><i class="fas fa-info-circle"></i> <?php _e('Sponsorship Details', 'al-huffaz-portal'); ?></h3>
                </div>
                <div class="sp-card-body">
                    <div class="sp-info-grid">
                        <div class="sp-info-item">
                            <span class="sp-info-label"><?php _e('Student:', 'al-huffaz-portal'); ?></span>
                            <span class="sp-info-value" id="proofStudentName">-</span>
                        </div>
                        <div class="sp-info-item">
                            <span class="sp-info-label"><?php _e('Duration:', 'al-huffaz-portal'); ?></span>
                            <span class="sp-info-value" id="proofDuration">-</span>
                        </div>
                        <div class="sp-info-item">
                            <span class="sp-info-label"><?php _e('Amount:', 'al-huffaz-portal'); ?></span>
                            <span class="sp-info-value" id="proofAmount">-</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="sp-card">
                <div class="sp-card-header">
                    <h3 class="sp-card-title"><i class="fas fa-receipt"></i> <?php _e('Payment Information', 'al-huffaz-portal'); ?></h3>
                </div>
                <div class="sp-card-body">
                    <form id="paymentProofForm" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="alhuffaz_submit_payment_proof">
                        <input type="hidden" name="nonce" value="<?php echo $nonce; ?>">
                        <input type="hidden" id="proofStudentId" name="student_id">
                        <input type="hidden" id="proofAmountVal" name="amount">
                        <input type="hidden" id="proofDurationVal" name="sponsorship_type">

                        <div class="sp-alert sp-alert-info">
                            <i class="fas fa-lightbulb"></i>
                            <div class="sp-alert-content">
                                <strong><?php _e('Payment Guidelines', 'al-huffaz-portal'); ?></strong>
                                <ul style="margin: 8px 0 0 20px; padding: 0;">
                                    <li><?php _e('Choose your preferred payment method below', 'al-huffaz-portal'); ?></li>
                                    <li><?php _e('Complete the payment using your method', 'al-huffaz-portal'); ?></li>
                                    <li><?php _e('Take a clear screenshot of the payment confirmation', 'al-huffaz-portal'); ?></li>
                                    <li><?php _e('Upload the screenshot and fill in the transaction details', 'al-huffaz-portal'); ?></li>
                                    <li><?php _e('We will verify your payment within 24-48 hours', 'al-huffaz-portal'); ?></li>
                                </ul>
                            </div>
                        </div>

                        <div class="sp-form-group">
                            <label class="sp-form-label"><?php _e('Payment Method', 'al-huffaz-portal'); ?> <span style="color: red;">*</span></label>
                            <select name="payment_method" class="sp-form-select" required>
                                <option value=""><?php _e('Select payment method...', 'al-huffaz-portal'); ?></option>
                                <option value="bank_transfer"><?php _e('Bank Transfer', 'al-huffaz-portal'); ?></option>
                                <option value="easypaisa"><?php _e('Easypaisa', 'al-huffaz-portal'); ?></option>
                                <option value="jazzcash"><?php _e('JazzCash', 'al-huffaz-portal'); ?></option>
                            </select>
                        </div>

                        <div class="sp-form-group">
                            <label class="sp-form-label"><?php _e('Transaction ID / Reference Number', 'al-huffaz-portal'); ?> <span style="color: var(--sp-text-muted);">(<?php _e('Optional', 'al-huffaz-portal'); ?>)</span></label>
                            <input type="text" name="transaction_id" class="sp-form-input" placeholder="<?php _e('e.g., TXN123456 (if available)', 'al-huffaz-portal'); ?>">
                            <small style="color: var(--sp-text-muted); margin-top: 4px; display: block;">
                                <?php _e('Enter transaction reference if your bank/service provided one. You can leave this blank if not available.', 'al-huffaz-portal'); ?>
                            </small>
                        </div>

                        <div class="sp-form-group">
                            <label class="sp-form-label"><?php _e('Payment Date', 'al-huffaz-portal'); ?></label>
                            <input type="date" name="payment_date" class="sp-form-input" value="<?php echo date('Y-m-d'); ?>">
                        </div>

                        <div class="sp-form-group">
                            <label class="sp-form-label"><?php _e('Payment Screenshot / Proof', 'al-huffaz-portal'); ?> <span style="color: red;">*</span></label>
                            <input type="file" name="payment_screenshot" class="sp-form-input" accept="image/*" required>
                            <small style="color: var(--sp-text-muted); margin-top: 4px; display: block;">
                                <?php _e('Upload a clear screenshot of your payment confirmation', 'al-huffaz-portal'); ?>
                            </small>
                        </div>

                        <div class="sp-form-group">
                            <label class="sp-form-label"><?php _e('Additional Notes (Optional)', 'al-huffaz-portal'); ?></label>
                            <textarea name="notes" class="sp-form-input" rows="3" placeholder="<?php _e('Any additional information...', 'al-huffaz-portal'); ?>"></textarea>
                        </div>

                        <div class="sp-form-actions">
                            <button type="button" class="sp-btn sp-btn-secondary" onclick="showPanel('available-students')">
                                <i class="fas fa-arrow-left"></i> <?php _e('Cancel', 'al-huffaz-portal'); ?>
                            </button>
                            <button type="submit" class="sp-btn sp-btn-success">
                                <i class="fas fa-paper-plane"></i> <?php _e('Submit Payment Proof', 'al-huffaz-portal'); ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- ==================== PAYMENT HISTORY PANEL ==================== -->
        <div class="sp-panel" id="panel-history">
            <h1 class="sp-page-title"><?php _e('Payment History', 'al-huffaz-portal'); ?></h1>
            <p class="sp-page-subtitle"><?php _e('View all your past payments and their status.', 'al-huffaz-portal'); ?></p>

            <div class="sp-card">
                <div class="sp-card-body" style="padding: 0;">
                    <?php if (empty($data['payments'])): ?>
                    <div class="sp-empty">
                        <i class="fas fa-receipt"></i>
                        <h3><?php _e('No Payment History', 'al-huffaz-portal'); ?></h3>
                        <p><?php _e('Your payment history will appear here once you make a payment.', 'al-huffaz-portal'); ?></p>
                    </div>
                    <?php else: ?>
                    <div class="sp-table-wrap">
                        <table class="sp-table">
                            <thead>
                                <tr>
                                    <th><?php _e('Date', 'al-huffaz-portal'); ?></th>
                                    <th><?php _e('Student', 'al-huffaz-portal'); ?></th>
                                    <th><?php _e('Amount', 'al-huffaz-portal'); ?></th>
                                    <th><?php _e('Method', 'al-huffaz-portal'); ?></th>
                                    <th><?php _e('Status', 'al-huffaz-portal'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data['payments'] as $payment): ?>
                                <tr>
                                    <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($payment['date']))); ?></td>
                                    <td><?php echo esc_html($payment['student_name']); ?></td>
                                    <td><strong>PKR <?php echo number_format($payment['amount']); ?></strong></td>
                                    <td><?php echo esc_html(ucfirst(str_replace('_', ' ', $payment['method']))); ?></td>
                                    <td><span class="sp-status <?php echo esc_attr($payment['status']); ?>"><?php echo esc_html(ucfirst($payment['status'])); ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- ==================== SPONSOR MODAL ==================== -->
    <div class="sp-modal" id="sponsorModal">
        <div class="sp-modal-content">
            <div class="sp-modal-header">
                <h3><i class="fas fa-hand-holding-heart"></i> <?php _e('Confirm Sponsorship', 'al-huffaz-portal'); ?></h3>
                <button class="sp-modal-close" onclick="closeSponsorModal()">&times;</button>
            </div>
            <div class="sp-modal-body">
                <p style="margin-bottom: 20px;"><?php _e('You are about to sponsor:', 'al-huffaz-portal'); ?></p>
                <div style="padding: 16px; background: var(--sp-bg); border-radius: 12px; margin-bottom: 20px;">
                    <div style="font-weight: 600; font-size: 18px; margin-bottom: 8px;" id="modalStudentName"></div>
                    <div style="color: var(--sp-text-secondary);" id="modalDuration"></div>
                    <div style="font-size: 24px; font-weight: 700; color: var(--sp-primary); margin-top: 8px;" id="modalAmount"></div>
                </div>
                <form id="sponsorForm">
                    <input type="hidden" name="action" value="alhuffaz_create_sponsorship">
                    <input type="hidden" name="nonce" value="<?php echo $nonce; ?>">
                    <input type="hidden" name="student_id" id="modalStudentId">
                    <input type="hidden" name="duration" id="modalDurationVal">
                    <input type="hidden" name="amount" id="modalAmountVal">
                </form>
            </div>
            <div class="sp-modal-footer">
                <button class="sp-btn sp-btn-secondary" onclick="closeSponsorModal()"><?php _e('Cancel', 'al-huffaz-portal'); ?></button>
                <button class="sp-btn sp-btn-success" onclick="submitSponsorship()">
                    <i class="fas fa-check"></i> <?php _e('Confirm Sponsorship', 'al-huffaz-portal'); ?>
                </button>
            </div>
        </div>
    </div>

    <!-- Toast -->
    <div class="sp-toast" id="spToast"></div>
</div>

<script>
(function() {
    'use strict';

    // Panel Navigation
    const navItems = document.querySelectorAll('.sp-nav-item[data-panel]');
    const panels = document.querySelectorAll('.sp-panel');

    navItems.forEach(item => {
        item.addEventListener('click', function() {
            const panelId = this.dataset.panel;
            showPanel(panelId);

            // Close mobile nav
            document.getElementById('spNav').classList.remove('open');
        });
    });

    window.showPanel = function(panelId) {
        // Update nav
        navItems.forEach(item => {
            item.classList.toggle('active', item.dataset.panel === panelId);
        });

        // Update panels
        panels.forEach(panel => {
            panel.classList.toggle('active', panel.id === 'panel-' + panelId);
        });

        // Scroll to top
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    // Mobile Navigation Toggle
    window.toggleMobileNav = function() {
        document.getElementById('spNav').classList.toggle('open');
    };

    // Sponsor Modal
    window.openSponsorModal = function(studentId, studentName, duration, amount) {
        document.getElementById('modalStudentName').textContent = studentName;
        document.getElementById('modalDuration').textContent = duration + ' Month' + (duration > 1 ? 's' : '') + ' Sponsorship';
        document.getElementById('modalAmount').textContent = 'PKR ' + amount.toLocaleString();
        document.getElementById('modalStudentId').value = studentId;
        document.getElementById('modalDurationVal').value = duration;
        document.getElementById('modalAmountVal').value = amount;
        document.getElementById('sponsorModal').classList.add('open');
    };

    window.closeSponsorModal = function() {
        document.getElementById('sponsorModal').classList.remove('open');
    };

    // Store sponsorship details for payment proof
    let pendingSponsorship = null;

    window.submitSponsorship = function() {
        const form = document.getElementById('sponsorForm');
        const formData = new FormData(form);

        // Store the sponsorship details for payment proof form
        pendingSponsorship = {
            studentId: formData.get('student_id'),
            studentName: document.getElementById('modalStudentName').textContent,
            duration: formData.get('duration'),
            amount: formData.get('amount')
        };

        closeSponsorModal();

        // Show toast and redirect to payment proof panel
        showToast('Please submit payment proof to complete sponsorship', 'info');

        setTimeout(() => {
            showPanel('payment-proof');

            // Pre-fill the payment proof form
            if (pendingSponsorship) {
                document.getElementById('proofStudentName').textContent = pendingSponsorship.studentName;
                document.getElementById('proofAmount').textContent = 'PKR ' + Number(pendingSponsorship.amount).toLocaleString();
                document.getElementById('proofDuration').textContent = pendingSponsorship.duration + ' month' + (pendingSponsorship.duration > 1 ? 's' : '');

                document.getElementById('proofStudentId').value = pendingSponsorship.studentId;
                document.getElementById('proofAmountVal').value = pendingSponsorship.amount;
                document.getElementById('proofDurationVal').value = pendingSponsorship.duration;
            }

            window.scrollTo(0, 0);
        }, 500);
    };

    // Payment Form (for existing sponsorships)
    const paymentForm = document.getElementById('paymentForm');
    if (paymentForm) {
        paymentForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Payment submitted successfully!', 'success');
                    this.reset();
                    setTimeout(() => showPanel('history'), 1500);
                } else {
                    showToast(data.data || 'An error occurred', 'error');
                }
            })
            .catch(() => {
                showToast('An error occurred', 'error');
            });
        });
    }

    // Payment Proof Form (for new sponsorships)
    const paymentProofForm = document.getElementById('paymentProofForm');
    if (paymentProofForm) {
        paymentProofForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';

            const formData = new FormData(this);

            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;

                if (data.success) {
                    // Reset form immediately
                    this.reset();
                    pendingSponsorship = null;

                    // Clear the info display
                    document.getElementById('proofStudentName').textContent = '-';
                    document.getElementById('proofAmount').textContent = '-';
                    document.getElementById('proofDuration').textContent = '-';

                    // Show beautiful success modal
                    const successModal = document.createElement('div');
                    successModal.style.cssText = `
                        position: fixed;
                        top: 0;
                        left: 0;
                        right: 0;
                        bottom: 0;
                        background: rgba(0,0,0,0.7);
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        z-index: 999999;
                        animation: fadeIn 0.3s ease;
                    `;
                    successModal.innerHTML = `
                        <style>
                        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
                        @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
                        @keyframes checkmark { 0% { transform: scale(0); } 50% { transform: scale(1.2); } 100% { transform: scale(1); } }
                        </style>
                        <div style="
                            background: white;
                            border-radius: 20px;
                            padding: 40px;
                            max-width: 500px;
                            width: 90%;
                            text-align: center;
                            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                            animation: slideUp 0.4s ease;
                        ">
                            <div style="
                                width: 80px;
                                height: 80px;
                                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                                border-radius: 50%;
                                margin: 0 auto 24px;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                animation: checkmark 0.5s ease 0.2s both;
                            ">
                                <i class="fas fa-check" style="font-size: 40px; color: white;"></i>
                            </div>
                            <h2 style="font-size: 28px; font-weight: 700; margin: 0 0 16px 0; color: #2d3748;">
                                Payment Submitted!
                            </h2>
                            <p style="font-size: 16px; line-height: 1.6; color: #4a5568; margin: 0 0 24px 0;">
                                Your payment proof has been received successfully.<br>
                                We'll verify it within <strong style="color: #667eea;">24-48 hours</strong>.<br><br>
                                You'll receive an <strong>email notification</strong> once approved.<br><br>
                                <span style="color: #667eea; font-size: 18px;">✨ Thank you for your generosity! ✨</span>
                            </p>
                            <div style="
                                background: #f7fafc;
                                border-radius: 12px;
                                padding: 16px;
                                margin-bottom: 24px;
                            ">
                                <div style="font-size: 14px; color: #718096; margin-bottom: 8px;">Redirecting in</div>
                                <div id="countdown" style="font-size: 32px; font-weight: 700; color: #667eea;">3</div>
                            </div>
                            <button onclick="this.closest('[style*=fixed]').remove(); showPanel('my-students'); location.reload();" style="
                                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                                color: white;
                                border: none;
                                padding: 14px 32px;
                                border-radius: 12px;
                                font-size: 16px;
                                font-weight: 600;
                                cursor: pointer;
                                transition: transform 0.2s;
                            " onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                                View My Sponsorships
                            </button>
                        </div>
                    `;
                    document.body.appendChild(successModal);

                    // Countdown timer
                    let countdown = 3;
                    const countdownEl = successModal.querySelector('#countdown');
                    const timer = setInterval(() => {
                        countdown--;
                        if (countdownEl) countdownEl.textContent = countdown;
                        if (countdown <= 0) {
                            clearInterval(timer);
                            successModal.remove();
                            showPanel('my-students');
                            // Force reload with cache-busting
                            location.href = location.href.split('?')[0] + '?t=' + Date.now();
                        }
                    }, 1000);
                } else {
                    showToast(data.data?.message || data.data || 'An error occurred', 'error');
                }
            })
            .catch(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
                showToast('An error occurred while submitting', 'error');
            });
        });
    }

    // Toast
    function showToast(message, type) {
        const toast = document.getElementById('spToast');
        toast.textContent = message;
        toast.className = 'sp-toast ' + type;
        toast.style.display = 'block';
        setTimeout(() => { toast.style.display = 'none'; }, 3000);
    }
    window.showToast = showToast;

    // Cancel sponsorship
    window.cancelSponsorship = function(sponsorshipId, studentName) {
        if (!confirm(`Are you sure you want to cancel your sponsorship for ${studentName}?\n\nThis action will make the student available for others to sponsor.`)) {
            return;
        }

        const confirmText = prompt('Type "CANCEL" to confirm:');
        if (confirmText !== 'CANCEL') {
            showToast('Cancellation aborted', 'info');
            return;
        }

        // Show loading state
        showToast('Canceling sponsorship...', 'info');

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({
                action: 'alhuffaz_cancel_sponsorship',
                nonce: '<?php echo $nonce; ?>',
                sponsorship_id: sponsorshipId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Sponsorship cancelled successfully', 'success');
                // FIX #4: Remove item from list without page reload - smoother UX
                const sponsorshipCard = document.querySelector(`[data-sponsorship-id="${sponsorshipId}"]`);
                if (sponsorshipCard) {
                    sponsorshipCard.style.transition = 'opacity 0.3s, transform 0.3s';
                    sponsorshipCard.style.opacity = '0';
                    sponsorshipCard.style.transform = 'scale(0.9)';
                    setTimeout(() => {
                        sponsorshipCard.remove();
                        // Update count badges
                        const countEl = document.querySelector('.sp-stat-count');
                        if (countEl) {
                            const currentCount = parseInt(countEl.textContent) || 0;
                            if (currentCount > 0) {
                                countEl.textContent = currentCount - 1;
                            }
                        }
                        // If no more sponsorships, show empty state
                        const remaining = document.querySelectorAll('[data-sponsorship-id]').length;
                        if (remaining === 0) {
                            setTimeout(() => location.reload(), 500);
                        }
                    }, 300);
                } else {
                    // Fallback: reload if we can't find the card
                    setTimeout(() => location.reload(), 1500);
                }
            } else {
                showToast(data.data?.message || 'Failed to cancel sponsorship', 'error');
            }
        })
        .catch(() => {
            showToast('An error occurred', 'error');
        });
    };

    // Close modal on outside click
    document.getElementById('sponsorModal').addEventListener('click', function(e) {
        if (e.target === this) closeSponsorModal();
    });

    // ==================== NOTIFICATIONS ====================
    let notificationPanel = null;

    window.toggleNotifications = function() {
        notificationPanel = document.getElementById('notificationPanel');
        const isVisible = notificationPanel.style.display === 'block';

        if (isVisible) {
            notificationPanel.style.display = 'none';
        } else {
            notificationPanel.style.display = 'block';
            loadNotifications();
        }
    };

    // Close notification panel when clicking outside
    document.addEventListener('click', function(e) {
        const bell = document.getElementById('notificationBell');
        const panel = document.getElementById('notificationPanel');

        if (panel && bell && !bell.contains(e.target) && !panel.contains(e.target)) {
            panel.style.display = 'none';
        }
    });

    function loadNotifications() {
        const listElement = document.getElementById('notificationList');
        listElement.innerHTML = '<div class="sp-notification-loading"><i class="fas fa-spinner fa-spin"></i> <?php _e('Loading notifications...', 'al-huffaz-portal'); ?></div>';

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'alhuffaz_get_notifications',
                nonce: '<?php echo wp_create_nonce('alhuffaz_public_nonce'); ?>',
                limit: 15
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderNotifications(data.data.notifications);
                updateNotificationBadge(data.data.unread_count);
            } else {
                listElement.innerHTML = '<div class="sp-notification-empty"><i class="fas fa-bell-slash"></i><br><?php _e('Failed to load notifications', 'al-huffaz-portal'); ?></div>';
            }
        })
        .catch(error => {
            console.error('Error loading notifications:', error);
            listElement.innerHTML = '<div class="sp-notification-empty"><i class="fas fa-exclamation-circle"></i><br><?php _e('Error loading notifications', 'al-huffaz-portal'); ?></div>';
        });
    }

    function renderNotifications(notifications) {
        const listElement = document.getElementById('notificationList');

        if (!notifications || notifications.length === 0) {
            listElement.innerHTML = '<div class="sp-notification-empty"><i class="fas fa-bell-slash"></i><br><?php _e('No notifications yet', 'al-huffaz-portal'); ?></div>';
            return;
        }

        let html = '';
        notifications.forEach(notification => {
            const unreadClass = notification.is_read ? '' : 'unread';
            const iconClass = notification.type || 'info';
            const iconMap = {
                'success': 'check-circle',
                'info': 'info-circle',
                'warning': 'exclamation-triangle',
                'error': 'times-circle'
            };
            const icon = iconMap[notification.type] || 'bell';

            html += `
                <div class="sp-notification-item ${unreadClass}" onclick="markNotificationRead(${notification.id})">
                    <div class="sp-notification-icon ${iconClass}">
                        <i class="fas fa-${icon}"></i>
                    </div>
                    <div class="sp-notification-content">
                        <div class="sp-notification-title">${escapeHtml(notification.title)}</div>
                        <div class="sp-notification-message">${escapeHtml(notification.message)}</div>
                        <div class="sp-notification-time"><i class="far fa-clock"></i> ${notification.time_ago}</div>
                    </div>
                </div>
            `;
        });

        listElement.innerHTML = html;
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function updateNotificationBadge(count) {
        const badge = document.getElementById('notificationBadge');
        if (count > 0) {
            badge.textContent = count > 99 ? '99+' : count;
            badge.style.display = 'flex';
        } else {
            badge.style.display = 'none';
        }
    }

    window.markNotificationRead = function(notificationId) {
        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'alhuffaz_mark_notification_read',
                nonce: '<?php echo wp_create_nonce('alhuffaz_public_nonce'); ?>',
                notification_id: notificationId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadNotifications();
            }
        })
        .catch(error => console.error('Error marking notification as read:', error));
    };

    window.markAllNotificationsRead = function() {
        const btn = document.getElementById('markAllReadBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <?php _e('Marking...', 'al-huffaz-portal'); ?>';

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'alhuffaz_mark_all_notifications_read',
                nonce: '<?php echo wp_create_nonce('alhuffaz_public_nonce'); ?>'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadNotifications();
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check-double"></i> <?php _e('Mark all read', 'al-huffaz-portal'); ?>';
            }
        })
        .catch(error => {
            console.error('Error marking all as read:', error);
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check-double"></i> <?php _e('Mark all read', 'al-huffaz-portal'); ?>';
        });
    };

    // Load notification count on page load
    function loadNotificationCount() {
        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'alhuffaz_get_notifications',
                nonce: '<?php echo wp_create_nonce('alhuffaz_public_nonce'); ?>',
                limit: 1,
                unread_only: 'true'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateNotificationBadge(data.data.unread_count);
            }
        })
        .catch(error => console.error('Error loading notification count:', error));
    }

    // Load notification count on page load
    loadNotificationCount();

    // Refresh notification count every 30 seconds
    setInterval(loadNotificationCount, 30000);

    // Auto-open panel based on URL parameter
    const urlParams = new URLSearchParams(window.location.search);
    const openTab = urlParams.get('open_tab');

    if (openTab === 'payments') {
        // Open payments panel
        showPanel('payments');
    } else if (openTab === 'my-students') {
        // Open my students panel
        showPanel('my-students');
    }

    // Auto-dismiss success alert after 8 seconds
    const successAlert = document.getElementById('payment-success-alert');
    if (successAlert) {
        setTimeout(function() {
            successAlert.style.transition = 'opacity 0.5s, transform 0.5s';
            successAlert.style.opacity = '0';
            successAlert.style.transform = 'translateY(-20px)';
            setTimeout(() => {
                successAlert.remove();
                // Remove URL parameters
                const url = new URL(window.location.href);
                url.searchParams.delete('payment_submitted');
                url.searchParams.delete('open_tab');
                url.searchParams.delete('sponsorship_id');
                window.history.replaceState({}, '', url);
            }, 500);
        }, 8000);
    }
})();
</script>
