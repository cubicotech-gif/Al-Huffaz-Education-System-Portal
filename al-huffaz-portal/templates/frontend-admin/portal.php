<?php
/**
 * Front-end Admin Portal Template
 * Al-Huffaz Education System Portal
 *
 * SIMPLIFIED VERSION - No complex role dependencies
 */

defined('ABSPATH') || exit;

// CRITICAL FIX: Hide WordPress admin bar on portal page
show_admin_bar(false);

// Simple check - just use WordPress capabilities
$current_user = wp_get_current_user();

// CRITICAL FIX: Check for both WP admin (manage_options) AND alhuffaz_admin role
$is_alhuffaz_admin = in_array('alhuffaz_admin', $current_user->roles);
$is_wp_admin = current_user_can('manage_options');

// Portal permissions
$is_admin = $is_wp_admin || $is_alhuffaz_admin;
$can_manage_sponsors = $is_wp_admin || $is_alhuffaz_admin || current_user_can('alhuffaz_manage_sponsors');
$can_manage_payments = $is_wp_admin || $is_alhuffaz_admin || current_user_can('alhuffaz_manage_payments');
$can_manage_staff = $is_wp_admin || $is_alhuffaz_admin || current_user_can('alhuffaz_manage_staff');
$is_staff = current_user_can('edit_posts') && !$is_admin;
$staff_count = 0;

// Get stats - with null checks
$student_counts = wp_count_posts('student');
$total_students = isset($student_counts->publish) ? (int)$student_counts->publish : 0;

// CRITICAL FIX: Count sponsorships (not alhuffaz_sponsor post type)
$sponsor_counts = wp_count_posts('sponsorship');
$total_sponsors = isset($sponsor_counts->publish) ? (int)$sponsor_counts->publish : 0;

// Category counts
$hifz_count = 0;
$nazra_count = 0;
$pending_sponsors_count = 0;
$pending_payments_count = 0;
$donation_eligible_count = 0;

// Only query if post type exists
if (post_type_exists('student')) {
    $hifz_posts = get_posts(array(
        'post_type' => 'student',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'meta_key' => 'islamic_studies_category',
        'meta_value' => 'hifz',
        'fields' => 'ids'
    ));
    $hifz_count = count($hifz_posts);

    $nazra_posts = get_posts(array(
        'post_type' => 'student',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'meta_key' => 'islamic_studies_category',
        'meta_value' => 'nazra',
        'fields' => 'ids'
    ));
    $nazra_count = count($nazra_posts);

    $eligible_posts = get_posts(array(
        'post_type' => 'student',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'meta_key' => 'donation_eligible',
        'meta_value' => 'yes',
        'fields' => 'ids'
    ));
    $donation_eligible_count = count($eligible_posts);
}

// CRITICAL FIX: Query 'sponsorship' post type with correct meta key
if (post_type_exists('sponsorship')) {
    $pending_posts = get_posts(array(
        'post_type' => 'sponsorship',
        'post_status' => 'any',
        'posts_per_page' => -1,
        'meta_key' => 'verification_status',
        'meta_value' => 'pending',
        'fields' => 'ids'
    ));
    $pending_sponsors_count = count($pending_posts);
}

// Get pending payments count from database
global $wpdb;
$payments_table = $wpdb->prefix . 'alhuffaz_payments';
$table_exists = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s",
    DB_NAME, $payments_table
));
if ($table_exists) {
    $pending_payments_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM $payments_table WHERE status = 'pending'");
}

// Get staff count
if (class_exists('\AlHuffaz\Core\Roles')) {
    $staff_users = get_users(array('role' => 'alhuffaz_staff'));
    $staff_count = count($staff_users);
}

// Get inactive sponsors count (sponsors with no active sponsorships)
// CRITICAL FIX: Query correct post type and meta keys
$inactive_sponsors_count = 0;
$all_sponsor_users = get_users(array('role' => 'sponsor'));  // Use 'sponsor' role
foreach ($all_sponsor_users as $sponsor_user) {
    $active_sponsorships = get_posts(array(
        'post_type' => 'sponsorship',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'meta_query' => array(
            'relation' => 'AND',
            array('key' => 'sponsor_user_id', 'value' => $sponsor_user->ID),
            array('key' => 'linked', 'value' => 'yes'),
        ),
        'fields' => 'ids',
    ));
    if (empty($active_sponsorships)) {
        $inactive_sponsors_count++;
    }
}

// Get recent students
$recent_students = array();
if (post_type_exists('student')) {
    $recent_students = get_posts(array(
        'post_type' => 'student',
        'post_status' => 'publish',
        'posts_per_page' => 5,
        'orderby' => 'date',
        'order' => 'DESC'
    ));
}

// Check for edit mode
$edit_id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
$is_edit = ($edit_id > 0 && get_post_type($edit_id) === 'student');

// CRITICAL FIX: If URL has edit parameter but student doesn't exist, redirect to clean URL
if ($edit_id > 0 && !$is_edit) {
    // Student doesn't exist or is not valid, clean the URL
    wp_redirect(remove_query_arg('edit'));
    exit;
}

$student_data = array();
$subjects = array();
$photo_url = '';

if ($is_edit) {
    $student_data = array(
        'student_name' => get_the_title($edit_id),
        'gr_number' => get_post_meta($edit_id, 'gr_number', true),
        'roll_number' => get_post_meta($edit_id, 'roll_number', true),
        'gender' => get_post_meta($edit_id, 'gender', true),
        'date_of_birth' => get_post_meta($edit_id, 'date_of_birth', true),
        'admission_date' => get_post_meta($edit_id, 'admission_date', true),
        'grade_level' => get_post_meta($edit_id, 'grade_level', true),
        'islamic_studies_category' => get_post_meta($edit_id, 'islamic_studies_category', true),
        'permanent_address' => get_post_meta($edit_id, 'permanent_address', true),
        'current_address' => get_post_meta($edit_id, 'current_address', true),
        'father_name' => get_post_meta($edit_id, 'father_name', true),
        'father_cnic' => get_post_meta($edit_id, 'father_cnic', true),
        'father_email' => get_post_meta($edit_id, 'father_email', true),
        'guardian_name' => get_post_meta($edit_id, 'guardian_name', true),
        'guardian_cnic' => get_post_meta($edit_id, 'guardian_cnic', true),
        'guardian_email' => get_post_meta($edit_id, 'guardian_email', true),
        'guardian_phone' => get_post_meta($edit_id, 'guardian_phone', true),
        'guardian_whatsapp' => get_post_meta($edit_id, 'guardian_whatsapp', true),
        'relationship_to_student' => get_post_meta($edit_id, 'relationship_to_student', true),
        'emergency_contact' => get_post_meta($edit_id, 'emergency_contact', true),
        'emergency_whatsapp' => get_post_meta($edit_id, 'emergency_whatsapp', true),
        'monthly_tuition_fee' => get_post_meta($edit_id, 'monthly_tuition_fee', true),
        'course_fee' => get_post_meta($edit_id, 'course_fee', true),
        'uniform_fee' => get_post_meta($edit_id, 'uniform_fee', true),
        'annual_fee' => get_post_meta($edit_id, 'annual_fee', true),
        'admission_fee' => get_post_meta($edit_id, 'admission_fee', true),
        'zakat_eligible' => get_post_meta($edit_id, 'zakat_eligible', true),
        'donation_eligible' => get_post_meta($edit_id, 'donation_eligible', true),
        'blood_group' => get_post_meta($edit_id, 'blood_group', true),
        'allergies' => get_post_meta($edit_id, 'allergies', true),
        'medical_conditions' => get_post_meta($edit_id, 'medical_conditions', true),
        'total_school_days' => get_post_meta($edit_id, 'total_school_days', true),
        'present_days' => get_post_meta($edit_id, 'present_days', true),
        'academic_term' => get_post_meta($edit_id, 'academic_term', true),
        'academic_year' => get_post_meta($edit_id, 'academic_year', true),
        'health_rating' => get_post_meta($edit_id, 'health_rating', true),
        'cleanness_rating' => get_post_meta($edit_id, 'cleanness_rating', true),
        'completes_homework' => get_post_meta($edit_id, 'completes_homework', true),
        'participates_in_class' => get_post_meta($edit_id, 'participates_in_class', true),
        'works_well_in_groups' => get_post_meta($edit_id, 'works_well_in_groups', true),
        'problem_solving_skills' => get_post_meta($edit_id, 'problem_solving_skills', true),
        'organization_preparedness' => get_post_meta($edit_id, 'organization_preparedness', true),
        'teacher_overall_comments' => get_post_meta($edit_id, 'teacher_overall_comments', true),
        'goal_1' => get_post_meta($edit_id, 'goal_1', true),
        'goal_2' => get_post_meta($edit_id, 'goal_2', true),
        'goal_3' => get_post_meta($edit_id, 'goal_3', true),
    );

    $subjects = get_post_meta($edit_id, 'subjects', true);
    if (!is_array($subjects)) $subjects = array();

    $photo_id = get_post_meta($edit_id, 'student_photo', true);
    $photo_url = $photo_id ? wp_get_attachment_image_url($photo_id, 'medium') : '';
}

// Helper functions - only define if not already defined
if (!function_exists('ahp_fe_selected')) {
    function ahp_fe_selected($field, $value, $data) {
        return (isset($data[$field]) && $data[$field] === $value) ? 'selected' : '';
    }
}
if (!function_exists('ahp_fe_checked')) {
    function ahp_fe_checked($field, $data) {
        return (!empty($data[$field]) && $data[$field] === 'yes') ? 'checked' : '';
    }
}

$portal_url = get_permalink();
$nonce = wp_create_nonce('alhuffaz_student_nonce');
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

<style>
/* ============================================
   AL-HUFFAZ ADMIN PORTAL - MODERN TOP NAV DESIGN
   Inspired by Stripe, Linear, Notion dashboards
   ============================================ */

/* CSS Variables - Scoped to .ahp-portal */
.ahp-portal {
    --ahp-primary: #0080ff;
    --ahp-primary-dark: #0056b3;
    --ahp-primary-light: #e0f2fe;
    --ahp-success: #10b981;
    --ahp-success-light: #d1fae5;
    --ahp-warning: #f59e0b;
    --ahp-warning-light: #fef3c7;
    --ahp-danger: #ef4444;
    --ahp-danger-light: #fee2e2;
    --ahp-text: #1e293b;
    --ahp-text-muted: #64748b;
    --ahp-border: #e2e8f0;
    --ahp-bg: #f8fafc;
    --ahp-sidebar: #0f172a;
    --ahp-card: #ffffff;
    --ahp-header-bg: #ffffff;
}

/* CSS Reset - Prevent WordPress theme bleeding */
.ahp-portal,
.ahp-portal *,
.ahp-portal *::before,
.ahp-portal *::after {
    box-sizing: border-box !important;
    margin: 0;
    padding: 0;
}

/* Reset specific elements */
.ahp-portal article, .ahp-portal aside, .ahp-portal details, .ahp-portal figcaption,
.ahp-portal figure, .ahp-portal footer, .ahp-portal header, .ahp-portal hgroup,
.ahp-portal menu, .ahp-portal nav, .ahp-portal section {
    display: block;
}

.ahp-portal ol, .ahp-portal ul {
    list-style: none;
}

.ahp-portal table {
    border-collapse: collapse;
    border-spacing: 0;
}

.ahp-portal a {
    text-decoration: none;
    color: inherit;
}

.ahp-portal button, .ahp-portal input, .ahp-portal select, .ahp-portal textarea {
    font-family: inherit;
    font-size: inherit;
    line-height: inherit;
}

/* Main Portal Container */
.ahp-portal {
    font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
    background: var(--ahp-bg) !important;
    min-height: 100vh !important;
    color: var(--ahp-text) !important;
    line-height: 1.5 !important;
    font-size: 14px !important;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
    position: relative;
    width: 100%;
}

/* ==================== HIDE WORDPRESS ADMIN BAR ==================== */
#wpadminbar {
    display: none !important;
}
html {
    margin-top: 0 !important;
}
body {
    margin-top: 0 !important;
}

/* ==================== TOP HEADER ==================== */
.ahp-portal .ahp-header {
    background: var(--ahp-header-bg) !important;
    border-bottom: 1px solid var(--ahp-border) !important;
    position: sticky !important;
    top: 0 !important;
    z-index: 100 !important;
}

.ahp-portal .ahp-header-inner {
    max-width: 1400px !important;
    margin: 0 auto !important;
    padding: 0 24px !important;
}

.ahp-portal .ahp-header-top {
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    height: 64px !important;
}

/* Layout - Top Navigation Design */
.ahp-portal .ahp-wrapper {
    display: flex !important;
    flex-direction: column !important;
    min-height: 100vh !important;
}

/* Sidebar converted to horizontal nav in header */
.ahp-portal .ahp-sidebar {
    display: none !important;
}

/* Main content - full width */
.ahp-portal .ahp-main {
    flex: 1 !important;
    margin-left: 0 !important;
    padding: 32px 24px !important;
    max-width: 1400px !important;
    margin: 0 auto !important;
    width: 100% !important;
    background: var(--ahp-bg) !important;
}

/* ==================== TOP HEADER STYLES ==================== */
.ahp-portal .ahp-top-header {
    background: var(--ahp-header-bg) !important;
    border-bottom: 1px solid var(--ahp-border) !important;
    position: sticky !important;
    top: 0 !important;
    z-index: 100 !important;
}

.ahp-portal .ahp-header-inner {
    max-width: 1400px !important;
    margin: 0 auto !important;
    padding: 0 24px !important;
}

.ahp-portal .ahp-header-top {
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    height: 64px !important;
}

.ahp-portal .ahp-logo {
    display: flex !important;
    align-items: center !important;
    gap: 12px !important;
    font-size: 18px !important;
    font-weight: 700 !important;
    color: var(--ahp-text) !important;
}
.ahp-portal .ahp-logo i {
    font-size: 24px !important;
    color: var(--ahp-primary) !important;
}

.ahp-portal .ahp-user-menu {
    display: flex !important;
    align-items: center !important;
    gap: 16px !important;
}

.ahp-portal .ahp-user-info {
    display: flex !important;
    align-items: center !important;
    gap: 12px !important;
    padding: 8px 16px !important;
    background: var(--ahp-bg) !important;
    border-radius: 100px !important;
}

.ahp-portal .ahp-avatar {
    width: 36px !important;
    height: 36px !important;
    border-radius: 50% !important;
    background: linear-gradient(135deg, var(--ahp-primary), var(--ahp-primary-dark)) !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    font-size: 14px !important;
    font-weight: 700 !important;
    color: white !important;
}

.ahp-portal .ahp-user-name {
    font-weight: 600 !important;
    font-size: 14px !important;
    color: var(--ahp-text) !important;
}

.ahp-portal .ahp-user-role {
    font-size: 11px !important;
    color: var(--ahp-text-muted) !important;
    background: var(--ahp-primary-light) !important;
    padding: 3px 10px !important;
    border-radius: 100px !important;
    font-weight: 600 !important;
}

.ahp-portal .ahp-logout-btn {
    display: flex !important;
    align-items: center !important;
    gap: 6px !important;
    padding: 8px 16px !important;
    background: transparent !important;
    border: 1px solid var(--ahp-border) !important;
    border-radius: 8px !important;
    font-size: 13px !important;
    font-weight: 500 !important;
    color: var(--ahp-text-muted) !important;
    cursor: pointer !important;
    transition: all 0.2s !important;
    text-decoration: none !important;
}

.ahp-portal .ahp-logout-btn:hover {
    background: var(--ahp-danger-light) !important;
    border-color: var(--ahp-danger) !important;
    color: var(--ahp-danger) !important;
}

/* ==================== HORIZONTAL TAB NAVIGATION ==================== */
.ahp-portal .ahp-nav {
    display: flex !important;
    gap: 4px !important;
    padding-bottom: 0 !important;
    overflow-x: auto !important;
    scrollbar-width: none !important;
}

.ahp-portal .ahp-nav::-webkit-scrollbar { display: none; }

.ahp-portal .ahp-nav-item {
    display: flex !important;
    align-items: center !important;
    gap: 8px !important;
    padding: 12px 16px !important;
    font-size: 14px !important;
    font-weight: 500 !important;
    color: var(--ahp-text-muted) !important;
    border-bottom: 2px solid transparent !important;
    cursor: pointer !important;
    transition: all 0.2s !important;
    white-space: nowrap !important;
    background: transparent !important;
    border-top: none !important;
    border-left: none !important;
    border-right: none !important;
}

.ahp-portal .ahp-nav-item:hover {
    color: var(--ahp-text) !important;
    background: var(--ahp-bg) !important;
}

.ahp-portal .ahp-nav-item.active {
    color: var(--ahp-primary) !important;
    border-bottom-color: var(--ahp-primary) !important;
}

.ahp-portal .ahp-nav-item i {
    font-size: 16px !important;
}

.ahp-portal .ahp-nav-badge {
    background: var(--ahp-primary) !important;
    color: white !important;
    padding: 2px 8px !important;
    border-radius: 100px !important;
    font-size: 11px !important;
    font-weight: 700 !important;
}

.ahp-portal .ahp-nav-badge.warning { background: var(--ahp-warning) !important; }
.ahp-portal .ahp-nav-badge.success { background: var(--ahp-success) !important; }
.ahp-portal .ahp-nav-badge.info { background: #3b82f6 !important; }
.ahp-portal .ahp-nav-badge.danger { background: var(--ahp-danger) !important; }

/* Mobile Menu Toggle */
.ahp-portal .ahp-menu-toggle {
    display: none !important;
    align-items: center !important;
    justify-content: center !important;
    width: 40px !important;
    height: 40px !important;
    background: var(--ahp-bg) !important;
    border: none !important;
    border-radius: 8px !important;
    font-size: 20px !important;
    color: var(--ahp-text) !important;
    cursor: pointer !important;
}

/* Old sidebar elements - hide */
.ahp-portal .ahp-sidebar-header,
.ahp-portal .ahp-sidebar-footer {
    display: none !important;
}

/* Page Header (within panels) */
.ahp-portal .ahp-header {
    display: flex !important;
    justify-content: space-between !important;
    align-items: center !important;
    margin-bottom: 24px !important;
}
.ahp-portal .ahp-title { margin: 0 !important; font-size: 24px !important; font-weight: 700 !important; color: var(--ahp-text) !important; }
.ahp-portal .ahp-actions { display: flex !important; gap: 10px !important; }

/* Panels */
.ahp-portal .ahp-panel { display: none !important; }
.ahp-portal .ahp-panel.active { display: block !important; animation: ahpFadeIn 0.3s ease !important; }
@keyframes ahpFadeIn { from { opacity: 0; } to { opacity: 1; } }

/* Stats */
.ahp-portal .ahp-stats { display: grid !important; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)) !important; gap: 20px !important; margin-bottom: 24px !important; }
.ahp-portal .ahp-stat {
    background: var(--ahp-card) !important;
    border-radius: 12px !important;
    padding: 20px !important;
    display: flex !important;
    align-items: center !important;
    gap: 16px !important;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04) !important;
    border: 1px solid var(--ahp-border) !important;
}
.ahp-portal .ahp-stat-icon {
    width: 56px !important;
    height: 56px !important;
    border-radius: 12px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    font-size: 24px !important;
    flex-shrink: 0 !important;
}
.ahp-portal .ahp-stat-icon.blue { background: #dbeafe !important; color: #1e40af !important; }
.ahp-portal .ahp-stat-icon.green { background: #d1fae5 !important; color: #065f46 !important; }
.ahp-portal .ahp-stat-icon.purple { background: #e9d5ff !important; color: #6b21a8 !important; }
.ahp-portal .ahp-stat-icon.orange { background: #fed7aa !important; color: #c2410c !important; }
.ahp-portal .ahp-stat-label { font-size: 13px !important; color: var(--ahp-text-muted) !important; margin-bottom: 4px !important; }
.ahp-portal .ahp-stat-value { font-size: 28px !important; font-weight: 800 !important; color: var(--ahp-text) !important; }

/* Card */
.ahp-portal .ahp-card {
    background: var(--ahp-card) !important;
    border-radius: 12px !important;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04) !important;
    margin-bottom: 24px !important;
    overflow: hidden !important;
    border: 1px solid var(--ahp-border) !important;
}
.ahp-portal .ahp-card-header {
    padding: 16px 20px !important;
    border-bottom: 1px solid var(--ahp-border) !important;
    display: flex !important;
    justify-content: space-between !important;
    align-items: center !important;
    background: var(--ahp-bg) !important;
}
.ahp-portal .ahp-card-title { margin: 0 !important; font-size: 16px !important; font-weight: 700 !important; color: var(--ahp-text) !important; }
.ahp-portal .ahp-card-body { padding: 20px !important; }

/* Buttons */
.ahp-portal .ahp-btn {
    display: inline-flex !important;
    align-items: center !important;
    gap: 6px !important;
    padding: 10px 18px !important;
    border-radius: 8px !important;
    font-weight: 600 !important;
    font-size: 13px !important;
    cursor: pointer !important;
    border: none !important;
    font-family: inherit !important;
    transition: all 0.2s ease !important;
    text-decoration: none !important;
    line-height: 1.4 !important;
}
.ahp-portal .ahp-btn-primary { background: var(--ahp-primary) !important; color: #fff !important; }
.ahp-portal .ahp-btn-primary:hover { background: var(--ahp-primary-dark) !important; }
.ahp-portal .ahp-btn-success { background: var(--ahp-success) !important; color: #fff !important; }
.ahp-portal .ahp-btn-danger { background: var(--ahp-danger) !important; color: #fff !important; }
.ahp-portal .ahp-btn-secondary { background: var(--ahp-bg) !important; color: var(--ahp-text) !important; border: 1px solid var(--ahp-border) !important; }
.ahp-portal .ahp-btn-sm { padding: 6px 12px !important; font-size: 12px !important; }
.ahp-portal .ahp-btn-icon { width: 32px !important; height: 32px !important; padding: 0 !important; justify-content: center !important; }

/* Table */
.ahp-portal .ahp-table-wrap { overflow-x: auto !important; }
.ahp-portal .ahp-table { width: 100% !important; border-collapse: collapse !important; }
.ahp-portal .ahp-table th, .ahp-portal .ahp-table td { padding: 12px 16px !important; text-align: left !important; border-bottom: 1px solid var(--ahp-border) !important; }
.ahp-portal .ahp-table th { background: var(--ahp-bg) !important; font-weight: 600 !important; font-size: 12px !important; text-transform: uppercase !important; color: var(--ahp-text-muted) !important; }
.ahp-portal .ahp-table tr:hover { background: var(--ahp-bg) !important; }
.ahp-portal .ahp-student-cell { display: flex !important; align-items: center !important; gap: 10px !important; }
.ahp-portal .ahp-student-avatar {
    width: 36px !important;
    height: 36px !important;
    border-radius: 50% !important;
    background: var(--ahp-primary) !important;
    color: #fff !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    font-weight: 700 !important;
    font-size: 13px !important;
    object-fit: cover !important;
}
.ahp-portal .ahp-badge {
    display: inline-block !important;
    padding: 4px 10px !important;
    border-radius: 20px !important;
    font-size: 11px !important;
    font-weight: 600 !important;
}
.ahp-portal .ahp-badge-primary { background: #dbeafe !important; color: #1e40af !important; }
.ahp-portal .ahp-badge-success { background: #d1fae5 !important; color: #065f46 !important; }
.ahp-portal .ahp-cell-actions { display: flex !important; gap: 6px !important; }

/* Toolbar */
.ahp-portal .ahp-toolbar { display: flex !important; gap: 12px !important; margin-bottom: 20px !important; flex-wrap: wrap !important; }
.ahp-portal .ahp-search {
    flex: 1 !important;
    min-width: 250px !important;
    position: relative !important;
}
.ahp-portal .ahp-search input {
    width: 100% !important;
    padding: 10px 14px 10px 40px !important;
    border: 2px solid var(--ahp-border) !important;
    border-radius: 8px !important;
    font-size: 14px !important;
    font-family: inherit !important;
    background: white !important;
    color: var(--ahp-text) !important;
}
.ahp-portal .ahp-search i { position: absolute !important; left: 14px !important; top: 50% !important; transform: translateY(-50%) !important; color: var(--ahp-text-muted) !important; }
.ahp-portal .ahp-filter {
    padding: 10px 14px !important;
    border: 2px solid var(--ahp-border) !important;
    border-radius: 8px !important;
    font-size: 14px !important;
    font-family: inherit !important;
    min-width: 140px !important;
    background: white !important;
}

/* Form */
.ahp-portal .ahp-form-grid { display: grid !important; grid-template-columns: repeat(12, 1fr) !important; gap: 16px !important; }
.ahp-portal .ahp-col-3 { grid-column: span 3 !important; }
.ahp-portal .ahp-col-4 { grid-column: span 4 !important; }
.ahp-portal .ahp-col-6 { grid-column: span 6 !important; }
.ahp-portal .ahp-col-12 { grid-column: span 12 !important; }
.ahp-portal .ahp-form-group { margin-bottom: 0 !important; }
.ahp-portal .ahp-label { display: block !important; font-weight: 600 !important; font-size: 13px !important; margin-bottom: 6px !important; color: var(--ahp-text) !important; }
.ahp-portal .ahp-label.required::after { content: ' *' !important; color: var(--ahp-danger) !important; }
.ahp-portal .ahp-input {
    width: 100% !important;
    padding: 10px 14px !important;
    border: 2px solid var(--ahp-border) !important;
    border-radius: 8px !important;
    font-size: 14px !important;
    font-family: inherit !important;
    transition: border-color 0.2s ease !important;
    background: white !important;
    color: var(--ahp-text) !important;
}
.ahp-portal .ahp-input:focus { outline: none !important; border-color: var(--ahp-primary) !important; }
.ahp-portal textarea.ahp-input { resize: vertical !important; min-height: 80px !important; }

/* Progress Bar */
.ahp-portal .ahp-progress {
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    margin-bottom: 32px !important;
    padding: 20px !important;
    background: var(--ahp-card) !important;
    border-radius: 12px !important;
}
.ahp-portal .ahp-step {
    display: flex !important;
    flex-direction: column !important;
    align-items: center !important;
    position: relative !important;
}
.ahp-portal .ahp-step-num {
    width: 40px !important;
    height: 40px !important;
    border-radius: 50% !important;
    background: var(--ahp-border) !important;
    color: var(--ahp-text-muted) !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    font-weight: 700 !important;
    transition: all 0.3s ease !important;
}
.ahp-portal .ahp-step.active .ahp-step-num { background: var(--ahp-primary) !important; color: #fff !important; }
.ahp-portal .ahp-step.completed .ahp-step-num { background: var(--ahp-success) !important; color: #fff !important; }
.ahp-portal .ahp-step-label { font-size: 12px !important; margin-top: 8px !important; color: var(--ahp-text-muted) !important; font-weight: 500 !important; }
.ahp-portal .ahp-step.active .ahp-step-label { color: var(--ahp-primary) !important; font-weight: 600 !important; }
.ahp-portal .ahp-step-line {
    width: 80px !important;
    height: 3px !important;
    background: var(--ahp-border) !important;
    margin: 0 8px !important;
    margin-bottom: 24px !important;
}
.ahp-portal .ahp-step-line.completed { background: var(--ahp-success) !important; }

/* Form Steps */
.ahp-portal .ahp-form-step { display: none !important; }
.ahp-portal .ahp-form-step.active { display: block !important; }
.ahp-portal .ahp-step-header {
    display: flex !important;
    align-items: center !important;
    gap: 12px !important;
    margin-bottom: 24px !important;
    padding-bottom: 16px !important;
    border-bottom: 2px solid var(--ahp-border) !important;
}
.ahp-portal .ahp-step-header i { font-size: 24px !important; color: var(--ahp-primary) !important; }
.ahp-portal .ahp-step-header h2 { margin: 0 !important; font-size: 20px !important; color: var(--ahp-text) !important; }

/* Sections */
.ahp-portal .ahp-section {
    background: var(--ahp-bg) !important;
    border-radius: 10px !important;
    padding: 20px !important;
    margin-bottom: 20px !important;
}
.ahp-portal .ahp-section-title {
    display: flex !important;
    align-items: center !important;
    gap: 8px !important;
    font-size: 15px !important;
    font-weight: 700 !important;
    margin: 0 0 16px !important;
    color: var(--ahp-text) !important;
}
.ahp-portal .ahp-section-title i { color: var(--ahp-primary) !important; }

/* Subjects */
.ahp-portal .ahp-subject-box {
    background: var(--ahp-card) !important;
    border: 2px solid var(--ahp-border) !important;
    border-radius: 12px !important;
    margin-bottom: 16px !important;
    overflow: hidden !important;
}
.ahp-portal .ahp-subject-header {
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    padding: 12px 16px !important;
    background: var(--ahp-bg) !important;
    border-bottom: 1px solid var(--ahp-border) !important;
}
.ahp-portal .ahp-subject-title { display: flex !important; align-items: center !important; gap: 10px !important; flex: 1 !important; }
.ahp-portal .ahp-subject-title i { color: var(--ahp-primary) !important; }
.ahp-portal .ahp-subject-name {
    flex: 1 !important;
    border: none !important;
    background: transparent !important;
    font-size: 15px !important;
    font-weight: 600 !important;
    font-family: inherit !important;
    color: var(--ahp-text) !important;
}
.ahp-portal .ahp-subject-name:focus { outline: none !important; }
.ahp-portal .ahp-subject-content { padding: 16px !important; }
.ahp-portal .ahp-exam-section { margin-bottom: 20px !important; }
.ahp-portal .ahp-exam-header {
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    margin-bottom: 12px !important;
}
.ahp-portal .ahp-exam-header h4 { margin: 0 !important; font-size: 14px !important; display: flex !important; align-items: center !important; gap: 8px !important; color: var(--ahp-text) !important; }
.ahp-portal .ahp-exam-header h4 i { color: var(--ahp-primary) !important; }
.ahp-marks-row {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
    margin-bottom: 12px;
}
.ahp-marks-group label {
    display: block;
    font-size: 11px;
    color: var(--ahp-text-muted);
    margin-bottom: 4px;
    font-weight: 500;
}
.ahp-marks-group input {
    width: 100%;
    padding: 8px 10px;
    border: 1px solid var(--ahp-border);
    border-radius: 6px;
    font-size: 14px;
    text-align: center;
}
.ahp-monthly-exam {
    background: var(--ahp-bg);
    border-radius: 8px;
    padding: 12px;
    margin-bottom: 10px;
}
.ahp-monthly-header {
    display: flex;
    gap: 10px;
    margin-bottom: 10px;
}
.ahp-month-name {
    flex: 1;
    padding: 6px 10px;
    border: 1px solid var(--ahp-border);
    border-radius: 6px;
    font-size: 13px;
}
.ahp-btn-xs { padding: 4px 8px; font-size: 11px; }
.ahp-empty-subjects {
    text-align: center;
    padding: 40px;
    color: var(--ahp-text-muted);
}
.ahp-empty-subjects i { font-size: 48px; margin-bottom: 12px; display: block; opacity: 0.5; }

/* Fee Cards */
.ahp-fee-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 20px; }
.ahp-fee-card {
    background: var(--ahp-bg);
    border-radius: 12px;
    padding: 20px;
    text-align: center;
}
.ahp-fee-icon { font-size: 24px; color: var(--ahp-primary); margin-bottom: 8px; }
.ahp-fee-card h4 { margin: 0 0 12px; font-size: 14px; }
.ahp-fee-input { display: flex; align-items: center; gap: 8px; }
.ahp-currency { font-weight: 600; color: var(--ahp-text-muted); }
.ahp-fee-input input {
    flex: 1;
    padding: 8px;
    border: 1px solid var(--ahp-border);
    border-radius: 6px;
    text-align: center;
    font-size: 16px;
    font-weight: 600;
}
.ahp-fee-summary {
    background: var(--ahp-bg);
    border-radius: 12px;
    padding: 20px;
}
.ahp-fee-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px dashed var(--ahp-border); }
.ahp-fee-row:last-child { border: none; font-size: 18px; font-weight: 700; color: var(--ahp-primary); }
.ahp-checkbox-group { display: flex; flex-wrap: wrap; gap: 20px; }
.ahp-checkbox-label {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
}
.ahp-checkbox-label input { width: 18px; height: 18px; }

/* Ratings */
.ahp-rating-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; }
.ahp-rating-item { background: var(--ahp-bg); padding: 16px; border-radius: 10px; }
.ahp-rating-label { display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 600; margin-bottom: 10px; }
.ahp-rating-label i { color: var(--ahp-primary); }
.ahp-stars { display: flex; gap: 4px; }
.ahp-star {
    font-size: 20px;
    color: var(--ahp-border);
    cursor: pointer;
    transition: color 0.2s;
}
.ahp-star.active, .ahp-star:hover { color: #fbbf24; }

/* Navigation */
.ahp-form-nav {
    display: flex;
    justify-content: space-between;
    padding-top: 24px;
    border-top: 2px solid var(--ahp-border);
    margin-top: 24px;
}

/* Toast */
.ahp-toast {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 14px 24px;
    border-radius: 10px;
    color: #fff;
    font-weight: 600;
    z-index: 9999;
    display: none;
    animation: slideIn 0.3s;
}
.ahp-toast.success { background: var(--ahp-success); }
.ahp-toast.error { background: var(--ahp-danger); }
@keyframes slideIn { from { transform: translateX(100%); } to { transform: translateX(0); } }

/* Loading */
.ahp-loading { display: flex; justify-content: center; padding: 40px; }
.ahp-spinner {
    width: 40px;
    height: 40px;
    border: 3px solid var(--ahp-border);
    border-top-color: var(--ahp-primary);
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* Pagination */
.ahp-portal .ahp-pagination { display: flex !important; justify-content: center !important; gap: 6px !important; padding: 20px !important; }

/* Mobile Menu Toggle */
.ahp-portal .ahp-menu-toggle {
    display: none !important;
    position: fixed !important;
    top: 16px !important;
    left: 16px !important;
    z-index: 1001 !important;
    width: 44px !important;
    height: 44px !important;
    border-radius: 12px !important;
    background: var(--ahp-sidebar) !important;
    color: white !important;
    border: none !important;
    font-size: 20px !important;
    cursor: pointer !important;
    align-items: center !important;
    justify-content: center !important;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important;
}
.ahp-portal .ahp-overlay {
    display: none !important;
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    width: 100% !important;
    height: 100% !important;
    background: rgba(0,0,0,0.5) !important;
    z-index: 99 !important;
}
.ahp-portal .ahp-overlay.open { display: block !important; }

/* Responsive */
@media (max-width: 1024px) {
    .ahp-portal .ahp-menu-toggle { display: flex !important; }
    .ahp-portal .ahp-main { padding: 24px 20px !important; }
    .ahp-portal .ahp-form-grid { grid-template-columns: repeat(6, 1fr) !important; }
    .ahp-portal .ahp-col-3, .ahp-portal .ahp-col-4 { grid-column: span 3 !important; }
}
@media (max-width: 768px) {
    .ahp-portal .ahp-header-inner { padding: 0 16px !important; }
    .ahp-portal .ahp-menu-toggle { display: flex !important; }

    /* CRITICAL FIX: Make logo text responsive */
    .ahp-portal .ahp-logo span { display: none !important; }

    /* CRITICAL FIX: Make user menu responsive instead of hiding it */
    .ahp-portal .ahp-user-info {
        padding: 6px 10px !important;
        gap: 8px !important;
    }

    .ahp-portal .ahp-user-name {
        display: none !important;
    }

    .ahp-portal .ahp-user-role {
        display: none !important;
    }

    .ahp-portal .ahp-avatar {
        width: 32px !important;
        height: 32px !important;
        font-size: 12px !important;
    }

    .ahp-portal .ahp-logout-btn {
        padding: 8px 12px !important;
        font-size: 12px !important;
    }

    .ahp-portal .ahp-logout-btn span {
        display: none !important;
    }

    .ahp-portal .ahp-logout-btn i {
        margin-right: 0 !important;
    }

    .ahp-portal .ahp-nav {
        display: none !important;
        position: absolute !important;
        top: 100% !important;
        left: 0 !important;
        right: 0 !important;
        background: white !important;
        flex-direction: column !important;
        padding: 16px !important;
        border-bottom: 1px solid var(--ahp-border) !important;
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1) !important;
    }

    .ahp-portal .ahp-nav.open { display: flex !important; }

    .ahp-portal .ahp-nav-item {
        border-bottom: none !important;
        border-radius: 8px !important;
        padding: 12px 16px !important;
    }

    .ahp-portal .ahp-nav-item.active {
        background: var(--ahp-primary-light) !important;
    }

    .ahp-portal .ahp-main { padding: 24px 16px !important; }
    .ahp-portal .ahp-form-grid { grid-template-columns: 1fr !important; }
    .ahp-portal .ahp-col-3, .ahp-portal .ahp-col-4, .ahp-portal .ahp-col-6 { grid-column: span 1 !important; }
    .ahp-portal .ahp-marks-row { grid-template-columns: repeat(2, 1fr) !important; }
    .ahp-portal .ahp-stats { grid-template-columns: 1fr !important; }
    .ahp-portal .ahp-progress { flex-wrap: wrap !important; }
    .ahp-portal .ahp-step-line { display: none !important; }
    .ahp-portal .ahp-title { font-size: 20px !important; }
}

@media (max-width: 480px) {
    /* Further mobile optimization for very small screens */
    .ahp-portal .ahp-logo i {
        font-size: 20px !important;
    }

    .ahp-portal .ahp-user-menu {
        gap: 6px !important;
    }

    .ahp-portal .ahp-logout-btn {
        padding: 6px 8px !important;
    }

    .ahp-portal .ahp-stats { grid-template-columns: 1fr !important; }
    .ahp-portal .ahp-marks-row { grid-template-columns: 1fr !important; }
}
/* FIX #5: Pending count badges for navigation tabs */
.ahp-badge {
    display: inline-block;
    background: linear-gradient(135deg, #f56565, #fc8181);
    color: white;
    font-size: 11px;
    font-weight: 700;
    padding: 2px 7px;
    border-radius: 12px;
    margin-left: 6px;
    min-width: 18px;
    text-align: center;
    box-shadow: 0 2px 4px rgba(245, 101, 101, 0.3);
    animation: ahp-badge-pulse 2s ease-in-out infinite;
}
@keyframes ahp-badge-pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}
</style>

<div class="ahp-portal">
    <!-- ==================== TOP HEADER ==================== -->
    <header class="ahp-top-header">
        <div class="ahp-header-inner">
            <div class="ahp-header-top">
                <div class="ahp-logo">
                    <i class="fas fa-graduation-cap"></i>
                    <span>School Admin Portal</span>
                </div>

                <div class="ahp-user-menu">
                    <div class="ahp-user-info">
                        <div class="ahp-avatar"><?php echo strtoupper(substr($current_user->display_name, 0, 1)); ?></div>
                        <span class="ahp-user-name"><?php echo esc_html($current_user->display_name); ?></span>
                        <span class="ahp-user-role"><?php echo esc_html(ucfirst($current_user->roles[0] ?? 'User')); ?></span>
                    </div>
                    <a href="<?php echo wp_logout_url(home_url()); ?>" class="ahp-logout-btn">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </div>

                <button class="ahp-menu-toggle" onclick="toggleMobileNav()">
                    <i class="fas fa-bars"></i>
                </button>
            </div>

            <!-- Navigation Tabs -->
            <nav class="ahp-nav" id="ahpNav">
                <button class="ahp-nav-item active" data-panel="dashboard">
                    <i class="fas fa-home"></i>
                    <span><?php _e('Dashboard', 'al-huffaz-portal'); ?></span>
                </button>
                <button class="ahp-nav-item" data-panel="students">
                    <i class="fas fa-users"></i>
                    <span><?php _e('Students', 'al-huffaz-portal'); ?></span>
                </button>
                <button class="ahp-nav-item" data-panel="add-student">
                    <i class="fas fa-user-plus"></i>
                    <span><?php _e('Add Student', 'al-huffaz-portal'); ?></span>
                </button>
                <?php if ($can_manage_sponsors): ?>
                <button class="ahp-nav-item" data-panel="sponsors">
                    <i class="fas fa-hand-holding-heart"></i>
                    <span><?php _e('Sponsors', 'al-huffaz-portal'); ?></span>
                    <span class="ahp-nav-badge danger" style="display:<?php echo $pending_sponsors_count > 0 ? 'inline-block' : 'none'; ?>"><?php echo $pending_sponsors_count; ?></span>
                </button>
                <button class="ahp-nav-item" data-panel="sponsor-users">
                    <i class="fas fa-users-cog"></i>
                    <span><?php _e('Sponsor Users', 'al-huffaz-portal'); ?></span>
                </button>
                <?php endif; ?>
                <?php if ($can_manage_payments): ?>
                <button class="ahp-nav-item" data-panel="payments">
                    <i class="fas fa-credit-card"></i>
                    <span><?php _e('Payments', 'al-huffaz-portal'); ?></span>
                    <?php if ($pending_payments_count > 0): ?>
                    <span class="ahp-nav-badge warning"><?php echo $pending_payments_count; ?></span>
                    <?php endif; ?>
                </button>
                <?php endif; ?>
                <?php if ($can_manage_staff): ?>
                <button class="ahp-nav-item" data-panel="staff">
                    <i class="fas fa-users-cog"></i>
                    <span><?php _e('Users', 'al-huffaz-portal'); ?></span>
                </button>
                <?php endif; ?>
                <button class="ahp-nav-item" data-panel="history">
                    <i class="fas fa-history"></i>
                    <span><?php _e('History & Recovery', 'al-huffaz-portal'); ?></span>
                </button>
            </nav>
        </div>
    </header>

    <div class="ahp-wrapper">
        <!-- Main Content -->
        <main class="ahp-main">
            <!-- ==================== DASHBOARD PANEL ==================== -->
            <div class="ahp-panel active" id="panel-dashboard">
                <div class="ahp-header">
                    <h1 class="ahp-title"><?php _e('Dashboard', 'al-huffaz-portal'); ?></h1>
                    <div class="ahp-actions">
                        <button class="ahp-btn ahp-btn-primary" onclick="showPanel('add-student')">
                            <i class="fas fa-plus"></i> <?php _e('Add Student', 'al-huffaz-portal'); ?>
                        </button>
                    </div>
                </div>

                <div class="ahp-stats">
                    <div class="ahp-stat">
                        <div class="ahp-stat-icon blue"><i class="fas fa-user-graduate"></i></div>
                        <div>
                            <div class="ahp-stat-label"><?php _e('Total Students', 'al-huffaz-portal'); ?></div>
                            <div class="ahp-stat-value"><?php echo $total_students; ?></div>
                        </div>
                    </div>
                    <div class="ahp-stat">
                        <div class="ahp-stat-icon green"><i class="fas fa-hand-holding-heart"></i></div>
                        <div>
                            <div class="ahp-stat-label"><?php _e('Total Sponsors', 'al-huffaz-portal'); ?></div>
                            <div class="ahp-stat-value"><?php echo $total_sponsors; ?></div>
                        </div>
                    </div>
                    <div class="ahp-stat">
                        <div class="ahp-stat-icon purple"><i class="fas fa-quran"></i></div>
                        <div>
                            <div class="ahp-stat-label"><?php _e('Hifz Students', 'al-huffaz-portal'); ?></div>
                            <div class="ahp-stat-value"><?php echo $hifz_count; ?></div>
                        </div>
                    </div>
                    <div class="ahp-stat">
                        <div class="ahp-stat-icon orange"><i class="fas fa-book-reader"></i></div>
                        <div>
                            <div class="ahp-stat-label"><?php _e('Nazra Students', 'al-huffaz-portal'); ?></div>
                            <div class="ahp-stat-value"><?php echo $nazra_count; ?></div>
                        </div>
                    </div>
                    <?php if ($can_manage_sponsors): ?>
                    <div class="ahp-stat" onclick="showPanel('sponsor-users'); document.getElementById('filterUserStatus').value='inactive'; loadSponsorUsers();" style="cursor:pointer;" title="Click to view inactive sponsors">
                        <div class="ahp-stat-icon gray"><i class="fas fa-user-slash"></i></div>
                        <div>
                            <div class="ahp-stat-label"><?php _e('Inactive Sponsors', 'al-huffaz-portal'); ?></div>
                            <div class="ahp-stat-value"><?php echo $inactive_sponsors_count; ?></div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="ahp-card">
                    <div class="ahp-card-header">
                        <h3 class="ahp-card-title"><i class="fas fa-clock"></i> <?php _e('Recent Students', 'al-huffaz-portal'); ?></h3>
                        <button class="ahp-btn ahp-btn-secondary ahp-btn-sm" onclick="showPanel('students')"><?php _e('View All', 'al-huffaz-portal'); ?></button>
                    </div>
                    <div class="ahp-card-body" style="padding:0;">
                        <div class="ahp-table-wrap">
                            <table class="ahp-table">
                                <thead>
                                    <tr>
                                        <th><?php _e('Student', 'al-huffaz-portal'); ?></th>
                                        <th><?php _e('GR #', 'al-huffaz-portal'); ?></th>
                                        <th><?php _e('Grade', 'al-huffaz-portal'); ?></th>
                                        <th><?php _e('Category', 'al-huffaz-portal'); ?></th>
                                        <th><?php _e('Actions', 'al-huffaz-portal'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($recent_students)): ?>
                                    <tr><td colspan="5" style="text-align:center;padding:40px;color:var(--ahp-text-muted);"><?php _e('No students yet', 'al-huffaz-portal'); ?></td></tr>
                                    <?php else: foreach ($recent_students as $s):
                                        $gr = get_post_meta($s->ID, 'gr_number', true);
                                        $grade = get_post_meta($s->ID, 'grade_level', true);
                                        $cat = get_post_meta($s->ID, 'islamic_studies_category', true);
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="ahp-student-cell">
                                                <div class="ahp-student-avatar"><?php echo strtoupper(substr($s->post_title, 0, 1)); ?></div>
                                                <span><?php echo esc_html($s->post_title); ?></span>
                                            </div>
                                        </td>
                                        <td><?php echo esc_html($gr ?: '-'); ?></td>
                                        <td><span class="ahp-badge ahp-badge-primary"><?php echo esc_html(strtoupper($grade ?: '-')); ?></span></td>
                                        <td><span class="ahp-badge ahp-badge-success"><?php echo esc_html(ucfirst($cat ?: '-')); ?></span></td>
                                        <td>
                                            <div class="ahp-cell-actions">
                                                <a href="<?php echo get_permalink($s->ID); ?>" class="ahp-btn ahp-btn-secondary ahp-btn-icon" target="_blank" title="View"><i class="fas fa-eye"></i></a>
                                                <button class="ahp-btn ahp-btn-primary ahp-btn-icon" onclick="editStudent(<?php echo $s->ID; ?>)" title="Edit"><i class="fas fa-edit"></i></button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ==================== STUDENTS LIST PANEL ==================== -->
            <div class="ahp-panel" id="panel-students">
                <div class="ahp-header">
                    <h1 class="ahp-title"><?php _e('Students', 'al-huffaz-portal'); ?></h1>
                    <div class="ahp-actions">
                        <button class="ahp-btn ahp-btn-primary" onclick="showPanel('add-student')">
                            <i class="fas fa-plus"></i> <?php _e('Add Student', 'al-huffaz-portal'); ?>
                        </button>
                    </div>
                </div>

                <div class="ahp-toolbar">
                    <div class="ahp-search">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchInput" placeholder="<?php _e('Search by name or GR number...', 'al-huffaz-portal'); ?>">
                    </div>
                    <select class="ahp-filter" id="filterGrade">
                        <option value=""><?php _e('All Grades', 'al-huffaz-portal'); ?></option>
                        <option value="kg1">KG 1</option>
                        <option value="kg2">KG 2</option>
                        <option value="class1">Class 1</option>
                        <option value="class2">Class 2</option>
                        <option value="class3">Class 3</option>
                        <option value="level1">Level 1</option>
                        <option value="level2">Level 2</option>
                        <option value="level3">Level 3</option>
                        <option value="shb">SHB</option>
                        <option value="shg">SHG</option>
                    </select>
                    <select class="ahp-filter" id="filterCategory">
                        <option value=""><?php _e('All Categories', 'al-huffaz-portal'); ?></option>
                        <option value="hifz">Hifz</option>
                        <option value="nazra">Nazra</option>
                        <option value="qaidah">Qaidah</option>
                    </select>
                </div>

                <div class="ahp-card">
                    <div class="ahp-card-body" style="padding:0;">
                        <div class="ahp-table-wrap">
                            <table class="ahp-table">
                                <thead>
                                    <tr>
                                        <th><?php _e('Student', 'al-huffaz-portal'); ?></th>
                                        <th><?php _e('GR #', 'al-huffaz-portal'); ?></th>
                                        <th><?php _e('Grade', 'al-huffaz-portal'); ?></th>
                                        <th><?php _e('Category', 'al-huffaz-portal'); ?></th>
                                        <th><?php _e('Father', 'al-huffaz-portal'); ?></th>
                                        <th><?php _e('Actions', 'al-huffaz-portal'); ?></th>
                                    </tr>
                                </thead>
                                <tbody id="studentsTableBody">
                                    <tr><td colspan="6" class="ahp-loading"><div class="ahp-spinner"></div></td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div id="pagination" class="ahp-pagination"></div>
            </div>

            <!-- ==================== ADD/EDIT STUDENT PANEL ==================== -->
            <div class="ahp-panel" id="panel-add-student">
                <div class="ahp-header">
                    <h1 class="ahp-title" id="formTitle"><?php echo $is_edit ? __('Edit Student', 'al-huffaz-portal') : __('Add New Student', 'al-huffaz-portal'); ?></h1>
                    <div class="ahp-actions">
                        <button class="ahp-btn ahp-btn-secondary" onclick="showPanel('students')">
                            <i class="fas fa-arrow-left"></i> <?php _e('Back to Students', 'al-huffaz-portal'); ?>
                        </button>
                    </div>
                </div>

                <!-- Progress Steps -->
                <div class="ahp-progress">
                    <div class="ahp-step active" data-step="1">
                        <div class="ahp-step-num">1</div>
                        <div class="ahp-step-label"><?php _e('Basic Info', 'al-huffaz-portal'); ?></div>
                    </div>
                    <div class="ahp-step-line"></div>
                    <div class="ahp-step" data-step="2">
                        <div class="ahp-step-num">2</div>
                        <div class="ahp-step-label"><?php _e('Family', 'al-huffaz-portal'); ?></div>
                    </div>
                    <div class="ahp-step-line"></div>
                    <div class="ahp-step" data-step="3">
                        <div class="ahp-step-num">3</div>
                        <div class="ahp-step-label"><?php _e('Academic', 'al-huffaz-portal'); ?></div>
                    </div>
                    <div class="ahp-step-line"></div>
                    <div class="ahp-step" data-step="4">
                        <div class="ahp-step-num">4</div>
                        <div class="ahp-step-label"><?php _e('Fees', 'al-huffaz-portal'); ?></div>
                    </div>
                    <div class="ahp-step-line"></div>
                    <div class="ahp-step" data-step="5">
                        <div class="ahp-step-num">5</div>
                        <div class="ahp-step-label"><?php _e('Health', 'al-huffaz-portal'); ?></div>
                    </div>
                </div>

                <!-- Form -->
                <form id="studentForm" enctype="multipart/form-data">
                    <input type="hidden" name="student_id" id="studentId" value="<?php echo $is_edit ? $edit_id : 0; ?>">
                    <input type="hidden" name="action" value="alhuffaz_save_student">
                    <input type="hidden" name="nonce" value="<?php echo $nonce; ?>">

                    <!-- STEP 1: Basic Information -->
                    <div class="ahp-form-step active" data-step="1">
                        <div class="ahp-card">
                            <div class="ahp-card-body">
                                <div class="ahp-step-header">
                                    <i class="fas fa-user"></i>
                                    <h2><?php _e('Basic Information', 'al-huffaz-portal'); ?></h2>
                                </div>
                                <div class="ahp-form-grid">
                                    <div class="ahp-form-group ahp-col-6">
                                        <label class="ahp-label required"><?php _e('Student Full Name', 'al-huffaz-portal'); ?></label>
                                        <input type="text" name="student_name" class="ahp-input" required value="<?php echo esc_attr($student_data['student_name'] ?? ''); ?>">
                                    </div>
                                    <div class="ahp-form-group ahp-col-3">
                                        <label class="ahp-label required"><?php _e('GR Number', 'al-huffaz-portal'); ?></label>
                                        <input type="text" name="gr_number" class="ahp-input" required value="<?php echo esc_attr($student_data['gr_number'] ?? ''); ?>">
                                    </div>
                                    <div class="ahp-form-group ahp-col-3">
                                        <label class="ahp-label"><?php _e('Roll Number', 'al-huffaz-portal'); ?></label>
                                        <input type="text" name="roll_number" class="ahp-input" value="<?php echo esc_attr($student_data['roll_number'] ?? ''); ?>">
                                    </div>
                                    <div class="ahp-form-group ahp-col-4">
                                        <label class="ahp-label required"><?php _e('Gender', 'al-huffaz-portal'); ?></label>
                                        <select name="gender" class="ahp-input" required>
                                            <option value="">Select Gender</option>
                                            <option value="male" <?php echo ahp_fe_selected('gender', 'male', $student_data); ?>>Male</option>
                                            <option value="female" <?php echo ahp_fe_selected('gender', 'female', $student_data); ?>>Female</option>
                                        </select>
                                    </div>
                                    <div class="ahp-form-group ahp-col-4">
                                        <label class="ahp-label"><?php _e('Date of Birth', 'al-huffaz-portal'); ?></label>
                                        <input type="date" name="date_of_birth" class="ahp-input" value="<?php echo esc_attr($student_data['date_of_birth'] ?? ''); ?>">
                                    </div>
                                    <div class="ahp-form-group ahp-col-4">
                                        <label class="ahp-label"><?php _e('Admission Date', 'al-huffaz-portal'); ?></label>
                                        <input type="date" name="admission_date" class="ahp-input" value="<?php echo esc_attr($student_data['admission_date'] ?? ''); ?>">
                                    </div>
                                    <div class="ahp-form-group ahp-col-4">
                                        <label class="ahp-label"><?php _e('Grade Level', 'al-huffaz-portal'); ?></label>
                                        <select name="grade_level" class="ahp-input">
                                            <option value="">Select Grade</option>
                                            <option value="kg1" <?php echo ahp_fe_selected('grade_level', 'kg1', $student_data); ?>>KG 1</option>
                                            <option value="kg2" <?php echo ahp_fe_selected('grade_level', 'kg2', $student_data); ?>>KG 2</option>
                                            <option value="class1" <?php echo ahp_fe_selected('grade_level', 'class1', $student_data); ?>>Class 1</option>
                                            <option value="class2" <?php echo ahp_fe_selected('grade_level', 'class2', $student_data); ?>>Class 2</option>
                                            <option value="class3" <?php echo ahp_fe_selected('grade_level', 'class3', $student_data); ?>>Class 3</option>
                                            <option value="level1" <?php echo ahp_fe_selected('grade_level', 'level1', $student_data); ?>>Level 1</option>
                                            <option value="level2" <?php echo ahp_fe_selected('grade_level', 'level2', $student_data); ?>>Level 2</option>
                                            <option value="level3" <?php echo ahp_fe_selected('grade_level', 'level3', $student_data); ?>>Level 3</option>
                                            <option value="shb" <?php echo ahp_fe_selected('grade_level', 'shb', $student_data); ?>>SHB</option>
                                            <option value="shg" <?php echo ahp_fe_selected('grade_level', 'shg', $student_data); ?>>SHG</option>
                                        </select>
                                    </div>
                                    <div class="ahp-form-group ahp-col-4">
                                        <label class="ahp-label"><?php _e('Islamic Studies', 'al-huffaz-portal'); ?></label>
                                        <select name="islamic_studies_category" class="ahp-input">
                                            <option value="">Select Category</option>
                                            <option value="hifz" <?php echo ahp_fe_selected('islamic_studies_category', 'hifz', $student_data); ?>>Hifz</option>
                                            <option value="nazra" <?php echo ahp_fe_selected('islamic_studies_category', 'nazra', $student_data); ?>>Nazra</option>
                                            <option value="qaidah" <?php echo ahp_fe_selected('islamic_studies_category', 'qaidah', $student_data); ?>>Qaidah</option>
                                        </select>
                                    </div>
                                    <div class="ahp-form-group ahp-col-4">
                                        <label class="ahp-label"><?php _e('Student Photo', 'al-huffaz-portal'); ?></label>
                                        <input type="file" name="student_photo" class="ahp-input" accept="image/*">
                                    </div>
                                    <div class="ahp-form-group ahp-col-6">
                                        <label class="ahp-label"><?php _e('Permanent Address', 'al-huffaz-portal'); ?></label>
                                        <textarea name="permanent_address" class="ahp-input" rows="2"><?php echo esc_textarea($student_data['permanent_address'] ?? ''); ?></textarea>
                                    </div>
                                    <div class="ahp-form-group ahp-col-6">
                                        <label class="ahp-label"><?php _e('Current Address', 'al-huffaz-portal'); ?></label>
                                        <textarea name="current_address" class="ahp-input" rows="2"><?php echo esc_textarea($student_data['current_address'] ?? ''); ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- STEP 2: Family Information -->
                    <div class="ahp-form-step" data-step="2">
                        <div class="ahp-card">
                            <div class="ahp-card-body">
                                <div class="ahp-step-header">
                                    <i class="fas fa-users"></i>
                                    <h2><?php _e('Family Information', 'al-huffaz-portal'); ?></h2>
                                </div>

                                <div class="ahp-section">
                                    <h3 class="ahp-section-title"><i class="fas fa-user-tie"></i> <?php _e("Father's Information", 'al-huffaz-portal'); ?></h3>
                                    <div class="ahp-form-grid">
                                        <div class="ahp-form-group ahp-col-4">
                                            <label class="ahp-label"><?php _e("Father's Name", 'al-huffaz-portal'); ?></label>
                                            <input type="text" name="father_name" class="ahp-input" value="<?php echo esc_attr($student_data['father_name'] ?? ''); ?>">
                                        </div>
                                        <div class="ahp-form-group ahp-col-4">
                                            <label class="ahp-label"><?php _e("Father's CNIC", 'al-huffaz-portal'); ?></label>
                                            <input type="text" name="father_cnic" class="ahp-input" placeholder="XXXXX-XXXXXXX-X" value="<?php echo esc_attr($student_data['father_cnic'] ?? ''); ?>">
                                        </div>
                                        <div class="ahp-form-group ahp-col-4">
                                            <label class="ahp-label"><?php _e("Father's Email", 'al-huffaz-portal'); ?></label>
                                            <input type="email" name="father_email" class="ahp-input" value="<?php echo esc_attr($student_data['father_email'] ?? ''); ?>">
                                        </div>
                                    </div>
                                </div>

                                <div class="ahp-section">
                                    <h3 class="ahp-section-title"><i class="fas fa-user-shield"></i> <?php _e('Guardian Information', 'al-huffaz-portal'); ?></h3>
                                    <div class="ahp-form-grid">
                                        <div class="ahp-form-group ahp-col-4">
                                            <label class="ahp-label"><?php _e('Guardian Name', 'al-huffaz-portal'); ?></label>
                                            <input type="text" name="guardian_name" class="ahp-input" value="<?php echo esc_attr($student_data['guardian_name'] ?? ''); ?>">
                                        </div>
                                        <div class="ahp-form-group ahp-col-4">
                                            <label class="ahp-label"><?php _e('Guardian CNIC', 'al-huffaz-portal'); ?></label>
                                            <input type="text" name="guardian_cnic" class="ahp-input" value="<?php echo esc_attr($student_data['guardian_cnic'] ?? ''); ?>">
                                        </div>
                                        <div class="ahp-form-group ahp-col-4">
                                            <label class="ahp-label"><?php _e('Relationship', 'al-huffaz-portal'); ?></label>
                                            <select name="relationship_to_student" class="ahp-input">
                                                <option value="">Select</option>
                                                <option value="father" <?php echo ahp_fe_selected('relationship_to_student', 'father', $student_data); ?>>Father</option>
                                                <option value="mother" <?php echo ahp_fe_selected('relationship_to_student', 'mother', $student_data); ?>>Mother</option>
                                                <option value="uncle" <?php echo ahp_fe_selected('relationship_to_student', 'uncle', $student_data); ?>>Uncle</option>
                                                <option value="aunt" <?php echo ahp_fe_selected('relationship_to_student', 'aunt', $student_data); ?>>Aunt</option>
                                                <option value="grandparent" <?php echo ahp_fe_selected('relationship_to_student', 'grandparent', $student_data); ?>>Grandparent</option>
                                                <option value="other" <?php echo ahp_fe_selected('relationship_to_student', 'other', $student_data); ?>>Other</option>
                                            </select>
                                        </div>
                                        <div class="ahp-form-group ahp-col-4">
                                            <label class="ahp-label"><?php _e('Guardian Email', 'al-huffaz-portal'); ?></label>
                                            <input type="email" name="guardian_email" class="ahp-input" value="<?php echo esc_attr($student_data['guardian_email'] ?? ''); ?>">
                                        </div>
                                        <div class="ahp-form-group ahp-col-4">
                                            <label class="ahp-label"><?php _e('Guardian Phone', 'al-huffaz-portal'); ?></label>
                                            <input type="tel" name="guardian_phone" class="ahp-input" value="<?php echo esc_attr($student_data['guardian_phone'] ?? ''); ?>">
                                        </div>
                                        <div class="ahp-form-group ahp-col-4">
                                            <label class="ahp-label"><?php _e('Guardian WhatsApp', 'al-huffaz-portal'); ?></label>
                                            <input type="tel" name="guardian_whatsapp" class="ahp-input" value="<?php echo esc_attr($student_data['guardian_whatsapp'] ?? ''); ?>">
                                        </div>
                                    </div>
                                </div>

                                <div class="ahp-section">
                                    <h3 class="ahp-section-title"><i class="fas fa-ambulance"></i> <?php _e('Emergency Contact', 'al-huffaz-portal'); ?></h3>
                                    <div class="ahp-form-grid">
                                        <div class="ahp-form-group ahp-col-6">
                                            <label class="ahp-label"><?php _e('Emergency Phone', 'al-huffaz-portal'); ?></label>
                                            <input type="tel" name="emergency_contact" class="ahp-input" value="<?php echo esc_attr($student_data['emergency_contact'] ?? ''); ?>">
                                        </div>
                                        <div class="ahp-form-group ahp-col-6">
                                            <label class="ahp-label"><?php _e('Emergency WhatsApp', 'al-huffaz-portal'); ?></label>
                                            <input type="tel" name="emergency_whatsapp" class="ahp-input" value="<?php echo esc_attr($student_data['emergency_whatsapp'] ?? ''); ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- STEP 3: Academic Information -->
                    <div class="ahp-form-step" data-step="3">
                        <div class="ahp-card">
                            <div class="ahp-card-body">
                                <div class="ahp-step-header">
                                    <i class="fas fa-graduation-cap"></i>
                                    <h2><?php _e('Academic Information', 'al-huffaz-portal'); ?></h2>
                                </div>

                                <div class="ahp-section">
                                    <h3 class="ahp-section-title"><i class="fas fa-calendar"></i> <?php _e('Academic Period', 'al-huffaz-portal'); ?></h3>
                                    <div class="ahp-form-grid">
                                        <div class="ahp-form-group ahp-col-6">
                                            <label class="ahp-label"><?php _e('Academic Year', 'al-huffaz-portal'); ?></label>
                                            <select name="academic_year" class="ahp-input">
                                                <option value="">Select Year</option>
                                                <?php for ($y = date('Y') + 1; $y >= 2020; $y--): ?>
                                                <option value="<?php echo $y . '-' . ($y+1); ?>" <?php echo ahp_fe_selected('academic_year', $y . '-' . ($y+1), $student_data); ?>><?php echo $y . '-' . ($y+1); ?></option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                        <div class="ahp-form-group ahp-col-6">
                                            <label class="ahp-label"><?php _e('Academic Term', 'al-huffaz-portal'); ?></label>
                                            <select name="academic_term" class="ahp-input">
                                                <option value="">Select Term</option>
                                                <option value="mid" <?php echo ahp_fe_selected('academic_term', 'mid', $student_data); ?>>Mid Term</option>
                                                <option value="annual" <?php echo ahp_fe_selected('academic_term', 'annual', $student_data); ?>>Annual</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="ahp-section">
                                    <h3 class="ahp-section-title"><i class="fas fa-clipboard-check"></i> <?php _e('Attendance', 'al-huffaz-portal'); ?></h3>
                                    <div class="ahp-form-grid">
                                        <div class="ahp-form-group ahp-col-4">
                                            <label class="ahp-label"><?php _e('Total School Days', 'al-huffaz-portal'); ?></label>
                                            <input type="number" name="total_school_days" id="totalDays" class="ahp-input" min="0" value="<?php echo esc_attr($student_data['total_school_days'] ?? ''); ?>">
                                        </div>
                                        <div class="ahp-form-group ahp-col-4">
                                            <label class="ahp-label"><?php _e('Present Days', 'al-huffaz-portal'); ?></label>
                                            <input type="number" name="present_days" id="presentDays" class="ahp-input" min="0" value="<?php echo esc_attr($student_data['present_days'] ?? ''); ?>">
                                        </div>
                                        <div class="ahp-form-group ahp-col-4">
                                            <label class="ahp-label"><?php _e('Attendance %', 'al-huffaz-portal'); ?></label>
                                            <div id="attendanceDisplay" style="padding:10px;background:var(--ahp-bg);border-radius:8px;text-align:center;font-weight:700;font-size:18px;">--%</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="ahp-section">
                                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
                                        <h3 class="ahp-section-title" style="margin:0;"><i class="fas fa-book"></i> <?php _e('Subject Performance', 'al-huffaz-portal'); ?></h3>
                                        <button type="button" id="addSubjectBtn" class="ahp-btn ahp-btn-success ahp-btn-sm">
                                            <i class="fas fa-plus"></i> <?php _e('Add Subject', 'al-huffaz-portal'); ?>
                                        </button>
                                    </div>
                                    <div id="subjectsContainer">
                                        <?php if (!empty($subjects)): foreach ($subjects as $idx => $subj): ?>
                                        <div class="ahp-subject-box" data-index="<?php echo $idx; ?>">
                                            <div class="ahp-subject-header">
                                                <div class="ahp-subject-title">
                                                    <i class="fas fa-book"></i>
                                                    <input type="text" name="subjects[<?php echo $idx; ?>][name]" class="ahp-subject-name" placeholder="Subject Name" value="<?php echo esc_attr($subj['name'] ?? ''); ?>">
                                                </div>
                                                <button type="button" class="ahp-btn ahp-btn-danger ahp-btn-sm remove-subject"><i class="fas fa-trash"></i></button>
                                            </div>
                                            <div class="ahp-subject-content">
                                                <!-- Monthly Exams -->
                                                <div class="ahp-exam-section">
                                                    <div class="ahp-exam-header">
                                                        <h4><i class="fas fa-calendar-alt"></i> <?php _e('Monthly Exams', 'al-huffaz-portal'); ?></h4>
                                                        <button type="button" class="ahp-btn ahp-btn-primary ahp-btn-sm add-monthly" data-subject="<?php echo $idx; ?>"><i class="fas fa-plus"></i> Add Month</button>
                                                    </div>
                                                    <div class="monthly-container" data-subject="<?php echo $idx; ?>">
                                                        <?php if (!empty($subj['monthly_exams'])): foreach ($subj['monthly_exams'] as $midx => $mon): ?>
                                                        <div class="ahp-monthly-exam" data-month="<?php echo $midx; ?>">
                                                            <div class="ahp-monthly-header">
                                                                <input type="text" name="subjects[<?php echo $idx; ?>][monthly_exams][<?php echo $midx; ?>][month_name]" class="ahp-month-name" placeholder="Month Name" value="<?php echo esc_attr($mon['month_name'] ?? ''); ?>">
                                                                <button type="button" class="ahp-btn ahp-btn-danger ahp-btn-xs remove-monthly"><i class="fas fa-times"></i></button>
                                                            </div>
                                                            <div class="ahp-marks-row">
                                                                <div class="ahp-marks-group"><label>Oral Total</label><input type="number" name="subjects[<?php echo $idx; ?>][monthly_exams][<?php echo $midx; ?>][oral_total]" min="0" value="<?php echo esc_attr($mon['oral_total'] ?? ''); ?>"></div>
                                                                <div class="ahp-marks-group"><label>Oral Obtained</label><input type="number" name="subjects[<?php echo $idx; ?>][monthly_exams][<?php echo $midx; ?>][oral_obtained]" min="0" value="<?php echo esc_attr($mon['oral_obtained'] ?? ''); ?>"></div>
                                                                <div class="ahp-marks-group"><label>Written Total</label><input type="number" name="subjects[<?php echo $idx; ?>][monthly_exams][<?php echo $midx; ?>][written_total]" min="0" value="<?php echo esc_attr($mon['written_total'] ?? ''); ?>"></div>
                                                                <div class="ahp-marks-group"><label>Written Obtained</label><input type="number" name="subjects[<?php echo $idx; ?>][monthly_exams][<?php echo $midx; ?>][written_obtained]" min="0" value="<?php echo esc_attr($mon['written_obtained'] ?? ''); ?>"></div>
                                                            </div>
                                                        </div>
                                                        <?php endforeach; endif; ?>
                                                    </div>
                                                </div>
                                                <!-- Mid Semester -->
                                                <div class="ahp-exam-section">
                                                    <h4><i class="fas fa-book-open"></i> <?php _e('Mid Semester Exam', 'al-huffaz-portal'); ?></h4>
                                                    <div class="ahp-marks-row">
                                                        <div class="ahp-marks-group"><label>Oral Total</label><input type="number" name="subjects[<?php echo $idx; ?>][mid_semester][oral_total]" min="0" value="<?php echo esc_attr($subj['mid_semester']['oral_total'] ?? ''); ?>"></div>
                                                        <div class="ahp-marks-group"><label>Oral Obtained</label><input type="number" name="subjects[<?php echo $idx; ?>][mid_semester][oral_obtained]" min="0" value="<?php echo esc_attr($subj['mid_semester']['oral_obtained'] ?? ''); ?>"></div>
                                                        <div class="ahp-marks-group"><label>Written Total</label><input type="number" name="subjects[<?php echo $idx; ?>][mid_semester][written_total]" min="0" value="<?php echo esc_attr($subj['mid_semester']['written_total'] ?? ''); ?>"></div>
                                                        <div class="ahp-marks-group"><label>Written Obtained</label><input type="number" name="subjects[<?php echo $idx; ?>][mid_semester][written_obtained]" min="0" value="<?php echo esc_attr($subj['mid_semester']['written_obtained'] ?? ''); ?>"></div>
                                                    </div>
                                                </div>
                                                <!-- Annual Exam -->
                                                <div class="ahp-exam-section">
                                                    <h4><i class="fas fa-graduation-cap"></i> <?php _e('Annual Exam', 'al-huffaz-portal'); ?></h4>
                                                    <div class="ahp-marks-row">
                                                        <div class="ahp-marks-group"><label>Oral Total</label><input type="number" name="subjects[<?php echo $idx; ?>][final_semester][oral_total]" min="0" value="<?php echo esc_attr($subj['final_semester']['oral_total'] ?? ''); ?>"></div>
                                                        <div class="ahp-marks-group"><label>Oral Obtained</label><input type="number" name="subjects[<?php echo $idx; ?>][final_semester][oral_obtained]" min="0" value="<?php echo esc_attr($subj['final_semester']['oral_obtained'] ?? ''); ?>"></div>
                                                        <div class="ahp-marks-group"><label>Written Total</label><input type="number" name="subjects[<?php echo $idx; ?>][final_semester][written_total]" min="0" value="<?php echo esc_attr($subj['final_semester']['written_total'] ?? ''); ?>"></div>
                                                        <div class="ahp-marks-group"><label>Written Obtained</label><input type="number" name="subjects[<?php echo $idx; ?>][final_semester][written_obtained]" min="0" value="<?php echo esc_attr($subj['final_semester']['written_obtained'] ?? ''); ?>"></div>
                                                    </div>
                                                </div>
                                                <!-- Teacher Assessment -->
                                                <div class="ahp-exam-section">
                                                    <h4><i class="fas fa-comment-dots"></i> <?php _e('Teacher Assessment', 'al-huffaz-portal'); ?></h4>
                                                    <div class="ahp-form-grid">
                                                        <div class="ahp-form-group ahp-col-6">
                                                            <label class="ahp-label">Strengths</label>
                                                            <textarea name="subjects[<?php echo $idx; ?>][strengths]" class="ahp-input" rows="2"><?php echo esc_textarea($subj['strengths'] ?? ''); ?></textarea>
                                                        </div>
                                                        <div class="ahp-form-group ahp-col-6">
                                                            <label class="ahp-label">Areas for Improvement</label>
                                                            <textarea name="subjects[<?php echo $idx; ?>][areas_for_improvement]" class="ahp-input" rows="2"><?php echo esc_textarea($subj['areas_for_improvement'] ?? ''); ?></textarea>
                                                        </div>
                                                        <div class="ahp-form-group ahp-col-12">
                                                            <label class="ahp-label">Teacher Comments</label>
                                                            <textarea name="subjects[<?php echo $idx; ?>][teacher_comments]" class="ahp-input" rows="2"><?php echo esc_textarea($subj['teacher_comments'] ?? ''); ?></textarea>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; endif; ?>
                                    </div>
                                    <div id="noSubjectsMsg" class="ahp-empty-subjects" <?php if (!empty($subjects)) echo 'style="display:none;"'; ?>>
                                        <i class="fas fa-book-open"></i>
                                        <p><?php _e('No subjects added yet. Click "Add Subject" to start.', 'al-huffaz-portal'); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- STEP 4: Fees -->
                    <div class="ahp-form-step" data-step="4">
                        <div class="ahp-card">
                            <div class="ahp-card-body">
                                <div class="ahp-step-header">
                                    <i class="fas fa-money-bill-wave"></i>
                                    <h2><?php _e('Fee Structure', 'al-huffaz-portal'); ?></h2>
                                </div>

                                <div class="ahp-fee-cards">
                                    <div class="ahp-fee-card">
                                        <div class="ahp-fee-icon"><i class="fas fa-sync-alt"></i></div>
                                        <h4><?php _e('Monthly Tuition', 'al-huffaz-portal'); ?></h4>
                                        <div class="ahp-fee-input">
                                            <span class="ahp-currency">PKR</span>
                                            <input type="number" name="monthly_tuition_fee" class="fee-input" min="0" value="<?php echo esc_attr($student_data['monthly_tuition_fee'] ?? ''); ?>">
                                        </div>
                                    </div>
                                    <div class="ahp-fee-card">
                                        <div class="ahp-fee-icon"><i class="fas fa-book-open"></i></div>
                                        <h4><?php _e('Course Fee', 'al-huffaz-portal'); ?></h4>
                                        <div class="ahp-fee-input">
                                            <span class="ahp-currency">PKR</span>
                                            <input type="number" name="course_fee" class="fee-input" min="0" value="<?php echo esc_attr($student_data['course_fee'] ?? ''); ?>">
                                        </div>
                                    </div>
                                    <div class="ahp-fee-card">
                                        <div class="ahp-fee-icon"><i class="fas fa-tshirt"></i></div>
                                        <h4><?php _e('Uniform Fee', 'al-huffaz-portal'); ?></h4>
                                        <div class="ahp-fee-input">
                                            <span class="ahp-currency">PKR</span>
                                            <input type="number" name="uniform_fee" class="fee-input" min="0" value="<?php echo esc_attr($student_data['uniform_fee'] ?? ''); ?>">
                                        </div>
                                    </div>
                                    <div class="ahp-fee-card">
                                        <div class="ahp-fee-icon"><i class="fas fa-calendar-alt"></i></div>
                                        <h4><?php _e('Annual Fee', 'al-huffaz-portal'); ?></h4>
                                        <div class="ahp-fee-input">
                                            <span class="ahp-currency">PKR</span>
                                            <input type="number" name="annual_fee" class="fee-input" min="0" value="<?php echo esc_attr($student_data['annual_fee'] ?? ''); ?>">
                                        </div>
                                    </div>
                                    <div class="ahp-fee-card">
                                        <div class="ahp-fee-icon"><i class="fas fa-user-plus"></i></div>
                                        <h4><?php _e('Admission Fee', 'al-huffaz-portal'); ?></h4>
                                        <div class="ahp-fee-input">
                                            <span class="ahp-currency">PKR</span>
                                            <input type="number" name="admission_fee" class="fee-input" min="0" value="<?php echo esc_attr($student_data['admission_fee'] ?? ''); ?>">
                                        </div>
                                    </div>
                                </div>

                                <div class="ahp-fee-summary">
                                    <div class="ahp-fee-row"><span>Monthly Fee:</span><strong id="monthlyTotal">PKR 0</strong></div>
                                    <div class="ahp-fee-row"><span>One-time Fees:</span><strong id="oneTimeTotal">PKR 0</strong></div>
                                    <div class="ahp-fee-row"><span>Total (First Month):</span><strong id="grandTotal">PKR 0</strong></div>
                                </div>

                                <div class="ahp-section" style="margin-top:20px;">
                                    <h3 class="ahp-section-title"><i class="fas fa-hand-holding-heart"></i> <?php _e('Financial Aid Eligibility', 'al-huffaz-portal'); ?></h3>
                                    <div class="ahp-checkbox-group">
                                        <label class="ahp-checkbox-label">
                                            <input type="checkbox" name="zakat_eligible" value="yes" <?php echo ahp_fe_checked('zakat_eligible', $student_data); ?>>
                                            <span><i class="fas fa-donate"></i> <?php _e('Eligible for Zakat', 'al-huffaz-portal'); ?></span>
                                        </label>
                                        <label class="ahp-checkbox-label">
                                            <input type="checkbox" name="donation_eligible" value="yes" <?php echo ahp_fe_checked('donation_eligible', $student_data); ?>>
                                            <span><i class="fas fa-gift"></i> <?php _e('Eligible for Donations/Sponsorship', 'al-huffaz-portal'); ?></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- STEP 5: Health & Behavior -->
                    <div class="ahp-form-step" data-step="5">
                        <div class="ahp-card">
                            <div class="ahp-card-body">
                                <div class="ahp-step-header">
                                    <i class="fas fa-heartbeat"></i>
                                    <h2><?php _e('Health & Behavior Assessment', 'al-huffaz-portal'); ?></h2>
                                </div>

                                <div class="ahp-section">
                                    <h3 class="ahp-section-title"><i class="fas fa-notes-medical"></i> <?php _e('Medical Information', 'al-huffaz-portal'); ?></h3>
                                    <div class="ahp-form-grid">
                                        <div class="ahp-form-group ahp-col-4">
                                            <label class="ahp-label"><?php _e('Blood Group', 'al-huffaz-portal'); ?></label>
                                            <select name="blood_group" class="ahp-input">
                                                <option value="">Select</option>
                                                <?php foreach (['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bg): ?>
                                                <option value="<?php echo $bg; ?>" <?php echo ahp_fe_selected('blood_group', $bg, $student_data); ?>><?php echo $bg; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="ahp-form-group ahp-col-4">
                                            <label class="ahp-label"><?php _e('Allergies', 'al-huffaz-portal'); ?></label>
                                            <input type="text" name="allergies" class="ahp-input" value="<?php echo esc_attr($student_data['allergies'] ?? ''); ?>">
                                        </div>
                                        <div class="ahp-form-group ahp-col-4">
                                            <label class="ahp-label"><?php _e('Medical Conditions', 'al-huffaz-portal'); ?></label>
                                            <input type="text" name="medical_conditions" class="ahp-input" value="<?php echo esc_attr($student_data['medical_conditions'] ?? ''); ?>">
                                        </div>
                                    </div>
                                </div>

                                <div class="ahp-section">
                                    <h3 class="ahp-section-title"><i class="fas fa-star"></i> <?php _e('Behavior Assessment', 'al-huffaz-portal'); ?></h3>
                                    <div class="ahp-rating-grid">
                                        <?php
                                        $ratings = array(
                                            'health_rating' => array('icon' => 'fa-heartbeat', 'label' => 'Health & Wellness'),
                                            'cleanness_rating' => array('icon' => 'fa-broom', 'label' => 'Cleanliness'),
                                            'completes_homework' => array('icon' => 'fa-tasks', 'label' => 'Completes Homework'),
                                            'participates_in_class' => array('icon' => 'fa-hand-paper', 'label' => 'Class Participation'),
                                            'works_well_in_groups' => array('icon' => 'fa-users', 'label' => 'Group Work'),
                                            'problem_solving_skills' => array('icon' => 'fa-lightbulb', 'label' => 'Problem Solving'),
                                            'organization_preparedness' => array('icon' => 'fa-folder-open', 'label' => 'Organization'),
                                        );
                                        foreach ($ratings as $field => $info):
                                            $val = $student_data[$field] ?? 0;
                                        ?>
                                        <div class="ahp-rating-item">
                                            <label class="ahp-rating-label"><i class="fas <?php echo $info['icon']; ?>"></i> <?php echo $info['label']; ?></label>
                                            <div class="ahp-stars" data-field="<?php echo $field; ?>">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star ahp-star <?php echo ($val >= $i) ? 'active' : ''; ?>" data-value="<?php echo $i; ?>"></i>
                                                <?php endfor; ?>
                                                <input type="hidden" name="<?php echo $field; ?>" value="<?php echo esc_attr($val); ?>">
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <div class="ahp-section">
                                    <h3 class="ahp-section-title"><i class="fas fa-bullseye"></i> <?php _e('Goals & Comments', 'al-huffaz-portal'); ?></h3>
                                    <div class="ahp-form-grid">
                                        <div class="ahp-form-group ahp-col-4">
                                            <label class="ahp-label"><?php _e('Goal 1', 'al-huffaz-portal'); ?></label>
                                            <input type="text" name="goal_1" class="ahp-input" value="<?php echo esc_attr($student_data['goal_1'] ?? ''); ?>">
                                        </div>
                                        <div class="ahp-form-group ahp-col-4">
                                            <label class="ahp-label"><?php _e('Goal 2', 'al-huffaz-portal'); ?></label>
                                            <input type="text" name="goal_2" class="ahp-input" value="<?php echo esc_attr($student_data['goal_2'] ?? ''); ?>">
                                        </div>
                                        <div class="ahp-form-group ahp-col-4">
                                            <label class="ahp-label"><?php _e('Goal 3', 'al-huffaz-portal'); ?></label>
                                            <input type="text" name="goal_3" class="ahp-input" value="<?php echo esc_attr($student_data['goal_3'] ?? ''); ?>">
                                        </div>
                                        <div class="ahp-form-group ahp-col-12">
                                            <label class="ahp-label"><?php _e("Teacher's Overall Comments", 'al-huffaz-portal'); ?></label>
                                            <textarea name="teacher_overall_comments" class="ahp-input" rows="3"><?php echo esc_textarea($student_data['teacher_overall_comments'] ?? ''); ?></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Form Navigation -->
                    <div class="ahp-form-nav">
                        <button type="button" id="prevBtn" class="ahp-btn ahp-btn-secondary" style="display:none;">
                            <i class="fas fa-arrow-left"></i> <?php _e('Previous', 'al-huffaz-portal'); ?>
                        </button>
                        <button type="button" id="nextBtn" class="ahp-btn ahp-btn-primary">
                            <?php _e('Next', 'al-huffaz-portal'); ?> <i class="fas fa-arrow-right"></i>
                        </button>
                        <button type="submit" id="submitBtn" class="ahp-btn ahp-btn-success" style="display:none;">
                            <i class="fas fa-save"></i> <?php echo $is_edit ? __('Update Student', 'al-huffaz-portal') : __('Enroll Student', 'al-huffaz-portal'); ?>
                        </button>
                    </div>
                </form>
            </div>

            <!-- ==================== SPONSORS PANEL ==================== -->
            <?php if ($can_manage_sponsors): ?>
            <div class="ahp-panel" id="panel-sponsors">
                <div class="ahp-header">
                    <h1 class="ahp-title"><?php _e('Sponsor Management', 'al-huffaz-portal'); ?></h1>
                    <div class="ahp-actions">
                        <select class="ahp-filter" id="filterSponsorStatus" onchange="loadSponsors()" style="display:none;">
                            <option value=""><?php _e('All Status', 'al-huffaz-portal'); ?></option>
                            <option value="pending" selected><?php _e('Pending', 'al-huffaz-portal'); ?></option>
                            <option value="approved"><?php _e('Approved', 'al-huffaz-portal'); ?></option>
                            <option value="rejected"><?php _e('Rejected', 'al-huffaz-portal'); ?></option>
                        </select>
                    </div>
                </div>

                <!-- Tab Navigation -->
                <div style="background:#fff;padding:0 20px;border-radius:8px;margin-bottom:20px;">
                    <div style="display:flex;gap:5px;border-bottom:2px solid var(--ahp-border);">
                        <button class="ahp-sponsor-tab active" data-tab="active" onclick="switchSponsorTab('active')" style="padding:12px 24px;background:none;border:none;border-bottom:3px solid var(--ahp-primary);color:var(--ahp-primary);font-weight:600;cursor:pointer;transition:all 0.3s;">
                            <i class="fas fa-users"></i> <?php _e('Active Sponsors', 'al-huffaz-portal'); ?>
                        </button>
                        <button class="ahp-sponsor-tab" data-tab="requests" onclick="switchSponsorTab('requests')" style="padding:12px 24px;background:none;border:none;border-bottom:3px solid transparent;color:var(--ahp-text-muted);font-weight:500;cursor:pointer;transition:all 0.3s;">
                            <i class="fas fa-inbox"></i> <?php _e('Requests', 'al-huffaz-portal'); ?>
                            <span class="ahp-nav-badge danger" style="display:<?php echo $pending_sponsors_count > 0 ? 'inline-block' : 'none'; ?>;margin-left:8px;"><?php echo $pending_sponsors_count; ?></span>
                        </button>
                    </div>
                </div>

                <!-- Active Sponsors View -->
                <div id="sponsor-tab-active" class="ahp-sponsor-tab-content" style="display:block;">
                    <?php
                    // CRITICAL FIX: Get active sponsors data grouped by sponsor
                    global $wpdb;
                    $active_sponsorships_query = get_posts(array(
                        'post_type' => 'sponsorship',
                        'post_status' => 'publish',
                        'posts_per_page' => -1,
                        'meta_query' => array(
                            array('key' => 'linked', 'value' => 'yes'),
                        ),
                    ));

                    // Group by sponsor
                    $sponsors_data = array();
                    foreach ($active_sponsorships_query as $sponsorship) {
                        $sponsor_user_id = get_post_meta($sponsorship->ID, 'sponsor_user_id', true);
                        $sponsor_email = get_post_meta($sponsorship->ID, 'sponsor_email', true);
                        $sponsor_key = $sponsor_user_id ? 'user_' . $sponsor_user_id : 'email_' . $sponsor_email;

                        if (!isset($sponsors_data[$sponsor_key])) {
                            $sponsors_data[$sponsor_key] = array(
                                'sponsor_name' => get_post_meta($sponsorship->ID, 'sponsor_name', true),
                                'sponsor_email' => $sponsor_email,
                                'sponsor_phone' => get_post_meta($sponsorship->ID, 'sponsor_phone', true),
                                'sponsor_country' => get_post_meta($sponsorship->ID, 'sponsor_country', true),
                                'sponsor_user_id' => $sponsor_user_id,
                                'total_amount' => 0,
                                'students' => array(),
                                'sponsorship_count' => 0,
                            );
                        }

                        $student_id = get_post_meta($sponsorship->ID, 'student_id', true);
                        $student = get_post($student_id);
                        $amount = floatval(get_post_meta($sponsorship->ID, 'amount', true));

                        if ($student) {
                            // Get student photo using helper function
                            $student_photo_id = get_post_meta($student_id, 'student_photo', true);
                            if ($student_photo_id) {
                                $student_photo = wp_get_attachment_image_url($student_photo_id, 'medium');
                            }
                            if (empty($student_photo)) {
                                $student_photo = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="200" height="200"%3E%3Crect fill="%23ddd" width="200" height="200"/%3E%3Ctext fill="%23999" font-family="sans-serif" font-size="60" dy="10.5" font-weight="bold" x="50%25" y="50%25" text-anchor="middle"%3E' . substr($student->post_title, 0, 1) . '%3C/text%3E%3C/svg%3E';
                            }

                            $sponsors_data[$sponsor_key]['students'][] = array(
                                'student_id' => $student_id,
                                'student_name' => $student->post_title,
                                'student_photo' => $student_photo,
                                'grade_level' => get_post_meta($student_id, 'grade_level', true),
                                'amount' => $amount,
                                'sponsorship_type' => get_post_meta($sponsorship->ID, 'sponsorship_type', true),
                                'sponsorship_id' => $sponsorship->ID,
                                'linked_date' => $sponsorship->post_date,
                            );
                        }

                        $sponsors_data[$sponsor_key]['total_amount'] += $amount;
                        $sponsors_data[$sponsor_key]['sponsorship_count']++;
                    }

                    $active_sponsors = array_values($sponsors_data);
                    ?>

                    <?php if (empty($active_sponsors)): ?>
                    <div class="ahp-card">
                        <div class="ahp-card-body" style="text-align:center;padding:60px 20px;color:var(--ahp-text-muted);">
                            <i class="fas fa-hand-holding-heart" style="font-size:48px;opacity:0.3;margin-bottom:16px;"></i>
                            <h3 style="margin:0 0 8px 0;"><?php _e('No Active Sponsors Yet', 'al-huffaz-portal'); ?></h3>
                            <p style="margin:0;"><?php _e('Active sponsors will appear here once their sponsorships are approved and linked to students.', 'al-huffaz-portal'); ?></p>
                        </div>
                    </div>
                    <?php else: ?>

                    <div style="display:grid;gap:20px;">
                        <?php foreach ($active_sponsors as $sponsor): ?>
                        <div class="ahp-card" style="overflow:hidden;">
                            <!-- Sponsor Header -->
                            <div style="background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);color:#fff;padding:24px;">
                                <div style="display:flex;justify-content:space-between;align-items:start;flex-wrap:wrap;gap:16px;">
                                    <div>
                                        <h3 style="margin:0 0 8px 0;font-size:22px;display:flex;align-items:center;gap:10px;">
                                            <i class="fas fa-user-circle"></i>
                                            <?php echo esc_html($sponsor['sponsor_name']); ?>
                                        </h3>
                                        <div style="opacity:0.9;font-size:14px;display:flex;flex-wrap:wrap;gap:16px;">
                                            <?php if ($sponsor['sponsor_email']): ?>
                                            <span><i class="fas fa-envelope"></i> <?php echo esc_html($sponsor['sponsor_email']); ?></span>
                                            <?php endif; ?>
                                            <?php if ($sponsor['sponsor_phone']): ?>
                                            <span><i class="fas fa-phone"></i> <?php echo esc_html($sponsor['sponsor_phone']); ?></span>
                                            <?php endif; ?>
                                            <?php if ($sponsor['sponsor_country']): ?>
                                            <span><i class="fas fa-globe"></i> <?php echo esc_html($sponsor['sponsor_country']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div style="text-align:right;">
                                        <div style="font-size:28px;font-weight:700;line-height:1;">
                                            $<?php echo number_format($sponsor['total_amount'], 2); ?>
                                        </div>
                                        <div style="opacity:0.9;font-size:13px;margin-top:4px;">
                                            <?php echo sprintf(_n('%s Sponsorship', '%s Sponsorships', $sponsor['sponsorship_count'], 'al-huffaz-portal'), $sponsor['sponsorship_count']); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Sponsored Students -->
                            <div style="padding:20px;">
                                <h4 style="margin:0 0 16px 0;color:var(--ahp-text);font-size:16px;display:flex;align-items:center;gap:8px;">
                                    <i class="fas fa-users"></i> <?php _e('Sponsored Students', 'al-huffaz-portal'); ?>
                                </h4>
                                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;">
                                    <?php foreach ($sponsor['students'] as $student): ?>
                                    <div style="background:var(--ahp-bg);border:1px solid var(--ahp-border);border-radius:12px;overflow:hidden;transition:all 0.3s;cursor:pointer;" onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 4px 12px rgba(0,0,0,0.1)';" onmouseout="this.style.transform='translateY(0)';this.style.boxShadow='none';">
                                        <!-- Student Photo -->
                                        <div style="position:relative;width:100%;height:180px;overflow:hidden;background:linear-gradient(135deg,#667eea,#764ba2);">
                                            <img src="<?php echo esc_url($student['student_photo']); ?>" alt="<?php echo esc_attr($student['student_name']); ?>" style="width:100%;height:100%;object-fit:cover;">
                                        </div>

                                        <!-- Student Info -->
                                        <div style="padding:16px;">
                                            <h5 style="margin:0 0 8px 0;font-size:16px;color:var(--ahp-text);">
                                                <?php echo esc_html($student['student_name']); ?>
                                            </h5>
                                            <div style="display:flex;flex-direction:column;gap:8px;margin-bottom:12px;font-size:13px;color:var(--ahp-text-muted);">
                                                <?php if ($student['grade_level']): ?>
                                                <div><i class="fas fa-graduation-cap"></i> <?php echo esc_html($student['grade_level']); ?></div>
                                                <?php endif; ?>
                                                <div><i class="fas fa-dollar-sign"></i> $<?php echo number_format($student['amount'], 2); ?> <span style="opacity:0.7;">(<?php echo ucfirst($student['sponsorship_type']); ?>)</span></div>
                                                <div><i class="fas fa-calendar"></i> <?php echo date_i18n(get_option('date_format'), strtotime($student['linked_date'])); ?></div>
                                            </div>

                                            <!-- Action Buttons -->
                                            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                                                <button onclick="viewStudent(<?php echo $student['student_id']; ?>)" class="ahp-btn ahp-btn-sm" style="flex:1;font-size:12px;padding:6px 12px;background:var(--ahp-primary);color:#fff;border:none;border-radius:6px;cursor:pointer;">
                                                    <i class="fas fa-eye"></i> <?php _e('View', 'al-huffaz-portal'); ?>
                                                </button>
                                                <button onclick="editStudent(<?php echo $student['student_id']; ?>)" class="ahp-btn ahp-btn-sm" style="flex:1;font-size:12px;padding:6px 12px;background:#10b981;color:#fff;border:none;border-radius:6px;cursor:pointer;">
                                                    <i class="fas fa-edit"></i> <?php _e('Edit', 'al-huffaz-portal'); ?>
                                                </button>
                                                <button onclick="if(confirm('<?php _e('Are you sure you want to unlink this sponsorship?', 'al-huffaz-portal'); ?>')) unlinkSponsorship(<?php echo $student['sponsorship_id']; ?>)" class="ahp-btn ahp-btn-sm" style="font-size:12px;padding:6px 12px;background:#ef4444;color:#fff;border:none;border-radius:6px;cursor:pointer;">
                                                    <i class="fas fa-unlink"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <?php endif; ?>
                </div>

                <!-- Requests View -->
                <div id="sponsor-tab-requests" class="ahp-sponsor-tab-content" style="display:none;">
                    <div class="ahp-stats">
                        <div class="ahp-stat">
                            <div class="ahp-stat-icon orange"><i class="fas fa-clock"></i></div>
                            <div>
                                <div class="ahp-stat-label"><?php _e('Pending Approval', 'al-huffaz-portal'); ?></div>
                                <div class="ahp-stat-value" id="pendingSponsorCount"><?php echo $pending_sponsors_count; ?></div>
                            </div>
                        </div>
                        <div class="ahp-stat">
                            <div class="ahp-stat-icon green"><i class="fas fa-check-circle"></i></div>
                            <div>
                                <div class="ahp-stat-label"><?php _e('Approved', 'al-huffaz-portal'); ?></div>
                                <div class="ahp-stat-value" id="approvedSponsorCount"><?php echo $total_sponsors; ?></div>
                            </div>
                        </div>
                        <div class="ahp-stat">
                            <div class="ahp-stat-icon blue"><i class="fas fa-user-check"></i></div>
                            <div>
                                <div class="ahp-stat-label"><?php _e('Donation Eligible Students', 'al-huffaz-portal'); ?></div>
                                <div class="ahp-stat-value"><?php echo $donation_eligible_count; ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="ahp-card">
                        <div class="ahp-card-header">
                            <h3 class="ahp-card-title"><i class="fas fa-hand-holding-heart"></i> <?php _e('Sponsorship Requests', 'al-huffaz-portal'); ?></h3>
                            <div style="margin-top:8px;">
                                <select class="ahp-filter" id="filterSponsorStatusMain" onchange="loadSponsors()" style="display:inline-block;">
                                    <option value=""><?php _e('All Status', 'al-huffaz-portal'); ?></option>
                                    <option value="pending" selected><?php _e('Pending', 'al-huffaz-portal'); ?></option>
                                    <option value="approved"><?php _e('Approved', 'al-huffaz-portal'); ?></option>
                                    <option value="rejected"><?php _e('Rejected', 'al-huffaz-portal'); ?></option>
                                </select>
                            </div>
                        </div>
                        <div class="ahp-card-body" style="padding:0;">
                            <div class="ahp-table-wrap">
                                <table class="ahp-table">
                                    <thead>
                                        <tr>
                                            <th><?php _e('Sponsor', 'al-huffaz-portal'); ?></th>
                                            <th><?php _e('Student', 'al-huffaz-portal'); ?></th>
                                            <th><?php _e('Amount', 'al-huffaz-portal'); ?></th>
                                            <th><?php _e('Type', 'al-huffaz-portal'); ?></th>
                                            <th><?php _e('Status', 'al-huffaz-portal'); ?></th>
                                            <th><?php _e('Date', 'al-huffaz-portal'); ?></th>
                                            <th><?php _e('Actions', 'al-huffaz-portal'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody id="sponsorsTableBody">
                                        <tr><td colspan="7" class="ahp-loading"><div class="ahp-spinner"></div></td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- ==================== SPONSOR USERS PANEL ==================== -->
            <?php if ($can_manage_sponsors): ?>
            <div class="ahp-panel" id="panel-sponsor-users">
                <div class="ahp-header">
                    <h1 class="ahp-title"><?php _e('Sponsor User Management', 'al-huffaz-portal'); ?></h1>
                    <div class="ahp-actions">
                        <select class="ahp-filter" id="filterUserStatus" onchange="loadSponsorUsers()">
                            <option value=""><?php _e('All Users', 'al-huffaz-portal'); ?></option>
                            <option value="pending"><?php _e('Pending Approval', 'al-huffaz-portal'); ?></option>
                            <option value="approved"><?php _e('Approved', 'al-huffaz-portal'); ?></option>
                            <option value="inactive"><?php _e('Inactive (No Sponsorships)', 'al-huffaz-portal'); ?></option>
                        </select>
                        <input type="text" class="ahp-search" id="searchSponsorUsers" placeholder="<?php _e('Search sponsors...', 'al-huffaz-portal'); ?>">
                    </div>
                </div>

                <div class="ahp-stats">
                    <div class="ahp-stat">
                        <div class="ahp-stat-icon orange"><i class="fas fa-user-clock"></i></div>
                        <div>
                            <div class="ahp-stat-label"><?php _e('Pending Approval', 'al-huffaz-portal'); ?></div>
                            <div class="ahp-stat-value" id="pendingUsersCount">0</div>
                        </div>
                    </div>
                    <div class="ahp-stat">
                        <div class="ahp-stat-icon green"><i class="fas fa-user-check"></i></div>
                        <div>
                            <div class="ahp-stat-label"><?php _e('Active Sponsors', 'al-huffaz-portal'); ?></div>
                            <div class="ahp-stat-value" id="activeUsersCount">0</div>
                        </div>
                    </div>
                    <div class="ahp-stat">
                        <div class="ahp-stat-icon gray"><i class="fas fa-user-slash"></i></div>
                        <div>
                            <div class="ahp-stat-label"><?php _e('Inactive Sponsors', 'al-huffaz-portal'); ?></div>
                            <div class="ahp-stat-value" id="inactiveUsersCount">0</div>
                        </div>
                    </div>
                    <div class="ahp-stat">
                        <div class="ahp-stat-icon blue"><i class="fas fa-users"></i></div>
                        <div>
                            <div class="ahp-stat-label"><?php _e('Total Registered', 'al-huffaz-portal'); ?></div>
                            <div class="ahp-stat-value" id="totalUsersCount">0</div>
                        </div>
                    </div>
                </div>

                <div class="ahp-card">
                    <div class="ahp-card-header">
                        <h3 class="ahp-card-title"><i class="fas fa-users-cog"></i> <?php _e('Sponsor User Accounts', 'al-huffaz-portal'); ?></h3>
                        <p style="margin:8px 0 0 0;color:var(--ahp-text-muted);font-size:14px;">
                            <?php _e('Manage sponsor user accounts - approve registrations, view activity, and manage access', 'al-huffaz-portal'); ?>
                        </p>
                    </div>
                    <div class="ahp-card-body" style="padding:0;">
                        <div class="ahp-table-wrap">
                            <table class="ahp-table">
                                <thead>
                                    <tr>
                                        <th><?php _e('User', 'al-huffaz-portal'); ?></th>
                                        <th><?php _e('Email', 'al-huffaz-portal'); ?></th>
                                        <th><?php _e('Phone', 'al-huffaz-portal'); ?></th>
                                        <th><?php _e('Status', 'al-huffaz-portal'); ?></th>
                                        <th><?php _e('Sponsorships', 'al-huffaz-portal'); ?></th>
                                        <th><?php _e('Registered', 'al-huffaz-portal'); ?></th>
                                        <th><?php _e('Actions', 'al-huffaz-portal'); ?></th>
                                    </tr>
                                </thead>
                                <tbody id="sponsorUsersTableBody">
                                    <tr><td colspan="7" class="ahp-loading"><div class="ahp-spinner"></div></td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- ==================== PAYMENTS PANEL ==================== -->
            <?php if ($can_manage_payments): ?>
            <div class="ahp-panel" id="panel-payments">
                <div class="ahp-header">
                    <h1 class="ahp-title"><?php _e('Payment Records & Analytics', 'al-huffaz-portal'); ?></h1>
                    <p style="color:var(--ahp-text-muted);margin:8px 0 0 0;font-size:14px;">
                        <?php _e('Track all sponsorship payments, donations, and financial records.', 'al-huffaz-portal'); ?>
                    </p>
                </div>

                <!-- Payment Statistics -->
                <div class="ahp-stats" style="grid-template-columns:repeat(auto-fit,minmax(200px,1fr));">
                    <div class="ahp-stat">
                        <div class="ahp-stat-icon green"><i class="fas fa-dollar-sign"></i></div>
                        <div>
                            <div class="ahp-stat-label"><?php _e('Total Approved Amount', 'al-huffaz-portal'); ?></div>
                            <div class="ahp-stat-value" id="totalApprovedAmount">Rs. 0</div>
                        </div>
                    </div>
                    <div class="ahp-stat">
                        <div class="ahp-stat-icon blue"><i class="fas fa-users"></i></div>
                        <div>
                            <div class="ahp-stat-label"><?php _e('Active Sponsors', 'al-huffaz-portal'); ?></div>
                            <div class="ahp-stat-value" id="totalActiveSponsors">0</div>
                        </div>
                    </div>
                    <div class="ahp-stat">
                        <div class="ahp-stat-icon purple"><i class="fas fa-user-graduate"></i></div>
                        <div>
                            <div class="ahp-stat-label"><?php _e('Students Sponsored', 'al-huffaz-portal'); ?></div>
                            <div class="ahp-stat-value" id="totalStudentsSponsored">0</div>
                        </div>
                    </div>
                    <div class="ahp-stat">
                        <div class="ahp-stat-icon orange"><i class="fas fa-hourglass-half"></i></div>
                        <div>
                            <div class="ahp-stat-label"><?php _e('Pending Verification', 'al-huffaz-portal'); ?></div>
                            <div class="ahp-stat-value" id="pendingPaymentCount"><?php echo $pending_payments_count; ?></div>
                        </div>
                    </div>
                    <div class="ahp-stat">
                        <div class="ahp-stat-icon success"><i class="fas fa-check-circle"></i></div>
                        <div>
                            <div class="ahp-stat-label"><?php _e('Approved Payments', 'al-huffaz-portal'); ?></div>
                            <div class="ahp-stat-value" id="approvedPaymentCount">0</div>
                        </div>
                    </div>
                    <div class="ahp-stat">
                        <div class="ahp-stat-icon red"><i class="fas fa-times-circle"></i></div>
                        <div>
                            <div class="ahp-stat-label"><?php _e('Rejected Payments', 'al-huffaz-portal'); ?></div>
                            <div class="ahp-stat-value" id="rejectedPaymentCount">0</div>
                        </div>
                    </div>
                </div>

                <!-- Sponsor-wise Payment Breakdown -->
                <div class="ahp-card" style="margin-bottom:20px;">
                    <div class="ahp-card-header">
                        <h3 class="ahp-card-title"><i class="fas fa-chart-bar"></i> <?php _e('Sponsor-wise Contribution Summary', 'al-huffaz-portal'); ?></h3>
                    </div>
                    <div class="ahp-card-body" style="padding:0;">
                        <div class="ahp-table-wrap">
                            <table class="ahp-table">
                                <thead>
                                    <tr>
                                        <th><?php _e('Sponsor Name', 'al-huffaz-portal'); ?></th>
                                        <th><?php _e('Email', 'al-huffaz-portal'); ?></th>
                                        <th><?php _e('Students Sponsored', 'al-huffaz-portal'); ?></th>
                                        <th><?php _e('Total Payments', 'al-huffaz-portal'); ?></th>
                                        <th><?php _e('Total Amount', 'al-huffaz-portal'); ?></th>
                                        <th><?php _e('Last Payment', 'al-huffaz-portal'); ?></th>
                                    </tr>
                                </thead>
                                <tbody id="sponsorPaymentSummaryBody">
                                    <tr><td colspan="6" class="ahp-loading"><div class="ahp-spinner"></div></td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- All Payment Records -->
                <div class="ahp-card">
                    <div class="ahp-card-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
                        <h3 class="ahp-card-title"><i class="fas fa-receipt"></i> <?php _e('All Payment Records', 'al-huffaz-portal'); ?></h3>
                        <select class="ahp-filter" id="filterPaymentStatus" onchange="loadAllPayments()" style="min-width:150px;">
                            <option value=""><?php _e('All Status', 'al-huffaz-portal'); ?></option>
                            <option value="approved"><?php _e('Approved', 'al-huffaz-portal'); ?></option>
                            <option value="pending"><?php _e('Pending', 'al-huffaz-portal'); ?></option>
                            <option value="rejected"><?php _e('Rejected', 'al-huffaz-portal'); ?></option>
                        </select>
                    </div>
                    <div class="ahp-card-body" style="padding:0;">
                        <div class="ahp-table-wrap">
                            <table class="ahp-table">
                                <thead>
                                    <tr>
                                        <th><?php _e('Date', 'al-huffaz-portal'); ?></th>
                                        <th><?php _e('Sponsor', 'al-huffaz-portal'); ?></th>
                                        <th><?php _e('Student', 'al-huffaz-portal'); ?></th>
                                        <th><?php _e('Amount', 'al-huffaz-portal'); ?></th>
                                        <th><?php _e('Duration', 'al-huffaz-portal'); ?></th>
                                        <th><?php _e('Method', 'al-huffaz-portal'); ?></th>
                                        <th><?php _e('Status', 'al-huffaz-portal'); ?></th>
                                        <th><?php _e('Actions', 'al-huffaz-portal'); ?></th>
                                    </tr>
                                </thead>
                                <tbody id="allPaymentsTableBody">
                                    <tr><td colspan="8" class="ahp-loading"><div class="ahp-spinner"></div></td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- ==================== STAFF MANAGEMENT PANEL ==================== -->
            <?php if ($can_manage_staff): ?>
            <div class="ahp-panel" id="panel-staff">
                <div class="ahp-header">
                    <h1 class="ahp-title"><?php _e('User Management', 'al-huffaz-portal'); ?></h1>
                    <p style="color:var(--ahp-text-muted);margin:8px 0 0 0;font-size:14px;">
                        <?php _e('Create and manage school admin and staff users.', 'al-huffaz-portal'); ?>
                    </p>
                    <div class="ahp-actions">
                        <button class="ahp-btn ahp-btn-primary" onclick="showCreateUserModal()">
                            <i class="fas fa-user-plus"></i> <?php _e('Create New User', 'al-huffaz-portal'); ?>
                        </button>
                    </div>
                </div>

                <div class="ahp-stats">
                    <div class="ahp-stat">
                        <div class="ahp-stat-icon blue"><i class="fas fa-user-shield"></i></div>
                        <div>
                            <div class="ahp-stat-label"><?php _e('School Admins', 'al-huffaz-portal'); ?></div>
                            <div class="ahp-stat-value" id="totalAdminsCount"><?php echo count(get_users(array('role' => 'alhuffaz_admin'))); ?></div>
                        </div>
                    </div>
                    <div class="ahp-stat">
                        <div class="ahp-stat-icon green"><i class="fas fa-users"></i></div>
                        <div>
                            <div class="ahp-stat-label"><?php _e('Staff Members', 'al-huffaz-portal'); ?></div>
                            <div class="ahp-stat-value" id="totalStaffCount"><?php echo count(get_users(array('role' => 'alhuffaz_staff'))); ?></div>
                        </div>
                    </div>
                    <div class="ahp-stat">
                        <div class="ahp-stat-icon orange"><i class="fas fa-user-check"></i></div>
                        <div>
                            <div class="ahp-stat-label"><?php _e('Total Users', 'al-huffaz-portal'); ?></div>
                            <div class="ahp-stat-value" id="totalUsersCount">
                                <?php echo count(get_users(array('role__in' => array('alhuffaz_admin', 'alhuffaz_staff')))); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="ahp-card">
                    <div class="ahp-card-header" style="display:flex;justify-content:space-between;align-items:center;">
                        <div>
                            <h3 class="ahp-card-title"><i class="fas fa-users-cog"></i> <?php _e('All Portal Users', 'al-huffaz-portal'); ?></h3>
                            <p style="color:var(--ahp-text-muted);margin:8px 0 0 0;font-size:14px;">
                                <?php _e('School Admins have full access. Staff can only add/edit students.', 'al-huffaz-portal'); ?>
                            </p>
                        </div>
                        <select id="filterUserRole" class="ahp-form-select" style="width:auto;min-width:150px;" onchange="loadPortalUsers()">
                            <option value=""><?php _e('All Roles', 'al-huffaz-portal'); ?></option>
                            <option value="alhuffaz_admin"><?php _e('School Admins', 'al-huffaz-portal'); ?></option>
                            <option value="alhuffaz_staff"><?php _e('Staff', 'al-huffaz-portal'); ?></option>
                        </select>
                    </div>
                    <div class="ahp-card-body" style="padding:0;">
                        <div class="ahp-table-wrap">
                            <table class="ahp-table">
                                <thead>
                                    <tr>
                                        <th><?php _e('User', 'al-huffaz-portal'); ?></th>
                                        <th><?php _e('Email', 'al-huffaz-portal'); ?></th>
                                        <th><?php _e('Role', 'al-huffaz-portal'); ?></th>
                                        <th><?php _e('Registered', 'al-huffaz-portal'); ?></th>
                                        <th><?php _e('Actions', 'al-huffaz-portal'); ?></th>
                                    </tr>
                                </thead>
                                <tbody id="portalUsersTableBody">
                                    <tr><td colspan="5" class="ahp-loading"><div class="ahp-spinner"></div></td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- ==================== HISTORY & RECOVERY PANEL ==================== -->
            <div class="ahp-panel" id="panel-history">
                <div class="ahp-header">
                    <h1 class="ahp-title"><?php _e('Activity History & Recovery', 'al-huffaz-portal'); ?></h1>
                    <p style="color:var(--ahp-text-muted);margin:8px 0 0 0;font-size:14px;">
                        <?php _e('Track all system activities and restore deleted items.', 'al-huffaz-portal'); ?>
                    </p>
                </div>

                <div class="ahp-card">
                    <div class="ahp-card-header" style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;">
                        <select id="filterLogAction" class="ahp-form-select" style="width:auto;min-width:180px;" onchange="loadActivityLogs()">
                            <option value=""><?php _e('All Actions', 'al-huffaz-portal'); ?></option>
                            <option value="save_student"><?php _e('Student Added', 'al-huffaz-portal'); ?></option>
                            <option value="update_student"><?php _e('Student Updated', 'al-huffaz-portal'); ?></option>
                            <option value="delete_student"><?php _e('Student Deleted', 'al-huffaz-portal'); ?></option>
                            <option value="approve_sponsorship"><?php _e('Sponsorship Approved', 'al-huffaz-portal'); ?></option>
                            <option value="reject_sponsorship"><?php _e('Sponsorship Rejected', 'al-huffaz-portal'); ?></option>
                            <option value="unlink_sponsor"><?php _e('Sponsor Unlinked', 'al-huffaz-portal'); ?></option>
                            <option value="restore_student"><?php _e('Student Restored', 'al-huffaz-portal'); ?></option>
                            <option value="restore_sponsorship"><?php _e('Sponsorship Restored', 'al-huffaz-portal'); ?></option>
                        </select>
                        <select id="filterLogType" class="ahp-form-select" style="width:auto;min-width:150px;" onchange="loadActivityLogs()">
                            <option value=""><?php _e('All Types', 'al-huffaz-portal'); ?></option>
                            <option value="student"><?php _e('Students', 'al-huffaz-portal'); ?></option>
                            <option value="sponsorship"><?php _e('Sponsorships', 'al-huffaz-portal'); ?></option>
                            <option value="user"><?php _e('Users', 'al-huffaz-portal'); ?></option>
                            <option value="payment"><?php _e('Payments', 'al-huffaz-portal'); ?></option>
                        </select>
                        <button class="ahp-btn ahp-btn-secondary ahp-btn-sm" onclick="loadActivityLogs()" style="margin-left:auto;">
                            <i class="fas fa-sync-alt"></i> <?php _e('Refresh', 'al-huffaz-portal'); ?>
                        </button>
                    </div>
                    <div class="ahp-card-body" style="padding:0;">
                        <div class="ahp-table-wrap">
                            <table class="ahp-table">
                                <thead>
                                    <tr>
                                        <th style="width:50px;"><?php _e('ID', 'al-huffaz-portal'); ?></th>
                                        <th><?php _e('User', 'al-huffaz-portal'); ?></th>
                                        <th><?php _e('Action', 'al-huffaz-portal'); ?></th>
                                        <th><?php _e('Object', 'al-huffaz-portal'); ?></th>
                                        <th><?php _e('Details', 'al-huffaz-portal'); ?></th>
                                        <th><?php _e('Time', 'al-huffaz-portal'); ?></th>
                                        <th><?php _e('Actions', 'al-huffaz-portal'); ?></th>
                                    </tr>
                                </thead>
                                <tbody id="activityLogsTableBody">
                                    <tr><td colspan="7" class="ahp-loading"><div class="ahp-spinner"></div></td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>
    <div class="ahp-toast" id="toast"></div>
</div>

<!-- Modal for Creating User -->
<?php if ($can_manage_staff): ?>
<div id="createUserModal" class="ahp-modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:12px;max-width:500px;width:90%;max-height:90vh;overflow:auto;box-shadow:0 20px 50px rgba(0,0,0,0.2);">
        <div style="padding:20px;border-bottom:1px solid var(--ahp-border);display:flex;justify-content:space-between;align-items:center;">
            <h3 style="margin:0;"><?php _e('Create New User', 'al-huffaz-portal'); ?></h3>
            <button onclick="closeCreateUserModal()" style="background:none;border:none;font-size:20px;cursor:pointer;">&times;</button>
        </div>
        <form id="createUserForm" style="padding:20px;">
            <div class="ahp-form-group">
                <label class="ahp-form-label"><?php _e('Full Name', 'al-huffaz-portal'); ?> <span style="color:red;">*</span></label>
                <input type="text" id="newUserName" name="full_name" class="ahp-form-input" required placeholder="e.g., Ahmed Khan">
            </div>

            <div class="ahp-form-group">
                <label class="ahp-form-label"><?php _e('Username', 'al-huffaz-portal'); ?> <span style="color:red;">*</span></label>
                <input type="text" id="newUsername" name="username" class="ahp-form-input" required placeholder="e.g., ahmedkhan" pattern="[a-z0-9_]+" title="Lowercase letters, numbers, and underscores only">
                <small style="color:var(--ahp-text-muted);display:block;margin-top:4px;">Lowercase letters, numbers, and underscores only</small>
            </div>

            <div class="ahp-form-group">
                <label class="ahp-form-label"><?php _e('Email', 'al-huffaz-portal'); ?> <span style="color:red;">*</span></label>
                <input type="email" id="newUserEmail" name="email" class="ahp-form-input" required placeholder="e.g., ahmed@school.com">
            </div>

            <div class="ahp-form-group">
                <label class="ahp-form-label"><?php _e('Password', 'al-huffaz-portal'); ?> <span style="color:red;">*</span></label>
                <input type="password" id="newUserPassword" name="password" class="ahp-form-input" required minlength="8" placeholder="Minimum 8 characters">
                <small style="color:var(--ahp-text-muted);display:block;margin-top:4px;">Minimum 8 characters</small>
            </div>

            <div class="ahp-form-group">
                <label class="ahp-form-label"><?php _e('User Role', 'al-huffaz-portal'); ?> <span style="color:red;">*</span></label>
                <select id="newUserRole" name="role" class="ahp-form-select" required>
                    <option value=""><?php _e('Select Role', 'al-huffaz-portal'); ?></option>
                    <option value="alhuffaz_admin"><?php _e('School Admin', 'al-huffaz-portal'); ?> - <?php _e('Full access to portal', 'al-huffaz-portal'); ?></option>
                    <option value="alhuffaz_staff"><?php _e('Staff', 'al-huffaz-portal'); ?> - <?php _e('Can only add/edit students', 'al-huffaz-portal'); ?></option>
                </select>
            </div>

            <div class="ahp-form-group" style="background:var(--ahp-bg);padding:15px;border-radius:8px;border-left:4px solid var(--ahp-primary);">
                <p style="margin:0;font-size:13px;color:var(--ahp-text-muted);">
                    <strong><?php _e('Note:', 'al-huffaz-portal'); ?></strong><br>
                    <strong>School Admin:</strong> Full access to students, sponsors, payments, users, and settings.<br>
                    <strong>Staff:</strong> Limited to adding and editing students only.
                </p>
            </div>
        </form>
        <div style="padding:20px;border-top:1px solid var(--ahp-border);display:flex;gap:10px;justify-content:flex-end;">
            <button onclick="closeCreateUserModal()" class="ahp-btn"><?php _e('Cancel', 'al-huffaz-portal'); ?></button>
            <button id="createUserBtn" onclick="createNewUser()" class="ahp-btn ahp-btn-primary">
                <i class="fas fa-user-plus"></i> <?php _e('Create User', 'al-huffaz-portal'); ?>
            </button>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Modal for Sponsor Details -->
<div id="sponsorModal" class="ahp-modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center;">
    <div class="ahp-modal-content" style="background:#fff;border-radius:16px;max-width:600px;width:90%;max-height:90vh;overflow-y:auto;margin:auto;">
        <div class="ahp-modal-header" style="padding:20px;border-bottom:1px solid var(--ahp-border);display:flex;justify-content:space-between;align-items:center;">
            <h3 style="margin:0;"><?php _e('Sponsorship Details', 'al-huffaz-portal'); ?></h3>
            <button onclick="closeSponsorModal()" style="background:none;border:none;font-size:20px;cursor:pointer;">&times;</button>
        </div>
        <div id="sponsorModalBody" class="ahp-modal-body" style="padding:20px;"></div>
        <div class="ahp-modal-footer" style="padding:20px;border-top:1px solid var(--ahp-border);display:flex;gap:10px;justify-content:flex-end;">
            <button onclick="closeSponsorModal()" class="ahp-btn ahp-btn-secondary"><?php _e('Close', 'al-huffaz-portal'); ?></button>
            <button id="rejectSponsorBtn" onclick="rejectSponsorship()" class="ahp-btn ahp-btn-danger"><i class="fas fa-times"></i> <?php _e('Reject', 'al-huffaz-portal'); ?></button>
            <button id="approveSponsorBtn" onclick="approveSponsorship()" class="ahp-btn ahp-btn-success"><i class="fas fa-check"></i> <?php _e('Approve & Link', 'al-huffaz-portal'); ?></button>
        </div>
    </div>
</div>

<script>
// Mobile Navigation Toggle
window.toggleMobileNav = function() {
    const nav = document.getElementById('ahpNav');
    nav.classList.toggle('open');
};

// CRITICAL FIX: Handle browser back/forward cache (bfcache)
// Prevents cached URL with ?edit= parameter from triggering redirect on page restore
window.addEventListener('pageshow', function(event) {
    <?php if (!$is_edit): ?>
    // If page is restored from cache and has edit parameter, clean it
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('edit') && window.history.replaceState) {
        window.history.replaceState({}, document.title, window.location.pathname);
        // Force show dashboard
        if (typeof showPanel === 'function') {
            showPanel('dashboard');
        }
    }
    <?php endif; ?>
});

document.addEventListener('DOMContentLoaded', function() {
    // CRITICAL FIX: Clear edit parameter from URL if not explicitly in edit mode
    // This prevents browser cache from keeping the ?edit= parameter on refresh
    const urlParams = new URLSearchParams(window.location.search);
    const editParam = urlParams.get('edit');
    <?php if (!$is_edit): ?>
    // If we're not in edit mode but URL has edit parameter, clean it immediately
    if (editParam && window.history.replaceState) {
        window.history.replaceState({}, document.title, window.location.pathname);
    }
    <?php endif; ?>

    // Make ajaxUrl and nonce global so they're accessible to all functions
    window.ajaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';
    window.nonce = '<?php echo $nonce; ?>';
    const ajaxUrl = window.ajaxUrl; // Keep local reference for backward compatibility
    const nonce = window.nonce;
    let currentStep = 1;
    const totalSteps = 5;
    let subjectIndex = <?php echo !empty($subjects) ? max(array_keys($subjects)) + 1 : 0; ?>;
    let currentPage = 1;

    // ==================== NAVIGATION ====================
    window.showPanel = function(panel) {
        document.querySelectorAll('.ahp-panel').forEach(p => p.classList.remove('active'));
        document.querySelectorAll('.ahp-nav-item').forEach(n => n.classList.remove('active'));
        document.getElementById('panel-' + panel)?.classList.add('active');
        document.querySelector('[data-panel="' + panel + '"]')?.classList.add('active');
        if (panel === 'students') loadStudents();
        if (panel === 'add-student' && !document.getElementById('studentId').value) resetForm();
        if (panel === 'sponsors') {
            loadSponsors();
            loadSponsorStats(); // CRITICAL FIX: Load sponsor stats when panel opens
        }
        if (panel === 'sponsor-users') loadSponsorUsers();
        if (panel === 'payments') loadPaymentAnalytics();
        if (panel === 'staff') loadPortalUsers();
        if (panel === 'history') loadActivityLogs();

        // Close mobile nav when panel is selected
        const nav = document.getElementById('ahpNav');
        nav?.classList.remove('open');

        // Scroll to top
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    document.querySelectorAll('.ahp-nav-item[data-panel]').forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            showPanel(this.dataset.panel);
        });
    });

    // ==================== STUDENTS LIST ====================
    function loadStudents(page = 1) {
        currentPage = page;
        const search = document.getElementById('searchInput').value;
        const grade = document.getElementById('filterGrade').value;
        const category = document.getElementById('filterCategory').value;
        document.getElementById('studentsTableBody').innerHTML = '<tr><td colspan="6" class="ahp-loading"><div class="ahp-spinner"></div></td></tr>';

        fetch(ajaxUrl, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({action: 'alhuffaz_get_students', nonce, page, search, grade, category, per_page: 15})
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                renderStudents(data.data.students);
                renderPagination(data.data.total_pages, page);
            } else {
                document.getElementById('studentsTableBody').innerHTML = '<tr><td colspan="6" style="text-align:center;padding:40px;">Error loading students</td></tr>';
            }
        });
    }

    function renderStudents(students) {
        const tbody = document.getElementById('studentsTableBody');
        if (!students || !students.length) {
            tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:40px;color:var(--ahp-text-muted);">No students found</td></tr>';
            return;
        }
        tbody.innerHTML = students.map(s => `
            <tr>
                <td><div class="ahp-student-cell">
                    ${s.photo ? `<img src="${s.photo}" class="ahp-student-avatar">` : `<div class="ahp-student-avatar">${(s.name||'S').charAt(0).toUpperCase()}</div>`}
                    <span>${s.name||'-'}</span>
                </div></td>
                <td>${s.gr_number||'-'}</td>
                <td><span class="ahp-badge ahp-badge-primary">${(s.grade_level||'-').toUpperCase()}</span></td>
                <td><span class="ahp-badge ahp-badge-success">${s.islamic_studies_category ? s.islamic_studies_category.charAt(0).toUpperCase() + s.islamic_studies_category.slice(1) : '-'}</span></td>
                <td>${s.father_name||'-'}</td>
                <td><div class="ahp-cell-actions">
                    <a href="${s.permalink||'#'}" class="ahp-btn ahp-btn-secondary ahp-btn-icon" target="_blank"><i class="fas fa-eye"></i></a>
                    <button class="ahp-btn ahp-btn-primary ahp-btn-icon" onclick="editStudent(${s.id})"><i class="fas fa-edit"></i></button>
                    <button class="ahp-btn ahp-btn-danger ahp-btn-icon" onclick="deleteStudent(${s.id})"><i class="fas fa-trash"></i></button>
                </div></td>
            </tr>
        `).join('');
    }

    function renderPagination(totalPages, current) {
        const container = document.getElementById('pagination');
        if (totalPages <= 1) { container.innerHTML = ''; return; }
        let html = '';
        for (let i = 1; i <= Math.min(totalPages, 10); i++) {
            html += `<button class="ahp-btn ${i === current ? 'ahp-btn-primary' : 'ahp-btn-secondary'} ahp-btn-sm" onclick="loadStudentsPage(${i})">${i}</button>`;
        }
        container.innerHTML = html;
    }
    window.loadStudentsPage = (p) => loadStudents(p);

    let searchTimeout;
    document.getElementById('searchInput').addEventListener('input', () => { clearTimeout(searchTimeout); searchTimeout = setTimeout(() => loadStudents(1), 300); });
    document.getElementById('filterGrade').addEventListener('change', () => loadStudents(1));
    document.getElementById('filterCategory').addEventListener('change', () => loadStudents(1));

    // ==================== EDIT/DELETE STUDENT ====================
    window.editStudent = function(id) {
        window.location.href = '<?php echo $portal_url; ?>?edit=' + id;
    };

    window.viewStudent = function(id) {
        // Show loading modal
        showStudentModal('<div style="text-align:center;padding:60px;"><i class="fas fa-spinner fa-spin" style="font-size:48px;color:var(--ahp-primary);"></i><p style="margin-top:20px;font-size:16px;">Loading student profile...</p></div>');

        // Fetch student data
        fetch(ajaxUrl, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({action: 'alhuffaz_get_student', nonce, student_id: id})
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                renderStudentProfile(data.data);
            } else {
                showStudentModal('<div style="text-align:center;padding:60px;color:var(--ahp-text-muted);">Error loading student profile</div>');
            }
        })
        .catch(err => {
            showStudentModal('<div style="text-align:center;padding:60px;color:var(--ahp-text-muted);">Network error</div>');
        });
    };

    function showStudentModal(content) {
        // Remove existing modal
        const existing = document.getElementById('studentProfileModal');
        if (existing) existing.remove();

        // Create modal
        const modal = document.createElement('div');
        modal.id = 'studentProfileModal';
        modal.innerHTML = `
            <div style="position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.6);z-index:10000;display:flex;align-items:center;justify-content:center;padding:20px;overflow-y:auto;" onclick="if(event.target===this) this.parentElement.remove()">
                <div style="background:var(--ahp-bg);border-radius:16px;max-width:1200px;width:100%;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,0.4);">
                    <div style="position:sticky;top:0;background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);color:#fff;padding:24px;display:flex;justify-content:space-between;align-items:center;z-index:1;border-radius:16px 16px 0 0;">
                        <h2 style="margin:0;font-size:24px;"><i class="fas fa-user-graduate"></i> <?php _e('Student Profile', 'al-huffaz-portal'); ?></h2>
                        <button onclick="this.closest('#studentProfileModal').remove()" style="background:rgba(255,255,255,0.2);border:none;font-size:24px;cursor:pointer;color:#fff;padding:8px;width:40px;height:40px;display:flex;align-items:center;justify-content:center;border-radius:8px;transition:all 0.3s;" onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div id="studentProfileContent" style="padding:0;">${content}</div>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
    }

    function renderStudentProfile(student) {
        // Parse subjects if it's a string
        const subjects = typeof student.subjects === 'string' ? JSON.parse(student.subjects) : (student.subjects || []);

        // Prepare academic info
        const academicYear = student.academic_year || 'N/A';
        const academicTerm = student.academic_term === 'mid' ? 'Mid Term' : (student.academic_term === 'annual' ? 'Annual' : 'N/A');
        const gradeLevel = student.grade_level || 'N/A';

        // Get photo
        let photoHTML = '';
        if (student.student_photo) {
            photoHTML = `<img src="${student.student_photo}" alt="${student.name}" style="width:150px;height:150px;border-radius:50%;object-fit:cover;border:5px solid #fff;box-shadow:0 4px 12px rgba(0,0,0,0.2);">`;
        } else {
            const initial = student.name ? student.name.charAt(0).toUpperCase() : '?';
            photoHTML = `<div style="width:150px;height:150px;border-radius:50%;background:linear-gradient(135deg,#667eea,#764ba2);display:flex;align-items:center;justify-content:center;font-size:60px;font-weight:700;color:#fff;border:5px solid #fff;box-shadow:0 4px 12px rgba(0,0,0,0.2);">${initial}</div>`;
        }

        let content = `
            <!-- Student Header -->
            <div style="background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);color:#fff;padding:40px 32px;text-align:center;">
                ${photoHTML}
                <h1 style="margin:20px 0 8px 0;font-size:32px;">${student.name}</h1>
                <div style="font-size:16px;opacity:0.9;display:flex;justify-content:center;gap:24px;flex-wrap:wrap;margin-top:12px;">
                    ${student.gr_number ? `<span><i class="fas fa-id-card"></i> GR: ${student.gr_number}</span>` : ''}
                    ${student.gender ? `<span><i class="fas fa-${student.gender === 'male' ? 'mars' : 'venus'}"></i> ${student.gender.charAt(0).toUpperCase() + student.gender.slice(1)}</span>` : ''}
                    ${gradeLevel !== 'N/A' ? `<span><i class="fas fa-graduation-cap"></i> Grade: ${gradeLevel}</span>` : ''}
                </div>
            </div>

            <!-- Tabs -->
            <div style="background:var(--ahp-hover);border-bottom:2px solid var(--ahp-border);display:flex;padding:0 32px;gap:8px;">
                <button class="student-tab active" onclick="switchStudentTab('basic')" style="padding:16px 24px;background:none;border:none;border-bottom:3px solid transparent;cursor:pointer;font-size:15px;font-weight:600;color:var(--ahp-text-muted);transition:all 0.3s;">
                    <i class="fas fa-user"></i> <?php _e('Basic Info', 'al-huffaz-portal'); ?>
                </button>
                <button class="student-tab" onclick="switchStudentTab('academics')" style="padding:16px 24px;background:none;border:none;border-bottom:3px solid transparent;cursor:pointer;font-size:15px;font-weight:600;color:var(--ahp-text-muted);transition:all 0.3s;">
                    <i class="fas fa-graduation-cap"></i> <?php _e('Academic Records', 'al-huffaz-portal'); ?>
                </button>
                <button class="student-tab" onclick="switchStudentTab('family')" style="padding:16px 24px;background:none;border:none;border-bottom:3px solid transparent;cursor:pointer;font-size:15px;font-weight:600;color:var(--ahp-text-muted);transition:all 0.3s;">
                    <i class="fas fa-users"></i> <?php _e('Family', 'al-huffaz-portal'); ?>
                </button>
            </div>

            <!-- Tab Content -->
            <div style="padding:32px;">
                <!-- Basic Info Tab -->
                <div id="student-tab-basic" class="student-tab-content">
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:20px;">
                        <div style="background:var(--ahp-hover);padding:20px;border-radius:10px;">
                            <div style="font-size:13px;color:var(--ahp-text-muted);margin-bottom:6px;"><i class="fas fa-calendar"></i> Date of Birth</div>
                            <div style="font-size:16px;font-weight:600;">${student.date_of_birth || 'N/A'}</div>
                        </div>
                        <div style="background:var(--ahp-hover);padding:20px;border-radius:10px;">
                            <div style="font-size:13px;color:var(--ahp-text-muted);margin-bottom:6px;"><i class="fas fa-calendar-check"></i> Admission Date</div>
                            <div style="font-size:16px;font-weight:600;">${student.admission_date || 'N/A'}</div>
                        </div>
                        <div style="background:var(--ahp-hover);padding:20px;border-radius:10px;">
                            <div style="font-size:13px;color:var(--ahp-text-muted);margin-bottom:6px;"><i class="fas fa-book-quran"></i> Islamic Studies</div>
                            <div style="font-size:16px;font-weight:600;">${student.islamic_studies_category || 'N/A'}</div>
                        </div>
                        <div style="background:var(--ahp-hover);padding:20px;border-radius:10px;">
                            <div style="font-size:13px;color:var(--ahp-text-muted);margin-bottom:6px;"><i class="fas fa-quran"></i> Hifz Status</div>
                            <div style="font-size:16px;font-weight:600;">${student.hifz_status || 'N/A'}</div>
                        </div>
                    </div>
                    ${student.permanent_address || student.current_address ? `
                    <div style="margin-top:24px;background:var(--ahp-hover);padding:24px;border-radius:10px;">
                        <h3 style="margin:0 0 16px 0;font-size:18px;"><i class="fas fa-map-marker-alt"></i> <?php _e('Address', 'al-huffaz-portal'); ?></h3>
                        ${student.permanent_address ? `<div style="margin-bottom:12px;"><strong><?php _e('Permanent:', 'al-huffaz-portal'); ?></strong> ${student.permanent_address}</div>` : ''}
                        ${student.current_address ? `<div><strong><?php _e('Current:', 'al-huffaz-portal'); ?></strong> ${student.current_address}</div>` : ''}
                    </div>
                    ` : ''}
                </div>

                <!-- Academic Records Tab -->
                <div id="student-tab-academics" class="student-tab-content" style="display:none;">
                    ${renderAcademicRecords(student, subjects, academicYear, academicTerm, gradeLevel)}
                </div>

                <!-- Family Tab -->
                <div id="student-tab-family" class="student-tab-content" style="display:none;">
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:24px;">
                        ${student.father_name || student.father_phone || student.father_email ? `
                        <div style="background:var(--ahp-hover);padding:24px;border-radius:10px;">
                            <h3 style="margin:0 0 16px 0;font-size:18px;color:var(--ahp-primary);"><i class="fas fa-male"></i> <?php _e('Father Information', 'al-huffaz-portal'); ?></h3>
                            ${student.father_name ? `<div style="margin-bottom:12px;"><strong><?php _e('Name:', 'al-huffaz-portal'); ?></strong> ${student.father_name}</div>` : ''}
                            ${student.father_cnic ? `<div style="margin-bottom:12px;"><strong><?php _e('CNIC:', 'al-huffaz-portal'); ?></strong> ${student.father_cnic}</div>` : ''}
                            ${student.father_phone ? `<div style="margin-bottom:12px;"><strong><?php _e('Phone:', 'al-huffaz-portal'); ?></strong> <a href="tel:${student.father_phone}">${student.father_phone}</a></div>` : ''}
                            ${student.father_email ? `<div><strong><?php _e('Email:', 'al-huffaz-portal'); ?></strong> <a href="mailto:${student.father_email}">${student.father_email}</a></div>` : ''}
                        </div>
                        ` : ''}
                        ${student.guardian_name || student.guardian_phone ? `
                        <div style="background:var(--ahp-hover);padding:24px;border-radius:10px;">
                            <h3 style="margin:0 0 16px 0;font-size:18px;color:var(--ahp-primary);"><i class="fas fa-user-shield"></i> <?php _e('Guardian Information', 'al-huffaz-portal'); ?></h3>
                            ${student.guardian_name ? `<div style="margin-bottom:12px;"><strong><?php _e('Name:', 'al-huffaz-portal'); ?></strong> ${student.guardian_name}</div>` : ''}
                            ${student.guardian_relation ? `<div style="margin-bottom:12px;"><strong><?php _e('Relation:', 'al-huffaz-portal'); ?></strong> ${student.guardian_relation}</div>` : ''}
                            ${student.guardian_phone ? `<div style="margin-bottom:12px;"><strong><?php _e('Phone:', 'al-huffaz-portal'); ?></strong> <a href="tel:${student.guardian_phone}">${student.guardian_phone}</a></div>` : ''}
                            ${student.guardian_cnic ? `<div><strong><?php _e('CNIC:', 'al-huffaz-portal'); ?></strong> ${student.guardian_cnic}</div>` : ''}
                        </div>
                        ` : ''}
                    </div>
                </div>
            </div>
        `;

        showStudentModal(content);
    }

    function renderAcademicRecords(student, subjects, academicYear, academicTerm, gradeLevel) {
        if (!subjects || subjects.length === 0 || !subjects[0] || !subjects[0].name) {
            return `
                <div style="text-align:center;padding:60px 20px;color:var(--ahp-text-muted);">
                    <i class="fas fa-clipboard-list" style="font-size:64px;opacity:0.3;margin-bottom:20px;"></i>
                    <h3 style="margin:0 0 12px 0;"><?php _e('No Academic Records Yet', 'al-huffaz-portal'); ?></h3>
                    <p style="margin:0;"><?php _e('Academic records will appear here once subjects and marks are added.', 'al-huffaz-portal'); ?></p>
                </div>
            `;
        }

        let html = `
            <!-- Academic Period Info -->
            <div style="background:var(--ahp-hover);padding:20px 24px;border-radius:10px;margin-bottom:24px;display:flex;gap:32px;flex-wrap:wrap;">
                <div><strong><?php _e('Academic Year:', 'al-huffaz-portal'); ?></strong> ${academicYear}</div>
                <div><strong><?php _e('Grade:', 'al-huffaz-portal'); ?></strong> ${gradeLevel}</div>
                <div><strong><?php _e('Term:', 'al-huffaz-portal'); ?></strong> ${academicTerm}</div>
            </div>

            <!-- Subjects Result Cards -->
        `;

        let grandTotalObtained = 0;
        let grandTotalMarks = 0;

        subjects.forEach(subject => {
            if (!subject || !subject.name) return;

            const monthlyExams = subject.monthly_exams || [];
            const midSemester = subject.mid_semester || {};
            const finalSemester = subject.final_semester || {};

            let subjectObtained = 0;
            let subjectTotal = 0;

            // Calculate mid semester
            const midTotal = (parseInt(midSemester.oral_total) || 0) + (parseInt(midSemester.written_total) || 0);
            const midObtained = (parseInt(midSemester.oral_obtained) || 0) + (parseInt(midSemester.written_obtained) || 0);
            const midPct = midTotal > 0 ? Math.round((midObtained / midTotal) * 100) : 0;
            if (midTotal > 0) {
                subjectObtained += midObtained;
                subjectTotal += midTotal;
            }

            // Calculate final semester
            const finalTotal = (parseInt(finalSemester.oral_total) || 0) + (parseInt(finalSemester.written_total) || 0);
            const finalObtained = (parseInt(finalSemester.oral_obtained) || 0) + (parseInt(finalSemester.written_obtained) || 0);
            const finalPct = finalTotal > 0 ? Math.round((finalObtained / finalTotal) * 100) : 0;
            if (finalTotal > 0) {
                subjectObtained += finalObtained;
                subjectTotal += finalTotal;
            }

            // Add monthly exams
            monthlyExams.forEach(monthly => {
                const mTotal = (parseInt(monthly.oral_total) || 0) + (parseInt(monthly.written_total) || 0);
                const mObtained = (parseInt(monthly.oral_obtained) || 0) + (parseInt(monthly.written_obtained) || 0);
                if (mTotal > 0) {
                    subjectObtained += mObtained;
                    subjectTotal += mTotal;
                }
            });

            const subjectPct = subjectTotal > 0 ? Math.round((subjectObtained / subjectTotal) * 100) : 0;
            const grade = getGrade(subjectPct);
            const gradeColor = getGradeColor(subjectPct);

            grandTotalObtained += subjectObtained;
            grandTotalMarks += subjectTotal;

            html += `
                <div style="background:var(--ahp-bg);border:2px solid var(--ahp-border);border-radius:12px;margin-bottom:20px;overflow:hidden;">
                    <!-- Subject Header -->
                    <div style="background:linear-gradient(135deg, ${gradeColor}, ${gradeColor}dd);color:#fff;padding:16px 24px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:16px;">
                        <h3 style="margin:0;font-size:20px;"><i class="fas fa-book"></i> ${subject.name}</h3>
                        <div style="display:flex;gap:16px;align-items:center;">
                            <div style="text-align:right;">
                                <div style="font-size:24px;font-weight:700;">${subjectObtained}/${subjectTotal}</div>
                                <div style="font-size:14px;opacity:0.9;">${subjectPct}%</div>
                            </div>
                            <div style="background:rgba(255,255,255,0.3);padding:8px 16px;border-radius:8px;font-size:20px;font-weight:700;">${grade}</div>
                        </div>
                    </div>

                    <!-- Marks Table -->
                    <div style="overflow-x:auto;">
                        <table style="width:100%;border-collapse:collapse;font-size:13px;">
                            <thead style="background:var(--ahp-hover);">
                                <tr>
                                    <th style="padding:12px;text-align:left;border-bottom:2px solid var(--ahp-border);"><?php _e('Examination', 'al-huffaz-portal'); ?></th>
                                    <th style="padding:12px;text-align:center;border-bottom:2px solid var(--ahp-border);"><?php _e('Oral Total', 'al-huffaz-portal'); ?></th>
                                    <th style="padding:12px;text-align:center;border-bottom:2px solid var(--ahp-border);"><?php _e('Oral Obt.', 'al-huffaz-portal'); ?></th>
                                    <th style="padding:12px;text-align:center;border-bottom:2px solid var(--ahp-border);"><?php _e('Written Total', 'al-huffaz-portal'); ?></th>
                                    <th style="padding:12px;text-align:center;border-bottom:2px solid var(--ahp-border);"><?php _e('Written Obt.', 'al-huffaz-portal'); ?></th>
                                    <th style="padding:12px;text-align:center;border-bottom:2px solid var(--ahp-border);"><?php _e('Total', 'al-huffaz-portal'); ?></th>
                                    <th style="padding:12px;text-align:center;border-bottom:2px solid var(--ahp-border);"><?php _e('Obtained', 'al-huffaz-portal'); ?></th>
                                    <th style="padding:12px;text-align:center;border-bottom:2px solid var(--ahp-border);">%</th>
                                    <th style="padding:12px;text-align:center;border-bottom:2px solid var(--ahp-border);"><?php _e('Grade', 'al-huffaz-portal'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
            `;

            // Monthly exams rows
            if (monthlyExams && monthlyExams.length > 0) {
                monthlyExams.forEach((monthly, idx) => {
                    const mOralTotal = parseInt(monthly.oral_total) || 0;
                    const mOralObt = parseInt(monthly.oral_obtained) || 0;
                    const mWrittenTotal = parseInt(monthly.written_total) || 0;
                    const mWrittenObt = parseInt(monthly.written_obtained) || 0;
                    const mTotal = mOralTotal + mWrittenTotal;
                    const mObt = mOralObt + mWrittenObt;
                    const mPct = mTotal > 0 ? Math.round((mObt / mTotal) * 100) : 0;
                    const mGrade = getGrade(mPct);

                    html += `
                        <tr style="border-bottom:1px solid var(--ahp-border);">
                            <td style="padding:12px;font-weight:500;"><i class="fas fa-calendar-alt"></i> ${monthly.month_name || 'Monthly ' + (idx + 1)}</td>
                            <td style="padding:12px;text-align:center;">${mOralTotal}</td>
                            <td style="padding:12px;text-align:center;">${mOralObt}</td>
                            <td style="padding:12px;text-align:center;">${mWrittenTotal}</td>
                            <td style="padding:12px;text-align:center;">${mWrittenObt}</td>
                            <td style="padding:12px;text-align:center;font-weight:600;">${mTotal}</td>
                            <td style="padding:12px;text-align:center;font-weight:600;">${mObt}</td>
                            <td style="padding:12px;text-align:center;font-weight:600;">${mPct}%</td>
                            <td style="padding:12px;text-align:center;"><span style="padding:4px 8px;background:${getGradeColor(mPct)};color:#fff;border-radius:4px;font-weight:600;">${mGrade}</span></td>
                        </tr>
                    `;
                });
            }

            // Mid semester row
            if (midTotal > 0) {
                html += `
                    <tr style="background:var(--ahp-hover);border-bottom:1px solid var(--ahp-border);">
                        <td style="padding:12px;font-weight:600;"><i class="fas fa-bookmark"></i> <?php _e('Mid Semester', 'al-huffaz-portal'); ?></td>
                        <td style="padding:12px;text-align:center;">${midSemester.oral_total || 0}</td>
                        <td style="padding:12px;text-align:center;">${midSemester.oral_obtained || 0}</td>
                        <td style="padding:12px;text-align:center;">${midSemester.written_total || 0}</td>
                        <td style="padding:12px;text-align:center;">${midSemester.written_obtained || 0}</td>
                        <td style="padding:12px;text-align:center;font-weight:700;">${midTotal}</td>
                        <td style="padding:12px;text-align:center;font-weight:700;">${midObtained}</td>
                        <td style="padding:12px;text-align:center;font-weight:700;">${midPct}%</td>
                        <td style="padding:12px;text-align:center;"><span style="padding:4px 8px;background:${getGradeColor(midPct)};color:#fff;border-radius:4px;font-weight:700;">${getGrade(midPct)}</span></td>
                    </tr>
                `;
            }

            // Final semester row
            if (finalTotal > 0) {
                html += `
                    <tr style="background:var(--ahp-hover);border-bottom:1px solid var(--ahp-border);">
                        <td style="padding:12px;font-weight:600;"><i class="fas fa-certificate"></i> <?php _e('Final Semester', 'al-huffaz-portal'); ?></td>
                        <td style="padding:12px;text-align:center;">${finalSemester.oral_total || 0}</td>
                        <td style="padding:12px;text-align:center;">${finalSemester.oral_obtained || 0}</td>
                        <td style="padding:12px;text-align:center;">${finalSemester.written_total || 0}</td>
                        <td style="padding:12px;text-align:center;">${finalSemester.written_obtained || 0}</td>
                        <td style="padding:12px;text-align:center;font-weight:700;">${finalTotal}</td>
                        <td style="padding:12px;text-align:center;font-weight:700;">${finalObtained}</td>
                        <td style="padding:12px;text-align:center;font-weight:700;">${finalPct}%</td>
                        <td style="padding:12px;text-align:center;"><span style="padding:4px 8px;background:${getGradeColor(finalPct)};color:#fff;border-radius:4px;font-weight:700;">${getGrade(finalPct)}</span></td>
                    </tr>
                `;
            }

            html += `
                            </tbody>
                        </table>
                    </div>
                </div>
            `;
        });

        // Grand Total
        const grandPct = grandTotalMarks > 0 ? Math.round((grandTotalObtained / grandTotalMarks) * 100) : 0;
        const grandGrade = getGrade(grandPct);
        const grandColor = getGradeColor(grandPct);

        html += `
            <!-- Grand Total -->
            <div style="background:linear-gradient(135deg, ${grandColor}, ${grandColor}dd);color:#fff;padding:24px;border-radius:12px;margin-top:24px;">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:16px;">
                    <h2 style="margin:0;font-size:24px;"><i class="fas fa-trophy"></i> <?php _e('Overall Performance', 'al-huffaz-portal'); ?></h2>
                    <div style="display:flex;gap:24px;align-items:center;">
                        <div style="text-align:right;">
                            <div style="font-size:14px;opacity:0.9;margin-bottom:4px;"><?php _e('Total Marks', 'al-huffaz-portal'); ?></div>
                            <div style="font-size:32px;font-weight:700;">${grandTotalObtained}/${grandTotalMarks}</div>
                        </div>
                        <div style="text-align:right;">
                            <div style="font-size:14px;opacity:0.9;margin-bottom:4px;"><?php _e('Percentage', 'al-huffaz-portal'); ?></div>
                            <div style="font-size:32px;font-weight:700;">${grandPct}%</div>
                        </div>
                        <div style="background:rgba(255,255,255,0.3);padding:12px 24px;border-radius:12px;">
                            <div style="font-size:14px;opacity:0.9;margin-bottom:4px;"><?php _e('Grade', 'al-huffaz-portal'); ?></div>
                            <div style="font-size:32px;font-weight:700;">${grandGrade}</div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        return html;
    }

    function getGrade(percentage) {
        if (percentage >= 90) return 'A+';
        if (percentage >= 80) return 'A';
        if (percentage >= 70) return 'B';
        if (percentage >= 60) return 'C';
        if (percentage >= 50) return 'D';
        return 'F';
    }

    function getGradeColor(percentage) {
        if (percentage >= 80) return '#10b981'; // Green
        if (percentage >= 60) return '#3b82f6'; // Blue
        if (percentage >= 50) return '#f59e0b'; // Orange
        return '#ef4444'; // Red
    }

    window.switchStudentTab = function(tabName) {
        // Update tab buttons
        document.querySelectorAll('.student-tab').forEach(btn => {
            btn.style.borderBottom = '3px solid transparent';
            btn.style.color = 'var(--ahp-text-muted)';
        });
        event.target.style.borderBottom = '3px solid var(--ahp-primary)';
        event.target.style.color = 'var(--ahp-primary)';

        // Update tab content
        document.querySelectorAll('.student-tab-content').forEach(content => {
            content.style.display = 'none';
        });
        document.getElementById('student-tab-' + tabName).style.display = 'block';
    };

    // FIX #18: Enhanced delete with sponsorship check
    window.deleteStudent = function(id, studentName) {
        if (!confirm(`<?php _e('Are you sure you want to delete', 'al-huffaz-portal'); ?> ${studentName || 'this student'}?\n\n<?php _e('This action cannot be undone.', 'al-huffaz-portal'); ?>`)) {
            return;
        }

        // Show loading toast
        showToast('<?php _e('Checking for active sponsorships...', 'al-huffaz-portal'); ?>', 'info');

        fetch(ajaxUrl, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({action: 'alhuffaz_delete_student', nonce, student_id: id})
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showToast('<?php _e('Student deleted successfully', 'al-huffaz-portal'); ?>', 'success');
                refreshDashboardStats();
                loadStudents(currentPage);
            } else {
                // Check if error is due to active sponsorships
                if (data.data?.has_sponsorships) {
                    showToast(data.data.message, 'error');
                    // Show helpful guidance
                    setTimeout(() => {
                        showToast(`<?php _e('Navigate to Active Sponsorships tab to unlink this student first.', 'al-huffaz-portal'); ?>`, 'info');
                    }, 3000);
                } else {
                    showToast(data.data?.message || '<?php _e('Error deleting student', 'al-huffaz-portal'); ?>', 'error');
                }
            }
        })
        .catch(err => {
            showToast('<?php _e('Network error. Please try again.', 'al-huffaz-portal'); ?>', 'error');
        });
    };

    // ==================== FORM STEPS ====================
    function updateSteps() {
        document.querySelectorAll('.ahp-form-step').forEach(s => s.classList.remove('active'));
        document.querySelector(`.ahp-form-step[data-step="${currentStep}"]`)?.classList.add('active');
        document.querySelectorAll('.ahp-step').forEach((s, i) => {
            s.classList.remove('active', 'completed');
            if (i + 1 === currentStep) s.classList.add('active');
            else if (i + 1 < currentStep) s.classList.add('completed');
        });
        document.querySelectorAll('.ahp-step-line').forEach((l, i) => {
            l.classList.toggle('completed', i + 1 < currentStep);
        });
        document.getElementById('prevBtn').style.display = currentStep === 1 ? 'none' : 'inline-flex';
        document.getElementById('nextBtn').style.display = currentStep === totalSteps ? 'none' : 'inline-flex';
        document.getElementById('submitBtn').style.display = currentStep === totalSteps ? 'inline-flex' : 'none';
    }

    document.getElementById('nextBtn').addEventListener('click', () => { if (currentStep < totalSteps) { currentStep++; updateSteps(); } });
    document.getElementById('prevBtn').addEventListener('click', () => { if (currentStep > 1) { currentStep--; updateSteps(); } });

    // ==================== SUBJECTS ====================
    document.getElementById('addSubjectBtn').addEventListener('click', addSubject);

    function addSubject() {
        document.getElementById('noSubjectsMsg').style.display = 'none';
        const html = `
        <div class="ahp-subject-box" data-index="${subjectIndex}">
            <div class="ahp-subject-header">
                <div class="ahp-subject-title"><i class="fas fa-book"></i><input type="text" name="subjects[${subjectIndex}][name]" class="ahp-subject-name" placeholder="Subject Name"></div>
                <button type="button" class="ahp-btn ahp-btn-danger ahp-btn-sm remove-subject"><i class="fas fa-trash"></i></button>
            </div>
            <div class="ahp-subject-content">
                <div class="ahp-exam-section">
                    <div class="ahp-exam-header"><h4><i class="fas fa-calendar-alt"></i> Monthly Exams</h4><button type="button" class="ahp-btn ahp-btn-primary ahp-btn-sm add-monthly" data-subject="${subjectIndex}"><i class="fas fa-plus"></i> Add Month</button></div>
                    <div class="monthly-container" data-subject="${subjectIndex}"></div>
                </div>
                <div class="ahp-exam-section">
                    <h4><i class="fas fa-book-open"></i> Mid Semester Exam</h4>
                    <div class="ahp-marks-row">
                        <div class="ahp-marks-group"><label>Oral Total</label><input type="number" name="subjects[${subjectIndex}][mid_semester][oral_total]" min="0"></div>
                        <div class="ahp-marks-group"><label>Oral Obtained</label><input type="number" name="subjects[${subjectIndex}][mid_semester][oral_obtained]" min="0"></div>
                        <div class="ahp-marks-group"><label>Written Total</label><input type="number" name="subjects[${subjectIndex}][mid_semester][written_total]" min="0"></div>
                        <div class="ahp-marks-group"><label>Written Obtained</label><input type="number" name="subjects[${subjectIndex}][mid_semester][written_obtained]" min="0"></div>
                    </div>
                </div>
                <div class="ahp-exam-section">
                    <h4><i class="fas fa-graduation-cap"></i> Annual Exam</h4>
                    <div class="ahp-marks-row">
                        <div class="ahp-marks-group"><label>Oral Total</label><input type="number" name="subjects[${subjectIndex}][final_semester][oral_total]" min="0"></div>
                        <div class="ahp-marks-group"><label>Oral Obtained</label><input type="number" name="subjects[${subjectIndex}][final_semester][oral_obtained]" min="0"></div>
                        <div class="ahp-marks-group"><label>Written Total</label><input type="number" name="subjects[${subjectIndex}][final_semester][written_total]" min="0"></div>
                        <div class="ahp-marks-group"><label>Written Obtained</label><input type="number" name="subjects[${subjectIndex}][final_semester][written_obtained]" min="0"></div>
                    </div>
                </div>
                <div class="ahp-exam-section">
                    <h4><i class="fas fa-comment-dots"></i> Teacher Assessment</h4>
                    <div class="ahp-form-grid">
                        <div class="ahp-form-group ahp-col-6"><label class="ahp-label">Strengths</label><textarea name="subjects[${subjectIndex}][strengths]" class="ahp-input" rows="2"></textarea></div>
                        <div class="ahp-form-group ahp-col-6"><label class="ahp-label">Areas for Improvement</label><textarea name="subjects[${subjectIndex}][areas_for_improvement]" class="ahp-input" rows="2"></textarea></div>
                        <div class="ahp-form-group ahp-col-12"><label class="ahp-label">Teacher Comments</label><textarea name="subjects[${subjectIndex}][teacher_comments]" class="ahp-input" rows="2"></textarea></div>
                    </div>
                </div>
            </div>
        </div>`;
        document.getElementById('subjectsContainer').insertAdjacentHTML('beforeend', html);
        subjectIndex++;
    }

    document.getElementById('subjectsContainer').addEventListener('click', function(e) {
        if (e.target.closest('.remove-subject')) {
            e.target.closest('.ahp-subject-box').remove();
            if (!document.querySelectorAll('.ahp-subject-box').length) document.getElementById('noSubjectsMsg').style.display = 'block';
        }
        if (e.target.closest('.add-monthly')) {
            const btn = e.target.closest('.add-monthly');
            const subj = btn.dataset.subject;
            const container = document.querySelector(`.monthly-container[data-subject="${subj}"]`);
            const midx = container.querySelectorAll('.ahp-monthly-exam').length;
            container.insertAdjacentHTML('beforeend', `
                <div class="ahp-monthly-exam" data-month="${midx}">
                    <div class="ahp-monthly-header">
                        <input type="text" name="subjects[${subj}][monthly_exams][${midx}][month_name]" class="ahp-month-name" placeholder="Month Name">
                        <button type="button" class="ahp-btn ahp-btn-danger ahp-btn-xs remove-monthly"><i class="fas fa-times"></i></button>
                    </div>
                    <div class="ahp-marks-row">
                        <div class="ahp-marks-group"><label>Oral Total</label><input type="number" name="subjects[${subj}][monthly_exams][${midx}][oral_total]" min="0"></div>
                        <div class="ahp-marks-group"><label>Oral Obtained</label><input type="number" name="subjects[${subj}][monthly_exams][${midx}][oral_obtained]" min="0"></div>
                        <div class="ahp-marks-group"><label>Written Total</label><input type="number" name="subjects[${subj}][monthly_exams][${midx}][written_total]" min="0"></div>
                        <div class="ahp-marks-group"><label>Written Obtained</label><input type="number" name="subjects[${subj}][monthly_exams][${midx}][written_obtained]" min="0"></div>
                    </div>
                </div>
            `);
        }
        if (e.target.closest('.remove-monthly')) {
            e.target.closest('.ahp-monthly-exam').remove();
        }
    });

    // ==================== ATTENDANCE ====================
    function updateAttendance() {
        const total = parseInt(document.getElementById('totalDays')?.value) || 0;
        const present = parseInt(document.getElementById('presentDays')?.value) || 0;
        const pct = total > 0 ? Math.round((present / total) * 100) : 0;
        document.getElementById('attendanceDisplay').textContent = pct + '%';
        document.getElementById('attendanceDisplay').style.color = pct >= 75 ? 'var(--ahp-success)' : 'var(--ahp-danger)';
    }
    document.getElementById('totalDays')?.addEventListener('input', updateAttendance);
    document.getElementById('presentDays')?.addEventListener('input', updateAttendance);
    updateAttendance();

    // ==================== FEES ====================
    function updateFees() {
        const monthly = parseFloat(document.querySelector('[name="monthly_tuition_fee"]')?.value) || 0;
        const course = parseFloat(document.querySelector('[name="course_fee"]')?.value) || 0;
        const uniform = parseFloat(document.querySelector('[name="uniform_fee"]')?.value) || 0;
        const annual = parseFloat(document.querySelector('[name="annual_fee"]')?.value) || 0;
        const admission = parseFloat(document.querySelector('[name="admission_fee"]')?.value) || 0;
        const oneTime = course + uniform + annual + admission;
        document.getElementById('monthlyTotal').textContent = 'PKR ' + monthly.toLocaleString();
        document.getElementById('oneTimeTotal').textContent = 'PKR ' + oneTime.toLocaleString();
        document.getElementById('grandTotal').textContent = 'PKR ' + (monthly + oneTime).toLocaleString();
    }
    document.querySelectorAll('.fee-input').forEach(i => i.addEventListener('input', updateFees));
    updateFees();

    // ==================== RATINGS ====================
    document.querySelectorAll('.ahp-stars').forEach(container => {
        container.querySelectorAll('.ahp-star').forEach(star => {
            star.addEventListener('click', function() {
                const value = this.dataset.value;
                const input = container.querySelector('input');
                input.value = value;
                container.querySelectorAll('.ahp-star').forEach((s, i) => {
                    s.classList.toggle('active', i < value);
                });
            });
        });
    });

    // ==================== FORM SUBMIT ====================
    // ==================== STUDENT FORM VALIDATION & SUBMISSION ====================
    document.getElementById('studentForm').addEventListener('submit', function(e) {
        e.preventDefault();

        // FIX #17: Client-side validation before AJAX submission
        const formData = new FormData(this);
        const studentName = formData.get('student_name');
        const grNumber = formData.get('gr_number');
        const gender = formData.get('gender');

        // Validate required fields
        const validationErrors = [];

        if (!studentName || studentName.trim().length < 2) {
            validationErrors.push('<?php _e('Student name must be at least 2 characters', 'al-huffaz-portal'); ?>');
        }

        if (!grNumber || grNumber.trim().length === 0) {
            validationErrors.push('<?php _e('GR Number is required', 'al-huffaz-portal'); ?>');
        }

        if (!gender) {
            validationErrors.push('<?php _e('Please select student gender', 'al-huffaz-portal'); ?>');
        }

        // Email validation if provided
        const guardianEmail = formData.get('guardian_email');
        if (guardianEmail && guardianEmail.length > 0) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(guardianEmail)) {
                validationErrors.push('<?php _e('Guardian email format is invalid', 'al-huffaz-portal'); ?>');
            }
        }

        // Show validation errors
        if (validationErrors.length > 0) {
            showToast(validationErrors.join(' • '), 'error');
            // Scroll to first error
            const firstInvalidInput = this.querySelector(':invalid, [required]:not([value])');
            if (firstInvalidInput) {
                firstInvalidInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
                firstInvalidInput.focus();
            }
            return;
        }

        const btn = document.getElementById('submitBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <?php _e('Saving...', 'al-huffaz-portal'); ?>';

        fetch(ajaxUrl, {method: 'POST', body: formData})
        .then(r => {
            if (!r.ok && (r.status === 403 || r.status === 401)) {
                throw new Error('SESSION_EXPIRED');
            }
            return r.json();
        })
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save"></i> <?php echo $is_edit ? "Update" : "Enroll"; ?> Student';
            if (data.success) {
                showToast(data.data?.message || '<?php _e('Student saved successfully!', 'al-huffaz-portal'); ?>', 'success');
                // FIX #1: Refresh dashboard stats after saving student
                refreshDashboardStats();
                // Clean URL to remove edit parameter
                if (window.history.replaceState) {
                    window.history.replaceState({}, document.title, window.location.pathname);
                }
                setTimeout(() => {
                    showPanel('students');
                    loadStudents(1); // Refresh student list
                }, 1000);
            } else {
                showToast(data.data?.message || '<?php _e('Error saving student', 'al-huffaz-portal'); ?>', 'error');
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save"></i> <?php echo $is_edit ? "Update" : "Enroll"; ?> Student';

            if (err.message === 'SESSION_EXPIRED') {
                showToast('<?php _e('Session expired. Refreshing page...', 'al-huffaz-portal'); ?>', 'error');
                setTimeout(() => location.reload(), 2000);
            } else {
                showToast('<?php _e('Network error. Please check connection and retry.', 'al-huffaz-portal'); ?>', 'error');
            }
        });
    });

    function resetForm() {
        document.getElementById('studentForm').reset();
        document.getElementById('studentId').value = 0;
        document.getElementById('formTitle').textContent = '<?php _e('Add New Student', 'al-huffaz-portal'); ?>';
        document.getElementById('subjectsContainer').innerHTML = '';
        document.getElementById('noSubjectsMsg').style.display = 'block';
        currentStep = 1;
        subjectIndex = 0;
        updateSteps();
        updateFees();
        updateAttendance();
    }

    // ==================== DASHBOARD STATS AUTO-REFRESH ====================
    // FIX #1: Auto-refresh dashboard stats after any action
    window.refreshDashboardStats = function() {
        fetch(ajaxUrl, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({action: 'alhuffaz_get_dashboard_stats', nonce})
        })
        .then(r => r.json())
        .then(data => {
            console.log('Dashboard stats response:', data);
            if (data.success && data.data) {
                const stats = data.data;
                console.log('Stats data:', stats);
                console.log('Pending sponsorships count:', stats.pending_sponsorships_count);
                // Update dashboard stat cards (if on dashboard panel)
                const totalStudentsEl = document.querySelector('.ahp-stat:nth-child(1) .ahp-stat-value');
                const totalSponsorsEl = document.querySelector('.ahp-stat:nth-child(2) .ahp-stat-value');
                const hifzCountEl = document.querySelector('.ahp-stat:nth-child(3) .ahp-stat-value');
                const nazraCountEl = document.querySelector('.ahp-stat:nth-child(4) .ahp-stat-value');
                const inactiveSponsorsEl = document.querySelector('.ahp-stat:nth-child(5) .ahp-stat-value');

                if (totalStudentsEl) totalStudentsEl.textContent = stats.total_students;
                if (totalSponsorsEl) totalSponsorsEl.textContent = stats.total_sponsors;
                if (hifzCountEl) hifzCountEl.textContent = stats.hifz_count;
                if (nazraCountEl) nazraCountEl.textContent = stats.nazra_count;
                if (inactiveSponsorsEl) inactiveSponsorsEl.textContent = stats.inactive_sponsors_count;

                // Update pending badges on navigation tabs
                console.log('Calling updatePendingBadges with:', {
                    pending_sponsor_users_count: stats.pending_sponsor_users_count,
                    pending_sponsorships_count: stats.pending_sponsorships_count,
                    pending_payments_count: stats.pending_payments_count
                });
                updatePendingBadges(stats.pending_sponsor_users_count, stats.pending_sponsorships_count, stats.pending_payments_count);

                // Update pending sponsor request count in Sponsors panel
                const pendingSponsorCountEl = document.getElementById('pendingSponsorCount');
                if (pendingSponsorCountEl) {
                    pendingSponsorCountEl.textContent = stats.pending_sponsorships_count || 0;
                }
            }
        })
        .catch(err => console.error('Failed to refresh stats:', err));
    };

    // Update pending count badges on nav tabs
    function updatePendingBadges(pendingSponsorUsers, pendingSponsorships, pendingPayments) {
        // Update Sponsors tab badge (payment approval requests)
        const sponsorsBadge = document.querySelector('[data-panel="sponsors"] .ahp-nav-badge');
        if (sponsorsBadge) {
            sponsorsBadge.textContent = pendingSponsorships || 0;
            sponsorsBadge.style.display = pendingSponsorships > 0 ? 'inline-block' : 'none';
        }

        // Update Requests subtab badge
        const requestsBadge = document.querySelector('[data-tab="requests"] .ahp-nav-badge');
        if (requestsBadge) {
            requestsBadge.textContent = pendingSponsorships || 0;
            requestsBadge.style.display = pendingSponsorships > 0 ? 'inline-block' : 'none';
        }
    }

    // Call refreshDashboardStats() on page load to set initial badges
    refreshDashboardStats();

    // CRITICAL FIX: Auto-refresh badges every 30 seconds to catch new payment submissions
    setInterval(function() {
        refreshDashboardStats();
    }, 30000); // Refresh every 30 seconds

    // ==================== TOAST ====================
    // FIX #6: Improved toast with longer duration and dismiss button
    window.showToast = function(msg, type) {
        const toast = document.getElementById('toast');
        toast.innerHTML = `
            <span>${msg}</span>
            <button onclick="this.parentElement.style.display='none'" style="margin-left:auto;background:none;border:none;color:inherit;cursor:pointer;font-size:18px;padding:0 8px;">&times;</button>
        `;
        toast.className = 'ahp-toast ' + type;
        toast.style.display = 'flex';
        toast.style.alignItems = 'center';
        toast.style.gap = '12px';
        // Increased from 3s to 5s for better readability
        setTimeout(() => toast.style.display = 'none', 5000);
    };

    // ==================== SPONSORS MANAGEMENT ====================
    let currentSponsorId = null;

    // Tab Switching for Sponsors
    window.switchSponsorTab = function(tab) {
        // Update tab buttons
        document.querySelectorAll('.ahp-sponsor-tab').forEach(btn => {
            if (btn.dataset.tab === tab) {
                btn.classList.add('active');
                btn.style.borderBottom = '3px solid var(--ahp-primary)';
                btn.style.color = 'var(--ahp-primary)';
                btn.style.fontWeight = '600';
            } else {
                btn.classList.remove('active');
                btn.style.borderBottom = '3px solid transparent';
                btn.style.color = 'var(--ahp-text-muted)';
                btn.style.fontWeight = '500';
            }
        });

        // Update tab content
        document.querySelectorAll('.ahp-sponsor-tab-content').forEach(content => {
            content.style.display = 'none';
        });
        document.getElementById('sponsor-tab-' + tab).style.display = 'block';

        // Load requests if switching to requests tab
        if (tab === 'requests') {
            loadSponsors();
        }
    };

    // CRITICAL FIX: Load sponsor statistics for stat cards
    window.loadSponsorStats = function() {
        fetch(ajaxUrl, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({action: 'alhuffaz_get_sponsor_stats', nonce})
        })
        .then(r => r.json())
        .then(data => {
            if (data.success && data.data) {
                const stats = data.data;
                // Update stat cards in Sponsors panel
                const pendingCountEl = document.getElementById('pendingSponsorCount');
                const approvedCountEl = document.getElementById('approvedSponsorCount');

                if (pendingCountEl) pendingCountEl.textContent = stats.pending_count || '0';
                if (approvedCountEl) approvedCountEl.textContent = stats.approved_count || '0';
            }
        })
        .catch(err => console.error('Failed to load sponsor stats:', err));
    };

    window.loadSponsors = function() {
        const status = document.getElementById('filterSponsorStatusMain')?.value || document.getElementById('filterSponsorStatus')?.value || '';
        document.getElementById('sponsorsTableBody').innerHTML = '<tr><td colspan="7" class="ahp-loading"><div class="ahp-spinner"></div></td></tr>';

        fetch(ajaxUrl, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({action: 'alhuffaz_get_sponsorships', nonce, status, per_page: 50})
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                renderSponsors(data.data.sponsorships);
            } else {
                document.getElementById('sponsorsTableBody').innerHTML = '<tr><td colspan="7" style="text-align:center;padding:40px;">Error loading sponsors</td></tr>';
            }
        });
    };

    function renderSponsors(sponsors) {
        const tbody = document.getElementById('sponsorsTableBody');
        if (!sponsors || !sponsors.length) {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:40px;color:var(--ahp-text-muted);">No sponsorship requests found</td></tr>';
            return;
        }

        tbody.innerHTML = sponsors.map(s => {
            const statusBadge = s.status === 'pending' ? 'ahp-badge-warning' :
                               s.status === 'approved' ? 'ahp-badge-success' : 'ahp-badge-danger';
            return `
            <tr>
                <td>
                    <div><strong>${s.sponsor_name || '-'}</strong></div>
                    <small style="color:var(--ahp-text-muted)">${s.sponsor_email || ''}</small>
                </td>
                <td>${s.student_name || '-'}</td>
                <td><strong>${s.amount || '-'}</strong></td>
                <td><span class="ahp-badge ahp-badge-primary">${(s.type || '-').toUpperCase()}</span></td>
                <td><span class="ahp-badge ${statusBadge}">${(s.status || '-').charAt(0).toUpperCase() + (s.status || '-').slice(1)}</span></td>
                <td>${s.date || '-'}</td>
                <td>
                    <div class="ahp-cell-actions">
                        <button class="ahp-btn ahp-btn-secondary ahp-btn-icon" onclick="viewSponsor(${s.id})" title="View"><i class="fas fa-eye"></i></button>
                        ${s.status === 'pending' ? `
                        <button class="ahp-btn ahp-btn-success ahp-btn-icon" onclick="quickApprove(${s.id})" title="Approve"><i class="fas fa-check"></i></button>
                        <button class="ahp-btn ahp-btn-danger ahp-btn-icon" onclick="quickReject(${s.id})" title="Reject"><i class="fas fa-times"></i></button>
                        ` : ''}
                        ${s.status === 'approved' && !s.linked ? `
                        <button class="ahp-btn ahp-btn-primary ahp-btn-icon" onclick="linkSponsor(${s.id})" title="Link to Student"><i class="fas fa-link"></i></button>
                        ` : ''}
                    </div>
                </td>
            </tr>`;
        }).join('');
    }

    window.viewSponsor = function(id) {
        currentSponsorId = id;
        document.getElementById('sponsorModalBody').innerHTML = '<div class="ahp-loading"><div class="ahp-spinner"></div></div>';
        document.getElementById('sponsorModal').style.display = 'flex';

        // Load sponsor details via AJAX
        fetch(ajaxUrl, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({action: 'alhuffaz_get_sponsorship_details', nonce, sponsorship_id: id})
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const s = data.data;
                const statusBadge = s.verification_status === 'pending' ? 'ahp-badge-warning' :
                                   s.verification_status === 'approved' ? 'ahp-badge-success' : 'ahp-badge-danger';

                document.getElementById('sponsorModalBody').innerHTML = `
                    <div style="display:grid;gap:20px;">
                        <div class="ahp-details-grid" style="display:grid;grid-template-columns:repeat(2, 1fr);gap:16px;">
                            <div>
                                <label style="display:block;font-size:12px;color:var(--ahp-text-muted);margin-bottom:4px;">Sponsor Name</label>
                                <strong>${s.sponsor_name || '-'}</strong>
                            </div>
                            <div>
                                <label style="display:block;font-size:12px;color:var(--ahp-text-muted);margin-bottom:4px;">Email</label>
                                <strong>${s.sponsor_email || '-'}</strong>
                            </div>
                            <div>
                                <label style="display:block;font-size:12px;color:var(--ahp-text-muted);margin-bottom:4px;">Student</label>
                                <strong>${s.student_name || '-'}</strong>
                            </div>
                            <div>
                                <label style="display:block;font-size:12px;color:var(--ahp-text-muted);margin-bottom:4px;">Amount</label>
                                <strong>${s.amount || '-'}</strong>
                            </div>
                            <div>
                                <label style="display:block;font-size:12px;color:var(--ahp-text-muted);margin-bottom:4px;">Payment Plan</label>
                                <strong>${s.duration_months} Month${s.duration_months > 1 ? 's' : ''}</strong>
                            </div>
                            <div>
                                <label style="display:block;font-size:12px;color:var(--ahp-text-muted);margin-bottom:4px;">Payment Method</label>
                                <strong>${s.payment_method ? s.payment_method.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) : '-'}</strong>
                            </div>
                            <div>
                                <label style="display:block;font-size:12px;color:var(--ahp-text-muted);margin-bottom:4px;">Transaction ID</label>
                                <strong>${s.transaction_id || '-'}</strong>
                            </div>
                            <div>
                                <label style="display:block;font-size:12px;color:var(--ahp-text-muted);margin-bottom:4px;">Payment Date</label>
                                <strong>${s.payment_date || '-'}</strong>
                            </div>
                            <div>
                                <label style="display:block;font-size:12px;color:var(--ahp-text-muted);margin-bottom:4px;">Status</label>
                                <span class="ahp-badge ${statusBadge}">${(s.verification_status || '-').charAt(0).toUpperCase() + (s.verification_status || '-').slice(1)}</span>
                            </div>
                            <div>
                                <label style="display:block;font-size:12px;color:var(--ahp-text-muted);margin-bottom:4px;">Submitted</label>
                                <strong>${s.created_at || '-'}</strong>
                            </div>
                        </div>
                        ${s.payment_screenshot ? `
                        <div>
                            <label style="display:block;font-size:12px;color:var(--ahp-text-muted);margin-bottom:8px;">Payment Screenshot</label>
                            <img src="${s.payment_screenshot}" style="max-width:100%;border-radius:8px;border:1px solid var(--ahp-border);" alt="Payment proof" onclick="window.open('${s.payment_screenshot}', '_blank')">
                        </div>
                        ` : ''}
                        ${s.notes ? `
                        <div>
                            <label style="display:block;font-size:12px;color:var(--ahp-text-muted);margin-bottom:4px;">Notes</label>
                            <p style="margin:0;padding:12px;background:var(--ahp-bg);border-radius:8px;">${s.notes}</p>
                        </div>
                        ` : ''}
                    </div>
                `;
            } else {
                document.getElementById('sponsorModalBody').innerHTML = `
                    <p style="text-align:center;color:var(--ahp-text-muted);">${data.data?.message || 'Error loading details'}</p>
                `;
            }
        })
        .catch(() => {
            document.getElementById('sponsorModalBody').innerHTML = `
                <p style="text-align:center;color:var(--ahp-text-muted);">Error loading sponsorship details</p>
            `;
        });
    };

    window.closeSponsorModal = function() {
        document.getElementById('sponsorModal').style.display = 'none';
        currentSponsorId = null;
    };

    window.quickApprove = function(id) {
        if (!confirm('<?php _e('Approve this sponsorship? This will create a sponsor account and link them to the student.', 'al-huffaz-portal'); ?>')) return;

        fetch(ajaxUrl, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({action: 'alhuffaz_approve_sponsorship', nonce, sponsorship_id: id})
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showToast('<?php _e('Sponsorship approved successfully!', 'al-huffaz-portal'); ?>', 'success');
                loadSponsors();
                loadSponsorStats(); // CRITICAL FIX: Update stat cards
                refreshDashboardStats(); // CRITICAL FIX: Update badges
            } else {
                showToast(data.data?.message || '<?php _e('Error approving sponsorship', 'al-huffaz-portal'); ?>', 'error');
            }
        });
    };

    window.quickReject = function(id) {
        const reason = prompt('<?php _e('Please enter rejection reason:', 'al-huffaz-portal'); ?>');
        if (reason === null) return;

        fetch(ajaxUrl, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({action: 'alhuffaz_reject_sponsorship', nonce, sponsorship_id: id, reason})
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showToast('<?php _e('Sponsorship rejected', 'al-huffaz-portal'); ?>', 'success');
                loadSponsors();
                loadSponsorStats(); // CRITICAL FIX: Update stat cards
                refreshDashboardStats(); // CRITICAL FIX: Update badges
            } else {
                showToast(data.data?.message || '<?php _e('Error rejecting sponsorship', 'al-huffaz-portal'); ?>', 'error');
            }
        });
    };

    window.approveSponsorship = function() {
        if (currentSponsorId) quickApprove(currentSponsorId);
        closeSponsorModal();
    };

    window.rejectSponsorship = function() {
        if (currentSponsorId) quickReject(currentSponsorId);
        closeSponsorModal();
    };

    window.linkSponsor = function(id) {
        fetch(ajaxUrl, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({action: 'alhuffaz_link_sponsor', nonce, sponsorship_id: id})
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showToast('<?php _e('Sponsor linked to student!', 'al-huffaz-portal'); ?>', 'success');
                loadSponsors();
            } else {
                showToast(data.data?.message || '<?php _e('Error linking sponsor', 'al-huffaz-portal'); ?>', 'error');
            }
        });
    };

    window.unlinkSponsorship = function(id) {
        fetch(ajaxUrl, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({action: 'alhuffaz_unlink_sponsor', nonce, sponsorship_id: id})
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showToast('<?php _e('Sponsorship unlinked successfully!', 'al-huffaz-portal'); ?>', 'success');
                // Reload the page to refresh the active sponsors view
                location.reload();
            } else {
                showToast(data.data?.message || '<?php _e('Error unlinking sponsorship', 'al-huffaz-portal'); ?>', 'error');
            }
        });
    };

    // ==================== SPONSOR USERS MANAGEMENT ====================
    window.loadSponsorUsers = function() {
        const status = document.getElementById('filterUserStatus')?.value || '';
        const search = document.getElementById('searchSponsorUsers')?.value || '';
        document.getElementById('sponsorUsersTableBody').innerHTML = '<tr><td colspan="7" class="ahp-loading"><div class="ahp-spinner"></div></td></tr>';

        fetch(ajaxUrl, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({action: 'alhuffaz_get_sponsor_users', nonce, status, search})
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                renderSponsorUsers(data.data.users);
                updateUserStats(data.data.stats);
            } else {
                document.getElementById('sponsorUsersTableBody').innerHTML = '<tr><td colspan="7" style="text-align:center;padding:40px;">Error loading sponsor users</td></tr>';
            }
        });
    };

    function renderSponsorUsers(users) {
        const tbody = document.getElementById('sponsorUsersTableBody');
        if (!users || !users.length) {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:40px;color:var(--ahp-text-muted);">No sponsor users found</td></tr>';
            return;
        }

        tbody.innerHTML = users.map(u => {
            const statusBadge = u.status === 'pending' ? 'ahp-badge-warning' :
                               u.status === 'approved' ? 'ahp-badge-success' : 'ahp-badge-secondary';
            const statusText = u.status === 'pending' ? 'Pending' :
                              u.status === 'approved' ? 'Approved' : 'Inactive';
            return `
            <tr>
                <td>
                    <div><strong>${u.display_name || u.username || '-'}</strong></div>
                    <small style="color:var(--ahp-text-muted)">ID: ${u.id}</small>
                </td>
                <td><a href="mailto:${u.email}" style="color:var(--ahp-primary)">${u.email}</a></td>
                <td>${u.phone || '-'}</td>
                <td><span class="ahp-badge ${statusBadge}">${statusText}</span></td>
                <td>
                    <strong>${u.active_sponsorships || 0}</strong> active
                    ${u.total_sponsorships > u.active_sponsorships ? `<br><small style="color:var(--ahp-text-muted)">${u.total_sponsorships} total</small>` : ''}
                </td>
                <td>${u.registered || '-'}</td>
                <td>
                    <div class="ahp-cell-actions">
                        <button class="ahp-btn ahp-btn-secondary ahp-btn-icon" onclick="viewSponsorUser(${u.id})" title="View Details"><i class="fas fa-eye"></i></button>
                        ${u.status === 'pending' ? `
                        <button class="ahp-btn ahp-btn-success ahp-btn-icon" onclick="approveSponsorUser(${u.id}, event)" title="Approve User"><i class="fas fa-check"></i></button>
                        <button class="ahp-btn ahp-btn-danger ahp-btn-icon" onclick="rejectSponsorUser(${u.id}, event)" title="Reject User"><i class="fas fa-times"></i></button>
                        ` : ''}
                        ${u.status === 'approved' ? `
                        <button class="ahp-btn ahp-btn-warning ahp-btn-icon" onclick="sendReEngagementEmail(${u.id})" title="Send Re-engagement Email"><i class="fas fa-envelope"></i></button>
                        ` : ''}
                        <button class="ahp-btn ahp-btn-danger ahp-btn-icon" onclick="deleteSponsorUser(${u.id})" title="Delete User"><i class="fas fa-trash"></i></button>
                    </div>
                </td>
            </tr>`;
        }).join('');
    }

    function updateUserStats(stats) {
        if (stats) {
            document.getElementById('pendingUsersCount').textContent = stats.pending || 0;
            document.getElementById('activeUsersCount').textContent = stats.active || 0;
            document.getElementById('inactiveUsersCount').textContent = stats.inactive || 0;
            document.getElementById('totalUsersCount').textContent = stats.total || 0;
        }
    }

    window.viewSponsorUser = function(userId) {
        // Show loading modal
        showSponsorDetailsModal('<div style="text-align:center;padding:40px;"><i class="fas fa-spinner fa-spin" style="font-size:32px;color:var(--ahp-primary);"></i><p style="margin-top:16px;">Loading sponsor details...</p></div>');

        // Fetch sponsor details
        fetch(ajaxUrl, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({action: 'alhuffaz_get_sponsor_user_details', nonce, user_id: userId})
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const u = data.data;
                renderSponsorDetailsModal(u, userId);
            } else {
                showSponsorDetailsModal('<div style="text-align:center;padding:40px;color:var(--ahp-text-muted);">Error loading sponsor details</div>');
            }
        })
        .catch(err => {
            showSponsorDetailsModal('<div style="text-align:center;padding:40px;color:var(--ahp-text-muted);">Network error</div>');
        });
    };

    function showSponsorDetailsModal(content) {
        // Remove existing modal if any
        const existing = document.getElementById('sponsorDetailsModal');
        if (existing) existing.remove();

        // Create modal - CRITICAL FIX: Use solid colors instead of CSS variables
        const modal = document.createElement('div');
        modal.id = 'sponsorDetailsModal';
        modal.innerHTML = `
            <div style="position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.7);z-index:10000;display:flex;align-items:center;justify-content:center;padding:20px;backdrop-filter:blur(4px);" onclick="if(event.target===this) this.parentElement.remove()">
                <div style="background:#ffffff;border-radius:16px;max-width:900px;width:100%;max-height:90vh;overflow-y:auto;box-shadow:0 25px 80px rgba(0,0,0,0.4);">
                    <div style="position:sticky;top:0;background:#ffffff;border-bottom:2px solid #e5e7eb;padding:24px;display:flex;justify-content:space-between;align-items:center;z-index:1;">
                        <h2 style="margin:0;font-size:22px;color:#1f2937;font-weight:700;"><i class="fas fa-user-circle" style="color:#6366f1;margin-right:8px;"></i> <?php _e('Sponsor User Details', 'al-huffaz-portal'); ?></h2>
                        <button onclick="this.closest('#sponsorDetailsModal').remove()" style="background:#f3f4f6;border:none;font-size:20px;cursor:pointer;color:#6b7280;padding:0;width:40px;height:40px;display:flex;align-items:center;justify-content:center;border-radius:8px;transition:all 0.2s;" onmouseover="this.style.background='#ef4444';this.style.color='#fff'" onmouseout="this.style.background='#f3f4f6';this.style.color='#6b7280'">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div id="sponsorDetailsContent" style="padding:24px;background:#f9fafb;">${content}</div>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
    }

    function renderSponsorDetailsModal(sponsor, userId) {
        const content = `
            <!-- Profile Info Card -->
            <div style="background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);color:#fff;padding:28px;border-radius:12px;margin-bottom:24px;box-shadow:0 10px 30px rgba(102,126,234,0.3);">
                <div style="display:flex;align-items:center;gap:20px;margin-bottom:20px;">
                    <div style="width:80px;height:80px;background:rgba(255,255,255,0.2);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:36px;border:3px solid rgba(255,255,255,0.3);">
                        <i class="fas fa-user-circle"></i>
                    </div>
                    <div>
                        <h3 style="margin:0 0 8px 0;font-size:28px;font-weight:700;">${sponsor.display_name || 'N/A'}</h3>
                        <div style="font-size:14px;opacity:0.9;">
                            <i class="fas fa-id-badge"></i> User ID: ${userId}
                        </div>
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:20px;font-size:15px;">
                    <div style="background:rgba(255,255,255,0.15);padding:16px;border-radius:8px;border-left:4px solid rgba(255,255,255,0.5);">
                        <div style="opacity:0.85;margin-bottom:6px;font-size:13px;"><i class="fas fa-envelope"></i> Email Address</div>
                        <div style="font-weight:600;font-size:16px;word-break:break-word;">${sponsor.email || 'N/A'}</div>
                    </div>
                    <div style="background:rgba(255,255,255,0.15);padding:16px;border-radius:8px;border-left:4px solid rgba(255,255,255,0.5);">
                        <div style="opacity:0.85;margin-bottom:6px;font-size:13px;"><i class="fas fa-phone"></i> Phone Number</div>
                        <div style="font-weight:600;font-size:16px;">${sponsor.phone || 'N/A'}</div>
                    </div>
                    <div style="background:rgba(255,255,255,0.15);padding:16px;border-radius:8px;border-left:4px solid rgba(255,255,255,0.5);">
                        <div style="opacity:0.85;margin-bottom:6px;font-size:13px;"><i class="fas fa-globe"></i> Country</div>
                        <div style="font-weight:600;font-size:16px;">${sponsor.country || 'N/A'}</div>
                    </div>
                    ${sponsor.whatsapp ? `
                    <div style="background:rgba(255,255,255,0.15);padding:16px;border-radius:8px;border-left:4px solid rgba(255,255,255,0.5);">
                        <div style="opacity:0.85;margin-bottom:6px;font-size:13px;"><i class="fab fa-whatsapp"></i> WhatsApp</div>
                        <div style="font-weight:600;font-size:16px;">${sponsor.whatsapp}</div>
                    </div>
                    ` : ''}
                </div>
            </div>

            <!-- Stats Cards -->
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-bottom:24px;">
                <div style="background:#ffffff;padding:20px;border-radius:12px;text-align:center;box-shadow:0 4px 12px rgba(0,0,0,0.08);border:2px solid #e5e7eb;">
                    <div style="font-size:36px;font-weight:800;color:#6366f1;margin-bottom:8px;">${sponsor.active_sponsorships || 0}</div>
                    <div style="font-size:13px;color:#6b7280;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;"><?php _e('Active Sponsorships', 'al-huffaz-portal'); ?></div>
                </div>
                <div style="background:#ffffff;padding:20px;border-radius:12px;text-align:center;box-shadow:0 4px 12px rgba(0,0,0,0.08);border:2px solid #e5e7eb;">
                    <div style="font-size:36px;font-weight:800;color:#10b981;margin-bottom:8px;">${sponsor.total_donated || 'PKR 0'}</div>
                    <div style="font-size:13px;color:#6b7280;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;"><?php _e('Total Donated', 'al-huffaz-portal'); ?></div>
                </div>
                <div style="background:#ffffff;padding:20px;border-radius:12px;text-align:center;box-shadow:0 4px 12px rgba(0,0,0,0.08);border:2px solid #e5e7eb;">
                    <div style="font-size:24px;font-weight:800;color:#f59e0b;margin-bottom:8px;text-transform:capitalize;">${sponsor.status}</div>
                    <div style="font-size:13px;color:#6b7280;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;"><?php _e('Account Status', 'al-huffaz-portal'); ?></div>
                </div>
                <div style="background:#ffffff;padding:20px;border-radius:12px;text-align:center;box-shadow:0 4px 12px rgba(0,0,0,0.08);border:2px solid #e5e7eb;">
                    <div style="font-size:16px;font-weight:700;color:#1f2937;margin-bottom:8px;">${sponsor.registered}</div>
                    <div style="font-size:13px;color:#6b7280;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;"><?php _e('Registered', 'al-huffaz-portal'); ?></div>
                </div>
            </div>

            <!-- Sponsored Students Section -->
            <div id="sponsorStudentsList" style="background:#ffffff;padding:24px;border-radius:12px;margin-bottom:20px;box-shadow:0 4px 12px rgba(0,0,0,0.08);border:2px solid #e5e7eb;">
                <h4 style="margin:0 0 16px 0;font-size:18px;color:#1f2937;font-weight:700;"><i class="fas fa-users" style="color:#6366f1;margin-right:8px;"></i> <?php _e('Sponsored Students', 'al-huffaz-portal'); ?></h4>
                <div style="text-align:center;padding:30px;color:#9ca3af;">
                    <i class="fas fa-spinner fa-spin" style="font-size:28px;margin-bottom:12px;"></i>
                    <p style="margin:0;font-size:14px;"><?php _e('Loading students...', 'al-huffaz-portal'); ?></p>
                </div>
            </div>

            <!-- Payment History Section -->
            <div id="sponsorPaymentHistory" style="background:#ffffff;padding:24px;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,0.08);border:2px solid #e5e7eb;">
                <h4 style="margin:0 0 16px 0;font-size:18px;color:#1f2937;font-weight:700;"><i class="fas fa-history" style="color:#6366f1;margin-right:8px;"></i> <?php _e('Payment History', 'al-huffaz-portal'); ?></h4>
                <div style="text-align:center;padding:30px;color:#9ca3af;">
                    <i class="fas fa-spinner fa-spin" style="font-size:28px;margin-bottom:12px;"></i>
                    <p style="margin:0;font-size:14px;"><?php _e('Loading payments...', 'al-huffaz-portal'); ?></p>
                </div>
            </div>
        `;

        showSponsorDetailsModal(content);

        // Load sponsored students
        loadSponsorStudents(userId);

        // Load payment history
        loadSponsorPayments(userId);
    }

    function loadSponsorStudents(userId) {
        fetch(ajaxUrl, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({action: 'alhuffaz_get_sponsor_students', nonce, user_id: userId})
        })
        .then(r => r.json())
        .then(data => {
            const container = document.getElementById('sponsorStudentsList');
            if (!container) return;

            if (data.success && data.data.students && data.data.students.length > 0) {
                const studentsHTML = `
                    <h4 style="margin:0 0 12px 0;font-size:16px;color:var(--ahp-text);"><i class="fas fa-users"></i> <?php _e('Sponsored Students', 'al-huffaz-portal'); ?></h4>
                    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:16px;">
                        ${data.data.students.map(s => `
                            <div style="border:1px solid var(--ahp-border);border-radius:8px;overflow:hidden;">
                                <div style="height:150px;background:linear-gradient(135deg,#667eea,#764ba2);position:relative;">
                                    <img src="${s.photo}" alt="${s.name}" style="width:100%;height:100%;object-fit:cover;">
                                </div>
                                <div style="padding:12px;">
                                    <h5 style="margin:0 0 8px 0;font-size:15px;">${s.name}</h5>
                                    <div style="font-size:13px;color:var(--ahp-text-muted);margin-bottom:8px;">
                                        <div><i class="fas fa-graduation-cap"></i> ${s.grade || 'N/A'}</div>
                                        <div><i class="fas fa-dollar-sign"></i> $${s.amount || '0'}/month</div>
                                    </div>
                                    <span style="font-size:11px;padding:4px 8px;background:#10b981;color:#fff;border-radius:4px;"><?php _e('Active', 'al-huffaz-portal'); ?></span>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                `;
                container.innerHTML = studentsHTML;
            } else {
                container.innerHTML = `
                    <h4 style="margin:0 0 12px 0;font-size:16px;color:var(--ahp-text);"><i class="fas fa-users"></i> <?php _e('Sponsored Students', 'al-huffaz-portal'); ?></h4>
                    <div style="text-align:center;padding:20px;color:var(--ahp-text-muted);background:var(--ahp-hover);border-radius:8px;">
                        <i class="fas fa-info-circle"></i> <?php _e('No active sponsorships yet', 'al-huffaz-portal'); ?>
                    </div>
                `;
            }
        });
    }

    function loadSponsorPayments(userId) {
        fetch(ajaxUrl, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({action: 'alhuffaz_get_sponsor_payments', nonce, user_id: userId})
        })
        .then(r => r.json())
        .then(data => {
            const container = document.getElementById('sponsorPaymentHistory');
            if (!container) return;

            if (data.success && data.data.payments && data.data.payments.length > 0) {
                const paymentsHTML = `
                    <h4 style="margin:0 0 12px 0;font-size:16px;color:var(--ahp-text);"><i class="fas fa-history"></i> <?php _e('Payment History', 'al-huffaz-portal'); ?></h4>
                    <div style="background:var(--ahp-hover);border-radius:8px;overflow:hidden;">
                        <table style="width:100%;border-collapse:collapse;">
                            <thead style="background:var(--ahp-primary);color:#fff;">
                                <tr>
                                    <th style="padding:12px;text-align:left;"><?php _e('Date', 'al-huffaz-portal'); ?></th>
                                    <th style="padding:12px;text-align:left;"><?php _e('Student', 'al-huffaz-portal'); ?></th>
                                    <th style="padding:12px;text-align:right;"><?php _e('Amount', 'al-huffaz-portal'); ?></th>
                                    <th style="padding:12px;text-align:center;"><?php _e('Method', 'al-huffaz-portal'); ?></th>
                                    <th style="padding:12px;text-align:center;"><?php _e('Status', 'al-huffaz-portal'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                ${data.data.payments.map((p, i) => `
                                    <tr style="border-bottom:1px solid var(--ahp-border);${i % 2 === 0 ? 'background:var(--ahp-bg);' : ''}">
                                        <td style="padding:12px;">${p.date}</td>
                                        <td style="padding:12px;">${p.student_name}</td>
                                        <td style="padding:12px;text-align:right;font-weight:600;">$${p.amount}</td>
                                        <td style="padding:12px;text-align:center;"><span style="font-size:12px;padding:4px 8px;background:var(--ahp-border);border-radius:4px;">${p.method}</span></td>
                                        <td style="padding:12px;text-align:center;">${p.status_badge}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                `;
                container.innerHTML = paymentsHTML;
            } else {
                container.innerHTML = `
                    <h4 style="margin:0 0 12px 0;font-size:16px;color:var(--ahp-text);"><i class="fas fa-history"></i> <?php _e('Payment History', 'al-huffaz-portal'); ?></h4>
                    <div style="text-align:center;padding:20px;color:var(--ahp-text-muted);background:var(--ahp-hover);border-radius:8px;">
                        <i class="fas fa-info-circle"></i> <?php _e('No payment history yet', 'al-huffaz-portal'); ?>
                    </div>
                `;
            }
        });
    };

    window.approveSponsorUser = function(userId, event) {
        if (!confirm('<?php _e('Approve this sponsor user account? They will receive an email confirmation.', 'al-huffaz-portal'); ?>')) return;

        // Get button element and row for optimistic UI update
        const btn = event ? event.target.closest('button') : null;
        const row = btn ? btn.closest('tr') : null;

        // FIX #13: Prevent double-click/rapid actions
        if (btn && btn.disabled) return;

        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <?php _e('Approving...', 'al-huffaz-portal'); ?>';
        }

        // FIX #9: Optimistic UI - fade row immediately for instant feedback
        if (row) {
            row.style.transition = 'opacity 0.3s, background-color 0.3s';
            row.style.opacity = '0.5';
            row.style.backgroundColor = 'rgba(16, 185, 129, 0.1)'; // green tint
        }

        fetch(ajaxUrl, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({action: 'alhuffaz_approve_sponsor_user', nonce, user_id: userId})
        })
        .then(r => {
            // FIX #16: Detect nonce expiry and other auth failures
            if (r.status === 403 || r.status === 401) {
                throw new Error('SESSION_EXPIRED');
            }
            return r.json();
        })
        .then(data => {
            if (data.success) {
                showToast('<?php _e('Sponsor user approved successfully! Email sent.', 'al-huffaz-portal'); ?>', 'success');

                // FIX #1: Refresh dashboard stats after approval
                refreshDashboardStats();

                // FIX #10: Better filter behavior - keep current filter, show inline notification
                const currentFilter = document.getElementById('filterUserStatus')?.value;
                if (currentFilter === 'pending_approval' || currentFilter === 'all') {
                    // Show inline success message on the row before it disappears
                    if (row) {
                        row.innerHTML = `<td colspan="5" style="padding:20px;text-align:center;background:linear-gradient(135deg, #10b981, #059669);color:white;font-weight:600;">
                            <i class="fas fa-check-circle"></i> <?php _e('Approved! User moved to Approved tab', 'al-huffaz-portal'); ?>
                        </td>`;
                        setTimeout(() => loadSponsorUsers(), 1000);
                    } else {
                        loadSponsorUsers();
                    }
                } else {
                    // Already in approved or other filter, just reload
                    loadSponsorUsers();
                }
            } else {
                showToast(data.data?.message || '<?php _e('Error approving user', 'al-huffaz-portal'); ?>', 'error');
                // FIX #11: Restore row state on error
                if (row) {
                    row.style.opacity = '1';
                    row.style.backgroundColor = '';
                }
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-check"></i>';
                }
            }
        })
        .catch(err => {
            // FIX #16: Better error messaging for session expiry
            if (err.message === 'SESSION_EXPIRED') {
                showToast('<?php _e('Session expired. Please refresh the page and login again.', 'al-huffaz-portal'); ?>', 'error');
                setTimeout(() => location.reload(), 2000);
            } else {
                showToast('<?php _e('Network error. Please try again.', 'al-huffaz-portal'); ?>', 'error');
            }
            // FIX #11: Always restore button state in catch block
            if (row) {
                row.style.opacity = '1';
                row.style.backgroundColor = '';
            }
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check"></i>';
            }
        });
    };

    window.rejectSponsorUser = function(userId, event) {
        const reason = prompt('<?php _e('Please enter rejection reason:', 'al-huffaz-portal'); ?>');
        if (reason === null) return;

        // Get button element and row for optimistic UI update
        const btn = event ? event.target.closest('button') : null;
        const row = btn ? btn.closest('tr') : null;

        // FIX #13: Prevent double-click
        if (btn && btn.disabled) return;

        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        }

        // FIX #9: Optimistic UI - fade row with red tint
        if (row) {
            row.style.transition = 'opacity 0.3s, background-color 0.3s';
            row.style.opacity = '0.5';
            row.style.backgroundColor = 'rgba(239, 68, 68, 0.1)'; // red tint
        }

        fetch(ajaxUrl, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({action: 'alhuffaz_reject_sponsor_user', nonce, user_id: userId, reason})
        })
        .then(r => {
            // FIX #16: Detect session expiry
            if (r.status === 403 || r.status === 401) {
                throw new Error('SESSION_EXPIRED');
            }
            return r.json();
        })
        .then(data => {
            if (data.success) {
                showToast('<?php _e('Sponsor user rejected successfully', 'al-huffaz-portal'); ?>', 'success');
                // FIX #1: Refresh dashboard stats after rejection
                refreshDashboardStats();

                // FIX #10: Show inline message then reload
                if (row) {
                    row.innerHTML = `<td colspan="5" style="padding:20px;text-align:center;background:linear-gradient(135deg, #ef4444, #dc2626);color:white;font-weight:600;">
                        <i class="fas fa-times-circle"></i> <?php _e('Rejected. Email sent to user.', 'al-huffaz-portal'); ?>
                    </td>`;
                    setTimeout(() => loadSponsorUsers(), 1000);
                } else {
                    loadSponsorUsers();
                }
            } else {
                showToast(data.data?.message || '<?php _e('Error rejecting user', 'al-huffaz-portal'); ?>', 'error');
                // FIX #11: Restore state on error
                if (row) {
                    row.style.opacity = '1';
                    row.style.backgroundColor = '';
                }
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-times"></i>';
                }
            }
        })
        .catch(err => {
            // FIX #16: Session expiry detection
            if (err.message === 'SESSION_EXPIRED') {
                showToast('<?php _e('Session expired. Please refresh the page and login again.', 'al-huffaz-portal'); ?>', 'error');
                setTimeout(() => location.reload(), 2000);
            } else {
                showToast('<?php _e('Network error. Please try again.', 'al-huffaz-portal'); ?>', 'error');
            }
            // FIX #11: Always restore state
            if (row) {
                row.style.opacity = '1';
                row.style.backgroundColor = '';
            }
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-times"></i>';
            }
        });
    };

    window.deleteSponsorUser = function(userId) {
        if (!confirm('<?php _e('Are you sure you want to delete this sponsor user? This action cannot be undone.', 'al-huffaz-portal'); ?>')) return;

        const confirmText = prompt('<?php _e('Type DELETE to confirm:', 'al-huffaz-portal'); ?>');
        if (confirmText !== 'DELETE') {
            showToast('<?php _e('Deletion cancelled', 'al-huffaz-portal'); ?>', 'info');
            return;
        }

        fetch(ajaxUrl, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({action: 'alhuffaz_delete_sponsor_user', nonce, user_id: userId})
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showToast('<?php _e('Sponsor user deleted successfully', 'al-huffaz-portal'); ?>', 'success');
                loadSponsorUsers();
            } else {
                showToast(data.data?.message || '<?php _e('Error deleting user', 'al-huffaz-portal'); ?>', 'error');
            }
        });
    };

    window.sendReEngagementEmail = function(userId) {
        if (!confirm('<?php _e('Send a re-engagement email to this inactive sponsor?', 'al-huffaz-portal'); ?>')) return;

        fetch(ajaxUrl, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({action: 'alhuffaz_send_reengagement_email', nonce, user_id: userId})
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showToast('<?php _e('Re-engagement email sent successfully!', 'al-huffaz-portal'); ?>', 'success');
            } else {
                showToast(data.data?.message || '<?php _e('Error sending email', 'al-huffaz-portal'); ?>', 'error');
            }
        });
    };

    // Search debouncing for sponsor users
    let userSearchTimeout;
    document.getElementById('searchSponsorUsers')?.addEventListener('input', function() {
        clearTimeout(userSearchTimeout);
        userSearchTimeout = setTimeout(() => loadSponsorUsers(), 500);
    });

    // ==================== PAYMENTS MANAGEMENT ====================
    // Load payment analytics when panel is opened
    window.loadPaymentAnalytics = function() {
        fetch(ajaxUrl, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({action: 'alhuffaz_get_payment_analytics', nonce})
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const stats = data.data;
                document.getElementById('totalApprovedAmount').textContent = stats.total_approved_amount || 'Rs. 0';
                document.getElementById('totalActiveSponsors').textContent = stats.total_active_sponsors || '0';
                document.getElementById('totalStudentsSponsored').textContent = stats.total_students_sponsored || '0';
                document.getElementById('approvedPaymentCount').textContent = stats.approved_count || '0';
                document.getElementById('rejectedPaymentCount').textContent = stats.rejected_count || '0';
            }
        })
        .catch(err => console.error('Failed to load payment analytics:', err));

        // Load both tables
        loadSponsorPaymentSummary();
        loadAllPayments();
    };

    // Load sponsor-wise payment summary
    function loadSponsorPaymentSummary() {
        document.getElementById('sponsorPaymentSummaryBody').innerHTML = '<tr><td colspan="6" class="ahp-loading"><div class="ahp-spinner"></div></td></tr>';

        fetch(ajaxUrl, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({action: 'alhuffaz_get_sponsor_payment_summary', nonce})
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                renderSponsorPaymentSummary(data.data.sponsors);
            } else {
                document.getElementById('sponsorPaymentSummaryBody').innerHTML = '<tr><td colspan="6" style="text-align:center;padding:40px;">Error loading sponsor summary</td></tr>';
            }
        });
    }

    function renderSponsorPaymentSummary(sponsors) {
        const tbody = document.getElementById('sponsorPaymentSummaryBody');
        if (!sponsors || !sponsors.length) {
            tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:40px;color:var(--ahp-text-muted);">No sponsor payment records found</td></tr>';
            return;
        }

        tbody.innerHTML = sponsors.map(s => `
            <tr>
                <td><strong>${s.name || '-'}</strong></td>
                <td>${s.email || '-'}</td>
                <td><span class="ahp-badge ahp-badge-primary">${s.students_count || '0'} students</span></td>
                <td>${s.payment_count || '0'} payments</td>
                <td><strong style="color:var(--ahp-success);">${s.total_amount || 'Rs. 0'}</strong></td>
                <td>${s.last_payment_date || '-'}</td>
            </tr>
        `).join('');
    }

    // Load all payment records
    window.loadAllPayments = function() {
        const status = document.getElementById('filterPaymentStatus')?.value || '';
        document.getElementById('allPaymentsTableBody').innerHTML = '<tr><td colspan="8" class="ahp-loading"><div class="ahp-spinner"></div></td></tr>';

        fetch(ajaxUrl, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({action: 'alhuffaz_get_all_payments', nonce, status})
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                renderAllPayments(data.data.payments);
            } else {
                document.getElementById('allPaymentsTableBody').innerHTML = '<tr><td colspan="8" style="text-align:center;padding:40px;">Error loading payments</td></tr>';
            }
        });
    };

    function renderAllPayments(payments) {
        const tbody = document.getElementById('allPaymentsTableBody');
        if (!payments || !payments.length) {
            tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:40px;color:var(--ahp-text-muted);">No payment records found</td></tr>';
            return;
        }

        tbody.innerHTML = payments.map(p => {
            const statusBadge = p.status === 'pending' ? 'ahp-badge-warning' :
                               p.status === 'approved' ? 'ahp-badge-success' : 'ahp-badge-danger';
            const durationMap = {'1': '1 Month', '3': '3 Months', '6': '6 Months', '12': '12 Months'};
            const duration = durationMap[p.duration_months] || p.duration_months + ' months';

            return `
            <tr>
                <td>${p.date || '-'}</td>
                <td><strong>${p.sponsor_name || '-'}</strong><br><small style="color:var(--ahp-text-muted);">${p.sponsor_email || ''}</small></td>
                <td>${p.student_name || '-'}</td>
                <td><strong style="color:var(--ahp-success);">${p.amount || '-'}</strong></td>
                <td><span class="ahp-badge ahp-badge-primary">${duration}</span></td>
                <td>${p.method || '-'}</td>
                <td><span class="ahp-badge ${statusBadge}">${(p.status || '-').charAt(0).toUpperCase() + (p.status || '-').slice(1)}</span></td>
                <td>
                    <div class="ahp-cell-actions">
                        ${p.status === 'pending' ? `
                        <button class="ahp-btn ahp-btn-success ahp-btn-icon" onclick="approvePaymentRequest(${p.id})" title="Approve"><i class="fas fa-check"></i></button>
                        <button class="ahp-btn ahp-btn-danger ahp-btn-icon" onclick="rejectPaymentRequest(${p.id})" title="Reject"><i class="fas fa-times"></i></button>
                        ` : '<span style="color:var(--ahp-text-muted);font-size:12px;">No actions</span>'}
                    </div>
                </td>
            </tr>`;
        }).join('');
    }

    window.approvePaymentRequest = function(id) {
        if (!confirm('<?php _e('Approve this payment request?', 'al-huffaz-portal'); ?>')) return;

        fetch(ajaxUrl, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({action: 'alhuffaz_approve_payment_request', nonce, sponsorship_id: id})
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showToast('<?php _e('Payment approved successfully!', 'al-huffaz-portal'); ?>', 'success');
                refreshDashboardStats();
                loadPaymentAnalytics();
            } else {
                showToast(data.data?.message || '<?php _e('Error approving payment', 'al-huffaz-portal'); ?>', 'error');
            }
        });
    };

    window.rejectPaymentRequest = function(id) {
        if (!confirm('<?php _e('Reject this payment request?', 'al-huffaz-portal'); ?>')) return;

        fetch(ajaxUrl, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({action: 'alhuffaz_reject_payment_request', nonce, sponsorship_id: id})
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showToast('<?php _e('Payment rejected', 'al-huffaz-portal'); ?>', 'success');
                refreshDashboardStats();
                loadPaymentAnalytics();
            } else {
                showToast(data.data?.message || '<?php _e('Error rejecting payment', 'al-huffaz-portal'); ?>', 'error');
            }
        });
    };

    // ==================== USER MANAGEMENT FUNCTIONS ====================
    <?php if ($can_manage_staff): ?>
    window.loadPortalUsers = function() {
        const tbody = document.getElementById('portalUsersTableBody');
        if (!tbody) return;

        tbody.innerHTML = '<tr><td colspan="5" class="ahp-loading"><div class="ahp-spinner"></div></td></tr>';

        const filterRole = document.getElementById('filterUserRole')?.value || '';

        fetch(window.ajaxUrl, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({
                action: 'alhuffaz_get_portal_users',
                nonce: window.nonce,
                filter_role: filterRole
            })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                // Update stats
                if (data.data.stats) {
                    document.getElementById('totalAdminsCount').textContent = data.data.stats.admins || 0;
                    document.getElementById('totalStaffCount').textContent = data.data.stats.staff || 0;
                    document.getElementById('totalUsersCount').textContent = data.data.stats.total || 0;
                }
                renderPortalUsers(data.data.users);
            } else {
                showToast(data.data?.message || '<?php _e('Error loading users', 'al-huffaz-portal'); ?>', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:40px;color:var(--ahp-text-muted);">Error loading users</td></tr>';
        });
    };

    function renderPortalUsers(users) {
        const tbody = document.getElementById('portalUsersTableBody');
        if (!users || users.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:40px;color:var(--ahp-text-muted);"><i class="fas fa-users" style="font-size:48px;opacity:0.3;display:block;margin-bottom:10px;"></i><?php _e('No users found. Click "Create New User" to get started.', 'al-huffaz-portal'); ?></td></tr>';
            return;
        }

        tbody.innerHTML = users.map(u => {
            const roleLabel = u.role === 'alhuffaz_admin' ? 'School Admin' : 'Staff';
            const roleColor = u.role === 'alhuffaz_admin' ? '#3b82f6' : '#10b981';
            const currentUserId = <?php echo get_current_user_id(); ?>;
            const isCurrentUser = u.id == currentUserId;

            return `
                <tr>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <img src="${u.avatar}" alt="" style="width:40px;height:40px;border-radius:50%;">
                            <div>
                                <strong>${u.display_name}</strong>
                                ${isCurrentUser ? '<span style="font-size:11px;color:var(--ahp-primary);margin-left:6px;">(You)</span>' : ''}
                            </div>
                        </div>
                    </td>
                    <td>${u.email}</td>
                    <td>
                        <span style="display:inline-block;padding:4px 10px;background:${roleColor};color:#fff;border-radius:4px;font-size:12px;font-weight:600;">
                            ${roleLabel}
                        </span>
                    </td>
                    <td style="color:var(--ahp-text-muted);font-size:13px;">${u.registered}</td>
                    <td>
                        ${!isCurrentUser ? `
                            <button class="ahp-btn ahp-btn-danger ahp-btn-icon" onclick="deletePortalUser(${u.id}, '${u.display_name}')" title="Delete User">
                                <i class="fas fa-trash"></i>
                            </button>
                        ` : '-'}
                    </td>
                </tr>
            `;
        }).join('');
    }

    window.showCreateUserModal = function() {
        document.getElementById('createUserModal').style.display = 'flex';
        document.getElementById('createUserForm').reset();
    };

    window.closeCreateUserModal = function() {
        document.getElementById('createUserModal').style.display = 'none';
        document.getElementById('createUserForm').reset();
    };

    window.createNewUser = function() {
        // Validate form
        const form = document.getElementById('createUserForm');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const btn = document.getElementById('createUserBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating...';

        const fullName = document.getElementById('newUserName').value;
        const username = document.getElementById('newUsername').value;
        const email = document.getElementById('newUserEmail').value;
        const password = document.getElementById('newUserPassword').value;
        const role = document.getElementById('newUserRole').value;

        fetch(window.ajaxUrl, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({
                action: 'alhuffaz_create_portal_user',
                nonce: window.nonce,
                full_name: fullName,
                username: username,
                email: email,
                password: password,
                role: role
            })
        })
        .then(r => r.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-user-plus"></i> <?php _e('Create User', 'al-huffaz-portal'); ?>';

            if (data.success) {
                showToast(data.data.message || '<?php _e('User created successfully!', 'al-huffaz-portal'); ?>', 'success');
                closeCreateUserModal();
                loadPortalUsers();
            } else {
                showToast(data.data?.message || '<?php _e('Error creating user', 'al-huffaz-portal'); ?>', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-user-plus"></i> <?php _e('Create User', 'al-huffaz-portal'); ?>';
            showToast('<?php _e('Error creating user', 'al-huffaz-portal'); ?>', 'error');
        });
    };

    window.deletePortalUser = function(userId, userName) {
        if (!confirm(`<?php _e('Are you sure you want to delete', 'al-huffaz-portal'); ?> "${userName}"?\n\n<?php _e('This action cannot be undone.', 'al-huffaz-portal'); ?>`)) return;

        fetch(window.ajaxUrl, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({
                action: 'alhuffaz_delete_portal_user',
                nonce: window.nonce,
                user_id: userId
            })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showToast(data.data.message || '<?php _e('User deleted successfully', 'al-huffaz-portal'); ?>', 'success');
                loadPortalUsers();
            } else {
                showToast(data.data?.message || '<?php _e('Error deleting user', 'al-huffaz-portal'); ?>', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('<?php _e('Error deleting user', 'al-huffaz-portal'); ?>', 'error');
        });
    };
    <?php endif; ?>

    // Auto-show panel based on URL - ONLY if explicitly editing
    <?php if ($is_edit): ?>
    showPanel('add-student');
    <?php else: ?>
    // Ensure dashboard is shown by default on page load/refresh
    showPanel('dashboard');
    <?php endif; ?>

    // ==================== KEYBOARD SHORTCUTS ====================
    // FIX #12 & #20: Add keyboard shortcuts for power users + visual helper
    let shortcutsModalOpen = false;

    function showKeyboardShortcuts() {
        if (shortcutsModalOpen) return;

        const modal = document.createElement('div');
        modal.id = 'keyboardShortcutsModal';
        modal.style.cssText = 'position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.7);z-index:99999;display:flex;align-items:center;justify-content:center;backdrop-filter:blur(4px);';

        modal.innerHTML = `
            <div style="background:white;border-radius:16px;padding:40px;max-width:600px;width:90%;box-shadow:0 25px 50px rgba(0,0,0,0.3);">
                <h2 style="margin:0 0 24px;font-size:24px;color:#1f2937;display:flex;align-items:center;gap:12px;">
                    <span style="font-size:32px;">⌨️</span> <?php _e('Keyboard Shortcuts', 'al-huffaz-portal'); ?>
                </h2>
                <div style="display:grid;gap:16px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;padding:12px;background:#f9fafb;border-radius:8px;">
                        <span><?php _e('Save student form', 'al-huffaz-portal'); ?></span>
                        <kbd style="background:#1f2937;color:white;padding:4px 12px;border-radius:6px;font-family:monospace;font-size:13px;">Ctrl+S</kbd>
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;padding:12px;background:#f9fafb;border-radius:8px;">
                        <span><?php _e('Close modals', 'al-huffaz-portal'); ?></span>
                        <kbd style="background:#1f2937;color:white;padding:4px 12px;border-radius:6px;font-family:monospace;font-size:13px;">Esc</kbd>
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;padding:12px;background:#f9fafb;border-radius:8px;">
                        <span><?php _e('Focus search', 'al-huffaz-portal'); ?></span>
                        <kbd style="background:#1f2937;color:white;padding:4px 12px;border-radius:6px;font-family:monospace;font-size:13px;">Ctrl+K</kbd>
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;padding:12px;background:#f9fafb;border-radius:8px;">
                        <span><?php _e('Quick panel switching', 'al-huffaz-portal'); ?></span>
                        <kbd style="background:#1f2937;color:white;padding:4px 12px;border-radius:6px;font-family:monospace;font-size:13px;">Ctrl+1-7</kbd>
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;padding:12px;background:#f9fafb;border-radius:8px;">
                        <span><?php _e('Show this help', 'al-huffaz-portal'); ?></span>
                        <kbd style="background:#1f2937;color:white;padding:4px 12px;border-radius:6px;font-family:monospace;font-size:13px;">?</kbd>
                    </div>
                </div>
                <button onclick="this.closest('#keyboardShortcutsModal').remove();shortcutsModalOpen=false;"
                    style="margin-top:24px;width:100%;padding:14px;background:linear-gradient(135deg,#6366f1,#8b5cf6);color:white;border:none;border-radius:8px;font-size:16px;font-weight:600;cursor:pointer;transition:transform 0.2s;">
                    <?php _e('Got it!', 'al-huffaz-portal'); ?>
                </button>
            </div>
        `;

        document.body.appendChild(modal);
        shortcutsModalOpen = true;

        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                modal.remove();
                shortcutsModalOpen = false;
            }
        });
    }

    document.addEventListener('keydown', function(e) {
        // ? - Show keyboard shortcuts help
        if (e.key === '?' && !e.ctrlKey && !e.metaKey && !e.altKey) {
            // Don't trigger if user is typing in an input
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;
            e.preventDefault();
            showKeyboardShortcuts();
            return;
        }

        // Esc - Close keyboard shortcuts modal first
        if (e.key === 'Escape' && shortcutsModalOpen) {
            document.getElementById('keyboardShortcutsModal')?.remove();
            shortcutsModalOpen = false;
            return;
        }

        // Ctrl+S or Cmd+S - Save student form
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            const activePanel = document.querySelector('.ahp-panel.active');
            if (activePanel && activePanel.id === 'panel-add-student') {
                const submitBtn = document.getElementById('submitBtn');
                if (submitBtn && !submitBtn.disabled) {
                    submitBtn.click();
                    showToast('<?php _e('Saving... (Ctrl+S)', 'al-huffaz-portal'); ?>', 'info');
                }
            }
        }

        // Esc - Close modals
        if (e.key === 'Escape') {
            // Close student view modal if open
            const viewModal = document.getElementById('studentViewModal');
            if (viewModal && viewModal.style.display !== 'none') {
                viewModal.style.display = 'none';
                return;
            }

            // Close sponsor details modal if open
            const sponsorModal = document.getElementById('sponsorModal');
            if (sponsorModal && sponsorModal.style.display !== 'none') {
                closeSponsorModal();
                return;
            }
        }

        // Ctrl+K or Cmd+K - Focus search
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            const searchInput = document.getElementById('searchStudents');
            if (searchInput) {
                searchInput.focus();
                searchInput.select();
                showToast('<?php _e('Search focused (Ctrl+K)', 'al-huffaz-portal'); ?>', 'info');
            }
        }

        // Ctrl+1 through Ctrl+7 - Quick panel switching
        if ((e.ctrlKey || e.metaKey) && e.key >= '1' && e.key <= '7') {
            e.preventDefault();
            const panels = ['dashboard', 'students', 'add-student', 'sponsors', 'sponsor-users', 'payments', 'staff'];
            const panelIndex = parseInt(e.key) - 1;
            if (panels[panelIndex]) {
                showPanel(panels[panelIndex]);
            }
        }
    });

    // Show keyboard shortcut hints on page load
    // Show keyboard shortcut hints on page load
    console.log('%c⌨️ Al-Huffaz Portal Keyboard Shortcuts', 'font-size:14px;font-weight:bold;color:#6366f1');
    console.log('%c💡 Press ? to see all shortcuts', 'font-size:12px;color:#6b7280;font-style:italic');
    console.log('%cCtrl+S', 'font-weight:bold', '- Save student form');
    console.log('%cEsc', 'font-weight:bold', '- Close modals');
    console.log('%cCtrl+K', 'font-weight:bold', '- Focus search');
    console.log('%cCtrl+1-7', 'font-weight:bold', '- Quick panel switch');

    // FIX #20: Show subtle hint on first visit
    if (!sessionStorage.getItem('ahp_shortcuts_seen')) {
        setTimeout(() => {
            showToast('<?php _e('💡 Tip: Press ? to see keyboard shortcuts', 'al-huffaz-portal'); ?>', 'info');
            sessionStorage.setItem('ahp_shortcuts_seen', 'true');
        }, 3000);
    }
});

// Activity Logs and Recovery Functions
window.loadActivityLogs = function() {
    const tbody = document.getElementById('activityLogsTableBody');
    if (!tbody) return;

    tbody.innerHTML = '<tr><td colspan="7" class="ahp-loading"><div class="ahp-spinner"></div></td></tr>';

    const filterAction = document.getElementById('filterLogAction')?.value || '';
    const filterType = document.getElementById('filterLogType')?.value || '';

    fetch(window.ajaxUrl, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({
            action: 'alhuffaz_get_activity_logs',
            nonce: window.nonce,
            filter_action: filterAction,
            filter_type: filterType,
            per_page: 100
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.data.logs) {
            renderActivityLogs(data.data.logs);
        } else {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:40px;color:var(--ahp-text-muted);"><?php _e('Error loading activity logs', 'al-huffaz-portal'); ?></td></tr>';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:40px;color:var(--ahp-text-muted);"><?php _e('Error loading activity logs', 'al-huffaz-portal'); ?></td></tr>';
    });
};

function renderActivityLogs(logs) {
    const tbody = document.getElementById('activityLogsTableBody');
    if (!logs || logs.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:40px;color:var(--ahp-text-muted);"><?php _e('No activity logs found', 'al-huffaz-portal'); ?></td></tr>';
        return;
    }

    tbody.innerHTML = logs.map(log => {
        const actionLabel = formatActionLabel(log.action);
        const actionColor = getActionColor(log.action);
        const restoreBtn = log.can_restore ?
            `<button onclick="restoreItem(${log.object_id}, '${log.object_type}', '${log.object_name}')" class="ahp-btn ahp-btn-sm ahp-btn-success" style="padding:4px 8px;font-size:12px;">
                <i class="fas fa-undo"></i> <?php _e('Restore', 'al-huffaz-portal'); ?>
            </button>` :
            '<span style="color:var(--ahp-text-muted);font-size:12px;">-</span>';

        return `
            <tr>
                <td style="color:var(--ahp-text-muted);font-size:12px;">${log.id}</td>
                <td><strong>${log.user}</strong></td>
                <td>
                    <span style="display:inline-block;padding:4px 8px;background:${actionColor};color:#fff;border-radius:4px;font-size:11px;font-weight:600;">
                        ${actionLabel}
                    </span>
                </td>
                <td>
                    <div><strong>${log.object_name}</strong></div>
                    <div style="font-size:12px;color:var(--ahp-text-muted);">${log.object_type} #${log.object_id}</div>
                </td>
                <td style="font-size:13px;max-width:300px;">${log.details || '-'}</td>
                <td style="font-size:12px;color:var(--ahp-text-muted);" title="${log.created_at}">${log.time_ago}</td>
                <td>${restoreBtn}</td>
            </tr>
        `;
    }).join('');
}

function formatActionLabel(action) {
    const labels = {
        'save_student': '<?php _e('Added Student', 'al-huffaz-portal'); ?>',
        'update_student': '<?php _e('Updated Student', 'al-huffaz-portal'); ?>',
        'delete_student': '<?php _e('Deleted Student', 'al-huffaz-portal'); ?>',
        'approve_sponsorship': '<?php _e('Approved Sponsor', 'al-huffaz-portal'); ?>',
        'reject_sponsorship': '<?php _e('Rejected Sponsor', 'al-huffaz-portal'); ?>',
        'link_sponsor': '<?php _e('Linked Sponsor', 'al-huffaz-portal'); ?>',
        'unlink_sponsor': '<?php _e('Unlinked Sponsor', 'al-huffaz-portal'); ?>',
        'restore_student': '<?php _e('Restored Student', 'al-huffaz-portal'); ?>',
        'restore_sponsorship': '<?php _e('Restored Sponsorship', 'al-huffaz-portal'); ?>',
        'sponsor_user_approved': '<?php _e('Approved User', 'al-huffaz-portal'); ?>',
        'sponsor_user_rejected': '<?php _e('Rejected User', 'al-huffaz-portal'); ?>',
        'grant_staff': '<?php _e('Granted Staff', 'al-huffaz-portal'); ?>',
        'revoke_staff': '<?php _e('Revoked Staff', 'al-huffaz-portal'); ?>'
    };
    return labels[action] || action.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
}

function getActionColor(action) {
    if (action.includes('approve') || action.includes('restore') || action.includes('grant')) return '#10b981';
    if (action.includes('reject') || action.includes('delete') || action.includes('revoke')) return '#ef4444';
    if (action.includes('update') || action.includes('link')) return '#3b82f6';
    if (action.includes('unlink')) return '#f59e0b';
    return '#6b7280';
}

window.restoreItem = function(itemId, itemType, itemName) {
    if (!confirm(`<?php _e('Are you sure you want to restore', 'al-huffaz-portal'); ?> "${itemName}"?`)) return;

    fetch(window.ajaxUrl, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({
            action: 'alhuffaz_restore_item',
            nonce: window.nonce,
            item_id: itemId,
            item_type: itemType
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.data.message || '<?php _e('Item restored successfully', 'al-huffaz-portal'); ?>', 'success');
            loadActivityLogs(); // Reload logs
            refreshDashboardStats(); // Refresh stats
        } else {
            showToast(data.data?.message || '<?php _e('Error restoring item', 'al-huffaz-portal'); ?>', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('<?php _e('Error restoring item', 'al-huffaz-portal'); ?>', 'error');
    });
};

// Load activity logs when history panel is shown
document.addEventListener('DOMContentLoaded', function() {
    const historyNav = document.querySelector('.ahp-nav-item[data-panel="history"]');
    if (historyNav) {
        historyNav.addEventListener('click', function() {
            if (document.getElementById('panel-history').classList.contains('active')) {
                loadActivityLogs();
            }
        });
    }
});
</script>
