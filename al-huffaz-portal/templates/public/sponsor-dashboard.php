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

// Get available students for sponsorship
$available_students = get_posts(array(
    'post_type' => 'student',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'meta_query' => array(
        'relation' => 'AND',
        array('key' => 'donation_eligible', 'value' => 'yes'),
        array(
            'relation' => 'OR',
            array('key' => '_is_sponsored', 'value' => 'no'),
            array('key' => '_is_sponsored', 'compare' => 'NOT EXISTS'),
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

            <?php if ($sponsor_status === 'pending'): ?>
            <div class="sp-alert sp-alert-warning">
                <i class="fas fa-clock"></i>
                <div class="sp-alert-content">
                    <strong><?php _e('Account Pending Verification', 'al-huffaz-portal'); ?></strong>
                    <span><?php _e('Your sponsor account is being reviewed. You will be able to sponsor students once approved.', 'al-huffaz-portal'); ?></span>
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
                    $monthly_fee = get_post_meta($student_id, 'monthly_tuition_fee', true);
                    $course_fee = get_post_meta($student_id, 'course_fee', true);
                    $total_fee = floatval($monthly_fee) + floatval($course_fee);
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
                                <div class="sp-fee-label"><?php _e('Monthly', 'al-huffaz-portal'); ?></div>
                                <div class="sp-fee-value">PKR <?php echo number_format($monthly_fee ?: 0); ?></div>
                            </div>
                            <div class="sp-fee-item">
                                <div class="sp-fee-label"><?php _e('Course', 'al-huffaz-portal'); ?></div>
                                <div class="sp-fee-value">PKR <?php echo number_format($course_fee ?: 0); ?></div>
                            </div>
                            <div class="sp-fee-item">
                                <div class="sp-fee-label"><?php _e('Total', 'al-huffaz-portal'); ?></div>
                                <div class="sp-fee-value">PKR <?php echo number_format($total_fee); ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="sp-student-footer">
                        <a href="<?php echo get_permalink($student_id); ?>" class="sp-btn sp-btn-primary sp-btn-block sp-btn-sm">
                            <i class="fas fa-eye"></i> <?php _e('View Full Profile', 'al-huffaz-portal'); ?>
                        </a>
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
                    $monthly_fee = get_post_meta($student_id, 'monthly_tuition_fee', true);
                    $course_fee = get_post_meta($student_id, 'course_fee', true);
                    $total_fee = floatval($monthly_fee) + floatval($course_fee);
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
                                <div class="sp-fee-label"><?php _e('Monthly', 'al-huffaz-portal'); ?></div>
                                <div class="sp-fee-value">PKR <?php echo number_format($monthly_fee ?: 0); ?></div>
                            </div>
                            <div class="sp-fee-item">
                                <div class="sp-fee-label"><?php _e('Course', 'al-huffaz-portal'); ?></div>
                                <div class="sp-fee-value">PKR <?php echo number_format($course_fee ?: 0); ?></div>
                            </div>
                            <div class="sp-fee-item">
                                <div class="sp-fee-label"><?php _e('Total', 'al-huffaz-portal'); ?></div>
                                <div class="sp-fee-value">PKR <?php echo number_format($total_fee); ?></div>
                            </div>
                        </div>
                        <p class="sp-plan-title"><?php _e('Choose Sponsorship Plan', 'al-huffaz-portal'); ?></p>
                        <div class="sp-plan-grid">
                            <button type="button" class="sp-plan-btn" onclick="openSponsorModal(<?php echo $student_id; ?>, '<?php echo esc_js($student->post_title); ?>', 1, <?php echo $total_fee; ?>)">
                                <span class="sp-plan-duration"><?php _e('1 Month', 'al-huffaz-portal'); ?></span>
                                <span class="sp-plan-amount">PKR <?php echo number_format($total_fee); ?></span>
                            </button>
                            <button type="button" class="sp-plan-btn" onclick="openSponsorModal(<?php echo $student_id; ?>, '<?php echo esc_js($student->post_title); ?>', 3, <?php echo $total_fee * 3; ?>)">
                                <span class="sp-plan-duration"><?php _e('3 Months', 'al-huffaz-portal'); ?></span>
                                <span class="sp-plan-amount">PKR <?php echo number_format($total_fee * 3); ?></span>
                            </button>
                            <button type="button" class="sp-plan-btn" onclick="openSponsorModal(<?php echo $student_id; ?>, '<?php echo esc_js($student->post_title); ?>', 6, <?php echo $total_fee * 6; ?>)">
                                <span class="sp-plan-duration"><?php _e('6 Months', 'al-huffaz-portal'); ?></span>
                                <span class="sp-plan-amount">PKR <?php echo number_format($total_fee * 6); ?></span>
                            </button>
                            <button type="button" class="sp-plan-btn featured" onclick="openSponsorModal(<?php echo $student_id; ?>, '<?php echo esc_js($student->post_title); ?>', 12, <?php echo $total_fee * 12; ?>)">
                                <span class="sp-plan-badge"><?php _e('Best Value', 'al-huffaz-portal'); ?></span>
                                <span class="sp-plan-duration"><?php _e('12 Months', 'al-huffaz-portal'); ?></span>
                                <span class="sp-plan-amount">PKR <?php echo number_format($total_fee * 12); ?></span>
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

    window.submitSponsorship = function() {
        const form = document.getElementById('sponsorForm');
        const formData = new FormData(form);

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            closeSponsorModal();
            if (data.success) {
                showToast('Sponsorship created successfully!', 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showToast(data.data || 'An error occurred', 'error');
            }
        })
        .catch(() => {
            showToast('An error occurred', 'error');
        });
    };

    // Payment Form
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

    // Toast
    function showToast(message, type) {
        const toast = document.getElementById('spToast');
        toast.textContent = message;
        toast.className = 'sp-toast ' + type;
        toast.style.display = 'block';
        setTimeout(() => { toast.style.display = 'none'; }, 3000);
    }
    window.showToast = showToast;

    // Close modal on outside click
    document.getElementById('sponsorModal').addEventListener('click', function(e) {
        if (e.target === this) closeSponsorModal();
    });
})();
</script>
