<?php
/**
 * Front-end Admin Dashboard Template
 * Al-Huffaz Education System Portal
 *
 * Standalone dashboard widget for front-end
 */

defined('ABSPATH') || exit;

$current_user = wp_get_current_user();

// Get stats
$total_students = wp_count_posts('student')->publish;
$total_sponsors = wp_count_posts('alhuffaz_sponsor')->publish;

// Get category counts
$hifz_count = count(get_posts(array(
    'post_type' => 'student',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'meta_query' => array(
        array('key' => 'islamic_studies_category', 'value' => 'hifz')
    ),
    'fields' => 'ids'
)));

$nazra_count = count(get_posts(array(
    'post_type' => 'student',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'meta_query' => array(
        array('key' => 'islamic_studies_category', 'value' => 'nazra')
    ),
    'fields' => 'ids'
)));

// Get recent students
$recent_students = get_posts(array(
    'post_type' => 'student',
    'post_status' => 'publish',
    'posts_per_page' => 5,
    'orderby' => 'date',
    'order' => 'DESC'
));
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

<style>
.ahp-dashboard {
    font-family: 'Poppins', sans-serif;
    padding: 24px;
    background: #f8fafc;
    border-radius: 16px;
}

.ahp-dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}

.ahp-dashboard-title {
    margin: 0;
    font-size: 24px;
    font-weight: 700;
    color: #1e293b;
}

.ahp-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 24px;
}

.ahp-stat-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    display: flex;
    align-items: center;
    gap: 16px;
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

.ahp-stat-label {
    font-size: 13px;
    color: #64748b;
    margin-bottom: 4px;
}

.ahp-stat-value {
    font-size: 28px;
    font-weight: 800;
    color: #1e293b;
}

.ahp-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    overflow: hidden;
}

.ahp-card-header {
    padding: 16px 20px;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.ahp-card-title {
    margin: 0;
    font-size: 16px;
    font-weight: 700;
    color: #1e293b;
}

.ahp-card-body {
    padding: 0;
}

.ahp-table {
    width: 100%;
    border-collapse: collapse;
}

.ahp-table th,
.ahp-table td {
    padding: 12px 16px;
    text-align: left;
    border-bottom: 1px solid #e2e8f0;
}

.ahp-table thead th {
    background: #f8fafc;
    font-weight: 600;
    color: #1e293b;
    font-size: 12px;
    text-transform: uppercase;
}

.ahp-table tbody tr:hover {
    background: #f8fafc;
}

.ahp-student-cell {
    display: flex;
    align-items: center;
    gap: 10px;
}

.ahp-student-avatar-placeholder {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: #0080ff;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 13px;
}

.ahp-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 16px;
    font-size: 11px;
    font-weight: 600;
}

.ahp-badge-primary { background: #dbeafe; color: #1e40af; }
.ahp-badge-success { background: #d1fae5; color: #065f46; }

.ahp-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 13px;
    text-decoration: none;
    cursor: pointer;
    border: none;
    font-family: 'Poppins', sans-serif;
}

.ahp-btn-primary {
    background: linear-gradient(135deg, #0080ff, #004d99);
    color: white;
}

.ahp-btn-secondary {
    background: #f8fafc;
    color: #1e293b;
    border: 1px solid #e2e8f0;
}

.ahp-btn-icon {
    width: 32px;
    height: 32px;
    padding: 0;
    justify-content: center;
}

.ahp-actions {
    display: flex;
    gap: 6px;
}

@media (max-width: 768px) {
    .ahp-stats-grid {
        grid-template-columns: 1fr 1fr;
    }
    .ahp-dashboard-header {
        flex-direction: column;
        gap: 16px;
        align-items: flex-start;
    }
}
</style>

<div class="ahp-dashboard">
    <div class="ahp-dashboard-header">
        <h2 class="ahp-dashboard-title"><i class="fas fa-home"></i> <?php _e('Dashboard', 'al-huffaz-portal'); ?></h2>
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
                <div class="ahp-stat-value"><?php echo $hifz_count; ?></div>
            </div>
        </div>
        <div class="ahp-stat-card">
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
        </div>
        <div class="ahp-card-body">
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
                    <tr>
                        <td colspan="5" style="text-align:center;padding:40px;color:#64748b;">
                            <?php _e('No students found.', 'al-huffaz-portal'); ?>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($recent_students as $student):
                        $gr = get_post_meta($student->ID, 'gr_number', true);
                        $grade = get_post_meta($student->ID, 'grade_level', true);
                        $category = get_post_meta($student->ID, 'islamic_studies_category', true);
                    ?>
                    <tr>
                        <td>
                            <div class="ahp-student-cell">
                                <div class="ahp-student-avatar-placeholder"><?php echo strtoupper(substr($student->post_title, 0, 1)); ?></div>
                                <span><?php echo esc_html($student->post_title); ?></span>
                            </div>
                        </td>
                        <td><?php echo esc_html($gr ?: '-'); ?></td>
                        <td><span class="ahp-badge ahp-badge-primary"><?php echo esc_html(strtoupper($grade ?: '-')); ?></span></td>
                        <td><span class="ahp-badge ahp-badge-success"><?php echo esc_html(ucfirst($category ?: '-')); ?></span></td>
                        <td>
                            <div class="ahp-actions">
                                <a href="<?php echo get_permalink($student->ID); ?>" class="ahp-btn ahp-btn-secondary ahp-btn-icon" target="_blank" title="View">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
