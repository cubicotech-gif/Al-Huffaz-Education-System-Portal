<?php
/**
 * Student Form Template
 *
 * @package AlHuffaz
 */

use AlHuffaz\Admin\Student_Manager;
use AlHuffaz\Admin\Settings;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$student_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$student = $student_id ? Student_Manager::get_student($student_id) : array();
$is_edit = !empty($student);

$grades = Settings::get('grade_levels', Settings::get_default_grades());
$categories = Settings::get('islamic_categories', Settings::get_default_categories());
?>

<div class="alhuffaz-wrap">
    <div class="alhuffaz-header">
        <h1>
            <span class="dashicons dashicons-<?php echo $is_edit ? 'edit' : 'plus-alt'; ?>"></span>
            <?php echo $is_edit ? __('Edit Student', 'al-huffaz-portal') : __('Add New Student', 'al-huffaz-portal'); ?>
        </h1>
        <div class="alhuffaz-header-actions">
            <a href="<?php echo admin_url('admin.php?page=alhuffaz-students'); ?>" class="alhuffaz-btn alhuffaz-btn-secondary">
                <span class="dashicons dashicons-arrow-left-alt"></span>
                <?php _e('Back to Students', 'al-huffaz-portal'); ?>
            </a>
        </div>
    </div>

    <form id="alhuffaz-student-form" class="alhuffaz-card">
        <input type="hidden" name="student_id" value="<?php echo esc_attr($student_id); ?>">

        <!-- Basic Information -->
        <div class="alhuffaz-form-section">
            <h3 class="alhuffaz-form-section-title">
                <span class="dashicons dashicons-admin-users"></span>
                <?php _e('Basic Information', 'al-huffaz-portal'); ?>
            </h3>

            <div class="alhuffaz-form-row">
                <div class="alhuffaz-form-group">
                    <label class="alhuffaz-form-label"><?php _e('Student Name', 'al-huffaz-portal'); ?> <span class="required">*</span></label>
                    <input type="text" name="student_name" class="alhuffaz-form-input" required value="<?php echo esc_attr($student['student_name'] ?? ''); ?>">
                </div>

                <div class="alhuffaz-form-group">
                    <label class="alhuffaz-form-label"><?php _e('GR Number', 'al-huffaz-portal'); ?></label>
                    <input type="text" name="gr_number" class="alhuffaz-form-input" value="<?php echo esc_attr($student['gr_number'] ?? ''); ?>">
                </div>
            </div>

            <div class="alhuffaz-form-row">
                <div class="alhuffaz-form-group">
                    <label class="alhuffaz-form-label"><?php _e('Roll Number', 'al-huffaz-portal'); ?></label>
                    <input type="text" name="roll_number" class="alhuffaz-form-input" value="<?php echo esc_attr($student['roll_number'] ?? ''); ?>">
                </div>

                <div class="alhuffaz-form-group">
                    <label class="alhuffaz-form-label"><?php _e('Gender', 'al-huffaz-portal'); ?> <span class="required">*</span></label>
                    <select name="gender" class="alhuffaz-form-select" required>
                        <option value=""><?php _e('Select Gender', 'al-huffaz-portal'); ?></option>
                        <option value="male" <?php selected($student['gender'] ?? '', 'male'); ?>><?php _e('Male', 'al-huffaz-portal'); ?></option>
                        <option value="female" <?php selected($student['gender'] ?? '', 'female'); ?>><?php _e('Female', 'al-huffaz-portal'); ?></option>
                    </select>
                </div>
            </div>

            <div class="alhuffaz-form-row">
                <div class="alhuffaz-form-group">
                    <label class="alhuffaz-form-label"><?php _e('Date of Birth', 'al-huffaz-portal'); ?></label>
                    <input type="text" name="date_of_birth" class="alhuffaz-form-input alhuffaz-datepicker" value="<?php echo esc_attr($student['date_of_birth'] ?? ''); ?>">
                </div>

                <div class="alhuffaz-form-group">
                    <label class="alhuffaz-form-label"><?php _e('Admission Date', 'al-huffaz-portal'); ?></label>
                    <input type="text" name="admission_date" class="alhuffaz-form-input alhuffaz-datepicker" value="<?php echo esc_attr($student['admission_date'] ?? ''); ?>">
                </div>
            </div>

            <div class="alhuffaz-form-row">
                <div class="alhuffaz-form-group">
                    <label class="alhuffaz-form-label"><?php _e('Grade Level', 'al-huffaz-portal'); ?> <span class="required">*</span></label>
                    <select name="grade_level" class="alhuffaz-form-select" required>
                        <option value=""><?php _e('Select Grade', 'al-huffaz-portal'); ?></option>
                        <?php foreach ($grades as $key => $label): ?>
                            <option value="<?php echo esc_attr($key); ?>" <?php selected($student['grade_level'] ?? '', $key); ?>><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="alhuffaz-form-group">
                    <label class="alhuffaz-form-label"><?php _e('Islamic Studies Category', 'al-huffaz-portal'); ?></label>
                    <select name="islamic_category" class="alhuffaz-form-select">
                        <option value=""><?php _e('Select Category', 'al-huffaz-portal'); ?></option>
                        <?php foreach ($categories as $key => $label): ?>
                            <option value="<?php echo esc_attr($key); ?>" <?php selected($student['islamic_category'] ?? '', $key); ?>><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="alhuffaz-form-group">
                <label class="alhuffaz-form-label"><?php _e('Student Photo', 'al-huffaz-portal'); ?></label>
                <div class="alhuffaz-photo-upload">
                    <?php
                    $photo_url = '';
                    if (!empty($student['student_photo'])) {
                        $photo_url = wp_get_attachment_url($student['student_photo']);
                    }
                    ?>
                    <img src="<?php echo $photo_url ?: ALHUFFAZ_ASSETS_URL . 'images/student-placeholder.png'; ?>" alt="" class="alhuffaz-photo-preview">
                    <input type="hidden" name="student_photo" value="<?php echo esc_attr($student['student_photo'] ?? ''); ?>">
                    <p><?php _e('Click to upload photo', 'al-huffaz-portal'); ?></p>
                </div>
            </div>
        </div>

        <!-- Family Information -->
        <div class="alhuffaz-form-section">
            <h3 class="alhuffaz-form-section-title">
                <span class="dashicons dashicons-groups"></span>
                <?php _e('Family Information', 'al-huffaz-portal'); ?>
            </h3>

            <div class="alhuffaz-form-row">
                <div class="alhuffaz-form-group">
                    <label class="alhuffaz-form-label"><?php _e('Father Name', 'al-huffaz-portal'); ?></label>
                    <input type="text" name="father_name" class="alhuffaz-form-input" value="<?php echo esc_attr($student['father_name'] ?? ''); ?>">
                </div>

                <div class="alhuffaz-form-group">
                    <label class="alhuffaz-form-label"><?php _e('Father CNIC', 'al-huffaz-portal'); ?></label>
                    <input type="text" name="father_cnic" class="alhuffaz-form-input" placeholder="12345-1234567-1" value="<?php echo esc_attr($student['father_cnic'] ?? ''); ?>">
                </div>
            </div>

            <div class="alhuffaz-form-row">
                <div class="alhuffaz-form-group">
                    <label class="alhuffaz-form-label"><?php _e('Guardian Name', 'al-huffaz-portal'); ?></label>
                    <input type="text" name="guardian_name" class="alhuffaz-form-input" value="<?php echo esc_attr($student['guardian_name'] ?? ''); ?>">
                </div>

                <div class="alhuffaz-form-group">
                    <label class="alhuffaz-form-label"><?php _e('Relationship', 'al-huffaz-portal'); ?></label>
                    <input type="text" name="relationship" class="alhuffaz-form-input" placeholder="<?php _e('e.g., Father, Uncle, etc.', 'al-huffaz-portal'); ?>" value="<?php echo esc_attr($student['relationship'] ?? ''); ?>">
                </div>
            </div>

            <div class="alhuffaz-form-row">
                <div class="alhuffaz-form-group">
                    <label class="alhuffaz-form-label"><?php _e('Guardian Phone', 'al-huffaz-portal'); ?></label>
                    <input type="tel" name="guardian_phone" class="alhuffaz-form-input" value="<?php echo esc_attr($student['guardian_phone'] ?? ''); ?>">
                </div>

                <div class="alhuffaz-form-group">
                    <label class="alhuffaz-form-label"><?php _e('Guardian WhatsApp', 'al-huffaz-portal'); ?></label>
                    <input type="tel" name="guardian_whatsapp" class="alhuffaz-form-input" value="<?php echo esc_attr($student['guardian_whatsapp'] ?? ''); ?>">
                </div>
            </div>

            <div class="alhuffaz-form-group">
                <label class="alhuffaz-form-label"><?php _e('Guardian Email', 'al-huffaz-portal'); ?></label>
                <input type="email" name="guardian_email" class="alhuffaz-form-input" value="<?php echo esc_attr($student['guardian_email'] ?? ''); ?>">
            </div>
        </div>

        <!-- Address -->
        <div class="alhuffaz-form-section">
            <h3 class="alhuffaz-form-section-title">
                <span class="dashicons dashicons-location"></span>
                <?php _e('Address', 'al-huffaz-portal'); ?>
            </h3>

            <div class="alhuffaz-form-group">
                <label class="alhuffaz-form-label"><?php _e('Permanent Address', 'al-huffaz-portal'); ?></label>
                <textarea name="permanent_address" class="alhuffaz-form-textarea" rows="2"><?php echo esc_textarea($student['permanent_address'] ?? ''); ?></textarea>
            </div>

            <div class="alhuffaz-form-group">
                <label class="alhuffaz-form-label"><?php _e('Current Address', 'al-huffaz-portal'); ?></label>
                <textarea name="current_address" class="alhuffaz-form-textarea" rows="2"><?php echo esc_textarea($student['current_address'] ?? ''); ?></textarea>
            </div>
        </div>

        <!-- Fee Information -->
        <div class="alhuffaz-form-section">
            <h3 class="alhuffaz-form-section-title">
                <span class="dashicons dashicons-money-alt"></span>
                <?php _e('Fee Information', 'al-huffaz-portal'); ?>
            </h3>

            <div class="alhuffaz-form-row">
                <div class="alhuffaz-form-group">
                    <label class="alhuffaz-form-label"><?php _e('Monthly Tuition Fee', 'al-huffaz-portal'); ?></label>
                    <input type="number" name="monthly_fee" class="alhuffaz-form-input" value="<?php echo esc_attr($student['monthly_fee'] ?? ''); ?>">
                </div>

                <div class="alhuffaz-form-group">
                    <label class="alhuffaz-form-label"><?php _e('Admission Fee', 'al-huffaz-portal'); ?></label>
                    <input type="number" name="admission_fee" class="alhuffaz-form-input" value="<?php echo esc_attr($student['admission_fee'] ?? ''); ?>">
                </div>
            </div>

            <div class="alhuffaz-form-row">
                <div class="alhuffaz-form-group">
                    <label class="alhuffaz-form-label">
                        <input type="checkbox" name="zakat_eligible" value="yes" <?php checked($student['zakat_eligible'] ?? '', 'yes'); ?>>
                        <?php _e('Zakat Eligible', 'al-huffaz-portal'); ?>
                    </label>
                </div>

                <div class="alhuffaz-form-group">
                    <label class="alhuffaz-form-label">
                        <input type="checkbox" name="donation_eligible" value="yes" <?php checked($student['donation_eligible'] ?? '', 'yes'); ?>>
                        <?php _e('Donation Eligible', 'al-huffaz-portal'); ?>
                    </label>
                </div>
            </div>
        </div>

        <!-- Submit -->
        <div class="alhuffaz-form-section" style="border-bottom: none; text-align: right;">
            <button type="submit" class="alhuffaz-btn alhuffaz-btn-lg alhuffaz-btn-primary">
                <span class="dashicons dashicons-saved"></span>
                <?php echo $is_edit ? __('Update Student', 'al-huffaz-portal') : __('Add Student', 'al-huffaz-portal'); ?>
            </button>
        </div>
    </form>
</div>
