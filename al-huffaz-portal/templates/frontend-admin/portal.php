<?php
/**
 * Front-end Admin Portal Template
 * Al-Huffaz Education System Portal
 *
 * Complete admin interface - mirrors WP Admin functionality exactly
 * Includes: Dashboard, Students List, 5-Step Student Form with Subjects/Marks
 */

defined('ABSPATH') || exit;

// Debug logging
if (class_exists('\AlHuffaz\Core\Debug')) {
    \AlHuffaz\Core\Debug::log('Admin portal template loaded', 'info');
}

try {
    $current_user = wp_get_current_user();

    // Get stats with error handling
    $student_counts = wp_count_posts('student');
    $total_students = isset($student_counts->publish) ? $student_counts->publish : 0;

    $sponsor_counts = wp_count_posts('alhuffaz_sponsor');
    $total_sponsors = isset($sponsor_counts->publish) ? $sponsor_counts->publish : 0;

    // Get category counts
    $hifz_count = count(get_posts(array(
        'post_type' => 'student',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'meta_query' => array(array('key' => 'islamic_studies_category', 'value' => 'hifz')),
        'fields' => 'ids'
    )));

    $nazra_count = count(get_posts(array(
        'post_type' => 'student',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'meta_query' => array(array('key' => 'islamic_studies_category', 'value' => 'nazra')),
        'fields' => 'ids'
    )));

    // Get pending sponsors count
    $pending_sponsors_count = count(get_posts(array(
        'post_type' => 'alhuffaz_sponsor',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'meta_query' => array(array('key' => '_status', 'value' => 'pending')),
        'fields' => 'ids'
    )));

    // Get pending payments count
    global $wpdb;
    $payments_table = $wpdb->prefix . 'alhuffaz_payments';
    $pending_payments_count = 0;

    // Check if payments table exists before querying
    $table_exists = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s",
        DB_NAME,
        $payments_table
    ));

    if ($table_exists) {
        $pending_payments_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM $payments_table WHERE status = 'pending'");
    }

    // Get donation eligible students count (not sponsored)
    $donation_eligible_count = count(get_posts(array(
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
        'fields' => 'ids'
    )));

    // Role-based access control - with fallback if Roles class not available
    $is_admin = false;
    $is_staff = false;
    $can_manage_sponsors = false;
    $can_manage_payments = false;
    $can_manage_staff = false;
    $staff_count = 0;

    if (class_exists('\AlHuffaz\Core\Roles')) {
        $is_admin = \AlHuffaz\Core\Roles::is_school_admin() || current_user_can('manage_options');
        $is_staff = \AlHuffaz\Core\Roles::is_staff();
        $can_manage_sponsors = \AlHuffaz\Core\Roles::can_manage_sponsors();
        $can_manage_payments = \AlHuffaz\Core\Roles::can_manage_payments();
        $can_manage_staff = \AlHuffaz\Core\Roles::can_manage_staff();
        $staff_count = $can_manage_staff ? count(\AlHuffaz\Core\Roles::get_staff_users()) : 0;

        // Debug log role checks
        if (class_exists('\AlHuffaz\Core\Debug')) {
            \AlHuffaz\Core\Debug::log('Role checks completed', 'debug', array(
                'is_admin' => $is_admin,
                'is_staff' => $is_staff,
                'can_manage_sponsors' => $can_manage_sponsors,
                'user_roles' => $current_user->roles,
            ));
        }
    } else {
        // Fallback: allow WP admins
        $is_admin = current_user_can('manage_options');
        $can_manage_sponsors = current_user_can('manage_options');
        $can_manage_payments = current_user_can('manage_options');
        $can_manage_staff = current_user_can('manage_options');

        if (class_exists('\AlHuffaz\Core\Debug')) {
            \AlHuffaz\Core\Debug::error('Roles class not found, using fallback');
        }
    }

// Get recent students
$recent_students = get_posts(array(
    'post_type' => 'student',
    'post_status' => 'publish',
    'posts_per_page' => 5,
    'orderby' => 'date',
    'order' => 'DESC'
));

// Check for edit mode
$edit_id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
$is_edit = ($edit_id > 0 && get_post_type($edit_id) === 'student');
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

// Helper functions
function ahp_fe_selected($field, $value, $data) {
    return (isset($data[$field]) && $data[$field] === $value) ? 'selected' : '';
}
function ahp_fe_checked($field, $data) {
    return (!empty($data[$field]) && $data[$field] === 'yes') ? 'checked' : '';
}

$portal_url = get_permalink();
$nonce = wp_create_nonce('alhuffaz_student_nonce');

} catch (Exception $e) {
    // Log the error
    if (class_exists('\AlHuffaz\Core\Debug')) {
        \AlHuffaz\Core\Debug::error('Portal initialization error: ' . $e->getMessage(), array(
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ));
    }

    // Display error message
    echo '<div style="padding: 20px; margin: 20px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; color: #721c24;">';
    echo '<h3>Portal Error</h3>';
    echo '<p>An error occurred while loading the portal. Please check the debug log at <code>wp-content/alhuffaz-debug.log</code></p>';
    if (current_user_can('manage_options')) {
        echo '<p><strong>Error:</strong> ' . esc_html($e->getMessage()) . '</p>';
    }
    echo '</div>';
    return;
} catch (Error $e) {
    // Catch PHP 7+ errors
    if (class_exists('\AlHuffaz\Core\Debug')) {
        \AlHuffaz\Core\Debug::error('Portal PHP error: ' . $e->getMessage(), array(
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ));
    }

    echo '<div style="padding: 20px; margin: 20px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; color: #721c24;">';
    echo '<h3>Portal Error</h3>';
    echo '<p>A critical error occurred. Please check the debug log at <code>wp-content/alhuffaz-debug.log</code></p>';
    if (current_user_can('manage_options')) {
        echo '<p><strong>Error:</strong> ' . esc_html($e->getMessage()) . '</p>';
        echo '<p><strong>File:</strong> ' . esc_html($e->getFile()) . ':' . $e->getLine() . '</p>';
    }
    echo '</div>';
    return;
}
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

<style>
/* ============================================
   AL-HUFFAZ ADMIN PORTAL - COMPLETE STYLES
   ============================================ */
:root {
    --ahp-primary: #0080ff;
    --ahp-primary-dark: #0056b3;
    --ahp-success: #10b981;
    --ahp-warning: #f59e0b;
    --ahp-danger: #ef4444;
    --ahp-text: #1e293b;
    --ahp-text-muted: #64748b;
    --ahp-border: #e2e8f0;
    --ahp-bg: #f8fafc;
    --ahp-sidebar: #0f172a;
    --ahp-card: #ffffff;
}
* { box-sizing: border-box; }
.ahp-portal {
    font-family: 'Poppins', sans-serif;
    background: var(--ahp-bg);
    min-height: 100vh;
    color: var(--ahp-text);
}

/* Layout */
.ahp-wrapper { display: flex; min-height: 100vh; }
.ahp-sidebar {
    width: 260px;
    background: var(--ahp-sidebar);
    color: #fff;
    position: fixed;
    height: 100vh;
    overflow-y: auto;
    z-index: 100;
}
.ahp-main {
    flex: 1;
    margin-left: 260px;
    padding: 24px;
    min-height: 100vh;
}

/* Sidebar */
.ahp-sidebar-header {
    padding: 20px;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}
.ahp-logo {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 18px;
    font-weight: 700;
}
.ahp-logo i { color: var(--ahp-primary); font-size: 24px; }
.ahp-nav { padding: 16px 0; }
.ahp-nav-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 20px;
    color: rgba(255,255,255,0.7);
    cursor: pointer;
    transition: all 0.2s;
    border-left: 3px solid transparent;
    text-decoration: none;
}
.ahp-nav-item:hover, .ahp-nav-item.active {
    background: rgba(255,255,255,0.1);
    color: #fff;
    border-left-color: var(--ahp-primary);
}
.ahp-nav-item i { width: 20px; }
.ahp-nav-badge {
    background: var(--ahp-danger);
    color: #fff;
    font-size: 10px;
    padding: 2px 6px;
    border-radius: 10px;
    margin-left: auto;
    font-weight: 700;
    min-width: 18px;
    text-align: center;
}
.ahp-nav-badge.warning { background: var(--ahp-warning); }
.ahp-nav-badge.success { background: var(--ahp-success); }
.ahp-nav-badge.info { background: #3b82f6; }
.ahp-sidebar-footer {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 16px 20px;
    border-top: 1px solid rgba(255,255,255,0.1);
    background: rgba(0,0,0,0.2);
}
.ahp-user { display: flex; align-items: center; gap: 10px; }
.ahp-avatar {
    width: 36px;
    height: 36px;
    background: var(--ahp-primary);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
}
.ahp-user-name { font-weight: 600; font-size: 14px; }
.ahp-user-role { font-size: 12px; color: rgba(255,255,255,0.6); }

/* Header */
.ahp-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}
.ahp-title { margin: 0; font-size: 24px; font-weight: 700; }
.ahp-actions { display: flex; gap: 10px; }

/* Panels */
.ahp-panel { display: none; animation: fadeIn 0.3s; }
.ahp-panel.active { display: block; }
@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

/* Stats */
.ahp-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 24px; }
.ahp-stat {
    background: var(--ahp-card);
    border-radius: 12px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}
.ahp-stat-icon {
    width: 56px;
    height: 56px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
}
.ahp-stat-icon.blue { background: #dbeafe; color: #1e40af; }
.ahp-stat-icon.green { background: #d1fae5; color: #065f46; }
.ahp-stat-icon.purple { background: #e9d5ff; color: #6b21a8; }
.ahp-stat-icon.orange { background: #fed7aa; color: #c2410c; }
.ahp-stat-label { font-size: 13px; color: var(--ahp-text-muted); margin-bottom: 4px; }
.ahp-stat-value { font-size: 28px; font-weight: 800; }

/* Card */
.ahp-card {
    background: var(--ahp-card);
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    margin-bottom: 24px;
    overflow: hidden;
}
.ahp-card-header {
    padding: 16px 20px;
    border-bottom: 1px solid var(--ahp-border);
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.ahp-card-title { margin: 0; font-size: 16px; font-weight: 700; }
.ahp-card-body { padding: 20px; }

/* Buttons */
.ahp-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 10px 18px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 13px;
    cursor: pointer;
    border: none;
    font-family: inherit;
    transition: all 0.2s;
    text-decoration: none;
}
.ahp-btn-primary { background: var(--ahp-primary); color: #fff; }
.ahp-btn-primary:hover { background: var(--ahp-primary-dark); }
.ahp-btn-success { background: var(--ahp-success); color: #fff; }
.ahp-btn-danger { background: var(--ahp-danger); color: #fff; }
.ahp-btn-secondary { background: var(--ahp-bg); color: var(--ahp-text); border: 1px solid var(--ahp-border); }
.ahp-btn-sm { padding: 6px 12px; font-size: 12px; }
.ahp-btn-icon { width: 32px; height: 32px; padding: 0; justify-content: center; }

/* Table */
.ahp-table-wrap { overflow-x: auto; }
.ahp-table { width: 100%; border-collapse: collapse; }
.ahp-table th, .ahp-table td { padding: 12px 16px; text-align: left; border-bottom: 1px solid var(--ahp-border); }
.ahp-table th { background: var(--ahp-bg); font-weight: 600; font-size: 12px; text-transform: uppercase; color: var(--ahp-text-muted); }
.ahp-table tr:hover { background: var(--ahp-bg); }
.ahp-student-cell { display: flex; align-items: center; gap: 10px; }
.ahp-student-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: var(--ahp-primary);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 13px;
    object-fit: cover;
}
.ahp-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
}
.ahp-badge-primary { background: #dbeafe; color: #1e40af; }
.ahp-badge-success { background: #d1fae5; color: #065f46; }
.ahp-cell-actions { display: flex; gap: 6px; }

/* Toolbar */
.ahp-toolbar { display: flex; gap: 12px; margin-bottom: 20px; flex-wrap: wrap; }
.ahp-search {
    flex: 1;
    min-width: 250px;
    position: relative;
}
.ahp-search input {
    width: 100%;
    padding: 10px 14px 10px 40px;
    border: 2px solid var(--ahp-border);
    border-radius: 8px;
    font-size: 14px;
    font-family: inherit;
}
.ahp-search i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--ahp-text-muted); }
.ahp-filter {
    padding: 10px 14px;
    border: 2px solid var(--ahp-border);
    border-radius: 8px;
    font-size: 14px;
    font-family: inherit;
    min-width: 140px;
}

/* Form */
.ahp-form-grid { display: grid; grid-template-columns: repeat(12, 1fr); gap: 16px; }
.ahp-col-3 { grid-column: span 3; }
.ahp-col-4 { grid-column: span 4; }
.ahp-col-6 { grid-column: span 6; }
.ahp-col-12 { grid-column: span 12; }
.ahp-form-group { margin-bottom: 0; }
.ahp-label { display: block; font-weight: 600; font-size: 13px; margin-bottom: 6px; color: var(--ahp-text); }
.ahp-label.required::after { content: ' *'; color: var(--ahp-danger); }
.ahp-input {
    width: 100%;
    padding: 10px 14px;
    border: 2px solid var(--ahp-border);
    border-radius: 8px;
    font-size: 14px;
    font-family: inherit;
    transition: border-color 0.2s;
}
.ahp-input:focus { outline: none; border-color: var(--ahp-primary); }
textarea.ahp-input { resize: vertical; min-height: 80px; }

/* Progress Bar */
.ahp-progress {
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 32px;
    padding: 20px;
    background: var(--ahp-card);
    border-radius: 12px;
}
.ahp-step {
    display: flex;
    flex-direction: column;
    align-items: center;
    position: relative;
}
.ahp-step-num {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: var(--ahp-border);
    color: var(--ahp-text-muted);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    transition: all 0.3s;
}
.ahp-step.active .ahp-step-num { background: var(--ahp-primary); color: #fff; }
.ahp-step.completed .ahp-step-num { background: var(--ahp-success); color: #fff; }
.ahp-step-label { font-size: 12px; margin-top: 8px; color: var(--ahp-text-muted); font-weight: 500; }
.ahp-step.active .ahp-step-label { color: var(--ahp-primary); font-weight: 600; }
.ahp-step-line {
    width: 80px;
    height: 3px;
    background: var(--ahp-border);
    margin: 0 8px;
    margin-bottom: 24px;
}
.ahp-step-line.completed { background: var(--ahp-success); }

/* Form Steps */
.ahp-form-step { display: none; }
.ahp-form-step.active { display: block; }
.ahp-step-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 2px solid var(--ahp-border);
}
.ahp-step-header i { font-size: 24px; color: var(--ahp-primary); }
.ahp-step-header h2 { margin: 0; font-size: 20px; }

/* Sections */
.ahp-section {
    background: var(--ahp-bg);
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 20px;
}
.ahp-section-title {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 15px;
    font-weight: 700;
    margin: 0 0 16px;
    color: var(--ahp-text);
}
.ahp-section-title i { color: var(--ahp-primary); }

/* Subjects */
.ahp-subject-box {
    background: var(--ahp-card);
    border: 2px solid var(--ahp-border);
    border-radius: 12px;
    margin-bottom: 16px;
    overflow: hidden;
}
.ahp-subject-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
    background: var(--ahp-bg);
    border-bottom: 1px solid var(--ahp-border);
}
.ahp-subject-title { display: flex; align-items: center; gap: 10px; flex: 1; }
.ahp-subject-title i { color: var(--ahp-primary); }
.ahp-subject-name {
    flex: 1;
    border: none;
    background: transparent;
    font-size: 15px;
    font-weight: 600;
    font-family: inherit;
}
.ahp-subject-name:focus { outline: none; }
.ahp-subject-content { padding: 16px; }
.ahp-exam-section { margin-bottom: 20px; }
.ahp-exam-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 12px;
}
.ahp-exam-header h4 { margin: 0; font-size: 14px; display: flex; align-items: center; gap: 8px; }
.ahp-exam-header h4 i { color: var(--ahp-primary); }
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
.ahp-pagination { display: flex; justify-content: center; gap: 6px; padding: 20px; }

/* Responsive */
@media (max-width: 1024px) {
    .ahp-sidebar { transform: translateX(-100%); transition: transform 0.3s; }
    .ahp-sidebar.open { transform: translateX(0); }
    .ahp-main { margin-left: 0; }
    .ahp-form-grid { grid-template-columns: repeat(6, 1fr); }
    .ahp-col-3, .ahp-col-4 { grid-column: span 3; }
}
@media (max-width: 768px) {
    .ahp-form-grid { grid-template-columns: 1fr; }
    .ahp-col-3, .ahp-col-4, .ahp-col-6 { grid-column: span 1; }
    .ahp-marks-row { grid-template-columns: repeat(2, 1fr); }
    .ahp-stats { grid-template-columns: 1fr 1fr; }
    .ahp-progress { flex-wrap: wrap; }
    .ahp-step-line { display: none; }
}
</style>

<div class="ahp-portal">
    <div class="ahp-wrapper">
        <!-- Sidebar -->
        <aside class="ahp-sidebar" id="sidebar">
            <div class="ahp-sidebar-header">
                <div class="ahp-logo">
                    <i class="fas fa-graduation-cap"></i>
                    <span>Al-Huffaz Portal</span>
                </div>
            </div>
            <nav class="ahp-nav">
                <a class="ahp-nav-item active" data-panel="dashboard">
                    <i class="fas fa-home"></i>
                    <span><?php _e('Dashboard', 'al-huffaz-portal'); ?></span>
                </a>
                <a class="ahp-nav-item" data-panel="students">
                    <i class="fas fa-users"></i>
                    <span><?php _e('Students', 'al-huffaz-portal'); ?></span>
                </a>
                <a class="ahp-nav-item" data-panel="add-student">
                    <i class="fas fa-user-plus"></i>
                    <span><?php _e('Add Student', 'al-huffaz-portal'); ?></span>
                </a>
                <?php if ($can_manage_sponsors): ?>
                <a class="ahp-nav-item" data-panel="sponsors">
                    <i class="fas fa-hand-holding-heart"></i>
                    <span><?php _e('Sponsors', 'al-huffaz-portal'); ?></span>
                    <?php if ($pending_sponsors_count > 0): ?>
                    <span class="ahp-nav-badge"><?php echo $pending_sponsors_count; ?></span>
                    <?php endif; ?>
                </a>
                <?php endif; ?>
                <?php if ($can_manage_payments): ?>
                <a class="ahp-nav-item" data-panel="payments">
                    <i class="fas fa-credit-card"></i>
                    <span><?php _e('Payments', 'al-huffaz-portal'); ?></span>
                    <?php if ($pending_payments_count > 0): ?>
                    <span class="ahp-nav-badge warning"><?php echo $pending_payments_count; ?></span>
                    <?php endif; ?>
                </a>
                <?php endif; ?>
                <?php if ($can_manage_staff): ?>
                <a class="ahp-nav-item" data-panel="staff">
                    <i class="fas fa-user-shield"></i>
                    <span><?php _e('Staff Management', 'al-huffaz-portal'); ?></span>
                    <?php if ($staff_count > 0): ?>
                    <span class="ahp-nav-badge info"><?php echo $staff_count; ?></span>
                    <?php endif; ?>
                </a>
                <?php endif; ?>
                <?php if ($is_admin): ?>
                <a class="ahp-nav-item" href="<?php echo admin_url(); ?>" target="_blank">
                    <i class="fas fa-cog"></i>
                    <span><?php _e('WP Admin', 'al-huffaz-portal'); ?></span>
                </a>
                <?php endif; ?>
                <a class="ahp-nav-item" href="<?php echo wp_logout_url(home_url()); ?>">
                    <i class="fas fa-sign-out-alt"></i>
                    <span><?php _e('Logout', 'al-huffaz-portal'); ?></span>
                </a>
            </nav>
            <div class="ahp-sidebar-footer">
                <div class="ahp-user">
                    <div class="ahp-avatar"><?php echo strtoupper(substr($current_user->display_name, 0, 1)); ?></div>
                    <div>
                        <div class="ahp-user-name"><?php echo esc_html($current_user->display_name); ?></div>
                        <div class="ahp-user-role"><?php echo esc_html(ucfirst($current_user->roles[0] ?? 'User')); ?></div>
                    </div>
                </div>
            </div>
        </aside>

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
                        <select class="ahp-filter" id="filterSponsorStatus" onchange="loadSponsors()">
                            <option value=""><?php _e('All Status', 'al-huffaz-portal'); ?></option>
                            <option value="pending" selected><?php _e('Pending', 'al-huffaz-portal'); ?></option>
                            <option value="approved"><?php _e('Approved', 'al-huffaz-portal'); ?></option>
                            <option value="rejected"><?php _e('Rejected', 'al-huffaz-portal'); ?></option>
                        </select>
                    </div>
                </div>

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
            <?php endif; ?>

            <!-- ==================== PAYMENTS PANEL ==================== -->
            <?php if ($can_manage_payments): ?>
            <div class="ahp-panel" id="panel-payments">
                <div class="ahp-header">
                    <h1 class="ahp-title"><?php _e('Payment Verification', 'al-huffaz-portal'); ?></h1>
                    <div class="ahp-actions">
                        <select class="ahp-filter" id="filterPaymentStatus" onchange="loadPayments()">
                            <option value=""><?php _e('All Status', 'al-huffaz-portal'); ?></option>
                            <option value="pending" selected><?php _e('Pending', 'al-huffaz-portal'); ?></option>
                            <option value="approved"><?php _e('Verified', 'al-huffaz-portal'); ?></option>
                            <option value="rejected"><?php _e('Rejected', 'al-huffaz-portal'); ?></option>
                        </select>
                    </div>
                </div>

                <div class="ahp-stats">
                    <div class="ahp-stat">
                        <div class="ahp-stat-icon orange"><i class="fas fa-hourglass-half"></i></div>
                        <div>
                            <div class="ahp-stat-label"><?php _e('Pending Verification', 'al-huffaz-portal'); ?></div>
                            <div class="ahp-stat-value" id="pendingPaymentCount"><?php echo $pending_payments_count; ?></div>
                        </div>
                    </div>
                    <div class="ahp-stat">
                        <div class="ahp-stat-icon green"><i class="fas fa-check-double"></i></div>
                        <div>
                            <div class="ahp-stat-label"><?php _e('Verified Payments', 'al-huffaz-portal'); ?></div>
                            <div class="ahp-stat-value" id="verifiedPaymentCount">-</div>
                        </div>
                    </div>
                    <div class="ahp-stat">
                        <div class="ahp-stat-icon blue"><i class="fas fa-rupee-sign"></i></div>
                        <div>
                            <div class="ahp-stat-label"><?php _e('Total Revenue', 'al-huffaz-portal'); ?></div>
                            <div class="ahp-stat-value" id="totalRevenue">-</div>
                        </div>
                    </div>
                </div>

                <div class="ahp-card">
                    <div class="ahp-card-header">
                        <h3 class="ahp-card-title"><i class="fas fa-receipt"></i> <?php _e('Payment Records', 'al-huffaz-portal'); ?></h3>
                    </div>
                    <div class="ahp-card-body" style="padding:0;">
                        <div class="ahp-table-wrap">
                            <table class="ahp-table">
                                <thead>
                                    <tr>
                                        <th><?php _e('Sponsor', 'al-huffaz-portal'); ?></th>
                                        <th><?php _e('Student', 'al-huffaz-portal'); ?></th>
                                        <th><?php _e('Amount', 'al-huffaz-portal'); ?></th>
                                        <th><?php _e('Method', 'al-huffaz-portal'); ?></th>
                                        <th><?php _e('Transaction ID', 'al-huffaz-portal'); ?></th>
                                        <th><?php _e('Status', 'al-huffaz-portal'); ?></th>
                                        <th><?php _e('Date', 'al-huffaz-portal'); ?></th>
                                        <th><?php _e('Actions', 'al-huffaz-portal'); ?></th>
                                    </tr>
                                </thead>
                                <tbody id="paymentsTableBody">
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
                    <h1 class="ahp-title"><?php _e('Staff Management', 'al-huffaz-portal'); ?></h1>
                    <div class="ahp-actions">
                        <button class="ahp-btn ahp-btn-primary" onclick="showAddStaffModal()">
                            <i class="fas fa-user-plus"></i> <?php _e('Add Staff', 'al-huffaz-portal'); ?>
                        </button>
                    </div>
                </div>

                <div class="ahp-stats">
                    <div class="ahp-stat">
                        <div class="ahp-stat-icon blue"><i class="fas fa-user-shield"></i></div>
                        <div>
                            <div class="ahp-stat-label"><?php _e('Total Staff', 'al-huffaz-portal'); ?></div>
                            <div class="ahp-stat-value" id="totalStaffCount"><?php echo $staff_count; ?></div>
                        </div>
                    </div>
                    <div class="ahp-stat">
                        <div class="ahp-stat-icon green"><i class="fas fa-user-check"></i></div>
                        <div>
                            <div class="ahp-stat-label"><?php _e('Admins', 'al-huffaz-portal'); ?></div>
                            <div class="ahp-stat-value"><?php echo count(get_users(array('role' => 'alhuffaz_admin'))); ?></div>
                        </div>
                    </div>
                    <div class="ahp-stat">
                        <div class="ahp-stat-icon orange"><i class="fas fa-users"></i></div>
                        <div>
                            <div class="ahp-stat-label"><?php _e('Eligible Users', 'al-huffaz-portal'); ?></div>
                            <div class="ahp-stat-value" id="eligibleUsersCount">-</div>
                        </div>
                    </div>
                </div>

                <div class="ahp-card">
                    <div class="ahp-card-header">
                        <h3 class="ahp-card-title"><i class="fas fa-user-shield"></i> <?php _e('Current Staff Members', 'al-huffaz-portal'); ?></h3>
                        <p style="color:var(--ahp-text-muted);margin:0;font-size:14px;"><?php _e('Staff can add and edit students only. They cannot manage sponsors, payments, or other staff.', 'al-huffaz-portal'); ?></p>
                    </div>
                    <div class="ahp-card-body" style="padding:0;">
                        <div class="ahp-table-wrap">
                            <table class="ahp-table">
                                <thead>
                                    <tr>
                                        <th><?php _e('User', 'al-huffaz-portal'); ?></th>
                                        <th><?php _e('Email', 'al-huffaz-portal'); ?></th>
                                        <th><?php _e('Registered', 'al-huffaz-portal'); ?></th>
                                        <th><?php _e('Actions', 'al-huffaz-portal'); ?></th>
                                    </tr>
                                </thead>
                                <tbody id="staffTableBody">
                                    <tr><td colspan="4" class="ahp-loading"><div class="ahp-spinner"></div></td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        </main>
    </div>
    <div class="ahp-toast" id="toast"></div>
</div>

<!-- Modal for Adding Staff -->
<?php if ($can_manage_staff): ?>
<div id="addStaffModal" class="ahp-modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:12px;max-width:500px;width:90%;max-height:80vh;overflow:auto;box-shadow:0 20px 50px rgba(0,0,0,0.2);">
        <div style="padding:20px;border-bottom:1px solid var(--ahp-border);display:flex;justify-content:space-between;align-items:center;">
            <h3 style="margin:0;"><?php _e('Grant Staff Access', 'al-huffaz-portal'); ?></h3>
            <button onclick="closeAddStaffModal()" style="background:none;border:none;font-size:20px;cursor:pointer;">&times;</button>
        </div>
        <div style="padding:20px;">
            <p style="color:var(--ahp-text-muted);margin-top:0;"><?php _e('Select a user to grant staff access. Staff members can add and edit students.', 'al-huffaz-portal'); ?></p>
            <div class="ahp-form-group">
                <label class="ahp-form-label"><?php _e('Select User', 'al-huffaz-portal'); ?></label>
                <select id="eligibleUserSelect" class="ahp-form-select" style="width:100%;">
                    <option value=""><?php _e('Loading users...', 'al-huffaz-portal'); ?></option>
                </select>
            </div>
            <div id="selectedUserInfo" style="display:none;background:var(--ahp-bg);padding:15px;border-radius:8px;margin-top:15px;">
                <strong id="selectedUserName"></strong>
                <p id="selectedUserEmail" style="margin:5px 0 0;color:var(--ahp-text-muted);font-size:14px;"></p>
            </div>
        </div>
        <div style="padding:20px;border-top:1px solid var(--ahp-border);display:flex;gap:10px;justify-content:flex-end;">
            <button onclick="closeAddStaffModal()" class="ahp-btn"><?php _e('Cancel', 'al-huffaz-portal'); ?></button>
            <button id="grantStaffBtn" onclick="grantStaffAccess()" class="ahp-btn ahp-btn-primary" disabled>
                <i class="fas fa-user-plus"></i> <?php _e('Grant Access', 'al-huffaz-portal'); ?>
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
document.addEventListener('DOMContentLoaded', function() {
    const ajaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';
    const nonce = '<?php echo $nonce; ?>';
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
        if (panel === 'sponsors') loadSponsors();
        if (panel === 'payments') loadPayments();
        if (panel === 'staff') loadStaff();
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

    window.deleteStudent = function(id) {
        if (!confirm('Are you sure you want to delete this student?')) return;
        fetch(ajaxUrl, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({action: 'alhuffaz_delete_student', nonce, student_id: id})
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) { showToast('Student deleted', 'success'); loadStudents(currentPage); }
            else showToast(data.data?.message || 'Error', 'error');
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
    document.getElementById('studentForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const btn = document.getElementById('submitBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

        fetch(ajaxUrl, {method: 'POST', body: new FormData(this)})
        .then(r => r.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save"></i> <?php echo $is_edit ? "Update" : "Enroll"; ?> Student';
            if (data.success) {
                showToast(data.data?.message || 'Student saved!', 'success');
                setTimeout(() => showPanel('students'), 1000);
            } else {
                showToast(data.data?.message || 'Error saving', 'error');
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

    // ==================== TOAST ====================
    window.showToast = function(msg, type) {
        const toast = document.getElementById('toast');
        toast.textContent = msg;
        toast.className = 'ahp-toast ' + type;
        toast.style.display = 'block';
        setTimeout(() => toast.style.display = 'none', 3000);
    };

    // ==================== SPONSORS MANAGEMENT ====================
    let currentSponsorId = null;

    window.loadSponsors = function() {
        const status = document.getElementById('filterSponsorStatus')?.value || '';
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

        // Load sponsor details via AJAX (simplified for now)
        document.getElementById('sponsorModalBody').innerHTML = `
            <p>Loading sponsor details for ID: ${id}</p>
            <p>Click Approve to approve this sponsorship and create the sponsor account.</p>
        `;
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

    // ==================== PAYMENTS MANAGEMENT ====================
    window.loadPayments = function() {
        const status = document.getElementById('filterPaymentStatus')?.value || '';
        document.getElementById('paymentsTableBody').innerHTML = '<tr><td colspan="8" class="ahp-loading"><div class="ahp-spinner"></div></td></tr>';

        fetch(ajaxUrl, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({action: 'alhuffaz_get_payments', nonce, status, per_page: 50})
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                renderPayments(data.data.payments);
            } else {
                document.getElementById('paymentsTableBody').innerHTML = '<tr><td colspan="8" style="text-align:center;padding:40px;">Error loading payments</td></tr>';
            }
        });
    };

    function renderPayments(payments) {
        const tbody = document.getElementById('paymentsTableBody');
        if (!payments || !payments.length) {
            tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:40px;color:var(--ahp-text-muted);">No payment records found</td></tr>';
            return;
        }

        tbody.innerHTML = payments.map(p => {
            const statusBadge = p.status === 'pending' ? 'ahp-badge-warning' :
                               p.status === 'approved' ? 'ahp-badge-success' : 'ahp-badge-danger';
            return `
            <tr>
                <td><strong>${p.sponsor_name || '-'}</strong></td>
                <td>${p.student_name || '-'}</td>
                <td><strong>${p.amount || '-'}</strong></td>
                <td><span class="ahp-badge ahp-badge-primary">${(p.method || '-').toUpperCase()}</span></td>
                <td><code>${p.transaction_id || '-'}</code></td>
                <td><span class="ahp-badge ${statusBadge}">${(p.status || '-').charAt(0).toUpperCase() + (p.status || '-').slice(1)}</span></td>
                <td>${p.date || '-'}</td>
                <td>
                    <div class="ahp-cell-actions">
                        ${p.status === 'pending' ? `
                        <button class="ahp-btn ahp-btn-success ahp-btn-icon" onclick="verifyPayment(${p.id}, 'approved')" title="Verify"><i class="fas fa-check"></i></button>
                        <button class="ahp-btn ahp-btn-danger ahp-btn-icon" onclick="verifyPayment(${p.id}, 'rejected')" title="Reject"><i class="fas fa-times"></i></button>
                        ` : ''}
                    </div>
                </td>
            </tr>`;
        }).join('');
    }

    window.verifyPayment = function(id, status) {
        const confirmMsg = status === 'approved' ?
            '<?php _e('Verify this payment?', 'al-huffaz-portal'); ?>' :
            '<?php _e('Reject this payment?', 'al-huffaz-portal'); ?>';
        if (!confirm(confirmMsg)) return;

        fetch(ajaxUrl, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({action: 'alhuffaz_verify_payment', nonce, payment_id: id, status})
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showToast(status === 'approved' ? '<?php _e('Payment verified!', 'al-huffaz-portal'); ?>' : '<?php _e('Payment rejected', 'al-huffaz-portal'); ?>', 'success');
                loadPayments();
            } else {
                showToast(data.data?.message || '<?php _e('Error processing payment', 'al-huffaz-portal'); ?>', 'error');
            }
        });
    };

    // ==================== STAFF MANAGEMENT FUNCTIONS ====================
    <?php if ($can_manage_staff): ?>
    let eligibleUsers = [];

    window.loadStaff = function() {
        document.getElementById('staffTableBody').innerHTML = '<tr><td colspan="4" class="ahp-loading"><div class="ahp-spinner"></div></td></tr>';

        fetch(ajaxUrl, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({action: 'alhuffaz_get_staff_users', nonce})
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                document.getElementById('totalStaffCount').textContent = data.data.count;
                renderStaff(data.data.staff);
            } else {
                showToast(data.data?.message || '<?php _e('Error loading staff', 'al-huffaz-portal'); ?>', 'error');
            }
        });
    };

    function renderStaff(staff) {
        const tbody = document.getElementById('staffTableBody');
        if (!staff || staff.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;padding:40px;color:var(--ahp-text-muted);"><i class="fas fa-user-shield" style="font-size:48px;opacity:0.3;display:block;margin-bottom:10px;"></i><?php _e('No staff members yet. Click "Add Staff" to grant access to a user.', 'al-huffaz-portal'); ?></td></tr>';
            return;
        }

        tbody.innerHTML = staff.map(s => `
            <tr>
                <td>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <img src="${s.avatar}" alt="" style="width:40px;height:40px;border-radius:50%;">
                        <strong>${s.display_name}</strong>
                    </div>
                </td>
                <td>${s.email}</td>
                <td>${s.registered}</td>
                <td>
                    <button class="ahp-btn ahp-btn-danger ahp-btn-icon" onclick="revokeStaffAccess(${s.id}, '${s.display_name}')" title="<?php _e('Revoke Access', 'al-huffaz-portal'); ?>">
                        <i class="fas fa-user-slash"></i>
                    </button>
                </td>
            </tr>
        `).join('');
    }

    window.loadEligibleUsers = function() {
        fetch(ajaxUrl, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({action: 'alhuffaz_get_eligible_users', nonce})
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                eligibleUsers = data.data.users;
                document.getElementById('eligibleUsersCount').textContent = data.data.count;
                updateEligibleUserSelect();
            }
        });
    };

    function updateEligibleUserSelect() {
        const select = document.getElementById('eligibleUserSelect');
        if (!eligibleUsers || eligibleUsers.length === 0) {
            select.innerHTML = '<option value=""><?php _e('No eligible users found', 'al-huffaz-portal'); ?></option>';
            return;
        }

        select.innerHTML = '<option value=""><?php _e('-- Select a user --', 'al-huffaz-portal'); ?></option>' +
            eligibleUsers.map(u => `<option value="${u.id}" data-name="${u.display_name}" data-email="${u.email}">${u.display_name} (${u.email})</option>`).join('');
    }

    window.showAddStaffModal = function() {
        document.getElementById('addStaffModal').style.display = 'flex';
        loadEligibleUsers();

        // Setup select change handler
        document.getElementById('eligibleUserSelect').onchange = function() {
            const selected = this.options[this.selectedIndex];
            const btn = document.getElementById('grantStaffBtn');
            const info = document.getElementById('selectedUserInfo');

            if (this.value) {
                document.getElementById('selectedUserName').textContent = selected.dataset.name;
                document.getElementById('selectedUserEmail').textContent = selected.dataset.email;
                info.style.display = 'block';
                btn.disabled = false;
            } else {
                info.style.display = 'none';
                btn.disabled = true;
            }
        };
    };

    window.closeAddStaffModal = function() {
        document.getElementById('addStaffModal').style.display = 'none';
        document.getElementById('eligibleUserSelect').value = '';
        document.getElementById('selectedUserInfo').style.display = 'none';
        document.getElementById('grantStaffBtn').disabled = true;
    };

    window.grantStaffAccess = function() {
        const userId = document.getElementById('eligibleUserSelect').value;
        if (!userId) return;

        const userName = document.getElementById('eligibleUserSelect').options[document.getElementById('eligibleUserSelect').selectedIndex].dataset.name;

        fetch(ajaxUrl, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({action: 'alhuffaz_grant_staff_role', nonce, user_id: userId})
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showToast(data.data.message, 'success');
                closeAddStaffModal();
                loadStaff();
                loadEligibleUsers();
            } else {
                showToast(data.data?.message || '<?php _e('Error granting access', 'al-huffaz-portal'); ?>', 'error');
            }
        });
    };

    window.revokeStaffAccess = function(userId, userName) {
        if (!confirm('<?php _e('Are you sure you want to revoke staff access for', 'al-huffaz-portal'); ?> ' + userName + '?')) return;

        fetch(ajaxUrl, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({action: 'alhuffaz_revoke_staff_role', nonce, user_id: userId})
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showToast(data.data.message, 'success');
                loadStaff();
                loadEligibleUsers();
            } else {
                showToast(data.data?.message || '<?php _e('Error revoking access', 'al-huffaz-portal'); ?>', 'error');
            }
        });
    };
    <?php endif; ?>

    // Auto-show panel based on URL
    <?php if ($is_edit): ?>
    showPanel('add-student');
    <?php endif; ?>
});
</script>
