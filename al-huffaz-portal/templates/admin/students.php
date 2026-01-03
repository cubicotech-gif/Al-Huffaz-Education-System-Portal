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

    <!-- Students Table -->
    <div class="alhuffaz-card">
        <?php if (empty($students)): ?>
            <div class="alhuffaz-empty">
                <span class="dashicons dashicons-groups"></span>
                <h3 class="alhuffaz-empty-title"><?php _e('No students found', 'al-huffaz-portal'); ?></h3>
                <p class="alhuffaz-empty-text"><?php _e('Try adjusting your search or filters, or add a new student.', 'al-huffaz-portal'); ?></p>
                <a href="<?php echo admin_url('admin.php?page=alhuffaz-add-student'); ?>" class="alhuffaz-btn alhuffaz-btn-primary">
                    <?php _e('Add Student', 'al-huffaz-portal'); ?>
                </a>
            </div>
        <?php else: ?>
            <div class="alhuffaz-table-wrapper">
                <table class="alhuffaz-table">
                    <thead>
                        <tr>
                            <th><?php _e('Student', 'al-huffaz-portal'); ?></th>
                            <th><?php _e('GR Number', 'al-huffaz-portal'); ?></th>
                            <th><?php _e('Grade', 'al-huffaz-portal'); ?></th>
                            <th><?php _e('Category', 'al-huffaz-portal'); ?></th>
                            <th><?php _e('Father', 'al-huffaz-portal'); ?></th>
                            <th><?php _e('Monthly Fee', 'al-huffaz-portal'); ?></th>
                            <th><?php _e('Status', 'al-huffaz-portal'); ?></th>
                            <th><?php _e('Actions', 'al-huffaz-portal'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                            <tr data-student-id="<?php echo esc_attr($student['id']); ?>">
                                <td>
                                    <div class="alhuffaz-student-cell">
                                        <img src="<?php echo esc_url($student['photo']); ?>" alt="" class="alhuffaz-student-avatar">
                                        <div class="alhuffaz-student-info">
                                            <span class="alhuffaz-student-name"><?php echo esc_html($student['name']); ?></span>
                                            <span class="alhuffaz-student-meta"><?php echo ucfirst($student['gender']); ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo esc_html($student['gr_number'] ?: '-'); ?></td>
                                <td><?php echo esc_html($student['grade_label']); ?></td>
                                <td><?php echo esc_html($student['islamic_category']); ?></td>
                                <td><?php echo esc_html($student['father_name'] ?: '-'); ?></td>
                                <td><?php echo esc_html($student['monthly_fee']); ?></td>
                                <td>
                                    <?php if ($student['is_sponsored']): ?>
                                        <span class="alhuffaz-badge badge-success"><?php _e('Sponsored', 'al-huffaz-portal'); ?></span>
                                    <?php else: ?>
                                        <span class="alhuffaz-badge badge-secondary"><?php _e('Available', 'al-huffaz-portal'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=alhuffaz-add-student&id=' . $student['id']); ?>" class="alhuffaz-btn alhuffaz-btn-sm alhuffaz-btn-secondary">
                                        <?php _e('Edit', 'al-huffaz-portal'); ?>
                                    </a>
                                    <button class="alhuffaz-btn alhuffaz-btn-sm alhuffaz-btn-danger alhuffaz-delete-student" data-id="<?php echo esc_attr($student['id']); ?>">
                                        <?php _e('Delete', 'al-huffaz-portal'); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
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
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
