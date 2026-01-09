<?php
/**
 * Students List Template
 *
 * @package AlHuffaz
 */

use AlHuffaz\Admin\Student_Manager;
use AlHuffaz\Core\Helpers;
use AlHuffaz\Admin\Settings;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$page = isset($_GET['paged']) ? intval($_GET['paged']) : 1;
$search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$grade = isset($_GET['grade']) ? sanitize_text_field($_GET['grade']) : '';
$gender = isset($_GET['gender']) ? sanitize_text_field($_GET['gender']) : '';

$result = Student_Manager::get_students(array(
    'page'     => $page,
    'per_page' => 20,
    'search'   => $search,
    'grade'    => $grade,
    'gender'   => $gender,
));

$students = $result['students'];
$total = $result['total'];
$total_pages = $result['total_pages'];

$grades = Settings::get('grade_levels', Settings::get_default_grades());
?>

<div class="alhuffaz-wrap">
    <div class="alhuffaz-header">
        <h1>
            <span class="dashicons dashicons-groups"></span>
            <?php _e('Students', 'al-huffaz-portal'); ?>
            <span class="alhuffaz-badge badge-secondary"><?php echo number_format($total); ?></span>
        </h1>
        <div class="alhuffaz-header-actions">
            <a href="<?php echo admin_url('admin.php?page=alhuffaz-add-student'); ?>" class="alhuffaz-btn alhuffaz-btn-primary">
                <span class="dashicons dashicons-plus-alt"></span>
                <?php _e('Add Student', 'al-huffaz-portal'); ?>
            </a>
            <a href="<?php echo admin_url('admin.php?page=alhuffaz-import'); ?>" class="alhuffaz-btn alhuffaz-btn-secondary">
                <span class="dashicons dashicons-upload"></span>
                <?php _e('Bulk Import', 'al-huffaz-portal'); ?>
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="alhuffaz-card">
        <form method="get" action="">
            <input type="hidden" name="page" value="alhuffaz-students">

            <div class="alhuffaz-filters">
                <div class="alhuffaz-search">
                    <span class="dashicons dashicons-search"></span>
                    <input type="text" name="s" placeholder="<?php _e('Search students...', 'al-huffaz-portal'); ?>" value="<?php echo esc_attr($search); ?>" class="alhuffaz-form-input">
                </div>

                <select name="grade" class="alhuffaz-form-select alhuffaz-filter-select">
                    <option value=""><?php _e('All Grades', 'al-huffaz-portal'); ?></option>
                    <?php foreach ($grades as $key => $label): ?>
                        <option value="<?php echo esc_attr($key); ?>" <?php selected($grade, $key); ?>><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>

                <select name="gender" class="alhuffaz-form-select alhuffaz-filter-select">
                    <option value=""><?php _e('All Genders', 'al-huffaz-portal'); ?></option>
                    <option value="male" <?php selected($gender, 'male'); ?>><?php _e('Male', 'al-huffaz-portal'); ?></option>
                    <option value="female" <?php selected($gender, 'female'); ?>><?php _e('Female', 'al-huffaz-portal'); ?></option>
                </select>

                <button type="submit" class="alhuffaz-btn alhuffaz-btn-primary">
                    <?php _e('Filter', 'al-huffaz-portal'); ?>
                </button>

                <?php if ($search || $grade || $gender): ?>
                    <a href="<?php echo admin_url('admin.php?page=alhuffaz-students'); ?>" class="alhuffaz-btn alhuffaz-btn-secondary">
                        <?php _e('Clear', 'al-huffaz-portal'); ?>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Students Cards -->
    <?php if (empty($students)): ?>
        <div class="alhuffaz-card">
            <div class="alhuffaz-empty">
                <span class="dashicons dashicons-groups"></span>
                <h3 class="alhuffaz-empty-title"><?php _e('No students found', 'al-huffaz-portal'); ?></h3>
                <p class="alhuffaz-empty-text"><?php _e('Try adjusting your search or filters, or add a new student.', 'al-huffaz-portal'); ?></p>
                <a href="<?php echo admin_url('admin.php?page=alhuffaz-add-student'); ?>" class="alhuffaz-btn alhuffaz-btn-primary">
                    <?php _e('Add Student', 'al-huffaz-portal'); ?>
                </a>
            </div>
        </div>
    <?php else: ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; margin-bottom: 20px;">
            <?php foreach ($students as $student): ?>
                <div class="alhuffaz-card" style="transition: all 0.3s; cursor: pointer;" onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 8px 16px rgba(0,0,0,0.1)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none';" data-student-id="<?php echo esc_attr($student['id']); ?>">
                    <!-- Student Photo -->
                    <div style="position: relative; width: 100%; height: 200px; overflow: hidden; border-radius: 8px 8px 0 0; margin: -20px -20px 15px -20px;">
                        <img src="<?php echo esc_url($student['photo']); ?>" alt="<?php echo esc_attr($student['name']); ?>" style="width: 100%; height: 100%; object-fit: cover;">

                        <!-- Status Badge Overlay -->
                        <div style="position: absolute; top: 10px; right: 10px;">
                            <?php if ($student['is_sponsored']): ?>
                                <span class="alhuffaz-badge badge-success" style="background: rgba(46, 204, 113, 0.95); color: white; padding: 6px 12px; border-radius: 20px; font-size: 11px; font-weight: bold; text-transform: uppercase;">
                                    <span class="dashicons dashicons-yes-alt" style="font-size: 12px; vertical-align: middle;"></span>
                                    <?php _e('Sponsored', 'al-huffaz-portal'); ?>
                                </span>
                            <?php else: ?>
                                <span class="alhuffaz-badge badge-secondary" style="background: rgba(149, 165, 166, 0.95); color: white; padding: 6px 12px; border-radius: 20px; font-size: 11px; font-weight: bold; text-transform: uppercase;">
                                    <?php _e('Available', 'al-huffaz-portal'); ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- GR Number Badge -->
                        <?php if ($student['gr_number']): ?>
                            <div style="position: absolute; bottom: 10px; left: 10px; background: rgba(0, 0, 0, 0.7); color: white; padding: 4px 10px; border-radius: 4px; font-size: 12px;">
                                <strong>GR:</strong> <?php echo esc_html($student['gr_number']); ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Student Info -->
                    <div style="padding: 0 0 10px 0;">
                        <h3 style="margin: 0 0 10px 0; font-size: 18px; font-weight: 600; color: #2c3e50;">
                            <?php echo esc_html($student['name']); ?>
                        </h3>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 12px; font-size: 13px; color: #666;">
                            <div style="display: flex; align-items: center; gap: 6px;">
                                <span class="dashicons dashicons-welcome-learn-more" style="font-size: 16px; color: var(--alhuffaz-primary);"></span>
                                <span><?php echo esc_html($student['grade_label']); ?></span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 6px;">
                                <span class="dashicons dashicons-admin-users" style="font-size: 16px; color: var(--alhuffaz-primary);"></span>
                                <span><?php echo ucfirst($student['gender']); ?></span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 6px; grid-column: 1 / -1;">
                                <span class="dashicons dashicons-book-alt" style="font-size: 16px; color: var(--alhuffaz-primary);"></span>
                                <span><?php echo esc_html($student['islamic_category']); ?></span>
                            </div>
                        </div>

                        <?php if ($student['father_name']): ?>
                            <div style="padding: 10px; background: #f8f9fa; border-radius: 6px; margin-bottom: 12px; font-size: 13px;">
                                <strong style="color: #555;"><?php _e('Father:', 'al-huffaz-portal'); ?></strong>
                                <span style="color: #666;"><?php echo esc_html($student['father_name']); ?></span>
                            </div>
                        <?php endif; ?>

                        <div style="padding: 12px; background: linear-gradient(135deg, var(--alhuffaz-primary) 0%, var(--alhuffaz-secondary) 100%); border-radius: 8px; text-align: center; margin-bottom: 15px;">
                            <div style="font-size: 12px; color: rgba(255,255,255,0.9); margin-bottom: 4px; text-transform: uppercase; font-weight: 500;">
                                <?php _e('Monthly Fee', 'al-huffaz-portal'); ?>
                            </div>
                            <div style="font-size: 22px; font-weight: bold; color: white;">
                                <?php echo esc_html($student['monthly_fee']); ?>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                            <a href="<?php echo get_permalink($student['id']); ?>" class="alhuffaz-btn alhuffaz-btn-sm alhuffaz-btn-primary" target="_blank" style="flex: 1; text-align: center; min-width: 80px;">
                                <span class="dashicons dashicons-visibility" style="font-size: 14px; vertical-align: middle;"></span>
                                <?php _e('View', 'al-huffaz-portal'); ?>
                            </a>
                            <a href="<?php echo admin_url('admin.php?page=alhuffaz-add-student&id=' . $student['id']); ?>" class="alhuffaz-btn alhuffaz-btn-sm alhuffaz-btn-secondary" style="flex: 1; text-align: center; min-width: 80px;">
                                <span class="dashicons dashicons-edit" style="font-size: 14px; vertical-align: middle;"></span>
                                <?php _e('Edit', 'al-huffaz-portal'); ?>
                            </a>
                            <button class="alhuffaz-btn alhuffaz-btn-sm alhuffaz-btn-danger alhuffaz-delete-student" data-id="<?php echo esc_attr($student['id']); ?>" style="padding: 6px 12px;">
                                <span class="dashicons dashicons-trash" style="font-size: 14px;"></span>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="alhuffaz-card">
                <div class="alhuffaz-pagination">
                    <?php if ($page > 1): ?>
                        <a href="<?php echo add_query_arg('paged', $page - 1); ?>" class="alhuffaz-btn alhuffaz-btn-sm alhuffaz-btn-secondary"><?php _e('Previous', 'al-huffaz-portal'); ?></a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="alhuffaz-btn alhuffaz-btn-sm alhuffaz-btn-primary"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="<?php echo add_query_arg('paged', $i); ?>" class="alhuffaz-btn alhuffaz-btn-sm alhuffaz-btn-secondary"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="<?php echo add_query_arg('paged', $page + 1); ?>" class="alhuffaz-btn alhuffaz-btn-sm alhuffaz-btn-secondary"><?php _e('Next', 'al-huffaz-portal'); ?></a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
