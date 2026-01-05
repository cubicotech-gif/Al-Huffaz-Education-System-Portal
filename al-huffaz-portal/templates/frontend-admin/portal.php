<?php
/**
 * Front-end Admin Portal Template
 * Al-Huffaz Education System Portal
 *
 * Full admin interface accessible from front-end
 */

defined('ABSPATH') || exit;

use AlHuffaz\Admin\Student_Manager;
use AlHuffaz\Core\Helpers;

$current_user = wp_get_current_user();

// Get stats
$total_students = wp_count_posts('student')->publish;
$total_sponsors = wp_count_posts('alhuffaz_sponsor')->publish;

// Get grade counts
$grades = array('kg1', 'kg2', 'class1', 'class2', 'class3', 'level1', 'level2', 'level3', 'shb', 'shg');
$grade_counts = array();
foreach ($grades as $grade) {
    $grade_counts[$grade] = count(get_posts(array(
        'post_type' => 'student',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'meta_query' => array(
            array('key' => 'grade_level', 'value' => $grade)
        ),
        'fields' => 'ids'
    )));
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
$student_data = array();
if ($edit_id) {
    $student_data = Student_Manager::get_student_data($edit_id);
}

// Page URLs
$portal_url = get_permalink();
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

<style>
/* ADMIN PORTAL STYLES */
:root {
    --portal-primary: #0080ff;
    --portal-primary-dark: #004d99;
    --portal-success: #10b981;
    --portal-warning: #f59e0b;
    --portal-danger: #ef4444;
    --portal-text: #1e293b;
    --portal-text-muted: #64748b;
    --portal-border: #e2e8f0;
    --portal-bg: #f8fafc;
    --portal-sidebar: #0f172a;
}

* { box-sizing: border-box; }

.ahp-portal {
    font-family: 'Poppins', sans-serif;
    min-height: 100vh;
    background: var(--portal-bg);
}

/* LOGIN STYLES */
.ahp-admin-login {
    min-height: 60vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px 20px;
    font-family: 'Poppins', sans-serif;
    background: linear-gradient(135deg, var(--portal-primary) 0%, var(--portal-primary-dark) 100%);
}

.ahp-admin-login-card {
    background: white;
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    padding: 48px;
    max-width: 420px;
    width: 100%;
    text-align: center;
}

.ahp-admin-login-icon {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, var(--portal-primary), var(--portal-primary-dark));
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 24px;
}

.ahp-admin-login-icon i {
    font-size: 36px;
    color: white;
}

.ahp-admin-login-card h2 {
    margin: 0 0 12px;
    font-size: 28px;
    color: var(--portal-text);
}

.ahp-admin-login-card p {
    margin: 0 0 32px;
    color: var(--portal-text-muted);
}

.ahp-admin-login-card input[type="text"],
.ahp-admin-login-card input[type="password"] {
    width: 100%;
    padding: 14px 16px;
    border: 2px solid var(--portal-border);
    border-radius: 10px;
    font-size: 15px;
    margin-bottom: 16px;
    font-family: 'Poppins', sans-serif;
}

.ahp-admin-login-card input[type="submit"] {
    width: 100%;
    padding: 14px;
    background: linear-gradient(135deg, var(--portal-primary), var(--portal-primary-dark));
    color: white;
    border: none;
    border-radius: 10px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    font-family: 'Poppins', sans-serif;
}

/* PORTAL LAYOUT */
.ahp-portal-wrapper {
    display: flex;
    min-height: 100vh;
}

/* SIDEBAR */
.ahp-portal-sidebar {
    width: 280px;
    background: var(--portal-sidebar);
    color: white;
    position: fixed;
    height: 100vh;
    overflow-y: auto;
    z-index: 100;
}

.ahp-sidebar-header {
    padding: 24px;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.ahp-sidebar-logo {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 20px;
    font-weight: 700;
}

.ahp-sidebar-logo i {
    font-size: 28px;
    color: var(--portal-primary);
}

.ahp-sidebar-nav {
    padding: 16px 0;
}

.ahp-nav-item {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 24px;
    color: rgba(255,255,255,0.7);
    text-decoration: none;
    cursor: pointer;
    transition: all 0.3s;
    border-left: 3px solid transparent;
}

.ahp-nav-item:hover,
.ahp-nav-item.active {
    background: rgba(255,255,255,0.1);
    color: white;
    border-left-color: var(--portal-primary);
}

.ahp-nav-item i {
    width: 24px;
    font-size: 18px;
}

.ahp-sidebar-footer {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 20px 24px;
    border-top: 1px solid rgba(255,255,255,0.1);
    background: rgba(0,0,0,0.2);
}

.ahp-user-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.ahp-user-avatar {
    width: 40px;
    height: 40px;
    background: var(--portal-primary);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
}

.ahp-user-name {
    font-weight: 600;
    font-size: 14px;
}

.ahp-user-role {
    font-size: 12px;
    color: rgba(255,255,255,0.6);
}

/* MAIN CONTENT */
.ahp-portal-main {
    flex: 1;
    margin-left: 280px;
    padding: 32px;
}

.ahp-portal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 32px;
}

.ahp-portal-title {
    margin: 0;
    font-size: 28px;
    font-weight: 700;
    color: var(--portal-text);
}

.ahp-header-actions {
    display: flex;
    gap: 12px;
}

/* PANELS */
.ahp-panel {
    display: none;
}

.ahp-panel.active {
    display: block;
    animation: fadeIn 0.3s;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* STATS GRID */
.ahp-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 24px;
    margin-bottom: 32px;
}

.ahp-stat-card {
    background: white;
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    display: flex;
    align-items: center;
    gap: 20px;
}

.ahp-stat-icon {
    width: 64px;
    height: 64px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
}

.ahp-stat-icon.blue { background: #dbeafe; color: #1e40af; }
.ahp-stat-icon.green { background: #d1fae5; color: #065f46; }
.ahp-stat-icon.purple { background: #e9d5ff; color: #6b21a8; }
.ahp-stat-icon.orange { background: #fed7aa; color: #c2410c; }

.ahp-stat-label {
    font-size: 14px;
    color: var(--portal-text-muted);
    margin-bottom: 4px;
}

.ahp-stat-value {
    font-size: 32px;
    font-weight: 800;
    color: var(--portal-text);
}

/* CARDS */
.ahp-card {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    overflow: hidden;
    margin-bottom: 24px;
}

.ahp-card-header {
    padding: 20px 24px;
    border-bottom: 1px solid var(--portal-border);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.ahp-card-title {
    margin: 0;
    font-size: 18px;
    font-weight: 700;
    color: var(--portal-text);
}

.ahp-card-body {
    padding: 24px;
}

/* BUTTONS */
.ahp-btn {
    display: inline-flex;
    align-items: center;
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

.ahp-btn-primary {
    background: linear-gradient(135deg, var(--portal-primary), var(--portal-primary-dark));
    color: white;
}

.ahp-btn-secondary {
    background: var(--portal-bg);
    color: var(--portal-text);
    border: 2px solid var(--portal-border);
}

.ahp-btn-success { background: var(--portal-success); color: white; }
.ahp-btn-danger { background: var(--portal-danger); color: white; }

.ahp-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.ahp-btn-sm {
    padding: 8px 16px;
    font-size: 13px;
}

.ahp-btn-icon {
    width: 36px;
    height: 36px;
    padding: 0;
    justify-content: center;
    border-radius: 8px;
}

/* TABLE */
.ahp-table-wrapper {
    overflow-x: auto;
}

.ahp-table {
    width: 100%;
    border-collapse: collapse;
}

.ahp-table th,
.ahp-table td {
    padding: 14px 16px;
    text-align: left;
    border-bottom: 1px solid var(--portal-border);
}

.ahp-table thead th {
    background: var(--portal-bg);
    font-weight: 700;
    color: var(--portal-text);
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.ahp-table tbody tr:hover {
    background: var(--portal-bg);
}

.ahp-student-cell {
    display: flex;
    align-items: center;
    gap: 12px;
}

.ahp-student-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
}

.ahp-student-avatar-placeholder {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: var(--portal-primary);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 14px;
}

.ahp-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.ahp-badge-primary { background: #dbeafe; color: #1e40af; }
.ahp-badge-success { background: #d1fae5; color: #065f46; }
.ahp-badge-warning { background: #fef3c7; color: #92400e; }

.ahp-actions {
    display: flex;
    gap: 8px;
}

/* SEARCH & FILTERS */
.ahp-toolbar {
    display: flex;
    gap: 16px;
    margin-bottom: 24px;
    flex-wrap: wrap;
}

.ahp-search-box {
    flex: 1;
    min-width: 300px;
    position: relative;
}

.ahp-search-box input {
    width: 100%;
    padding: 12px 16px 12px 44px;
    border: 2px solid var(--portal-border);
    border-radius: 10px;
    font-size: 14px;
    font-family: 'Poppins', sans-serif;
}

.ahp-search-box i {
    position: absolute;
    left: 16px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--portal-text-muted);
}

.ahp-filter-select {
    padding: 12px 16px;
    border: 2px solid var(--portal-border);
    border-radius: 10px;
    font-size: 14px;
    font-family: 'Poppins', sans-serif;
    min-width: 160px;
}

/* FORM */
.ahp-form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
}

.ahp-form-group {
    margin-bottom: 20px;
}

.ahp-form-group.full-width {
    grid-column: 1 / -1;
}

.ahp-form-label {
    display: block;
    font-weight: 600;
    color: var(--portal-text);
    margin-bottom: 8px;
    font-size: 14px;
}

.ahp-form-input,
.ahp-form-select,
.ahp-form-textarea {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid var(--portal-border);
    border-radius: 10px;
    font-size: 14px;
    font-family: 'Poppins', sans-serif;
    transition: border-color 0.3s;
}

.ahp-form-input:focus,
.ahp-form-select:focus,
.ahp-form-textarea:focus {
    outline: none;
    border-color: var(--portal-primary);
}

.ahp-form-section {
    background: var(--portal-bg);
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 24px;
}

.ahp-form-section-title {
    margin: 0 0 20px;
    font-size: 16px;
    font-weight: 700;
    color: var(--portal-text);
    display: flex;
    align-items: center;
    gap: 10px;
}

.ahp-form-section-title i {
    color: var(--portal-primary);
}

/* TOAST */
.ahp-toast {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 16px 24px;
    border-radius: 10px;
    color: white;
    font-weight: 600;
    z-index: 9999;
    display: none;
    animation: slideIn 0.3s;
}

.ahp-toast.success { background: var(--portal-success); }
.ahp-toast.error { background: var(--portal-danger); }

@keyframes slideIn {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}

/* LOADING */
.ahp-loading {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 60px;
}

.ahp-spinner {
    width: 48px;
    height: 48px;
    border: 4px solid var(--portal-border);
    border-top-color: var(--portal-primary);
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* RESPONSIVE */
@media (max-width: 1024px) {
    .ahp-portal-sidebar {
        transform: translateX(-100%);
        transition: transform 0.3s;
    }
    .ahp-portal-sidebar.open {
        transform: translateX(0);
    }
    .ahp-portal-main {
        margin-left: 0;
    }
    .ahp-form-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .ahp-portal-main {
        padding: 16px;
    }
    .ahp-stats-grid {
        grid-template-columns: 1fr;
    }
    .ahp-toolbar {
        flex-direction: column;
    }
    .ahp-search-box {
        min-width: 100%;
    }
}
</style>

<div class="ahp-portal">
    <div class="ahp-portal-wrapper">
        <!-- Sidebar -->
        <aside class="ahp-portal-sidebar" id="portalSidebar">
            <div class="ahp-sidebar-header">
                <div class="ahp-sidebar-logo">
                    <i class="fas fa-graduation-cap"></i>
                    <span>Al-Huffaz Portal</span>
                </div>
            </div>

            <nav class="ahp-sidebar-nav">
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
                <a class="ahp-nav-item" data-panel="sponsors">
                    <i class="fas fa-hand-holding-heart"></i>
                    <span><?php _e('Sponsors', 'al-huffaz-portal'); ?></span>
                </a>
                <a class="ahp-nav-item" href="<?php echo admin_url(); ?>" target="_blank">
                    <i class="fas fa-cog"></i>
                    <span><?php _e('WP Admin', 'al-huffaz-portal'); ?></span>
                </a>
                <a class="ahp-nav-item" href="<?php echo wp_logout_url(home_url()); ?>">
                    <i class="fas fa-sign-out-alt"></i>
                    <span><?php _e('Logout', 'al-huffaz-portal'); ?></span>
                </a>
            </nav>

            <div class="ahp-sidebar-footer">
                <div class="ahp-user-info">
                    <div class="ahp-user-avatar">
                        <?php echo strtoupper(substr($current_user->display_name, 0, 1)); ?>
                    </div>
                    <div>
                        <div class="ahp-user-name"><?php echo esc_html($current_user->display_name); ?></div>
                        <div class="ahp-user-role"><?php echo esc_html(ucfirst($current_user->roles[0] ?? 'User')); ?></div>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="ahp-portal-main">

            <!-- Dashboard Panel -->
            <div class="ahp-panel active" id="panel-dashboard">
                <div class="ahp-portal-header">
                    <h1 class="ahp-portal-title"><?php _e('Dashboard', 'al-huffaz-portal'); ?></h1>
                    <div class="ahp-header-actions">
                        <button class="ahp-btn ahp-btn-primary" onclick="showPanel('add-student')">
                            <i class="fas fa-plus"></i> <?php _e('Add Student', 'al-huffaz-portal'); ?>
                        </button>
                    </div>
                </div>

                <div class="ahp-stats-grid">
                    <div class="ahp-stat-card">
                        <div class="ahp-stat-icon blue"><i class="fas fa-user-graduate"></i></div>
                        <div>
                            <div class="ahp-stat-label"><?php _e('Total Students', 'al-huffaz-portal'); ?></div>
                            <div class="ahp-stat-value"><?php echo $total_students; ?></div>
                        </div>
                    </div>
                    <div class="ahp-stat-card">
                        <div class="ahp-stat-icon green"><i class="fas fa-hand-holding-heart"></i></div>
                        <div>
                            <div class="ahp-stat-label"><?php _e('Total Sponsors', 'al-huffaz-portal'); ?></div>
                            <div class="ahp-stat-value"><?php echo $total_sponsors; ?></div>
                        </div>
                    </div>
                    <div class="ahp-stat-card">
                        <div class="ahp-stat-icon purple"><i class="fas fa-quran"></i></div>
                        <div>
                            <div class="ahp-stat-label"><?php _e('Hifz Students', 'al-huffaz-portal'); ?></div>
                            <div class="ahp-stat-value" id="hifzCount">-</div>
                        </div>
                    </div>
                    <div class="ahp-stat-card">
                        <div class="ahp-stat-icon orange"><i class="fas fa-book-reader"></i></div>
                        <div>
                            <div class="ahp-stat-label"><?php _e('Nazra Students', 'al-huffaz-portal'); ?></div>
                            <div class="ahp-stat-value" id="nazraCount">-</div>
                        </div>
                    </div>
                </div>

                <div class="ahp-card">
                    <div class="ahp-card-header">
                        <h3 class="ahp-card-title"><i class="fas fa-clock"></i> <?php _e('Recent Students', 'al-huffaz-portal'); ?></h3>
                        <button class="ahp-btn ahp-btn-secondary ahp-btn-sm" onclick="showPanel('students')">
                            <?php _e('View All', 'al-huffaz-portal'); ?>
                        </button>
                    </div>
                    <div class="ahp-card-body">
                        <div class="ahp-table-wrapper">
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
                                    <?php foreach ($recent_students as $student):
                                        $gr = get_post_meta($student->ID, 'gr_number', true);
                                        $grade = get_post_meta($student->ID, 'grade_level', true);
                                        $category = get_post_meta($student->ID, 'islamic_studies_category', true);
                                        $photo_id = get_post_meta($student->ID, 'student_photo', true);
                                        $photo_url = $photo_id ? wp_get_attachment_image_url($photo_id, 'thumbnail') : '';
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="ahp-student-cell">
                                                <?php if ($photo_url): ?>
                                                    <img src="<?php echo esc_url($photo_url); ?>" class="ahp-student-avatar">
                                                <?php else: ?>
                                                    <div class="ahp-student-avatar-placeholder"><?php echo strtoupper(substr($student->post_title, 0, 1)); ?></div>
                                                <?php endif; ?>
                                                <span><?php echo esc_html($student->post_title); ?></span>
                                            </div>
                                        </td>
                                        <td><?php echo esc_html($gr ?: '-'); ?></td>
                                        <td><span class="ahp-badge ahp-badge-primary"><?php echo esc_html(strtoupper($grade)); ?></span></td>
                                        <td><span class="ahp-badge ahp-badge-success"><?php echo esc_html(ucfirst($category)); ?></span></td>
                                        <td>
                                            <div class="ahp-actions">
                                                <a href="<?php echo get_permalink($student->ID); ?>" class="ahp-btn ahp-btn-secondary ahp-btn-icon" target="_blank" title="View">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <button class="ahp-btn ahp-btn-primary ahp-btn-icon" onclick="editStudent(<?php echo $student->ID; ?>)" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Students List Panel -->
            <div class="ahp-panel" id="panel-students">
                <div class="ahp-portal-header">
                    <h1 class="ahp-portal-title"><?php _e('Students', 'al-huffaz-portal'); ?></h1>
                    <div class="ahp-header-actions">
                        <button class="ahp-btn ahp-btn-primary" onclick="showPanel('add-student')">
                            <i class="fas fa-plus"></i> <?php _e('Add Student', 'al-huffaz-portal'); ?>
                        </button>
                    </div>
                </div>

                <div class="ahp-toolbar">
                    <div class="ahp-search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="studentSearch" placeholder="<?php _e('Search by name or GR number...', 'al-huffaz-portal'); ?>">
                    </div>
                    <select class="ahp-filter-select" id="filterGrade">
                        <option value=""><?php _e('All Grades', 'al-huffaz-portal'); ?></option>
                        <option value="kg1">KG 1</option>
                        <option value="kg2">KG 2</option>
                        <option value="class1">Class 1</option>
                        <option value="class2">Class 2</option>
                        <option value="class3">Class 3</option>
                        <option value="level1">Level 1</option>
                        <option value="level2">Level 2</option>
                        <option value="level3">Level 3</option>
                    </select>
                    <select class="ahp-filter-select" id="filterCategory">
                        <option value=""><?php _e('All Categories', 'al-huffaz-portal'); ?></option>
                        <option value="hifz">Hifz</option>
                        <option value="nazra">Nazra</option>
                        <option value="qaidah">Qaidah</option>
                    </select>
                </div>

                <div class="ahp-card">
                    <div class="ahp-card-body">
                        <div class="ahp-table-wrapper">
                            <table class="ahp-table" id="studentsTable">
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

                <div id="studentsPagination" style="text-align: center; padding: 20px;"></div>
            </div>

            <!-- Add/Edit Student Panel -->
            <div class="ahp-panel" id="panel-add-student">
                <div class="ahp-portal-header">
                    <h1 class="ahp-portal-title" id="formTitle"><?php _e('Add New Student', 'al-huffaz-portal'); ?></h1>
                    <div class="ahp-header-actions">
                        <button class="ahp-btn ahp-btn-secondary" onclick="showPanel('students')">
                            <i class="fas fa-arrow-left"></i> <?php _e('Back to List', 'al-huffaz-portal'); ?>
                        </button>
                    </div>
                </div>

                <form id="studentForm" class="ahp-card">
                    <input type="hidden" name="student_id" id="studentId" value="0">
                    <input type="hidden" name="action" value="alhuffaz_save_student">
                    <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('alhuffaz_student_nonce'); ?>">

                    <div class="ahp-card-body">
                        <!-- Basic Information -->
                        <div class="ahp-form-section">
                            <h4 class="ahp-form-section-title"><i class="fas fa-user"></i> <?php _e('Basic Information', 'al-huffaz-portal'); ?></h4>
                            <div class="ahp-form-grid">
                                <div class="ahp-form-group">
                                    <label class="ahp-form-label"><?php _e('Full Name', 'al-huffaz-portal'); ?> *</label>
                                    <input type="text" name="student_name" class="ahp-form-input" required>
                                </div>
                                <div class="ahp-form-group">
                                    <label class="ahp-form-label"><?php _e('GR Number', 'al-huffaz-portal'); ?></label>
                                    <input type="text" name="gr_number" class="ahp-form-input">
                                </div>
                                <div class="ahp-form-group">
                                    <label class="ahp-form-label"><?php _e('Roll Number', 'al-huffaz-portal'); ?></label>
                                    <input type="text" name="roll_number" class="ahp-form-input">
                                </div>
                                <div class="ahp-form-group">
                                    <label class="ahp-form-label"><?php _e('Gender', 'al-huffaz-portal'); ?></label>
                                    <select name="gender" class="ahp-form-select">
                                        <option value="">Select Gender</option>
                                        <option value="male">Male</option>
                                        <option value="female">Female</option>
                                    </select>
                                </div>
                                <div class="ahp-form-group">
                                    <label class="ahp-form-label"><?php _e('Date of Birth', 'al-huffaz-portal'); ?></label>
                                    <input type="date" name="date_of_birth" class="ahp-form-input">
                                </div>
                                <div class="ahp-form-group">
                                    <label class="ahp-form-label"><?php _e('Admission Date', 'al-huffaz-portal'); ?></label>
                                    <input type="date" name="admission_date" class="ahp-form-input">
                                </div>
                            </div>
                        </div>

                        <!-- Academic Information -->
                        <div class="ahp-form-section">
                            <h4 class="ahp-form-section-title"><i class="fas fa-graduation-cap"></i> <?php _e('Academic Information', 'al-huffaz-portal'); ?></h4>
                            <div class="ahp-form-grid">
                                <div class="ahp-form-group">
                                    <label class="ahp-form-label"><?php _e('Grade Level', 'al-huffaz-portal'); ?></label>
                                    <select name="grade_level" class="ahp-form-select">
                                        <option value="">Select Grade</option>
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
                                </div>
                                <div class="ahp-form-group">
                                    <label class="ahp-form-label"><?php _e('Islamic Studies Category', 'al-huffaz-portal'); ?></label>
                                    <select name="islamic_studies_category" class="ahp-form-select">
                                        <option value="">Select Category</option>
                                        <option value="hifz">Hifz</option>
                                        <option value="nazra">Nazra</option>
                                        <option value="qaidah">Qaidah</option>
                                    </select>
                                </div>
                                <div class="ahp-form-group">
                                    <label class="ahp-form-label"><?php _e('Academic Year', 'al-huffaz-portal'); ?></label>
                                    <select name="academic_year" class="ahp-form-select">
                                        <option value="">Select Year</option>
                                        <?php for ($y = date('Y') + 1; $y >= 2020; $y--): ?>
                                        <option value="<?php echo $y . '-' . ($y + 1); ?>"><?php echo $y . '-' . ($y + 1); ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="ahp-form-group">
                                    <label class="ahp-form-label"><?php _e('Academic Term', 'al-huffaz-portal'); ?></label>
                                    <select name="academic_term" class="ahp-form-select">
                                        <option value="">Select Term</option>
                                        <option value="mid">Mid Term</option>
                                        <option value="annual">Annual</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Family Information -->
                        <div class="ahp-form-section">
                            <h4 class="ahp-form-section-title"><i class="fas fa-users"></i> <?php _e('Family Information', 'al-huffaz-portal'); ?></h4>
                            <div class="ahp-form-grid">
                                <div class="ahp-form-group">
                                    <label class="ahp-form-label"><?php _e('Father Name', 'al-huffaz-portal'); ?></label>
                                    <input type="text" name="father_name" class="ahp-form-input">
                                </div>
                                <div class="ahp-form-group">
                                    <label class="ahp-form-label"><?php _e('Father CNIC', 'al-huffaz-portal'); ?></label>
                                    <input type="text" name="father_cnic" class="ahp-form-input" placeholder="12345-1234567-1">
                                </div>
                                <div class="ahp-form-group">
                                    <label class="ahp-form-label"><?php _e('Guardian Name', 'al-huffaz-portal'); ?></label>
                                    <input type="text" name="guardian_name" class="ahp-form-input">
                                </div>
                                <div class="ahp-form-group">
                                    <label class="ahp-form-label"><?php _e('Guardian Phone', 'al-huffaz-portal'); ?></label>
                                    <input type="text" name="guardian_phone" class="ahp-form-input">
                                </div>
                                <div class="ahp-form-group">
                                    <label class="ahp-form-label"><?php _e('Guardian WhatsApp', 'al-huffaz-portal'); ?></label>
                                    <input type="text" name="guardian_whatsapp" class="ahp-form-input">
                                </div>
                                <div class="ahp-form-group">
                                    <label class="ahp-form-label"><?php _e('Relationship to Student', 'al-huffaz-portal'); ?></label>
                                    <select name="relationship_to_student" class="ahp-form-select">
                                        <option value="">Select Relationship</option>
                                        <option value="father">Father</option>
                                        <option value="mother">Mother</option>
                                        <option value="uncle">Uncle</option>
                                        <option value="aunt">Aunt</option>
                                        <option value="grandfather">Grandfather</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Address -->
                        <div class="ahp-form-section">
                            <h4 class="ahp-form-section-title"><i class="fas fa-map-marker-alt"></i> <?php _e('Address', 'al-huffaz-portal'); ?></h4>
                            <div class="ahp-form-grid">
                                <div class="ahp-form-group full-width">
                                    <label class="ahp-form-label"><?php _e('Permanent Address', 'al-huffaz-portal'); ?></label>
                                    <textarea name="permanent_address" class="ahp-form-textarea" rows="2"></textarea>
                                </div>
                                <div class="ahp-form-group full-width">
                                    <label class="ahp-form-label"><?php _e('Current Address', 'al-huffaz-portal'); ?></label>
                                    <textarea name="current_address" class="ahp-form-textarea" rows="2"></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Financial Information -->
                        <div class="ahp-form-section">
                            <h4 class="ahp-form-section-title"><i class="fas fa-dollar-sign"></i> <?php _e('Financial Information', 'al-huffaz-portal'); ?></h4>
                            <div class="ahp-form-grid">
                                <div class="ahp-form-group">
                                    <label class="ahp-form-label"><?php _e('Monthly Tuition Fee (PKR)', 'al-huffaz-portal'); ?></label>
                                    <input type="number" name="monthly_tuition_fee" class="ahp-form-input" min="0">
                                </div>
                                <div class="ahp-form-group">
                                    <label class="ahp-form-label"><?php _e('Admission Fee (PKR)', 'al-huffaz-portal'); ?></label>
                                    <input type="number" name="admission_fee" class="ahp-form-input" min="0">
                                </div>
                                <div class="ahp-form-group">
                                    <label class="ahp-form-label"><?php _e('Zakat Eligible', 'al-huffaz-portal'); ?></label>
                                    <select name="zakat_eligible" class="ahp-form-select">
                                        <option value="">Select</option>
                                        <option value="yes">Yes</option>
                                        <option value="no">No</option>
                                    </select>
                                </div>
                                <div class="ahp-form-group">
                                    <label class="ahp-form-label"><?php _e('Donation Eligible', 'al-huffaz-portal'); ?></label>
                                    <select name="donation_eligible" class="ahp-form-select">
                                        <option value="">Select</option>
                                        <option value="yes">Yes</option>
                                        <option value="no">No</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Health Information -->
                        <div class="ahp-form-section">
                            <h4 class="ahp-form-section-title"><i class="fas fa-heartbeat"></i> <?php _e('Health Information', 'al-huffaz-portal'); ?></h4>
                            <div class="ahp-form-grid">
                                <div class="ahp-form-group">
                                    <label class="ahp-form-label"><?php _e('Blood Group', 'al-huffaz-portal'); ?></label>
                                    <select name="blood_group" class="ahp-form-select">
                                        <option value="">Select</option>
                                        <option value="A+">A+</option>
                                        <option value="A-">A-</option>
                                        <option value="B+">B+</option>
                                        <option value="B-">B-</option>
                                        <option value="AB+">AB+</option>
                                        <option value="AB-">AB-</option>
                                        <option value="O+">O+</option>
                                        <option value="O-">O-</option>
                                    </select>
                                </div>
                                <div class="ahp-form-group">
                                    <label class="ahp-form-label"><?php _e('Allergies', 'al-huffaz-portal'); ?></label>
                                    <input type="text" name="allergies" class="ahp-form-input" placeholder="None">
                                </div>
                                <div class="ahp-form-group full-width">
                                    <label class="ahp-form-label"><?php _e('Medical Conditions', 'al-huffaz-portal'); ?></label>
                                    <textarea name="medical_conditions" class="ahp-form-textarea" rows="2" placeholder="None"></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Submit -->
                        <div style="display: flex; gap: 16px; justify-content: flex-end; padding-top: 24px; border-top: 1px solid var(--portal-border);">
                            <button type="button" class="ahp-btn ahp-btn-secondary" onclick="resetForm()">
                                <i class="fas fa-undo"></i> <?php _e('Reset', 'al-huffaz-portal'); ?>
                            </button>
                            <button type="submit" class="ahp-btn ahp-btn-success" id="submitBtn">
                                <i class="fas fa-save"></i> <?php _e('Save Student', 'al-huffaz-portal'); ?>
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Sponsors Panel -->
            <div class="ahp-panel" id="panel-sponsors">
                <div class="ahp-portal-header">
                    <h1 class="ahp-portal-title"><?php _e('Sponsors', 'al-huffaz-portal'); ?></h1>
                </div>
                <div class="ahp-card">
                    <div class="ahp-card-body">
                        <p style="text-align: center; padding: 40px; color: var(--portal-text-muted);">
                            <i class="fas fa-hand-holding-heart" style="font-size: 48px; margin-bottom: 16px; display: block;"></i>
                            <?php _e('Sponsors management coming soon...', 'al-huffaz-portal'); ?>
                        </p>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <!-- Toast Notification -->
    <div class="ahp-toast" id="toast"></div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Navigation
    window.showPanel = function(panel) {
        document.querySelectorAll('.ahp-panel').forEach(p => p.classList.remove('active'));
        document.querySelectorAll('.ahp-nav-item').forEach(n => n.classList.remove('active'));

        document.getElementById('panel-' + panel).classList.add('active');
        document.querySelector('[data-panel="' + panel + '"]')?.classList.add('active');

        if (panel === 'students') {
            loadStudents();
        }

        if (panel === 'add-student') {
            document.getElementById('formTitle').textContent = '<?php _e('Add New Student', 'al-huffaz-portal'); ?>';
            document.getElementById('studentId').value = 0;
        }
    };

    document.querySelectorAll('.ahp-nav-item[data-panel]').forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            showPanel(this.dataset.panel);
        });
    });

    // Load Students
    let currentPage = 1;
    window.loadStudents = function(page = 1) {
        currentPage = page;
        const search = document.getElementById('studentSearch').value;
        const grade = document.getElementById('filterGrade').value;
        const category = document.getElementById('filterCategory').value;

        document.getElementById('studentsTableBody').innerHTML = '<tr><td colspan="6" class="ahp-loading"><div class="ahp-spinner"></div></td></tr>';

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'alhuffaz_get_students',
                nonce: '<?php echo wp_create_nonce('alhuffaz_student_nonce'); ?>',
                page: page,
                search: search,
                grade: grade,
                category: category,
                per_page: 20
            })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                renderStudents(data.data.students);
                renderPagination(data.data.total_pages, page);
            }
        });
    };

    function renderStudents(students) {
        const tbody = document.getElementById('studentsTableBody');
        if (students.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:40px;">No students found</td></tr>';
            return;
        }

        tbody.innerHTML = students.map(s => `
            <tr>
                <td>
                    <div class="ahp-student-cell">
                        ${s.photo ? `<img src="${s.photo}" class="ahp-student-avatar">` : `<div class="ahp-student-avatar-placeholder">${s.name.charAt(0).toUpperCase()}</div>`}
                        <span>${s.name}</span>
                    </div>
                </td>
                <td>${s.gr_number || '-'}</td>
                <td><span class="ahp-badge ahp-badge-primary">${(s.grade_level || '-').toUpperCase()}</span></td>
                <td><span class="ahp-badge ahp-badge-success">${s.islamic_studies_category ? s.islamic_studies_category.charAt(0).toUpperCase() + s.islamic_studies_category.slice(1) : '-'}</span></td>
                <td>${s.father_name || '-'}</td>
                <td>
                    <div class="ahp-actions">
                        <a href="${s.permalink}" class="ahp-btn ahp-btn-secondary ahp-btn-icon" target="_blank" title="View"><i class="fas fa-eye"></i></a>
                        <button class="ahp-btn ahp-btn-primary ahp-btn-icon" onclick="editStudent(${s.id})" title="Edit"><i class="fas fa-edit"></i></button>
                        <button class="ahp-btn ahp-btn-danger ahp-btn-icon" onclick="deleteStudent(${s.id})" title="Delete"><i class="fas fa-trash"></i></button>
                    </div>
                </td>
            </tr>
        `).join('');
    }

    function renderPagination(totalPages, currentPage) {
        const container = document.getElementById('studentsPagination');
        if (totalPages <= 1) {
            container.innerHTML = '';
            return;
        }

        let html = '';
        for (let i = 1; i <= totalPages; i++) {
            html += `<button class="ahp-btn ${i === currentPage ? 'ahp-btn-primary' : 'ahp-btn-secondary'} ahp-btn-sm" onclick="loadStudents(${i})" style="margin: 0 4px;">${i}</button>`;
        }
        container.innerHTML = html;
    }

    // Search and Filter
    let searchTimeout;
    document.getElementById('studentSearch').addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => loadStudents(1), 300);
    });

    document.getElementById('filterGrade').addEventListener('change', () => loadStudents(1));
    document.getElementById('filterCategory').addEventListener('change', () => loadStudents(1));

    // Edit Student
    window.editStudent = function(id) {
        document.getElementById('formTitle').textContent = '<?php _e('Edit Student', 'al-huffaz-portal'); ?>';
        document.getElementById('studentId').value = id;
        showPanel('add-student');

        // Load student data
        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'alhuffaz_get_student',
                nonce: '<?php echo wp_create_nonce('alhuffaz_student_nonce'); ?>',
                student_id: id
            })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                populateForm(data.data);
            }
        });
    };

    function populateForm(student) {
        const form = document.getElementById('studentForm');
        form.querySelector('[name="student_name"]').value = student.name || '';
        form.querySelector('[name="gr_number"]').value = student.gr_number || '';
        form.querySelector('[name="roll_number"]').value = student.roll_number || '';
        form.querySelector('[name="gender"]').value = student.gender || '';
        form.querySelector('[name="date_of_birth"]').value = student.date_of_birth || '';
        form.querySelector('[name="admission_date"]').value = student.admission_date || '';
        form.querySelector('[name="grade_level"]').value = student.grade_level || '';
        form.querySelector('[name="islamic_studies_category"]').value = student.islamic_studies_category || '';
        form.querySelector('[name="academic_year"]').value = student.academic_year || '';
        form.querySelector('[name="academic_term"]').value = student.academic_term || '';
        form.querySelector('[name="father_name"]').value = student.father_name || '';
        form.querySelector('[name="father_cnic"]').value = student.father_cnic || '';
        form.querySelector('[name="guardian_name"]').value = student.guardian_name || '';
        form.querySelector('[name="guardian_phone"]').value = student.guardian_phone || '';
        form.querySelector('[name="guardian_whatsapp"]').value = student.guardian_whatsapp || '';
        form.querySelector('[name="relationship_to_student"]').value = student.relationship_to_student || '';
        form.querySelector('[name="permanent_address"]').value = student.permanent_address || '';
        form.querySelector('[name="current_address"]').value = student.current_address || '';
        form.querySelector('[name="monthly_tuition_fee"]').value = student.monthly_tuition_fee || '';
        form.querySelector('[name="admission_fee"]').value = student.admission_fee || '';
        form.querySelector('[name="zakat_eligible"]').value = student.zakat_eligible || '';
        form.querySelector('[name="donation_eligible"]').value = student.donation_eligible || '';
        form.querySelector('[name="blood_group"]').value = student.blood_group || '';
        form.querySelector('[name="allergies"]').value = student.allergies || '';
        form.querySelector('[name="medical_conditions"]').value = student.medical_conditions || '';
    }

    window.resetForm = function() {
        document.getElementById('studentForm').reset();
        document.getElementById('studentId').value = 0;
        document.getElementById('formTitle').textContent = '<?php _e('Add New Student', 'al-huffaz-portal'); ?>';
    };

    // Delete Student
    window.deleteStudent = function(id) {
        if (!confirm('<?php _e('Are you sure you want to delete this student?', 'al-huffaz-portal'); ?>')) {
            return;
        }

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'alhuffaz_delete_student',
                nonce: '<?php echo wp_create_nonce('alhuffaz_student_nonce'); ?>',
                student_id: id
            })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showToast('Student deleted successfully', 'success');
                loadStudents(currentPage);
            } else {
                showToast(data.data.message || 'Error deleting student', 'error');
            }
        });
    };

    // Save Student
    document.getElementById('studentForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const btn = document.getElementById('submitBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

        const formData = new FormData(this);

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save"></i> <?php _e('Save Student', 'al-huffaz-portal'); ?>';

            if (data.success) {
                showToast(data.data.message || 'Student saved successfully', 'success');
                resetForm();
                showPanel('students');
            } else {
                showToast(data.data.message || 'Error saving student', 'error');
            }
        });
    });

    // Toast
    window.showToast = function(message, type) {
        const toast = document.getElementById('toast');
        toast.textContent = message;
        toast.className = 'ahp-toast ' + type;
        toast.style.display = 'block';
        setTimeout(() => { toast.style.display = 'none'; }, 3000);
    };

    // Mobile sidebar toggle
    document.getElementById('toggleSidebar')?.addEventListener('click', function() {
        document.getElementById('portalSidebar').classList.toggle('open');
    });
});
</script>
