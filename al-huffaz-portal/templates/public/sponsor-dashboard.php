<?php
/**
 * Sponsor Dashboard Template
 * Al-Huffaz Education System Portal
 *
 * Complete sponsor portal with sidebar navigation:
 * - Dashboard with stats
 * - My Profile
 * - My Sponsored Students
 * - Available Students to Sponsor
 * - Make Payment
 * - Payment History
 */

use AlHuffaz\Frontend\Sponsor_Dashboard;
use AlHuffaz\Core\Helpers;
use AlHuffaz\Core\Roles;

if (!defined('ABSPATH')) exit;

// Check if user is logged in
if (!is_user_logged_in()) {
    ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
    .ahp-sponsor-login {
        min-height: 80vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 40px 20px;
        font-family: 'Poppins', sans-serif;
        background: linear-gradient(135deg, #f0f8ff 0%, #e6f3ff 100%);
    }
    .ahp-login-card {
        background: white;
        border-radius: 24px;
        box-shadow: 0 20px 60px rgba(0, 128, 255, 0.15);
        padding: 48px;
        max-width: 420px;
        width: 100%;
        text-align: center;
    }
    .ahp-login-header i { font-size: 72px; color: #0080ff; margin-bottom: 20px; display: block; }
    .ahp-login-header h2 { margin: 0 0 12px 0; font-size: 28px; font-weight: 700; color: #001a33; }
    .ahp-login-header p { margin: 0 0 32px 0; color: #64748b; }
    .ahp-login-form label { display: block; font-weight: 600; color: #001a33; margin-bottom: 8px; font-size: 14px; text-align: left; }
    .ahp-login-form input[type="text"], .ahp-login-form input[type="password"] {
        width: 100%; padding: 14px 16px; border: 2px solid #cce6ff; border-radius: 12px;
        font-size: 15px; margin-bottom: 20px; font-family: 'Poppins', sans-serif; box-sizing: border-box;
    }
    .ahp-login-form input:focus { outline: none; border-color: #0080ff; }
    .ahp-login-form input[type="submit"] {
        width: 100%; padding: 16px; background: linear-gradient(135deg, #0080ff, #004d99); color: white;
        border: none; border-radius: 12px; font-size: 16px; font-weight: 600; cursor: pointer;
        font-family: 'Poppins', sans-serif; transition: transform 0.3s, box-shadow 0.3s;
    }
    .ahp-login-form input[type="submit"]:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(0, 128, 255, 0.3); }
    .ahp-login-footer { margin-top: 32px; padding-top: 24px; border-top: 2px solid #f0f8ff; }
    .ahp-login-footer p { margin: 0 0 16px 0; color: #64748b; }
    .ahp-btn { display: inline-flex; align-items: center; gap: 8px; padding: 14px 28px; border-radius: 12px;
        font-weight: 600; font-size: 15px; text-decoration: none; background: linear-gradient(135deg, #0080ff, #004d99); color: white; }
    </style>
    <div class="ahp-sponsor-login">
        <div class="ahp-login-card">
            <div class="ahp-login-header">
                <i class="fas fa-hand-holding-heart"></i>
                <h2><?php _e('Sponsor Portal', 'al-huffaz-portal'); ?></h2>
                <p><?php _e('Login to access your sponsor dashboard', 'al-huffaz-portal'); ?></p>
            </div>
            <div class="ahp-login-form">
                <?php wp_login_form(array('redirect' => get_permalink(), 'form_id' => 'ahp-sponsor-login-form',
                    'label_username' => __('Email or Username', 'al-huffaz-portal'), 'label_password' => __('Password', 'al-huffaz-portal'),
                    'label_remember' => __('Remember Me', 'al-huffaz-portal'), 'label_log_in' => __('Login', 'al-huffaz-portal'))); ?>
            </div>
            <div class="ahp-login-footer">
                <p><?php _e('Not a sponsor yet?', 'al-huffaz-portal'); ?></p>
                <a href="<?php echo home_url('/become-a-sponsor'); ?>" class="ahp-btn">
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

// User approval status - if user can log in, they're approved at WP/UM level
// The sponsor role or ability to login means they're an approved user
$is_user_approved = true; // If they're logged in, they've been approved by WP/UM

// Check Ultimate Member approval status if UM is active
if (function_exists('um_user') && function_exists('um_is_user_approved')) {
    $is_user_approved = um_is_user_approved($user_id);
}

// User is approved if: they're logged in AND (UM says approved OR UM not installed)
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

// Get pending sponsorship requests
$pending_requests = get_posts(array(
    'post_type' => 'alhuffaz_sponsor',
    'posts_per_page' => -1,
    'meta_query' => array(
        array('key' => '_sponsor_user_id', 'value' => $user_id),
        array('key' => '_status', 'value' => 'pending'),
    ),
));

$portal_url = get_permalink();
$nonce = wp_create_nonce('alhuffaz_public_nonce');
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

<style>
/* ============================================
   SPONSOR PORTAL - COMPLETE STYLES
   ============================================ */
:root {
    --sp-primary: #0080ff;
    --sp-primary-dark: #004d99;
    --sp-success: #10b981;
    --sp-warning: #f59e0b;
    --sp-danger: #ef4444;
    --sp-text: #1e293b;
    --sp-text-muted: #64748b;
    --sp-border: #e2e8f0;
    --sp-bg: #f8fafc;
    --sp-sidebar: #0f172a;
    --sp-card: #ffffff;
}
* { box-sizing: border-box; }
.sp-portal {
    font-family: 'Poppins', sans-serif;
    background: var(--sp-bg);
    min-height: 100vh;
    color: var(--sp-text);
}

/* Layout */
.sp-wrapper { display: flex; min-height: 100vh; }
.sp-sidebar {
    width: 280px;
    background: linear-gradient(180deg, var(--sp-sidebar) 0%, #1e293b 100%);
    color: #fff;
    position: fixed;
    height: 100vh;
    overflow-y: auto;
    z-index: 100;
}
.sp-main {
    flex: 1;
    margin-left: 280px;
    padding: 24px;
    min-height: 100vh;
}

/* Sidebar */
.sp-sidebar-header {
    padding: 24px;
    border-bottom: 1px solid rgba(255,255,255,0.1);
    text-align: center;
}
.sp-logo {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    font-size: 20px;
    font-weight: 700;
}
.sp-logo i { font-size: 28px; color: var(--sp-primary); }
.sp-user-card {
    padding: 20px;
    margin: 16px;
    background: rgba(255,255,255,0.05);
    border-radius: 16px;
    text-align: center;
}
.sp-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--sp-primary), var(--sp-primary-dark));
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    font-weight: 800;
    margin: 0 auto 12px;
    border: 4px solid rgba(255,255,255,0.2);
}
.sp-user-name { font-size: 18px; font-weight: 700; margin-bottom: 4px; }
.sp-user-status {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}
.sp-status-approved { background: rgba(16, 185, 129, 0.2); color: #34d399; }
.sp-status-pending { background: rgba(245, 158, 11, 0.2); color: #fbbf24; }
.sp-nav { padding: 16px 0; }
.sp-nav-item {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 24px;
    color: rgba(255,255,255,0.7);
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s;
    cursor: pointer;
    border-left: 4px solid transparent;
}
.sp-nav-item:hover, .sp-nav-item.active {
    background: rgba(0, 128, 255, 0.15);
    color: #fff;
    border-left-color: var(--sp-primary);
}
.sp-nav-item i { width: 20px; text-align: center; }
.sp-nav-badge {
    margin-left: auto;
    background: var(--sp-primary);
    color: white;
    padding: 2px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 700;
}
.sp-nav-badge.warning { background: var(--sp-warning); }

/* Main Content */
.sp-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    flex-wrap: wrap;
    gap: 16px;
}
.sp-title { font-size: 28px; font-weight: 700; margin: 0; color: var(--sp-text); }
.sp-panel { display: none; }
.sp-panel.active { display: block; }

/* Stats */
.sp-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 20px;
    margin-bottom: 32px;
}
.sp-stat {
    background: var(--sp-card);
    border-radius: 16px;
    padding: 24px;
    display: flex;
    align-items: center;
    gap: 20px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.05);
    border: 1px solid var(--sp-border);
}
.sp-stat-icon {
    width: 64px;
    height: 64px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
}
.sp-stat-icon.blue { background: #dbeafe; color: #1e40af; }
.sp-stat-icon.green { background: #d1fae5; color: #065f46; }
.sp-stat-icon.orange { background: #fef3c7; color: #92400e; }
.sp-stat-icon.purple { background: #ede9fe; color: #5b21b6; }
.sp-stat-label { font-size: 14px; color: var(--sp-text-muted); margin-bottom: 4px; }
.sp-stat-value { font-size: 28px; font-weight: 800; color: var(--sp-text); }

/* Cards */
.sp-card {
    background: var(--sp-card);
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.05);
    border: 1px solid var(--sp-border);
    margin-bottom: 24px;
}
.sp-card-header {
    padding: 20px 24px;
    border-bottom: 1px solid var(--sp-border);
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.sp-card-title { font-size: 18px; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 10px; }
.sp-card-title i { color: var(--sp-primary); }
.sp-card-body { padding: 24px; }

/* Student Grid */
.sp-students-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 24px;
}
.sp-student-card {
    background: var(--sp-card);
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0,0,0,0.05);
    border: 1px solid var(--sp-border);
    transition: transform 0.3s, box-shadow 0.3s;
}
.sp-student-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 40px rgba(0,128,255,0.15);
}
.sp-student-header {
    padding: 20px;
    background: linear-gradient(135deg, #f0f8ff, #fff);
    display: flex;
    align-items: center;
    gap: 16px;
    border-bottom: 1px solid var(--sp-border);
}
.sp-student-photo {
    width: 70px;
    height: 70px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid white;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.sp-student-placeholder {
    width: 70px;
    height: 70px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--sp-primary), var(--sp-primary-dark));
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    font-weight: 800;
}
.sp-student-info h3 { margin: 0 0 8px 0; font-size: 18px; font-weight: 700; }
.sp-student-badges { display: flex; gap: 8px; flex-wrap: wrap; }
.sp-badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
}
.sp-badge-grade { background: var(--sp-primary); color: white; }
.sp-badge-category { background: #d1fae5; color: #065f46; }
.sp-student-body { padding: 20px; }
.sp-fee-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
    margin-bottom: 16px;
}
.sp-fee-item {
    text-align: center;
    padding: 12px;
    background: var(--sp-bg);
    border-radius: 10px;
}
.sp-fee-label { font-size: 11px; color: var(--sp-text-muted); margin-bottom: 4px; }
.sp-fee-value { font-size: 16px; font-weight: 700; color: var(--sp-text); }
.sp-student-footer {
    padding: 16px 20px;
    background: var(--sp-bg);
    border-top: 1px solid var(--sp-border);
}

/* Buttons */
.sp-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px 24px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 14px;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.3s;
    border: none;
    font-family: 'Poppins', sans-serif;
}
.sp-btn-primary {
    background: linear-gradient(135deg, var(--sp-primary), var(--sp-primary-dark));
    color: white;
}
.sp-btn-success { background: var(--sp-success); color: white; }
.sp-btn-secondary { background: var(--sp-bg); color: var(--sp-text); border: 1px solid var(--sp-border); }
.sp-btn-outline { background: transparent; color: var(--sp-primary); border: 2px solid var(--sp-primary); }
.sp-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
.sp-btn-block { width: 100%; }
.sp-btn-sm { padding: 8px 16px; font-size: 13px; }

/* Payment Plan Buttons */
.sp-plan-title {
    font-size: 14px;
    font-weight: 600;
    color: var(--sp-text);
    margin: 0 0 12px 0;
    text-align: center;
}
.sp-plan-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
}
.sp-plan-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 16px 12px;
    background: var(--sp-bg);
    border: 2px solid var(--sp-border);
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.3s;
    font-family: 'Poppins', sans-serif;
    position: relative;
}
.sp-plan-btn:hover {
    border-color: var(--sp-primary);
    background: #f0f7ff;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 128, 255, 0.15);
}
.sp-plan-btn.sp-plan-featured {
    background: linear-gradient(135deg, #e0f2ff, #cce6ff);
    border-color: var(--sp-primary);
}
.sp-plan-badge {
    position: absolute;
    top: -10px;
    right: -10px;
    background: var(--sp-success);
    color: white;
    font-size: 10px;
    font-weight: 700;
    padding: 4px 8px;
    border-radius: 20px;
}
.sp-plan-duration {
    font-size: 14px;
    font-weight: 600;
    color: var(--sp-text);
    margin-bottom: 4px;
}
.sp-plan-amount {
    font-size: 18px;
    font-weight: 700;
    color: var(--sp-primary);
}
.sp-plan-note {
    font-size: 10px;
    color: var(--sp-text-muted);
    margin-top: 4px;
}

/* Payment Proof Summary */
.sp-proof-summary {
    display: grid;
    gap: 12px;
}
.sp-proof-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 16px;
    background: var(--sp-bg);
    border-radius: 10px;
}
.sp-proof-label {
    font-size: 14px;
    color: var(--sp-text-muted);
}
.sp-proof-value {
    font-size: 15px;
    font-weight: 600;
    color: var(--sp-text);
}
.sp-proof-total {
    background: linear-gradient(135deg, #e0f2ff, #cce6ff);
    border: 2px solid var(--sp-primary);
}
.sp-proof-total .sp-proof-value {
    font-size: 20px;
    color: var(--sp-primary);
}
.sp-form-hint {
    display: block;
    font-size: 12px;
    color: var(--sp-text-muted);
    margin-top: 6px;
}

/* Profile */
.sp-profile-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 16px;
}
.sp-profile-item {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 16px;
    background: var(--sp-bg);
    border-radius: 12px;
}
.sp-profile-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: white;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--sp-primary);
    font-size: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}
.sp-profile-label { font-size: 12px; color: var(--sp-text-muted); }
.sp-profile-value { font-size: 15px; font-weight: 600; color: var(--sp-text); }

/* Table */
.sp-table-wrap { overflow-x: auto; }
.sp-table { width: 100%; border-collapse: collapse; }
.sp-table th, .sp-table td { padding: 14px 16px; text-align: left; border-bottom: 1px solid var(--sp-border); }
.sp-table thead th { background: var(--sp-bg); font-weight: 700; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; color: var(--sp-text-muted); }
.sp-table tbody tr:hover { background: var(--sp-bg); }
.sp-status {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}
.sp-status-approved { background: #d1fae5; color: #065f46; }
.sp-status-pending { background: #fef3c7; color: #92400e; }
.sp-status-rejected { background: #fee2e2; color: #991b1b; }

/* Payment Form */
.sp-form-group { margin-bottom: 20px; }
.sp-form-label { display: block; font-weight: 600; margin-bottom: 8px; font-size: 14px; }
.sp-form-input, .sp-form-select {
    width: 100%;
    padding: 14px 16px;
    border: 2px solid var(--sp-border);
    border-radius: 10px;
    font-size: 15px;
    font-family: 'Poppins', sans-serif;
    transition: border-color 0.3s;
}
.sp-form-input:focus, .sp-form-select:focus { outline: none; border-color: var(--sp-primary); }
.sp-form-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; }

/* Empty State */
.sp-empty {
    text-align: center;
    padding: 60px 20px;
    color: var(--sp-text-muted);
}
.sp-empty i { font-size: 64px; opacity: 0.3; margin-bottom: 16px; }
.sp-empty h3 { margin: 0 0 8px 0; color: var(--sp-text); }
.sp-empty p { margin: 0 0 20px 0; }

/* Alerts */
.sp-alert {
    padding: 16px 20px;
    border-radius: 12px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 12px;
}
.sp-alert-warning { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
.sp-alert-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
.sp-alert-info { background: #dbeafe; color: #1e40af; border: 1px solid #bfdbfe; }

/* Toast */
.sp-toast {
    position: fixed;
    bottom: 24px;
    right: 24px;
    padding: 16px 24px;
    background: var(--sp-text);
    color: white;
    border-radius: 12px;
    font-weight: 500;
    z-index: 9999;
    display: none;
    box-shadow: 0 8px 30px rgba(0,0,0,0.2);
}
.sp-toast.success { background: var(--sp-success); }
.sp-toast.error { background: var(--sp-danger); }

/* Modal */
.sp-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
    align-items: center;
    justify-content: center;
}
.sp-modal-content {
    background: white;
    border-radius: 20px;
    max-width: 500px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
}
.sp-modal-header {
    padding: 20px 24px;
    border-bottom: 1px solid var(--sp-border);
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.sp-modal-header h3 { margin: 0; font-size: 20px; }
.sp-modal-close { background: none; border: none; font-size: 24px; cursor: pointer; color: var(--sp-text-muted); }
.sp-modal-body { padding: 24px; }
.sp-modal-footer { padding: 16px 24px; border-top: 1px solid var(--sp-border); display: flex; gap: 12px; justify-content: flex-end; }

/* Responsive */
@media (max-width: 992px) {
    .sp-sidebar { width: 240px; }
    .sp-main { margin-left: 240px; }
}
@media (max-width: 768px) {
    .sp-sidebar { position: relative; width: 100%; height: auto; }
    .sp-main { margin-left: 0; }
    .sp-wrapper { flex-direction: column; }
    .sp-stats { grid-template-columns: 1fr; }
    .sp-students-grid { grid-template-columns: 1fr; }
}
</style>

<div class="sp-portal">
    <div class="sp-wrapper">
        <!-- Sidebar -->
        <aside class="sp-sidebar">
            <div class="sp-sidebar-header">
                <div class="sp-logo">
                    <i class="fas fa-graduation-cap"></i>
                    <span>Sponsor Portal</span>
                </div>
            </div>

            <div class="sp-user-card">
                <div class="sp-avatar"><?php echo strtoupper(substr($user->display_name, 0, 1)); ?></div>
                <div class="sp-user-name"><?php echo esc_html($user->display_name); ?></div>
                <span class="sp-user-status sp-status-<?php echo $sponsor_status; ?>">
                    <i class="fas fa-<?php echo $sponsor_status === 'approved' ? 'check-circle' : 'clock'; ?>"></i>
                    <?php echo ucfirst($sponsor_status); ?>
                </span>
            </div>

            <nav class="sp-nav">
                <a class="sp-nav-item active" data-panel="dashboard">
                    <i class="fas fa-home"></i>
                    <span><?php _e('Dashboard', 'al-huffaz-portal'); ?></span>
                </a>
                <a class="sp-nav-item" data-panel="profile">
                    <i class="fas fa-user"></i>
                    <span><?php _e('My Profile', 'al-huffaz-portal'); ?></span>
                </a>
                <a class="sp-nav-item" data-panel="my-students">
                    <i class="fas fa-user-graduate"></i>
                    <span><?php _e('My Students', 'al-huffaz-portal'); ?></span>
                    <?php if (count($data['sponsorships']) > 0): ?>
                    <span class="sp-nav-badge"><?php echo count($data['sponsorships']); ?></span>
                    <?php endif; ?>
                </a>
                <a class="sp-nav-item" data-panel="available-students">
                    <i class="fas fa-hand-holding-heart"></i>
                    <span><?php _e('Sponsor a Student', 'al-huffaz-portal'); ?></span>
                    <?php if (count($available_students) > 0): ?>
                    <span class="sp-nav-badge"><?php echo count($available_students); ?></span>
                    <?php endif; ?>
                </a>
                <a class="sp-nav-item" data-panel="payments">
                    <i class="fas fa-credit-card"></i>
                    <span><?php _e('Make Payment', 'al-huffaz-portal'); ?></span>
                </a>
                <a class="sp-nav-item" data-panel="history">
                    <i class="fas fa-history"></i>
                    <span><?php _e('Payment History', 'al-huffaz-portal'); ?></span>
                    <?php if ($data['pending_payments'] > 0): ?>
                    <span class="sp-nav-badge warning"><?php echo $data['pending_payments']; ?></span>
                    <?php endif; ?>
                </a>
                <a class="sp-nav-item" href="<?php echo wp_logout_url(home_url()); ?>">
                    <i class="fas fa-sign-out-alt"></i>
                    <span><?php _e('Logout', 'al-huffaz-portal'); ?></span>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="sp-main">
            <!-- ==================== DASHBOARD PANEL ==================== -->
            <div class="sp-panel active" id="panel-dashboard">
                <div class="sp-header">
                    <h1 class="sp-title"><?php printf(__('Welcome, %s!', 'al-huffaz-portal'), esc_html($user->first_name ?: $user->display_name)); ?></h1>
                </div>

                <?php if ($sponsor_status === 'pending'): ?>
                <div class="sp-alert sp-alert-warning">
                    <i class="fas fa-clock"></i>
                    <div>
                        <strong><?php _e('Account Pending Verification', 'al-huffaz-portal'); ?></strong>
                        <p style="margin:4px 0 0;"><?php _e('Your sponsor account is being reviewed. You will be able to sponsor students once approved.', 'al-huffaz-portal'); ?></p>
                    </div>
                </div>
                <?php endif; ?>

                <div class="sp-stats">
                    <div class="sp-stat">
                        <div class="sp-stat-icon blue"><i class="fas fa-user-graduate"></i></div>
                        <div>
                            <div class="sp-stat-label"><?php _e('Students Sponsored', 'al-huffaz-portal'); ?></div>
                            <div class="sp-stat-value"><?php echo intval($data['students_count']); ?></div>
                        </div>
                    </div>
                    <div class="sp-stat">
                        <div class="sp-stat-icon green"><i class="fas fa-donate"></i></div>
                        <div>
                            <div class="sp-stat-label"><?php _e('Total Contributed', 'al-huffaz-portal'); ?></div>
                            <div class="sp-stat-value"><?php echo esc_html($data['total_contributed']); ?></div>
                        </div>
                    </div>
                    <div class="sp-stat">
                        <div class="sp-stat-icon orange"><i class="fas fa-hourglass-half"></i></div>
                        <div>
                            <div class="sp-stat-label"><?php _e('Pending Payments', 'al-huffaz-portal'); ?></div>
                            <div class="sp-stat-value"><?php echo intval($data['pending_payments']); ?></div>
                        </div>
                    </div>
                    <div class="sp-stat">
                        <div class="sp-stat-icon purple"><i class="fas fa-heart"></i></div>
                        <div>
                            <div class="sp-stat-label"><?php _e('Available to Sponsor', 'al-huffaz-portal'); ?></div>
                            <div class="sp-stat-value"><?php echo count($available_students); ?></div>
                        </div>
                    </div>
                </div>

                <?php if (!empty($data['sponsorships'])): ?>
                <div class="sp-card">
                    <div class="sp-card-header">
                        <h3 class="sp-card-title"><i class="fas fa-users"></i> <?php _e('Your Sponsored Students', 'al-huffaz-portal'); ?></h3>
                        <button class="sp-btn sp-btn-sm sp-btn-outline" onclick="showPanel('my-students')"><?php _e('View All', 'al-huffaz-portal'); ?></button>
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
                <div class="sp-header">
                    <h1 class="sp-title"><?php _e('My Profile', 'al-huffaz-portal'); ?></h1>
                </div>

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
                <div class="sp-header">
                    <h1 class="sp-title"><?php _e('My Sponsored Students', 'al-huffaz-portal'); ?></h1>
                </div>

                <?php if (empty($data['sponsorships'])): ?>
                <div class="sp-card">
                    <div class="sp-card-body">
                        <div class="sp-empty">
                            <i class="fas fa-users"></i>
                            <h3><?php _e('No Sponsored Students Yet', 'al-huffaz-portal'); ?></h3>
                            <p><?php _e('Start sponsoring a student to see them here.', 'al-huffaz-portal'); ?></p>
                            <button class="sp-btn sp-btn-primary" onclick="showPanel('available-students')">
                                <i class="fas fa-hand-holding-heart"></i> <?php _e('Sponsor a Student', 'al-huffaz-portal'); ?>
                            </button>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="sp-students-grid">
                    <?php foreach ($data['sponsorships'] as $s): ?>
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
                                    <div class="sp-fee-label"><?php _e('Amount', 'al-huffaz-portal'); ?></div>
                                    <div class="sp-fee-value"><?php echo Helpers::format_currency($s['amount']); ?></div>
                                </div>
                                <div class="sp-fee-item">
                                    <div class="sp-fee-label"><?php _e('Plan', 'al-huffaz-portal'); ?></div>
                                    <div class="sp-fee-value"><?php echo ucfirst($s['type']); ?></div>
                                </div>
                                <div class="sp-fee-item">
                                    <div class="sp-fee-label"><?php _e('Since', 'al-huffaz-portal'); ?></div>
                                    <div class="sp-fee-value"><?php echo esc_html($s['start_date']); ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="sp-student-footer" style="display:flex;gap:10px;">
                            <a href="<?php echo get_permalink($s['student_id']); ?>" class="sp-btn sp-btn-secondary sp-btn-sm" style="flex:1;">
                                <i class="fas fa-eye"></i> <?php _e('View', 'al-huffaz-portal'); ?>
                            </a>
                            <button class="sp-btn sp-btn-primary sp-btn-sm" style="flex:1;" onclick="openPaymentModal(<?php echo $s['student_id']; ?>, '<?php echo esc_js($s['student_name']); ?>', <?php echo floatval($s['amount']); ?>)">
                                <i class="fas fa-credit-card"></i> <?php _e('Pay', 'al-huffaz-portal'); ?>
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- ==================== AVAILABLE STUDENTS PANEL ==================== -->
            <div class="sp-panel" id="panel-available-students">
                <div class="sp-header">
                    <h1 class="sp-title"><?php _e('Sponsor a Student', 'al-huffaz-portal'); ?></h1>
                </div>

                <?php if ($sponsor_status !== 'approved'): ?>
                <div class="sp-alert sp-alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div><?php _e('Your account needs to be approved before you can sponsor students.', 'al-huffaz-portal'); ?></div>
                </div>
                <?php elseif (empty($available_students)): ?>
                <div class="sp-card">
                    <div class="sp-card-body">
                        <div class="sp-empty">
                            <i class="fas fa-check-circle"></i>
                            <h3><?php _e('All Students Sponsored!', 'al-huffaz-portal'); ?></h3>
                            <p><?php _e('All eligible students currently have sponsors. Check back later!', 'al-huffaz-portal'); ?></p>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="sp-students-grid">
                    <?php foreach ($available_students as $student):
                        $student_id = $student->ID;
                        $photo_id = get_post_meta($student_id, 'student_photo', true);
                        $photo_url = $photo_id ? wp_get_attachment_image_url($photo_id, 'medium') : '';
                        $grade = Helpers::get_grade_label(get_post_meta($student_id, 'grade_level', true));
                        $category = Helpers::get_islamic_category_label(get_post_meta($student_id, 'islamic_studies_category', true));

                        // Fee calculations
                        $monthly_fee = floatval(get_post_meta($student_id, 'monthly_tuition_fee', true));
                        $course_fee = floatval(get_post_meta($student_id, 'course_fee', true));
                        $uniform_fee = floatval(get_post_meta($student_id, 'uniform_fee', true));
                        $annual_fee = floatval(get_post_meta($student_id, 'annual_fee', true));
                        $admission_fee = floatval(get_post_meta($student_id, 'admission_fee', true));
                        $one_time_total = $course_fee + $uniform_fee + $annual_fee + $admission_fee;

                        // Payment plans: 1, 3, 6, 12 months
                        $plan_1_month = $monthly_fee;
                        $plan_3_months = $monthly_fee * 3;
                        $plan_6_months = $monthly_fee * 6;
                        $plan_12_months = ($monthly_fee * 12) + $one_time_total;
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
                                    <?php if ($grade): ?><span class="sp-badge sp-badge-grade"><?php echo esc_html($grade); ?></span><?php endif; ?>
                                    <?php if ($category): ?><span class="sp-badge sp-badge-category"><?php echo esc_html($category); ?></span><?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="sp-student-body">
                            <p class="sp-plan-title"><?php _e('Select a Sponsorship Plan:', 'al-huffaz-portal'); ?></p>
                            <div class="sp-plan-grid">
                                <button class="sp-plan-btn" onclick="goToPaymentPage(<?php echo $student_id; ?>, '<?php echo esc_js($student->post_title); ?>', 1, <?php echo $plan_1_month; ?>)">
                                    <span class="sp-plan-duration">1 <?php _e('Month', 'al-huffaz-portal'); ?></span>
                                    <span class="sp-plan-amount"><?php echo Helpers::format_currency($plan_1_month); ?></span>
                                </button>
                                <button class="sp-plan-btn" onclick="goToPaymentPage(<?php echo $student_id; ?>, '<?php echo esc_js($student->post_title); ?>', 3, <?php echo $plan_3_months; ?>)">
                                    <span class="sp-plan-duration">3 <?php _e('Months', 'al-huffaz-portal'); ?></span>
                                    <span class="sp-plan-amount"><?php echo Helpers::format_currency($plan_3_months); ?></span>
                                </button>
                                <button class="sp-plan-btn" onclick="goToPaymentPage(<?php echo $student_id; ?>, '<?php echo esc_js($student->post_title); ?>', 6, <?php echo $plan_6_months; ?>)">
                                    <span class="sp-plan-duration">6 <?php _e('Months', 'al-huffaz-portal'); ?></span>
                                    <span class="sp-plan-amount"><?php echo Helpers::format_currency($plan_6_months); ?></span>
                                </button>
                                <button class="sp-plan-btn sp-plan-featured" onclick="goToPaymentPage(<?php echo $student_id; ?>, '<?php echo esc_js($student->post_title); ?>', 12, <?php echo $plan_12_months; ?>)">
                                    <span class="sp-plan-badge"><?php _e('Best Value', 'al-huffaz-portal'); ?></span>
                                    <span class="sp-plan-duration">12 <?php _e('Months', 'al-huffaz-portal'); ?></span>
                                    <span class="sp-plan-amount"><?php echo Helpers::format_currency($plan_12_months); ?></span>
                                    <span class="sp-plan-note"><?php _e('Includes one-time fees', 'al-huffaz-portal'); ?></span>
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- ==================== PAYMENT PROOF PANEL ==================== -->
            <div class="sp-panel" id="panel-payment-proof">
                <div class="sp-header">
                    <button class="sp-btn sp-btn-secondary sp-btn-sm" onclick="showPanel('available-students')" style="margin-right:16px;">
                        <i class="fas fa-arrow-left"></i> <?php _e('Back', 'al-huffaz-portal'); ?>
                    </button>
                    <h1 class="sp-title"><?php _e('Submit Payment Proof', 'al-huffaz-portal'); ?></h1>
                </div>

                <div class="sp-card">
                    <div class="sp-card-header" style="background: linear-gradient(135deg, var(--sp-primary), var(--sp-primary-dark)); color: white;">
                        <h3 class="sp-card-title" style="color: white;"><i class="fas fa-hand-holding-heart"></i> <?php _e('Sponsorship Details', 'al-huffaz-portal'); ?></h3>
                    </div>
                    <div class="sp-card-body">
                        <div class="sp-proof-summary">
                            <div class="sp-proof-item">
                                <span class="sp-proof-label"><?php _e('Student:', 'al-huffaz-portal'); ?></span>
                                <span class="sp-proof-value" id="proofStudentName">-</span>
                            </div>
                            <div class="sp-proof-item">
                                <span class="sp-proof-label"><?php _e('Duration:', 'al-huffaz-portal'); ?></span>
                                <span class="sp-proof-value" id="proofPlanMonths">-</span>
                            </div>
                            <div class="sp-proof-item sp-proof-total">
                                <span class="sp-proof-label"><?php _e('Total Amount:', 'al-huffaz-portal'); ?></span>
                                <span class="sp-proof-value" id="proofAmountDisplay">-</span>
                            </div>
                        </div>

                        <hr style="margin: 24px 0; border: none; border-top: 2px solid var(--sp-border);">

                        <h4 style="margin: 0 0 20px 0; color: var(--sp-text);"><i class="fas fa-file-invoice"></i> <?php _e('Payment Information', 'al-huffaz-portal'); ?></h4>

                        <form id="paymentProofForm" enctype="multipart/form-data">
                            <input type="hidden" name="student_id" id="proofStudentId" value="">
                            <input type="hidden" name="amount" id="proofAmount" value="">
                            <input type="hidden" name="sponsorship_type" id="proofPlanType" value="">
                            <input type="hidden" name="action" value="alhuffaz_submit_payment_proof">
                            <input type="hidden" name="nonce" value="<?php echo $nonce; ?>">

                            <div class="sp-form-group">
                                <label class="sp-form-label"><?php _e('Payment Method', 'al-huffaz-portal'); ?> *</label>
                                <select name="payment_method" id="proofPaymentMethod" class="sp-form-select" required>
                                    <option value=""><?php _e('-- Select Payment Method --', 'al-huffaz-portal'); ?></option>
                                    <option value="bank_transfer"><?php _e('Bank Transfer', 'al-huffaz-portal'); ?></option>
                                    <option value="easypaisa"><?php _e('EasyPaisa', 'al-huffaz-portal'); ?></option>
                                    <option value="jazzcash"><?php _e('JazzCash', 'al-huffaz-portal'); ?></option>
                                    <option value="sadapay"><?php _e('SadaPay', 'al-huffaz-portal'); ?></option>
                                    <option value="nayapay"><?php _e('NayaPay', 'al-huffaz-portal'); ?></option>
                                    <option value="cash"><?php _e('Cash Deposit', 'al-huffaz-portal'); ?></option>
                                </select>
                            </div>

                            <div class="sp-form-group">
                                <label class="sp-form-label"><?php _e('Transaction ID / Reference Number', 'al-huffaz-portal'); ?> *</label>
                                <input type="text" name="transaction_id" id="proofTransactionId" class="sp-form-input" required placeholder="<?php _e('Enter your transaction reference number', 'al-huffaz-portal'); ?>">
                            </div>

                            <div class="sp-form-group">
                                <label class="sp-form-label"><?php _e('Payment Date', 'al-huffaz-portal'); ?> *</label>
                                <input type="date" name="payment_date" id="proofPaymentDate" class="sp-form-input" required value="<?php echo date('Y-m-d'); ?>">
                            </div>

                            <div class="sp-form-group">
                                <label class="sp-form-label"><?php _e('Payment Receipt / Screenshot', 'al-huffaz-portal'); ?> *</label>
                                <input type="file" name="payment_screenshot" id="proofScreenshot" class="sp-form-input" accept="image/*" required>
                                <small class="sp-form-hint"><?php _e('Upload a clear screenshot or photo of your payment receipt', 'al-huffaz-portal'); ?></small>
                            </div>

                            <div class="sp-form-group">
                                <label class="sp-form-label"><?php _e('Additional Notes (Optional)', 'al-huffaz-portal'); ?></label>
                                <textarea name="notes" id="proofNotes" class="sp-form-input" rows="3" placeholder="<?php _e('Any additional information about your payment...', 'al-huffaz-portal'); ?>"></textarea>
                            </div>

                            <div class="sp-alert sp-alert-info" style="margin-bottom:24px;">
                                <i class="fas fa-info-circle"></i>
                                <div>
                                    <strong><?php _e('What happens next?', 'al-huffaz-portal'); ?></strong>
                                    <p style="margin:8px 0 0;"><?php _e('After submitting, the school will verify your payment. Once approved, the student will be linked to your profile and you can track their progress.', 'al-huffaz-portal'); ?></p>
                                </div>
                            </div>

                            <button type="submit" class="sp-btn sp-btn-primary sp-btn-block" id="submitProofBtn">
                                <i class="fas fa-paper-plane"></i> <?php _e('Submit Payment Proof', 'al-huffaz-portal'); ?>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- ==================== PAYMENTS PANEL ==================== -->
            <div class="sp-panel" id="panel-payments">
                <div class="sp-header">
                    <h1 class="sp-title"><?php _e('Make a Payment', 'al-huffaz-portal'); ?></h1>
                </div>

                <?php if (empty($data['sponsorships'])): ?>
                <div class="sp-alert sp-alert-info">
                    <i class="fas fa-info-circle"></i>
                    <div><?php _e('You need to sponsor a student first before making payments.', 'al-huffaz-portal'); ?></div>
                </div>
                <?php else: ?>
                <div class="sp-card">
                    <div class="sp-card-header">
                        <h3 class="sp-card-title"><i class="fas fa-credit-card"></i> <?php _e('Payment Details', 'al-huffaz-portal'); ?></h3>
                    </div>
                    <div class="sp-card-body">
                        <form id="paymentForm">
                            <div class="sp-form-group">
                                <label class="sp-form-label"><?php _e('Select Student', 'al-huffaz-portal'); ?> *</label>
                                <select name="student_id" id="paymentStudent" class="sp-form-select" required onchange="updatePaymentAmount()">
                                    <option value=""><?php _e('-- Select Student --', 'al-huffaz-portal'); ?></option>
                                    <?php foreach ($data['sponsorships'] as $s): ?>
                                    <option value="<?php echo $s['student_id']; ?>" data-amount="<?php echo floatval($s['amount']); ?>" data-type="<?php echo esc_attr($s['type']); ?>">
                                        <?php echo esc_html($s['student_name']); ?> (<?php echo Helpers::format_currency($s['amount']); ?>/<?php echo $s['type']; ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="sp-form-row">
                                <div class="sp-form-group">
                                    <label class="sp-form-label"><?php _e('Amount', 'al-huffaz-portal'); ?> *</label>
                                    <input type="number" name="amount" id="paymentAmount" class="sp-form-input" required min="1" step="0.01">
                                </div>
                                <div class="sp-form-group">
                                    <label class="sp-form-label"><?php _e('Payment Method', 'al-huffaz-portal'); ?> *</label>
                                    <select name="payment_method" class="sp-form-select" required>
                                        <option value="bank_transfer"><?php _e('Bank Transfer', 'al-huffaz-portal'); ?></option>
                                        <option value="easypaisa"><?php _e('EasyPaisa', 'al-huffaz-portal'); ?></option>
                                        <option value="jazzcash"><?php _e('JazzCash', 'al-huffaz-portal'); ?></option>
                                        <option value="cash"><?php _e('Cash', 'al-huffaz-portal'); ?></option>
                                    </select>
                                </div>
                            </div>

                            <div class="sp-form-group">
                                <label class="sp-form-label"><?php _e('Transaction ID / Reference', 'al-huffaz-portal'); ?></label>
                                <input type="text" name="transaction_id" class="sp-form-input" placeholder="<?php _e('Enter transaction reference number', 'al-huffaz-portal'); ?>">
                            </div>

                            <div class="sp-form-group">
                                <label class="sp-form-label"><?php _e('Payment Screenshot (Optional)', 'al-huffaz-portal'); ?></label>
                                <input type="file" name="screenshot" id="paymentScreenshot" class="sp-form-input" accept="image/*">
                            </div>

                            <div class="sp-form-group">
                                <label class="sp-form-label"><?php _e('Notes (Optional)', 'al-huffaz-portal'); ?></label>
                                <textarea name="notes" class="sp-form-input" rows="3" placeholder="<?php _e('Any additional notes...', 'al-huffaz-portal'); ?>"></textarea>
                            </div>

                            <div class="sp-alert sp-alert-info" style="margin-bottom:20px;">
                                <i class="fas fa-info-circle"></i>
                                <div><?php _e('Your payment will be verified by the school administration. You will receive a confirmation once approved.', 'al-huffaz-portal'); ?></div>
                            </div>

                            <button type="submit" class="sp-btn sp-btn-primary sp-btn-block">
                                <i class="fas fa-paper-plane"></i> <?php _e('Submit Payment', 'al-huffaz-portal'); ?>
                            </button>
                        </form>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- ==================== HISTORY PANEL ==================== -->
            <div class="sp-panel" id="panel-history">
                <div class="sp-header">
                    <h1 class="sp-title"><?php _e('Payment History', 'al-huffaz-portal'); ?></h1>
                </div>

                <div class="sp-card">
                    <div class="sp-card-body" style="padding:0;">
                        <?php if (empty($data['recent_payments'])): ?>
                        <div class="sp-empty" style="padding:40px;">
                            <i class="fas fa-receipt"></i>
                            <h3><?php _e('No Payment History', 'al-huffaz-portal'); ?></h3>
                            <p><?php _e('Your payment records will appear here.', 'al-huffaz-portal'); ?></p>
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
                                    <?php foreach ($data['recent_payments'] as $p): ?>
                                    <tr>
                                        <td><?php echo esc_html($p['payment_date_formatted'] ?? '-'); ?></td>
                                        <td><strong><?php echo esc_html($p['student_name'] ?? '-'); ?></strong></td>
                                        <td><strong><?php echo esc_html($p['amount_formatted'] ?? '-'); ?></strong></td>
                                        <td><?php echo esc_html(ucfirst($p['payment_method'] ?? '-')); ?></td>
                                        <td>
                                            <span class="sp-status sp-status-<?php echo esc_attr($p['status'] ?? 'pending'); ?>">
                                                <i class="fas fa-<?php echo ($p['status'] ?? '') === 'approved' ? 'check-circle' : (($p['status'] ?? '') === 'rejected' ? 'times-circle' : 'clock'); ?>"></i>
                                                <?php echo ucfirst($p['status'] ?? 'Pending'); ?>
                                            </span>
                                        </td>
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
    </div>
    <div class="sp-toast" id="toast"></div>
</div>

<!-- Sponsor Student Modal -->
<div id="sponsorModal" class="sp-modal">
    <div class="sp-modal-content">
        <div class="sp-modal-header">
            <h3><i class="fas fa-hand-holding-heart"></i> <?php _e('Sponsor Student', 'al-huffaz-portal'); ?></h3>
            <button class="sp-modal-close" onclick="closeSponsorModal()">&times;</button>
        </div>
        <div class="sp-modal-body">
            <input type="hidden" id="sponsorStudentId">
            <div style="text-align:center;margin-bottom:20px;">
                <h3 id="sponsorStudentName" style="margin:0;"></h3>
            </div>

            <div class="sp-form-group">
                <label class="sp-form-label"><?php _e('Select Sponsorship Plan', 'al-huffaz-portal'); ?></label>
                <div style="display:grid;gap:12px;">
                    <label style="display:flex;align-items:center;padding:16px;background:var(--sp-bg);border-radius:10px;cursor:pointer;border:2px solid transparent;" class="plan-option" data-plan="monthly">
                        <input type="radio" name="sponsor_plan" value="monthly" style="margin-right:12px;" checked>
                        <div style="flex:1;">
                            <strong><?php _e('Monthly', 'al-huffaz-portal'); ?></strong>
                            <p style="margin:4px 0 0;font-size:13px;color:var(--sp-text-muted);"><?php _e('Pay every month', 'al-huffaz-portal'); ?></p>
                        </div>
                        <strong id="monthlyPrice" style="color:var(--sp-primary);"></strong>
                    </label>
                    <label style="display:flex;align-items:center;padding:16px;background:var(--sp-bg);border-radius:10px;cursor:pointer;border:2px solid transparent;" class="plan-option" data-plan="quarterly">
                        <input type="radio" name="sponsor_plan" value="quarterly" style="margin-right:12px;">
                        <div style="flex:1;">
                            <strong><?php _e('Quarterly', 'al-huffaz-portal'); ?></strong>
                            <p style="margin:4px 0 0;font-size:13px;color:var(--sp-text-muted);"><?php _e('Pay every 3 months', 'al-huffaz-portal'); ?></p>
                        </div>
                        <strong id="quarterlyPrice" style="color:var(--sp-primary);"></strong>
                    </label>
                    <label style="display:flex;align-items:center;padding:16px;background:var(--sp-bg);border-radius:10px;cursor:pointer;border:2px solid transparent;" class="plan-option" data-plan="yearly">
                        <input type="radio" name="sponsor_plan" value="yearly" style="margin-right:12px;">
                        <div style="flex:1;">
                            <strong><?php _e('Yearly', 'al-huffaz-portal'); ?></strong>
                            <p style="margin:4px 0 0;font-size:13px;color:var(--sp-text-muted);"><?php _e('Pay annually (includes one-time fees)', 'al-huffaz-portal'); ?></p>
                        </div>
                        <strong id="yearlyPrice" style="color:var(--sp-primary);"></strong>
                    </label>
                </div>
            </div>
        </div>
        <div class="sp-modal-footer">
            <button class="sp-btn sp-btn-secondary" onclick="closeSponsorModal()"><?php _e('Cancel', 'al-huffaz-portal'); ?></button>
            <button class="sp-btn sp-btn-primary" onclick="submitSponsorship()">
                <i class="fas fa-heart"></i> <?php _e('Confirm & Proceed to Payment', 'al-huffaz-portal'); ?>
            </button>
        </div>
    </div>
</div>

<!-- Payment Modal -->
<div id="quickPayModal" class="sp-modal">
    <div class="sp-modal-content">
        <div class="sp-modal-header">
            <h3><i class="fas fa-credit-card"></i> <?php _e('Quick Payment', 'al-huffaz-portal'); ?></h3>
            <button class="sp-modal-close" onclick="closePaymentModal()">&times;</button>
        </div>
        <div class="sp-modal-body">
            <form id="quickPayForm">
                <input type="hidden" name="student_id" id="quickPayStudentId">
                <div style="text-align:center;margin-bottom:20px;">
                    <h3 id="quickPayStudentName" style="margin:0;"></h3>
                </div>

                <div class="sp-form-row">
                    <div class="sp-form-group">
                        <label class="sp-form-label"><?php _e('Amount', 'al-huffaz-portal'); ?> *</label>
                        <input type="number" name="amount" id="quickPayAmount" class="sp-form-input" required min="1">
                    </div>
                    <div class="sp-form-group">
                        <label class="sp-form-label"><?php _e('Method', 'al-huffaz-portal'); ?> *</label>
                        <select name="payment_method" class="sp-form-select" required>
                            <option value="bank_transfer"><?php _e('Bank Transfer', 'al-huffaz-portal'); ?></option>
                            <option value="easypaisa"><?php _e('EasyPaisa', 'al-huffaz-portal'); ?></option>
                            <option value="jazzcash"><?php _e('JazzCash', 'al-huffaz-portal'); ?></option>
                        </select>
                    </div>
                </div>

                <div class="sp-form-group">
                    <label class="sp-form-label"><?php _e('Transaction ID', 'al-huffaz-portal'); ?></label>
                    <input type="text" name="transaction_id" class="sp-form-input">
                </div>
            </form>
        </div>
        <div class="sp-modal-footer">
            <button class="sp-btn sp-btn-secondary" onclick="closePaymentModal()"><?php _e('Cancel', 'al-huffaz-portal'); ?></button>
            <button class="sp-btn sp-btn-success" onclick="submitQuickPayment()">
                <i class="fas fa-check"></i> <?php _e('Submit Payment', 'al-huffaz-portal'); ?>
            </button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ajaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';
    const nonce = '<?php echo $nonce; ?>';

    // Panel navigation
    window.showPanel = function(panel) {
        document.querySelectorAll('.sp-panel').forEach(p => p.classList.remove('active'));
        document.querySelectorAll('.sp-nav-item').forEach(n => n.classList.remove('active'));
        document.getElementById('panel-' + panel)?.classList.add('active');
        document.querySelector('[data-panel="' + panel + '"]')?.classList.add('active');
    };

    document.querySelectorAll('.sp-nav-item[data-panel]').forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            showPanel(this.dataset.panel);
        });
    });

    // Toast notification
    window.showToast = function(msg, type = 'success') {
        const toast = document.getElementById('toast');
        toast.textContent = msg;
        toast.className = 'sp-toast ' + type;
        toast.style.display = 'block';
        setTimeout(() => { toast.style.display = 'none'; }, 3000);
    };

    // Update payment amount when student is selected
    window.updatePaymentAmount = function() {
        const select = document.getElementById('paymentStudent');
        const amountInput = document.getElementById('paymentAmount');
        const selected = select.options[select.selectedIndex];
        if (selected && selected.dataset.amount) {
            amountInput.value = selected.dataset.amount;
        }
    };

    // Go to payment proof page with selected plan
    window.goToPaymentPage = function(studentId, studentName, months, amount) {
        // Store the selected plan data
        document.getElementById('proofStudentId').value = studentId;
        document.getElementById('proofStudentName').textContent = studentName;
        document.getElementById('proofPlanMonths').textContent = months + ' <?php _e('Month(s)', 'al-huffaz-portal'); ?>';
        document.getElementById('proofAmount').value = amount;
        document.getElementById('proofAmountDisplay').textContent = formatCurrency(amount);
        document.getElementById('proofPlanType').value = months === 1 ? 'monthly' : (months === 3 ? 'quarterly' : (months === 6 ? 'semi-annual' : 'yearly'));

        // Switch to payment proof panel
        showPanel('payment-proof');
    };

    // Sponsor modal
    let currentSponsorData = {};

    window.openSponsorModal = function(studentId, studentName, monthly, quarterly, yearly) {
        currentSponsorData = { studentId, studentName, monthly, quarterly, yearly };
        document.getElementById('sponsorStudentId').value = studentId;
        document.getElementById('sponsorStudentName').textContent = studentName;
        document.getElementById('monthlyPrice').textContent = formatCurrency(monthly);
        document.getElementById('quarterlyPrice').textContent = formatCurrency(quarterly);
        document.getElementById('yearlyPrice').textContent = formatCurrency(yearly);
        document.getElementById('sponsorModal').style.display = 'flex';
    };

    window.closeSponsorModal = function() {
        document.getElementById('sponsorModal').style.display = 'none';
    };

    window.submitSponsorship = function() {
        const plan = document.querySelector('input[name="sponsor_plan"]:checked').value;
        const amount = currentSponsorData[plan] || currentSponsorData.monthly;

        const formData = new FormData();
        formData.append('action', 'alhuffaz_submit_sponsorship');
        formData.append('nonce', nonce);
        formData.append('student_id', currentSponsorData.studentId);
        formData.append('sponsorship_type', plan);
        formData.append('amount', amount);

        fetch(ajaxUrl, { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showToast('<?php _e('Sponsorship request submitted! Proceeding to payment...', 'al-huffaz-portal'); ?>', 'success');
                closeSponsorModal();
                // Open payment modal
                setTimeout(() => {
                    openPaymentModal(currentSponsorData.studentId, currentSponsorData.studentName, amount);
                }, 500);
            } else {
                showToast(data.data?.message || '<?php _e('Error submitting sponsorship', 'al-huffaz-portal'); ?>', 'error');
            }
        });
    };

    // Payment modal
    window.openPaymentModal = function(studentId, studentName, amount) {
        document.getElementById('quickPayStudentId').value = studentId;
        document.getElementById('quickPayStudentName').textContent = studentName;
        document.getElementById('quickPayAmount').value = amount;
        document.getElementById('quickPayModal').style.display = 'flex';
    };

    window.closePaymentModal = function() {
        document.getElementById('quickPayModal').style.display = 'none';
    };

    window.submitQuickPayment = function() {
        const form = document.getElementById('quickPayForm');
        const formData = new FormData(form);
        formData.append('action', 'alhuffaz_submit_payment');
        formData.append('nonce', nonce);

        fetch(ajaxUrl, { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showToast('<?php _e('Payment submitted for verification!', 'al-huffaz-portal'); ?>', 'success');
                closePaymentModal();
                setTimeout(() => location.reload(), 1500);
            } else {
                showToast(data.data?.message || '<?php _e('Error submitting payment', 'al-huffaz-portal'); ?>', 'error');
            }
        });
    };

    // Payment proof form submission
    document.getElementById('paymentProofForm')?.addEventListener('submit', function(e) {
        e.preventDefault();

        const submitBtn = document.getElementById('submitProofBtn');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <?php _e('Submitting...', 'al-huffaz-portal'); ?>';
        submitBtn.disabled = true;

        const formData = new FormData(this);

        fetch(ajaxUrl, { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;

            if (data.success) {
                showToast('<?php _e('Payment proof submitted successfully! School will verify and notify you.', 'al-huffaz-portal'); ?>', 'success');
                this.reset();
                // Redirect to dashboard after 2 seconds
                setTimeout(() => {
                    showPanel('dashboard');
                    showToast('<?php _e('Your sponsorship request is pending verification.', 'al-huffaz-portal'); ?>', 'info');
                }, 2000);
            } else {
                showToast(data.data?.message || '<?php _e('Error submitting payment proof', 'al-huffaz-portal'); ?>', 'error');
            }
        })
        .catch(error => {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
            showToast('<?php _e('Network error. Please try again.', 'al-huffaz-portal'); ?>', 'error');
        });
    });

    // Main payment form
    document.getElementById('paymentForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'alhuffaz_submit_payment');
        formData.append('nonce', nonce);

        fetch(ajaxUrl, { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showToast('<?php _e('Payment submitted for verification!', 'al-huffaz-portal'); ?>', 'success');
                this.reset();
                setTimeout(() => showPanel('history'), 1500);
            } else {
                showToast(data.data?.message || '<?php _e('Error submitting payment', 'al-huffaz-portal'); ?>', 'error');
            }
        });
    });

    // Plan selection styling
    document.querySelectorAll('.plan-option input[type="radio"]').forEach(radio => {
        radio.addEventListener('change', function() {
            document.querySelectorAll('.plan-option').forEach(opt => {
                opt.style.borderColor = 'transparent';
            });
            if (this.checked) {
                this.closest('.plan-option').style.borderColor = 'var(--sp-primary)';
            }
        });
    });

    // Format currency helper
    function formatCurrency(amount) {
        return 'Rs. ' + parseFloat(amount).toLocaleString();
    }

    // Initialize first plan selection
    document.querySelector('.plan-option')?.style.setProperty('border-color', 'var(--sp-primary)');
});
</script>
