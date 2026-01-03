<?php
/*
Plugin Name: Al-Huffaz Sponsor Admin Panel (Full Featured + Notifications)
Description: Complete sponsor management with notification badges
Version: 6.2 NOTIFICATIONS
Author: RoohUl Hasnain
*/

defined('ABSPATH') || exit;

// Prevent caching
add_action('template_redirect', 'sponsor_admin_no_cache');
function sponsor_admin_no_cache() {
    if (is_page() && has_shortcode(get_post()->post_content, 'sponsor-admin-panel')) {
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
    }
}

// ✅ NEW: Get notification counts for badges
function get_sponsor_notification_counts() {
    // Pending Registrations Count
    $pending_users = get_users(array('role' => 'sponsor', 'number' => 100));
    $pending_count = count(array_filter($pending_users, function($user) {
        $status = get_user_meta($user->ID, 'account_status', true);
        return $status === 'awaiting_admin_review' || $status === '' || $status === 'pending';
    }));
    
    // Pending Payments Count
    $all_sponsorships = get_posts(array(
        'post_type' => 'sponsorship',
        'post_status' => 'any',
        'posts_per_page' => -1,
        'cache_results' => false
    ));
    
    $payment_count = count(array_filter($all_sponsorships, function($payment) {
        $linked = get_post_meta($payment->ID, 'linked', true);
        $post_status = get_post_status($payment->ID);
        return $linked !== 'yes' && $post_status !== 'publish';
    }));
    
    // Active Sponsorships Count
    $active_sponsorships = get_posts(array(
        'post_type' => 'sponsorship',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'cache_results' => false
    ));
    
    $active_count = count(array_filter($active_sponsorships, function($sp) {
        return get_post_meta($sp->ID, 'linked', true) === 'yes';
    }));
    
    // Deleted Sponsorships Count
    $deleted_count = count(get_posts(array(
        'post_type' => 'sponsorship',
        'post_status' => 'trash',
        'posts_per_page' => -1
    )));
    
    // All Sponsors Count
    $all_sponsors_count = count(get_users(array('role' => 'sponsor')));
    
    return array(
        'pending' => $pending_count,
        'payments' => $payment_count,
        'all' => $all_sponsors_count,
        'sponsorships' => $active_count,
        'deleted' => $deleted_count
    );
}

// Auto-cleanup orphaned sponsorship markers on plugin load
add_action('init', 'sponsor_cleanup_orphaned_markers');
function sponsor_cleanup_orphaned_markers() {
    // Only run once per day to avoid performance issues
    $last_cleanup = get_option('sponsor_last_cleanup', 0);
    if (time() - $last_cleanup < 86400) { // 24 hours
        return;
    }
    
    // Find all students marked as sponsored
    $sponsored_students = get_posts(array(
        'post_type' => 'student',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                'key' => 'already_sponsored',
                'value' => 'yes',
                'compare' => '='
            )
        ),
        'fields' => 'ids'
    ));
    
    $cleaned = 0;
    foreach ($sponsored_students as $student_id) {
        // Check if there's an active sponsorship for this student
        $active_sponsorships = get_posts(array(
            'post_type' => 'sponsorship',
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'meta_query' => array(
                array(
                    'key' => 'student_id',
                    'value' => $student_id,
                    'compare' => '='
                ),
                array(
                    'key' => 'linked',
                    'value' => 'yes',
                    'compare' => '='
                )
            )
        ));
        
        // If no active sponsorship found, remove the marker
        if (empty($active_sponsorships)) {
            delete_post_meta($student_id, 'already_sponsored');
            delete_post_meta($student_id, 'sponsored_date');
            $cleaned++;
        }
    }
    
    // Update last cleanup timestamp
    update_option('sponsor_last_cleanup', time());
    
    // Log cleanup if any orphans were found
    if ($cleaned > 0) {
        error_log("Al-Huffaz Sponsor: Cleaned up {$cleaned} orphaned sponsorship markers");
    }
}

// Register AJAX handlers
add_action('wp_ajax_sponsor_approve_registration', 'ajax_sponsor_approve_registration');
add_action('wp_ajax_sponsor_reject_registration', 'ajax_sponsor_reject_registration');
add_action('wp_ajax_sponsor_verify_payment', 'ajax_sponsor_verify_payment');
add_action('wp_ajax_sponsor_reject_payment', 'ajax_sponsor_reject_payment');
add_action('wp_ajax_sponsor_delete_user', 'ajax_sponsor_delete_user');
add_action('wp_ajax_sponsor_reset_password', 'ajax_sponsor_reset_password');
add_action('wp_ajax_sponsor_update_user', 'ajax_sponsor_update_user');
add_action('wp_ajax_sponsor_restore_sponsorship', 'ajax_sponsor_restore_sponsorship');
add_action('wp_ajax_sponsor_permanent_delete_sponsorship', 'ajax_sponsor_permanent_delete_sponsorship');
add_action('wp_ajax_sponsor_cleanup_now', 'ajax_sponsor_cleanup_now');

// Manual cleanup AJAX handler
function ajax_sponsor_cleanup_now() {
    check_ajax_referer('sponsor_admin_ajax', 'nonce');
    
    if (!current_user_can('administrator') && !current_user_can('school_admin')) {
        wp_send_json_error('Access denied');
    }
    
    // Force cleanup
    delete_option('sponsor_last_cleanup');
    sponsor_cleanup_orphaned_markers();
    
    wp_send_json_success('Cleanup completed! Orphaned markers removed.');
}

// AJAX: Approve Registration
function ajax_sponsor_approve_registration() {
    check_ajax_referer('sponsor_admin_ajax', 'nonce');
    
    if (!current_user_can('administrator') && !current_user_can('school_admin')) {
        wp_send_json_error('Access denied');
    }
    
    $user_id = intval($_POST['user_id']);
    update_user_meta($user_id, 'account_status', 'approved');
    
    $user = get_userdata($user_id);
    if ($user) {
        wp_mail(
            $user->user_email,
            'Al-Huffaz Account Approved!',
            "Dear {$user->display_name},\n\nYour sponsor account has been approved! Login at: " . home_url('/login/')
        );
    }
    
    wp_send_json_success('Registration approved and email sent!');
}

// AJAX: Reject Registration
function ajax_sponsor_reject_registration() {
    check_ajax_referer('sponsor_admin_ajax', 'nonce');
    
    if (!current_user_can('administrator') && !current_user_can('school_admin')) {
        wp_send_json_error('Access denied');
    }
    
    $user_id = intval($_POST['user_id']);
    update_user_meta($user_id, 'account_status', 'rejected');
    
    $user = get_userdata($user_id);
    if ($user) {
        wp_mail($user->user_email, 'Registration Update', 'Thank you for your interest.');
    }
    
    wp_send_json_success('Registration rejected.');
}

// AJAX: Soft Delete User + Keep History
function ajax_sponsor_delete_user() {
    check_ajax_referer('sponsor_admin_ajax', 'nonce');
    
    if (!current_user_can('administrator') && !current_user_can('school_admin')) {
        wp_send_json_error('Access denied');
    }
    
    $user_id = intval($_POST['user_id']);
    
    $user = get_userdata($user_id);
    if (!$user || in_array('administrator', $user->roles)) {
        wp_send_json_error('Cannot delete administrator accounts');
    }
    
    $user_sponsorships = get_posts(array(
        'post_type' => 'sponsorship',
        'post_status' => 'any',
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                'key' => 'sponsor_user_id',
                'value' => $user_id,
                'compare' => '='
            )
        )
    ));
    
    $affected_students = array();
    foreach ($user_sponsorships as $sponsorship) {
        $student_id = get_post_meta($sponsorship->ID, 'student_id', true);
        
        if ($student_id && !in_array($student_id, $affected_students)) {
            delete_post_meta($student_id, 'already_sponsored');
            delete_post_meta($student_id, 'sponsored_date');
            $affected_students[] = $student_id;
        }
        
        wp_update_post(array(
            'ID' => $sponsorship->ID,
            'post_status' => 'trash'
        ));
        
        update_post_meta($sponsorship->ID, 'linked', 'no');
        update_post_meta($sponsorship->ID, 'deleted_date', current_time('mysql'));
        update_post_meta($sponsorship->ID, 'deleted_by', get_current_user_id());
        update_post_meta($sponsorship->ID, 'deletion_reason', 'Sponsor account deleted by ' . wp_get_current_user()->display_name);
    }
    
    require_once(ABSPATH . 'wp-admin/includes/user.php');
    $deleted = wp_delete_user($user_id);
    
    if ($deleted) {
        $student_count = count($affected_students);
        $sponsorship_count = count($user_sponsorships);
        $message = 'User deleted successfully. ';
        if ($sponsorship_count > 0) {
            $message .= $sponsorship_count . ' sponsorship(s) moved to history. ';
        }
        if ($student_count > 0) {
            $message .= $student_count . ' student(s) made visible again.';
        }
        wp_send_json_success($message);
    } else {
        wp_send_json_error('Failed to delete user');
    }
}

// AJAX: Reset Password
function ajax_sponsor_reset_password() {
    check_ajax_referer('sponsor_admin_ajax', 'nonce');
    
    if (!current_user_can('administrator') && !current_user_can('school_admin')) {
        wp_send_json_error('Access denied');
    }
    
    $user_id = intval($_POST['user_id']);
    $user = get_userdata($user_id);
    
    if (!$user) {
        wp_send_json_error('User not found');
    }
    
    $reset_key = get_password_reset_key($user);
    
    if (is_wp_error($reset_key)) {
        wp_send_json_error('Failed to generate reset key');
    }
    
    $reset_url = network_site_url("wp-login.php?action=rp&key=$reset_key&login=" . rawurlencode($user->user_login), 'login');
    
    $message = "Password reset requested for: {$user->display_name}\n\n";
    $message .= "Click this link to reset your password:\n";
    $message .= $reset_url . "\n\n";
    $message .= "If you didn't request this, please ignore this email.";
    
    $sent = wp_mail($user->user_email, 'Password Reset - Al-Huffaz', $message);
    
    if ($sent) {
        wp_send_json_success('Password reset email sent to ' . $user->user_email);
    } else {
        wp_send_json_error('Failed to send email');
    }
}

// AJAX: Update User
function ajax_sponsor_update_user() {
    check_ajax_referer('sponsor_admin_ajax', 'nonce');
    
    if (!current_user_can('administrator') && !current_user_can('school_admin')) {
        wp_send_json_error('Access denied');
    }
    
    $user_id = intval($_POST['user_id']);
    $display_name = sanitize_text_field($_POST['display_name']);
    $email = sanitize_email($_POST['email']);
    $phone = sanitize_text_field($_POST['phone']);
    $country = sanitize_text_field($_POST['country']);
    $status = sanitize_text_field($_POST['status']);
    
    if (!is_email($email)) {
        wp_send_json_error('Invalid email address');
    }
    
    $email_exists = email_exists($email);
    if ($email_exists && $email_exists != $user_id) {
        wp_send_json_error('Email already in use by another user');
    }
    
    $updated = wp_update_user(array(
        'ID' => $user_id,
        'display_name' => $display_name,
        'user_email' => $email
    ));
    
    if (is_wp_error($updated)) {
        wp_send_json_error($updated->get_error_message());
    }
    
    update_user_meta($user_id, 'sponsor_phone', $phone);
    update_user_meta($user_id, 'sponsor_country', $country);
    update_user_meta($user_id, 'account_status', $status);
    
    wp_send_json_success('User updated successfully!');
}

// AJAX: Verify Payment with proper redirect
function ajax_sponsor_verify_payment() {
    check_ajax_referer('sponsor_admin_ajax', 'nonce');
    
    if (!current_user_can('administrator') && !current_user_can('school_admin')) {
        wp_send_json_error('Access denied');
    }
    
    $sponsorship_id = intval($_POST['sponsorship_id']);
    
    // Clear all caches
    wp_cache_flush();
    
    wp_update_post(array(
        'ID' => $sponsorship_id,
        'post_status' => 'publish'
    ));
    
    update_post_meta($sponsorship_id, 'verification_status', 'approved');
    update_post_meta($sponsorship_id, 'linked', 'yes');
    update_post_meta($sponsorship_id, 'approved_date', current_time('mysql'));
    
    $student_id = get_post_meta($sponsorship_id, 'student_id', true);
    if ($student_id) {
        update_post_meta($student_id, 'already_sponsored', 'yes');
        update_post_meta($student_id, 'sponsored_date', current_time('mysql'));
    }
    
    $sponsor_id = get_post_meta($sponsorship_id, 'sponsor_user_id', true);
    $student = get_post($student_id);
    $sponsor = get_userdata($sponsor_id);
    
    if ($sponsor && $student) {
        wp_mail(
            $sponsor->user_email,
            'Sponsorship Confirmed!',
            "Your sponsorship for {$student->post_title} has been confirmed! Login: " . home_url('/sponsor-dashboard/')
        );
    }
    
    $current_url = isset($_POST['current_url']) ? esc_url_raw($_POST['current_url']) : '';
    
    if ($current_url) {
        // Parse the current URL and update the sponsor_tab parameter
        $redirect_url = add_query_arg(array(
            'sponsor_tab' => 'sponsorships',
            '_' => time()
        ), $current_url);
    } else {
        // Fallback to current page
        $redirect_url = add_query_arg(array(
            'sponsor_tab' => 'sponsorships',
            '_' => time()
        ));
    }
    
    wp_send_json_success(array(
        'message' => '✅ Payment verified, sponsor linked, and student marked as SPONSORED!',
        'redirect' => $redirect_url
    ));
}

// AJAX: Reject Payment
function ajax_sponsor_reject_payment() {
    check_ajax_referer('sponsor_admin_ajax', 'nonce');
    
    if (!current_user_can('administrator') && !current_user_can('school_admin')) {
        wp_send_json_error('Access denied');
    }
    
    $sponsorship_id = intval($_POST['sponsorship_id']);
    update_post_meta($sponsorship_id, 'verification_status', 'rejected');
    
    wp_send_json_success('Payment rejected.');
}

// AJAX: Restore Deleted Sponsorship
function ajax_sponsor_restore_sponsorship() {
    check_ajax_referer('sponsor_admin_ajax', 'nonce');
    
    if (!current_user_can('administrator') && !current_user_can('school_admin')) {
        wp_send_json_error('Access denied');
    }
    
    $sponsorship_id = intval($_POST['sponsorship_id']);
    
    wp_update_post(array(
        'ID' => $sponsorship_id,
        'post_status' => 'publish'
    ));
    
    update_post_meta($sponsorship_id, 'linked', 'yes');
    delete_post_meta($sponsorship_id, 'deleted_date');
    delete_post_meta($sponsorship_id, 'deleted_by');
    delete_post_meta($sponsorship_id, 'deletion_reason');
    
    $student_id = get_post_meta($sponsorship_id, 'student_id', true);
    if ($student_id) {
        update_post_meta($student_id, 'already_sponsored', 'yes');
        update_post_meta($student_id, 'sponsored_date', current_time('mysql'));
    }
    
    wp_send_json_success('Sponsorship restored successfully! Student is now hidden from public browse again.');
}

// AJAX: Permanently Delete Sponsorship
function ajax_sponsor_permanent_delete_sponsorship() {
    check_ajax_referer('sponsor_admin_ajax', 'nonce');
    
    if (!current_user_can('administrator') && !current_user_can('school_admin')) {
        wp_send_json_error('Access denied');
    }
    
    $sponsorship_id = intval($_POST['sponsorship_id']);
    
    $deleted = wp_delete_post($sponsorship_id, true);
    
    if ($deleted) {
        wp_send_json_success('Sponsorship permanently deleted from database.');
    } else {
        wp_send_json_error('Failed to delete sponsorship.');
    }
}

// Register shortcode
add_shortcode('sponsor-admin-panel', 'alhuffaz_sponsor_admin_panel_display');

function alhuffaz_sponsor_admin_panel_display() {
    
    if (!current_user_can('administrator') && !current_user_can('school_admin')) {
        return '<div style="padding: 40px; text-align: center;">
                    <h3>Access Denied</h3>
                    <p>This panel is only for school staff.</p>
                </div>';
    }
    
    $sub_tab = isset($_GET['sponsor_tab']) ? sanitize_text_field($_GET['sponsor_tab']) : 'pending';
    
    // Get notification counts
    $counts = get_sponsor_notification_counts();
    
    ob_start();
    ?>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
    .sponsor-admin-panel { font-family: 'Poppins', sans-serif; }
    
    /* ✅ Notification Badge Styles */
    .notification-badge {
        position: absolute;
        top: -8px;
        right: -8px;
        background: linear-gradient(135deg, #ef4444, #dc2626);
        color: white;
        border-radius: 50%;
        min-width: 22px;
        height: 22px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 11px;
        font-weight: 700;
        padding: 0 6px;
        box-shadow: 0 2px 8px rgba(239, 68, 68, 0.4);
        animation: pulse 2s infinite;
        border: 2px solid white;
    }
    
    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.1); }
    }
    
    .sponsor-sub-tab {
        position: relative;
        flex: 1;
        min-width: 150px;
        padding: 12px 20px;
        border: none;
        border-radius: 8px;
        background: transparent;
        color: #64748b;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.3s;
        font-family: 'Poppins', sans-serif;
    }
    
    .sponsor-sub-tab:hover { background: #f1f5f9; }
    .sponsor-sub-tab.active {
        background: linear-gradient(135deg, #ec407a, #c2185b);
        color: white;
    }
    
    .sponsor-sub-tab.active .notification-badge {
        background: white;
        color: #ec407a;
        border-color: #ec407a;
    }
    
    #ajax-message {
        padding: 15px 20px;
        border-radius: 8px;
        margin-bottom: 20px;
        display: none;
        align-items: center;
        gap: 10px;
        font-weight: 600;
        animation: slideDown 0.3s;
    }
    
    @keyframes slideDown {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    #ajax-message.success {
        background: #d1fae5;
        color: #065f46;
        border-left: 4px solid #10b981;
        display: flex;
    }
    
    #ajax-message.error {
        background: #fee2e2;
        color: #991b1b;
        border-left: 4px solid #ef4444;
        display: flex;
    }
    
    .cleanup-notice {
        background: #dbeafe;
        border-left: 4px solid #3b82f6;
        padding: 15px 20px;
        border-radius: 8px;
        margin-bottom: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .cleanup-notice p {
        margin: 0;
        color: #1e40af;
        font-size: 14px;
    }
    
    .btn-cleanup {
        padding: 8px 16px;
        background: #3b82f6;
        color: white;
        border: none;
        border-radius: 6px;
        font-weight: 600;
        cursor: pointer;
        font-size: 13px;
        transition: all 0.3s;
    }
    
    .btn-cleanup:hover {
        background: #2563eb;
        transform: translateY(-2px);
    }
    
    .sponsor-sub-nav {
        display: flex;
        gap: 10px;
        margin-bottom: 24px;
        background: white;
        padding: 12px;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        flex-wrap: wrap;
    }
    
    .sponsor-content {
        background: white;
        border-radius: 12px;
        padding: 30px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    }
    
    .sponsor-grid { display: grid; gap: 20px; }
    
    .sponsor-card {
        background: #f8fafc;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        padding: 24px;
        display: grid;
        grid-template-columns: 1fr auto;
        gap: 20px;
        align-items: center;
        transition: all 0.3s;
    }
    
    .sponsor-card:hover {
        border-color: #ec407a;
        box-shadow: 0 4px 12px rgba(236, 64, 122, 0.1);
    }
    
    .sponsor-info h3 {
        margin: 0 0 8px 0;
        font-size: 18px;
        font-weight: 700;
        color: #001a33;
    }
    
    .sponsor-meta {
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
        color: #64748b;
        font-size: 13px;
    }
    
    .sponsor-meta span {
        display: flex;
        align-items: center;
        gap: 6px;
    }
    
    .sponsor-actions { 
        display: flex; 
        gap: 10px;
        flex-wrap: wrap;
    }
    
    .btn-approve, .btn-reject, .btn-verify, .btn-view, .btn-edit, .btn-delete, .btn-reset, .btn-restore, .btn-permanent-delete {
        padding: 10px 20px;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        font-family: 'Poppins', sans-serif;
        font-size: 13px;
        color: white;
    }
    
    .btn-approve { background: #10b981; }
    .btn-approve:hover { background: #059669; transform: translateY(-2px); }
    .btn-reject { background: #ef4444; }
    .btn-reject:hover { background: #dc2626; transform: translateY(-2px); }
    .btn-verify { background: #0ea5e9; }
    .btn-verify:hover { background: #0284c7; transform: translateY(-2px); }
    .btn-view { background: #6366f1; padding: 8px 16px; font-size: 12px; text-decoration: none; display: inline-block; }
    .btn-view:hover { background: #4f46e5; }
    .btn-edit { background: #8b5cf6; }
    .btn-edit:hover { background: #7c3aed; transform: translateY(-2px); }
    .btn-delete { background: #f97316; }
    .btn-delete:hover { background: #ea580c; transform: translateY(-2px); }
    .btn-reset { background: #14b8a6; }
    .btn-reset:hover { background: #0d9488; transform: translateY(-2px); }
    .btn-restore { background: #10b981; }
    .btn-restore:hover { background: #059669; transform: translateY(-2px); }
    .btn-permanent-delete { background: #dc2626; }
    .btn-permanent-delete:hover { background: #991b1b; transform: translateY(-2px); }
    
    .btn-approve:disabled, .btn-reject:disabled, .btn-verify:disabled, .btn-edit:disabled, .btn-delete:disabled, .btn-reset:disabled, .btn-restore:disabled, .btn-permanent-delete:disabled {
        background: #cbd5e1;
        cursor: not-allowed;
        transform: none;
    }
    
    .status-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
    }
    
    .status-pending { background: #fef3c7; color: #92400e; }
    .status-approved { background: #d1fae5; color: #065f46; }
    .status-rejected { background: #fee2e2; color: #991b1b; }
    .status-deleted { background: #f3f4f6; color: #6b7280; }
    
    .empty-state {
        text-align: center;
        padding: 60px 20px;
    }
    
    .empty-state i {
        font-size: 64px;
        color: #cbd5e1;
        margin-bottom: 20px;
    }
    
    .empty-state h3 {
        font-size: 22px;
        font-weight: 700;
        color: #334155;
        margin: 0 0 10px 0;
    }
    
    .payment-details {
        background: white;
        padding: 20px;
        border-radius: 8px;
        margin-top: 15px;
        border: 1px solid #e2e8f0;
    }
    
    .payment-row {
        display: flex;
        justify-content: space-between;
        padding: 8px 0;
        border-bottom: 1px solid #f1f5f9;
    }
    
    .payment-row:last-child { border-bottom: none; }
    
    .payment-screenshot {
        margin-top: 15px;
        text-align: center;
    }
    
    .payment-screenshot img {
        max-width: 100%;
        max-height: 400px;
        border-radius: 8px;
        border: 2px solid #e2e8f0;
        cursor: pointer;
        transition: transform 0.3s;
    }
    
    .payment-screenshot img:hover { transform: scale(1.02); }
    
    .sponsor-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }
    
    .sponsor-table th {
        text-align: left;
        padding: 12px;
        background: #f8fafc;
        font-weight: 600;
        font-size: 13px;
        color: #334155;
        border-bottom: 2px solid #e2e8f0;
    }
    
    .sponsor-table td {
        padding: 15px 12px;
        border-bottom: 1px solid #f1f5f9;
        font-size: 14px;
    }
    
    .sponsor-table tr:hover { background: #f8fafc; }
    
    .loading-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 9999;
        align-items: center;
        justify-content: center;
    }
    
    .loading-overlay.active {
        display: flex;
    }
    
    .loading-spinner {
        background: white;
        padding: 30px 50px;
        border-radius: 12px;
        text-align: center;
    }
    
    .loading-spinner i {
        font-size: 48px;
        color: #0ea5e9;
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    
    .modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.6);
        z-index: 10000;
        align-items: center;
        justify-content: center;
    }
    
    .modal-overlay.active {
        display: flex;
    }
    
    .modal-content {
        background: white;
        border-radius: 12px;
        padding: 30px;
        max-width: 500px;
        width: 90%;
        max-height: 90vh;
        overflow-y: auto;
    }
    
    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    
    .modal-header h2 {
        margin: 0;
        font-size: 22px;
        color: #001a33;
    }
    
    .modal-close {
        background: none;
        border: none;
        font-size: 24px;
        cursor: pointer;
        color: #64748b;
        padding: 0;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        transition: all 0.2s;
    }
    
    .modal-close:hover {
        background: #f1f5f9;
        color: #334155;
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-group label {
        display: block;
        font-weight: 600;
        margin-bottom: 8px;
        color: #334155;
        font-size: 14px;
    }
    
    .form-group input,
    .form-group select {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        font-size: 14px;
        font-family: 'Poppins', sans-serif;
        transition: border 0.2s;
    }
    
    .form-group input:focus,
    .form-group select:focus {
        outline: none;
        border-color: #8b5cf6;
        box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
    }
    
    .modal-actions {
        display: flex;
        gap: 10px;
        margin-top: 25px;
    }
    
    .btn-save {
        flex: 1;
        padding: 12px;
        background: #8b5cf6;
        color: white;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        font-family: 'Poppins', sans-serif;
        transition: all 0.3s;
    }
    
    .btn-save:hover {
        background: #7c3aed;
        transform: translateY(-2px);
    }
    
    .btn-cancel {
        flex: 1;
        padding: 12px;
        background: #64748b;
        color: white;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        font-family: 'Poppins', sans-serif;
        transition: all 0.3s;
    }
    
    .btn-cancel:hover {
        background: #475569;
    }
    
    .deleted-card {
        background: #fafafa;
        border-color: #d1d5db;
        opacity: 0.9;
    }
    
    .deletion-info {
        background: #fff7ed;
        border-left: 4px solid #f59e0b;
        padding: 12px;
        border-radius: 6px;
        margin-top: 12px;
        font-size: 12px;
    }
    
    .deletion-info strong {
        color: #92400e;
        display: block;
        margin-bottom: 4px;
    }
    
    @media (max-width: 768px) {
        .sponsor-sub-nav { flex-direction: column; }
        .sponsor-card { grid-template-columns: 1fr; }
        .sponsor-actions { 
            flex-direction: column;
            width: 100%;
        }
        .sponsor-actions button {
            width: 100%;
        }
        
        .notification-badge {
            top: -5px;
            right: -5px;
            min-width: 20px;
            height: 20px;
            font-size: 10px;
        }
    }
    </style>
    
    <div class="sponsor-admin-panel">
        
        <!-- Loading Overlay -->
        <div class="loading-overlay" id="loadingOverlay">
            <div class="loading-spinner">
                <i class="fas fa-spinner fa-spin"></i>
                <p style="margin: 15px 0 0 0; font-weight: 600; color: #334155;">Processing...</p>
            </div>
        </div>
        
        <!-- Edit User Modal -->
        <div class="modal-overlay" id="editModal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2><i class="fas fa-user-edit"></i> Edit Sponsor</h2>
                    <button class="modal-close" onclick="closeEditModal()">×</button>
                </div>
                <form id="editUserForm">
                    <input type="hidden" id="edit_user_id" name="user_id">
                    
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Full Name</label>
                        <input type="text" id="edit_display_name" name="display_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-envelope"></i> Email</label>
                        <input type="email" id="edit_email" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-phone"></i> Phone</label>
                        <input type="text" id="edit_phone" name="phone">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-globe"></i> Country</label>
                        <input type="text" id="edit_country" name="country">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-toggle-on"></i> Account Status</label>
                        <select id="edit_status" name="status">
                            <option value="approved">Approved</option>
                            <option value="pending">Pending</option>
                            <option value="rejected">Rejected</option>
                            <option value="awaiting_admin_review">Awaiting Review</option>
                        </select>
                    </div>
                    
                    <div class="modal-actions">
                        <button type="submit" class="btn-save"><i class="fas fa-save"></i> Save Changes</button>
                        <button type="button" class="btn-cancel" onclick="closeEditModal()"><i class="fas fa-times"></i> Cancel</button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- AJAX Message -->
        <div id="ajax-message"></div>
        
        <!-- Cleanup Notice -->
        <?php if ($sub_tab === 'sponsorships'): ?>
        <div class="cleanup-notice">
            <p><i class="fas fa-sync-alt"></i> <strong>Sync Issue?</strong> If you see old deleted sponsorships still showing as active, click here to fix orphaned data:</p>
            <button class="btn-cleanup" onclick="runCleanup(this)">
                <i class="fas fa-broom"></i> Run Cleanup
            </button>
        </div>
        <?php endif; ?>
        
        <!-- Sub Navigation with Notification Badges -->
        <div class="sponsor-sub-nav">
            <button class="sponsor-sub-tab <?php echo $sub_tab === 'pending' ? 'active' : ''; ?>" 
                    onclick="sponsorSubNavigate('pending')">
                <i class="fas fa-clock"></i> Pending
                <?php if ($counts['pending'] > 0): ?>
                    <span class="notification-badge"><?php echo $counts['pending']; ?></span>
                <?php endif; ?>
            </button>
            
            <button class="sponsor-sub-tab <?php echo $sub_tab === 'payments' ? 'active' : ''; ?>" 
                    onclick="sponsorSubNavigate('payments')">
                <i class="fas fa-receipt"></i> Payments
                <?php if ($counts['payments'] > 0): ?>
                    <span class="notification-badge"><?php echo $counts['payments']; ?></span>
                <?php endif; ?>
            </button>
            
            <button class="sponsor-sub-tab <?php echo $sub_tab === 'all' ? 'active' : ''; ?>" 
                    onclick="sponsorSubNavigate('all')">
                <i class="fas fa-users"></i> All Sponsors
                <?php if ($counts['all'] > 0): ?>
                    <span class="notification-badge" style="background: #10b981; border-color: #10b981;"><?php echo $counts['all']; ?></span>
                <?php endif; ?>
            </button>
            
            <button class="sponsor-sub-tab <?php echo $sub_tab === 'sponsorships' ? 'active' : ''; ?>" 
                    onclick="sponsorSubNavigate('sponsorships')">
                <i class="fas fa-link"></i> Active
                <?php if ($counts['sponsorships'] > 0): ?>
                    <span class="notification-badge" style="background: #3b82f6; border-color: #3b82f6;"><?php echo $counts['sponsorships']; ?></span>
                <?php endif; ?>
            </button>
            
            <button class="sponsor-sub-tab <?php echo $sub_tab === 'deleted' ? 'active' : ''; ?>" 
                    onclick="sponsorSubNavigate('deleted')">
                <i class="fas fa-history"></i> History
                <?php if ($counts['deleted'] > 0): ?>
                    <span class="notification-badge" style="background: #f59e0b; border-color: #f59e0b;"><?php echo $counts['deleted']; ?></span>
                <?php endif; ?>
            </button>
        </div>
        
        <!-- Content Area -->
        <div class="sponsor-content">
            
            <?php if ($sub_tab === 'pending'): ?>
                <?php echo render_pending_registrations(); ?>
            <?php endif; ?>
            
            <?php if ($sub_tab === 'payments'): ?>
                <?php echo render_payment_verifications(); ?>
            <?php endif; ?>
            
            <?php if ($sub_tab === 'all'): ?>
                <?php echo render_all_sponsors(); ?>
            <?php endif; ?>
            
            <?php if ($sub_tab === 'sponsorships'): ?>
                <?php echo render_sponsorships(); ?>
            <?php endif; ?>
            
            <?php if ($sub_tab === 'deleted'): ?>
                <?php echo render_deleted_sponsorships(); ?>
            <?php endif; ?>
            
        </div>
    </div>
    
    <script>
    var sponsorAjaxNonce = '<?php echo wp_create_nonce('sponsor_admin_ajax'); ?>';
    var currentPageUrl = window.location.href;
    
    function sponsorSubNavigate(tab) {
        document.getElementById('loadingOverlay').classList.add('active');
        const url = new URL(window.location.href);
        url.searchParams.set('sponsor_tab', tab);
        url.searchParams.set('_', Date.now());
        window.location.href = url.toString();
    }
    
    function showMessage(message, type) {
        const msgBox = document.getElementById('ajax-message');
        msgBox.className = type;
        msgBox.innerHTML = '<i class="fas fa-' + (type === 'success' ? 'check-circle' : 'exclamation-circle') + '"></i> ' + message;
        msgBox.style.display = 'flex';
        setTimeout(() => {
            msgBox.style.display = 'none';
        }, 4000);
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
    
    function runCleanup(button) {
        if (!confirm('Run cleanup to fix orphaned sponsorship markers?\n\nThis will:\n• Check all students marked as sponsored\n• Remove marker if no active sponsorship exists\n• Recommended if you see sync issues')) return;
        
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cleaning...';
        
        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'sponsor_cleanup_now',
                nonce: sponsorAjaxNonce
            })
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                showMessage(result.data, 'success');
                setTimeout(() => location.reload(true), 1500);
            } else {
                showMessage(result.data, 'error');
                button.disabled = false;
                button.innerHTML = '<i class="fas fa-broom"></i> Run Cleanup';
            }
        });
    }
    
    function sponsorAjax(action, data, button) {
        document.getElementById('loadingOverlay').classList.add('active');
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        
        data.action = action;
        data.nonce = sponsorAjaxNonce;
        data.current_url = currentPageUrl;
        
        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams(data)
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                if (result.data && typeof result.data === 'object' && result.data.redirect) {
                    window.location.href = result.data.redirect;
                } else {
                    const message = typeof result.data === 'object' ? result.data.message : result.data;
                    showMessage(message, 'success');
                    setTimeout(() => {
                        location.reload(true);
                    }, 800);
                }
            } else {
                document.getElementById('loadingOverlay').classList.remove('active');
                showMessage(result.data, 'error');
                button.disabled = false;
                button.innerHTML = button.getAttribute('data-original-text');
            }
        })
        .catch(error => {
            document.getElementById('loadingOverlay').classList.remove('active');
            showMessage('Error: ' + error.message, 'error');
            button.disabled = false;
            button.innerHTML = button.getAttribute('data-original-text');
        });
    }
    
    function approveRegistration(userId, button) {
        if (!confirm('Approve this registration?')) return;
        button.setAttribute('data-original-text', button.innerHTML);
        sponsorAjax('sponsor_approve_registration', { user_id: userId }, button);
    }
    
    function rejectRegistration(userId, button) {
        if (!confirm('Reject this registration?')) return;
        button.setAttribute('data-original-text', button.innerHTML);
        sponsorAjax('sponsor_reject_registration', { user_id: userId }, button);
    }
    
    function verifyPayment(sponsorshipId, button) {
        if (!confirm('Verify this payment and link sponsor to student?\n\nThis will:\n✅ Mark sponsorship as active\n✅ Hide student from public browse\n✅ Send confirmation email')) return;
        button.setAttribute('data-original-text', button.innerHTML);
        sponsorAjax('sponsor_verify_payment', { sponsorship_id: sponsorshipId }, button);
    }
    
    function rejectPayment(sponsorshipId, button) {
        if (!confirm('Reject this payment?')) return;
        button.setAttribute('data-original-text', button.innerHTML);
        sponsorAjax('sponsor_reject_payment', { sponsorship_id: sponsorshipId }, button);
    }
    
    function deleteUser(userId, userName, button) {
        if (!confirm('⚠️ DELETE USER: ' + userName + '?\n\nThis will:\n• Delete user account\n• Move sponsorships to history\n• Make students visible again\n\nAre you sure?')) return;
        button.setAttribute('data-original-text', button.innerHTML);
        sponsorAjax('sponsor_delete_user', { user_id: userId }, button);
    }
    
    function resetPassword(userId, userEmail, button) {
        if (!confirm('Send password reset email to: ' + userEmail + '?')) return;
        button.setAttribute('data-original-text', button.innerHTML);
        sponsorAjax('sponsor_reset_password', { user_id: userId }, button);
    }
    
    function restoreSponsorship(sponsorshipId, button) {
        if (!confirm('Restore this sponsorship?\n\nThis will:\n✅ Reactivate the sponsorship\n✅ Hide student from public browse again')) return;
        button.setAttribute('data-original-text', button.innerHTML);
        sponsorAjax('sponsor_restore_sponsorship', { sponsorship_id: sponsorshipId }, button);
    }
    
    function permanentDeleteSponsorship(sponsorshipId, button) {
        if (!confirm('⚠️ PERMANENTLY DELETE?\n\nThis will:\n• Remove from database completely\n• Cannot be restored\n• Keep student visible\n\nAre you ABSOLUTELY sure?')) return;
        button.setAttribute('data-original-text', button.innerHTML);
        sponsorAjax('sponsor_permanent_delete_sponsorship', { sponsorship_id: sponsorshipId }, button);
    }
    
    function openEditModal(userId, displayName, email, phone, country, status) {
        document.getElementById('edit_user_id').value = userId;
        document.getElementById('edit_display_name').value = displayName;
        document.getElementById('edit_email').value = email;
        document.getElementById('edit_phone').value = phone || '';
        document.getElementById('edit_country').value = country || '';
        document.getElementById('edit_status').value = status || 'approved';
        document.getElementById('editModal').classList.add('active');
    }
    
    function closeEditModal() {
        document.getElementById('editModal').classList.remove('active');
    }
    
    document.getElementById('editUserForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const data = {};
        formData.forEach((value, key) => data[key] = value);
        
        document.getElementById('loadingOverlay').classList.add('active');
        data.action = 'sponsor_update_user';
        data.nonce = sponsorAjaxNonce;
        
        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams(data)
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                showMessage(result.data, 'success');
                closeEditModal();
                setTimeout(() => {
                    location.reload(true);
                }, 1000);
            } else {
                document.getElementById('loadingOverlay').classList.remove('active');
                showMessage(result.data, 'error');
            }
        })
        .catch(error => {
            document.getElementById('loadingOverlay').classList.remove('active');
            showMessage('Error: ' + error.message, 'error');
        });
    });
    
    function viewScreenshot(url) {
        window.open(url, '_blank', 'width=800,height=600');
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(() => {
            document.getElementById('ajax-message').style.display = 'none';
        }, 100);
    });
    </script>
    
    <?php
    return ob_get_clean();
}

// RENDER FUNCTIONS
function render_pending_registrations() {
    $pending_users = get_users(array('role' => 'sponsor', 'number' => 100));
    
    $pending_users = array_filter($pending_users, function($user) {
        $status = get_user_meta($user->ID, 'account_status', true);
        return $status === 'awaiting_admin_review' || $status === '' || $status === 'pending';
    });
    
    if (empty($pending_users)) {
        return '<div class="empty-state">
                    <i class="fas fa-check-circle"></i>
                    <h3>All Caught Up!</h3>
                    <p>No pending registrations to review.</p>
                </div>';
    }
    
    $output = '<div class="sponsor-grid">';
    
    foreach ($pending_users as $user) {
        $phone = get_user_meta($user->ID, 'sponsor_phone', true);
        $country = get_user_meta($user->ID, 'sponsor_country', true);
        $status = get_user_meta($user->ID, 'account_status', true);
        
        $output .= '<div class="sponsor-card" style="grid-template-columns: 1fr;">';
        $output .= '<div class="sponsor-info">';
        $output .= '<h3>' . esc_html($user->display_name) . '</h3>';
        $output .= '<div class="sponsor-meta">';
        $output .= '<span><i class="fas fa-envelope"></i> ' . esc_html($user->user_email) . '</span>';
        if ($phone) $output .= '<span><i class="fas fa-phone"></i> ' . esc_html($phone) . '</span>';
        if ($country) $output .= '<span><i class="fas fa-globe"></i> ' . esc_html($country) . '</span>';
        $output .= '<span><i class="fas fa-calendar"></i> ' . date('M d, Y', strtotime($user->user_registered)) . '</span>';
        $output .= '</div></div>';
        
        $output .= '<div class="sponsor-actions" style="margin-top: 15px;">';
        $output .= '<button class="btn-approve" onclick="approveRegistration(' . $user->ID . ', this)"><i class="fas fa-check"></i> Approve</button>';
        $output .= '<button class="btn-reject" onclick="rejectRegistration(' . $user->ID . ', this)"><i class="fas fa-times"></i> Reject</button>';
        $output .= '<button class="btn-edit" onclick="openEditModal(' . $user->ID . ', \'' . esc_js($user->display_name) . '\', \'' . esc_js($user->user_email) . '\', \'' . esc_js($phone) . '\', \'' . esc_js($country) . '\', \'' . esc_js($status) . '\')"><i class="fas fa-edit"></i> Edit</button>';
        $output .= '<button class="btn-reset" onclick="resetPassword(' . $user->ID . ', \'' . esc_js($user->user_email) . '\', this)"><i class="fas fa-key"></i> Reset Password</button>';
        $output .= '<button class="btn-delete" onclick="deleteUser(' . $user->ID . ', \'' . esc_js($user->display_name) . '\', this)"><i class="fas fa-trash"></i> Delete</button>';
        $output .= '</div></div>';
    }
    
    $output .= '</div>';
    return $output;
}

function render_payment_verifications() {
    wp_cache_flush();
    
    $all_sponsorships = get_posts(array(
        'post_type' => 'sponsorship',
        'post_status' => 'any',
        'posts_per_page' => -1,
        'cache_results' => false,
        'suppress_filters' => false
    ));
    
    $pending_payments = array_filter($all_sponsorships, function($payment) {
        $linked = get_post_meta($payment->ID, 'linked', true);
        $post_status = get_post_status($payment->ID);
        return $linked !== 'yes' && $post_status !== 'publish';
    });
    
    if (empty($pending_payments)) {
        return '<div class="empty-state">
                    <i class="fas fa-check-circle"></i>
                    <h3>No Pending Payments!</h3>
                    <p>All payments have been verified.</p>
                </div>';
    }
    
    $output = '<div class="sponsor-grid">';
    
    foreach ($pending_payments as $payment) {
        $sponsor_id = get_post_meta($payment->ID, 'sponsor_user_id', true);
        $sponsor = get_userdata($sponsor_id);
        $student_id = get_post_meta($payment->ID, 'student_id', true);
        $student = get_post($student_id);
        
        $amount = get_post_meta($payment->ID, 'amount', true);
        $type = get_post_meta($payment->ID, 'sponsorship_type', true);
        $transaction_id = get_post_meta($payment->ID, 'transaction_id', true);
        $payment_method = get_post_meta($payment->ID, 'payment_method', true);
        $payment_date = get_post_meta($payment->ID, 'payment_date', true);
        $screenshot_id = get_post_meta($payment->ID, 'payment_screenshot', true);
        $screenshot_url = wp_get_attachment_url($screenshot_id);
        
        $output .= '<div class="sponsor-card" style="grid-template-columns: 1fr;">';
        $output .= '<div class="sponsor-info">';
        $output .= '<h3>' . esc_html($sponsor ? $sponsor->display_name : 'Unknown') . ' → ' . esc_html($student ? $student->post_title : 'Unknown') . '</h3>';
        
        $output .= '<div class="payment-details">';
        $output .= '<div class="payment-row"><span>Type:</span><strong>' . ucfirst($type) . '</strong></div>';
        $output .= '<div class="payment-row"><span>Amount:</span><strong>PKR ' . number_format($amount) . '</strong></div>';
        $output .= '<div class="payment-row"><span>Transaction:</span><strong>' . esc_html($transaction_id) . '</strong></div>';
        $output .= '<div class="payment-row"><span>Method:</span><strong>' . esc_html($payment_method) . '</strong></div>';
        $output .= '<div class="payment-row"><span>Date:</span><strong>' . date('M d, Y', strtotime($payment_date)) . '</strong></div>';
        $output .= '</div>';
        
        if ($screenshot_url) {
            $output .= '<div class="payment-screenshot">';
            $output .= '<p><strong>Payment Proof:</strong></p>';
            $output .= '<img src="' . esc_url($screenshot_url) . '" onclick="viewScreenshot(\'' . esc_url($screenshot_url) . '\')">';
            $output .= '</div>';
        }
        
        $output .= '<div class="sponsor-actions" style="margin-top: 20px;">';
        $output .= '<button class="btn-verify" onclick="verifyPayment(' . $payment->ID . ', this)"><i class="fas fa-check-circle"></i> Verify & Link</button>';
        $output .= '<button class="btn-reject" onclick="rejectPayment(' . $payment->ID . ', this)"><i class="fas fa-times-circle"></i> Reject</button>';
        $output .= '</div></div></div>';
    }
    
    $output .= '</div>';
    return $output;
}

function render_all_sponsors() {
    $sponsors = get_users(array('role' => 'sponsor', 'orderby' => 'registered', 'order' => 'DESC', 'number' => 100));
    
    if (empty($sponsors)) {
        return '<div class="empty-state">
                    <i class="fas fa-users-slash"></i>
                    <h3>No Sponsors Yet</h3>
                </div>';
    }
    
    $output = '<table class="sponsor-table"><thead><tr>';
    $output .= '<th>Name</th><th>Email</th><th>Country</th><th>Status</th><th>Students</th><th>Total</th><th>Registered</th><th>Actions</th>';
    $output .= '</tr></thead><tbody>';
    
    foreach ($sponsors as $sponsor) {
        $status = get_user_meta($sponsor->ID, 'account_status', true) ?: 'approved';
        $phone = get_user_meta($sponsor->ID, 'sponsor_phone', true);
        $country = get_user_meta($sponsor->ID, 'sponsor_country', true);
        
        $sponsorships = get_posts(array(
            'post_type' => 'sponsorship',
            'author' => $sponsor->ID,
            'post_status' => 'publish',
            'posts_per_page' => -1
        ));
        
        $linked = array_filter($sponsorships, function($sp) {
            return get_post_meta($sp->ID, 'linked', true) === 'yes';
        });
        
        $total = 0;
        $students = array();
        foreach ($linked as $sp) {
            $total += floatval(get_post_meta($sp->ID, 'amount', true));
            $sid = get_post_meta($sp->ID, 'student_id', true);
            if (!in_array($sid, $students)) $students[] = $sid;
        }
        
        $output .= '<tr>';
        $output .= '<td><strong>' . esc_html($sponsor->display_name) . '</strong></td>';
        $output .= '<td>' . esc_html($sponsor->user_email) . '</td>';
        $output .= '<td>' . esc_html($country ?: 'N/A') . '</td>';
        $output .= '<td><span class="status-badge status-' . $status . '">' . ucfirst($status) . '</span></td>';
        $output .= '<td><strong>' . count($students) . '</strong></td>';
        $output .= '<td><strong>PKR ' . number_format($total) . '</strong></td>';
        $output .= '<td>' . date('M d, Y', strtotime($sponsor->user_registered)) . '</td>';
        $output .= '<td>';
        $output .= '<div style="display: flex; gap: 5px; flex-wrap: wrap;">';
        $output .= '<button class="btn-edit" style="padding: 6px 12px; font-size: 11px;" onclick="openEditModal(' . $sponsor->ID . ', \'' . esc_js($sponsor->display_name) . '\', \'' . esc_js($sponsor->user_email) . '\', \'' . esc_js($phone) . '\', \'' . esc_js($country) . '\', \'' . esc_js($status) . '\')"><i class="fas fa-edit"></i></button>';
        $output .= '<button class="btn-reset" style="padding: 6px 12px; font-size: 11px;" onclick="resetPassword(' . $sponsor->ID . ', \'' . esc_js($sponsor->user_email) . '\', this)"><i class="fas fa-key"></i></button>';
        $output .= '<button class="btn-delete" style="padding: 6px 12px; font-size: 11px;" onclick="deleteUser(' . $sponsor->ID . ', \'' . esc_js($sponsor->display_name) . '\', this)"><i class="fas fa-trash"></i></button>';
        $output .= '</div></td></tr>';
    }
    
    $output .= '</tbody></table>';
    return $output;
}

function render_sponsorships() {
    wp_cache_flush();
    
    $sponsorships = get_posts(array(
        'post_type' => 'sponsorship',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'cache_results' => false
    ));
    
    $sponsorships = array_filter($sponsorships, function($sp) {
        return get_post_meta($sp->ID, 'linked', true) === 'yes';
    });
    
    if (empty($sponsorships)) {
        return '<div class="empty-state">
                    <i class="fas fa-link-slash"></i>
                    <h3>No Active Sponsorships</h3>
                </div>';
    }
    
    $output = '<table class="sponsor-table"><thead><tr>';
    $output .= '<th>Sponsor</th><th>Student</th><th>Type</th><th>Amount</th><th>Approved</th><th>Status</th>';
    $output .= '</tr></thead><tbody>';
    
    foreach ($sponsorships as $sp) {
        $sponsor_id = get_post_meta($sp->ID, 'sponsor_user_id', true);
        $sponsor = get_userdata($sponsor_id);
        $student_id = get_post_meta($sp->ID, 'student_id', true);
        $student = get_post($student_id);
        
        $type = get_post_meta($sp->ID, 'sponsorship_type', true);
        $amount = get_post_meta($sp->ID, 'amount', true);
        $approved = get_post_meta($sp->ID, 'approved_date', true);
        $is_hidden = get_post_meta($student_id, 'already_sponsored', true);
        
        $output .= '<tr>';
        $output .= '<td><strong>' . esc_html($sponsor ? $sponsor->display_name : 'N/A') . '</strong></td>';
        $output .= '<td><strong>' . esc_html($student ? $student->post_title : 'N/A') . '</strong><br><small>GR: ' . get_post_meta($student_id, 'gr_number', true) . '</small></td>';
        $output .= '<td><span class="status-badge status-approved">' . ucfirst($type) . '</span></td>';
        $output .= '<td><strong>PKR ' . number_format($amount) . '</strong></td>';
        $output .= '<td>' . ($approved ? date('M d, Y', strtotime($approved)) : 'N/A') . '</td>';
        $output .= '<td>';
        
        if ($is_hidden === 'yes') {
            $output .= '<span class="status-badge" style="background: #fce7f3; color: #c2185b;"><i class="fas fa-eye-slash"></i> Hidden from Public</span>';
        } else {
            $output .= '<span class="status-badge status-pending"><i class="fas fa-eye"></i> Visible</span>';
        }
        
        $output .= '</td></tr>';
    }
    
    $output .= '</tbody></table>';
    return $output;
}

function render_deleted_sponsorships() {
    $deleted_sponsorships = get_posts(array(
        'post_type' => 'sponsorship',
        'post_status' => 'trash',
        'posts_per_page' => -1,
        'orderby' => 'modified',
        'order' => 'DESC'
    ));
    
    if (empty($deleted_sponsorships)) {
        return '<div class="empty-state">
                    <i class="fas fa-archive"></i>
                    <h3>No Deleted Sponsorships</h3>
                    <p>All sponsorships are active.</p>
                </div>';
    }
    
    $output = '<div style="background: #fef3c7; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #f59e0b;">';
    $output .= '<p style="margin: 0; font-size: 14px; color: #92400e;"><i class="fas fa-info-circle"></i> <strong>History Tab:</strong> Shows deleted sponsorships. You can restore them or permanently delete from database.</p>';
    $output .= '</div>';
    
    $output .= '<div class="sponsor-grid">';
    
    foreach ($deleted_sponsorships as $sponsorship) {
        $sponsor_id = get_post_meta($sponsorship->ID, 'sponsor_user_id', true);
        $sponsor = get_userdata($sponsor_id);
        $student_id = get_post_meta($sponsorship->ID, 'student_id', true);
        $student = get_post($student_id);
        
        $amount = get_post_meta($sponsorship->ID, 'amount', true);
        $type = get_post_meta($sponsorship->ID, 'sponsorship_type', true);
        $deleted_date = get_post_meta($sponsorship->ID, 'deleted_date', true);
        $deleted_by_id = get_post_meta($sponsorship->ID, 'deleted_by', true);
        $deleted_by = get_userdata($deleted_by_id);
        $deletion_reason = get_post_meta($sponsorship->ID, 'deletion_reason', true);
        
        $output .= '<div class="sponsor-card deleted-card" style="grid-template-columns: 1fr;">';
        $output .= '<div class="sponsor-info">';
        $output .= '<h3><i class="fas fa-archive"></i> ' . esc_html($sponsor ? $sponsor->display_name : 'Deleted User') . ' → ' . esc_html($student ? $student->post_title : 'Unknown') . '</h3>';
        
        $output .= '<div class="payment-details">';
        $output .= '<div class="payment-row"><span>Type:</span><strong>' . ucfirst($type) . '</strong></div>';
        $output .= '<div class="payment-row"><span>Amount:</span><strong>PKR ' . number_format($amount) . '</strong></div>';
        $output .= '<div class="payment-row"><span>Student GR:</span><strong>' . get_post_meta($student_id, 'gr_number', true) . '</strong></div>';
        $output .= '</div>';
        
        if ($deletion_reason) {
            $output .= '<div class="deletion-info">';
            $output .= '<strong><i class="fas fa-info-circle"></i> Deletion Info:</strong>';
            $output .= '<p style="margin: 5px 0 0 0;">' . esc_html($deletion_reason) . '</p>';
            if ($deleted_date) {
                $output .= '<p style="margin: 3px 0 0 0; font-size: 11px; color: #6b7280;">Deleted: ' . date('M d, Y g:i A', strtotime($deleted_date)) . '</p>';
            }
            if ($deleted_by) {
                $output .= '<p style="margin: 3px 0 0 0; font-size: 11px; color: #6b7280;">By: ' . esc_html($deleted_by->display_name) . '</p>';
            }
            $output .= '</div>';
        }
        
        $output .= '<div class="sponsor-actions" style="margin-top: 20px;">';
        $output .= '<button class="btn-restore" onclick="restoreSponsorship(' . $sponsorship->ID . ', this)"><i class="fas fa-undo"></i> Restore</button>';
        $output .= '<button class="btn-permanent-delete" onclick="permanentDeleteSponsorship(' . $sponsorship->ID . ', this)"><i class="fas fa-trash-alt"></i> Delete Forever</button>';
        $output .= '</div></div></div>';
    }
    
    $output .= '</div>';
    return $output;
}
?>