<?php
/**
 * AJAX Handler
 *
 * @package AlHuffaz
 */

namespace AlHuffaz\Core;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Ajax_Handler
 */
class Ajax_Handler {

    /**
     * Constructor
     */
    public function __construct() {
        // Admin AJAX actions
        add_action('wp_ajax_alhuffaz_save_student', array($this, 'save_student'));
        add_action('wp_ajax_alhuffaz_delete_student', array($this, 'delete_student'));
        add_action('wp_ajax_alhuffaz_get_student', array($this, 'get_student'));
        add_action('wp_ajax_alhuffaz_get_students', array($this, 'get_students'));
        add_action('wp_ajax_alhuffaz_search_students', array($this, 'search_students'));

        // Enhanced student form handlers
        add_action('wp_ajax_ahp_add_student', array($this, 'ahp_save_student'));
        add_action('wp_ajax_ahp_update_student', array($this, 'ahp_save_student'));

        add_action('wp_ajax_alhuffaz_approve_sponsorship', array($this, 'approve_sponsorship'));
        add_action('wp_ajax_alhuffaz_reject_sponsorship', array($this, 'reject_sponsorship'));
        add_action('wp_ajax_alhuffaz_link_sponsor', array($this, 'link_sponsor'));
        add_action('wp_ajax_alhuffaz_unlink_sponsor', array($this, 'unlink_sponsor'));
        add_action('wp_ajax_alhuffaz_get_sponsorships', array($this, 'get_sponsorships'));
        add_action('wp_ajax_alhuffaz_get_sponsorship_details', array($this, 'get_sponsorship_details'));

        add_action('wp_ajax_alhuffaz_verify_payment', array($this, 'verify_payment'));
        add_action('wp_ajax_alhuffaz_get_payments', array($this, 'get_payments'));

        add_action('wp_ajax_alhuffaz_get_dashboard_stats', array($this, 'get_dashboard_stats'));
        add_action('wp_ajax_alhuffaz_export_data', array($this, 'export_data'));
        add_action('wp_ajax_alhuffaz_bulk_import', array($this, 'bulk_import'));

        add_action('wp_ajax_alhuffaz_upload_image', array($this, 'upload_image'));

        // Public AJAX actions
        add_action('wp_ajax_alhuffaz_register_sponsor', array($this, 'register_sponsor'));
        add_action('wp_ajax_nopriv_alhuffaz_register_sponsor', array($this, 'register_sponsor'));

        add_action('wp_ajax_alhuffaz_submit_sponsorship', array($this, 'submit_sponsorship'));
        add_action('wp_ajax_nopriv_alhuffaz_submit_sponsorship', array($this, 'submit_sponsorship'));

        add_action('wp_ajax_alhuffaz_submit_payment', array($this, 'submit_payment'));
        add_action('wp_ajax_nopriv_alhuffaz_submit_payment', array($this, 'submit_payment'));

        add_action('wp_ajax_alhuffaz_submit_payment_proof', array($this, 'submit_payment_proof'));

        add_action('wp_ajax_alhuffaz_create_sponsorship', array($this, 'create_sponsorship'));
        add_action('wp_ajax_alhuffaz_cancel_sponsorship', array($this, 'cancel_sponsorship'));

        add_action('wp_ajax_alhuffaz_get_student_profile', array($this, 'get_student_profile'));

        add_action('wp_ajax_alhuffaz_get_available_students', array($this, 'get_available_students'));
        add_action('wp_ajax_nopriv_alhuffaz_get_available_students', array($this, 'get_available_students'));

        // Staff management AJAX actions (admin only)
        add_action('wp_ajax_alhuffaz_get_staff_users', array($this, 'get_staff_users'));
        add_action('wp_ajax_alhuffaz_grant_staff_role', array($this, 'grant_staff_role'));
        add_action('wp_ajax_alhuffaz_revoke_staff_role', array($this, 'revoke_staff_role'));
        add_action('wp_ajax_alhuffaz_get_eligible_users', array($this, 'get_eligible_users'));

        // Sponsor user management AJAX actions (admin only)
        add_action('wp_ajax_alhuffaz_get_sponsor_users', array($this, 'get_sponsor_users'));
        add_action('wp_ajax_alhuffaz_get_sponsor_user_details', array($this, 'get_sponsor_user_details'));
        add_action('wp_ajax_alhuffaz_get_sponsor_students', array($this, 'get_sponsor_students'));
        add_action('wp_ajax_alhuffaz_get_sponsor_payments', array($this, 'get_sponsor_payments'));
        add_action('wp_ajax_alhuffaz_approve_sponsor_user', array($this, 'approve_sponsor_user'));
        add_action('wp_ajax_alhuffaz_reject_sponsor_user', array($this, 'reject_sponsor_user'));
        add_action('wp_ajax_alhuffaz_delete_sponsor_user', array($this, 'delete_sponsor_user'));
        add_action('wp_ajax_alhuffaz_send_reengagement_email', array($this, 'send_reengagement_email'));

        // Notifications AJAX actions
        add_action('wp_ajax_alhuffaz_get_notifications', array($this, 'get_notifications'));
        add_action('wp_ajax_alhuffaz_mark_notification_read', array($this, 'mark_notification_read'));
        add_action('wp_ajax_alhuffaz_mark_all_notifications_read', array($this, 'mark_all_notifications_read'));
    }

    /**
     * Verify admin nonce
     */
    private function verify_admin_nonce() {
        $valid = false;

        if (isset($_POST['nonce'])) {
            // Check multiple possible nonces for flexibility
            if (wp_verify_nonce($_POST['nonce'], 'alhuffaz_admin_nonce') ||
                wp_verify_nonce($_POST['nonce'], 'alhuffaz_student_nonce')) {
                $valid = true;
            }
        }

        if (!$valid) {
            wp_send_json_error(array('message' => __('Security check failed.', 'al-huffaz-portal')));
        }

        // Check permissions - allow edit_posts for front-end admin portal
        if (!current_user_can('alhuffaz_manage_students') && !current_user_can('edit_posts') && !current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'al-huffaz-portal')));
        }
    }

    /**
     * Verify public nonce
     */
    private function verify_public_nonce() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'alhuffaz_public_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'al-huffaz-portal')));
        }
    }

    /**
     * Save student
     */
    public function save_student() {
        $this->verify_admin_nonce();

        $student_id = isset($_POST['student_id']) ? intval($_POST['student_id']) : 0;

        // Handle both nested 'data' array and flat POST data (from front-end portal)
        $data = isset($_POST['data']) ? $_POST['data'] : $_POST;

        // Get student name from either format
        $student_name = '';
        if (!empty($data['student_name'])) {
            $student_name = $data['student_name'];
        } elseif (!empty($_POST['student_name'])) {
            $student_name = $_POST['student_name'];
        }

        if (empty($student_name)) {
            wp_send_json_error(array('message' => __('Student name is required.', 'al-huffaz-portal')));
        }

        // Prepare post data
        $post_data = array(
            'post_title'  => sanitize_text_field($student_name),
            'post_type'   => 'student',
            'post_status' => 'publish',
        );

        if ($student_id) {
            $post_data['ID'] = $student_id;
            $result = wp_update_post($post_data);
        } else {
            $result = wp_insert_post($post_data);
        }

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        $student_id = $result;

        // List of all fields to save (without underscore prefix for CPT UI compatibility)
        $text_fields = array(
            'gr_number', 'roll_number', 'gender', 'date_of_birth', 'admission_date',
            'grade_level', 'islamic_studies_category', 'permanent_address', 'current_address',
            'father_name', 'father_cnic', 'father_email',
            'guardian_name', 'guardian_cnic', 'guardian_email', 'guardian_phone', 'guardian_whatsapp',
            'relationship_to_student', 'emergency_contact', 'emergency_whatsapp',
            'academic_year', 'academic_term',
            'blood_group', 'allergies', 'medical_conditions',
            'teacher_overall_comments', 'goal_1', 'goal_2', 'goal_3'
        );

        foreach ($text_fields as $field) {
            if (isset($data[$field])) {
                update_post_meta($student_id, $field, sanitize_text_field($data[$field]));
            }
        }

        // Number fields
        $number_fields = array(
            'monthly_tuition_fee', 'course_fee', 'uniform_fee', 'annual_fee', 'admission_fee',
            'total_school_days', 'present_days'
        );

        foreach ($number_fields as $field) {
            if (isset($data[$field])) {
                update_post_meta($student_id, $field, floatval($data[$field]));
            }
        }

        // Checkbox fields
        $checkbox_fields = array('zakat_eligible', 'donation_eligible');
        foreach ($checkbox_fields as $field) {
            if (isset($data[$field])) {
                $value = ($data[$field] === 'yes' || $data[$field] === '1' || $data[$field] === true) ? 'yes' : 'no';
                update_post_meta($student_id, $field, $value);
            }
        }

        // Behavioral rating fields
        $rating_fields = array(
            'health_rating', 'cleanness_rating', 'completes_homework', 'participates_in_class',
            'works_well_in_groups', 'problem_solving_skills', 'organization_preparedness'
        );
        foreach ($rating_fields as $field) {
            if (isset($data[$field])) {
                update_post_meta($student_id, $field, floatval($data[$field]));
            }
        }

        // Subjects array with nested monthly exams, mid/final semester
        // Check both $data and $_POST for subjects (frontend forms use direct POST)
        $subjects_data = null;
        if (isset($data['subjects']) && is_array($data['subjects'])) {
            $subjects_data = $data['subjects'];
        } elseif (isset($_POST['subjects']) && is_array($_POST['subjects'])) {
            $subjects_data = $_POST['subjects'];
        }

        if ($subjects_data) {
            $subjects = array();

            foreach ($subjects_data as $index => $subject) {
                $subject_data = array(
                    'name' => sanitize_text_field($subject['name'] ?? ''),
                    'strengths' => sanitize_textarea_field($subject['strengths'] ?? ''),
                    'areas_for_improvement' => sanitize_textarea_field($subject['areas_for_improvement'] ?? ''),
                    'teacher_comments' => sanitize_textarea_field($subject['teacher_comments'] ?? ''),
                    'monthly_exams' => array(),
                    'mid_semester' => array(),
                    'final_semester' => array(),
                );

                // Monthly exams
                if (isset($subject['monthly_exams']) && is_array($subject['monthly_exams'])) {
                    foreach ($subject['monthly_exams'] as $monthly) {
                        $oral_total = floatval($monthly['oral_total'] ?? 0);
                        $oral_obtained = floatval($monthly['oral_obtained'] ?? 0);
                        $written_total = floatval($monthly['written_total'] ?? 0);
                        $written_obtained = floatval($monthly['written_obtained'] ?? 0);
                        $overall_total = $oral_total + $written_total;
                        $overall_obtained = $oral_obtained + $written_obtained;
                        $percentage = $overall_total > 0 ? round(($overall_obtained / $overall_total) * 100, 1) : 0;
                        $grade = $this->calculate_grade($percentage);

                        $subject_data['monthly_exams'][] = array(
                            'month_name' => sanitize_text_field($monthly['month_name'] ?? ''),
                            'oral_total' => $oral_total,
                            'oral_obtained' => $oral_obtained,
                            'written_total' => $written_total,
                            'written_obtained' => $written_obtained,
                            'overall_total' => $overall_total,
                            'overall_obtained' => $overall_obtained,
                            'percentage' => $percentage,
                            'grade' => $grade,
                        );
                    }
                }

                // Mid semester
                if (isset($subject['mid_semester'])) {
                    $mid = $subject['mid_semester'];
                    $oral_total = floatval($mid['oral_total'] ?? 0);
                    $oral_obtained = floatval($mid['oral_obtained'] ?? 0);
                    $written_total = floatval($mid['written_total'] ?? 0);
                    $written_obtained = floatval($mid['written_obtained'] ?? 0);
                    $overall_total = $oral_total + $written_total;
                    $overall_obtained = $oral_obtained + $written_obtained;
                    $percentage = $overall_total > 0 ? round(($overall_obtained / $overall_total) * 100, 1) : 0;

                    $subject_data['mid_semester'] = array(
                        'oral_total' => $oral_total,
                        'oral_obtained' => $oral_obtained,
                        'written_total' => $written_total,
                        'written_obtained' => $written_obtained,
                        'overall_total' => $overall_total,
                        'overall_obtained' => $overall_obtained,
                        'percentage' => $percentage,
                        'grade' => $this->calculate_grade($percentage),
                    );
                }

                // Final semester
                if (isset($subject['final_semester'])) {
                    $final = $subject['final_semester'];
                    $oral_total = floatval($final['oral_total'] ?? 0);
                    $oral_obtained = floatval($final['oral_obtained'] ?? 0);
                    $written_total = floatval($final['written_total'] ?? 0);
                    $written_obtained = floatval($final['written_obtained'] ?? 0);
                    $overall_total = $oral_total + $written_total;
                    $overall_obtained = $oral_obtained + $written_obtained;
                    $percentage = $overall_total > 0 ? round(($overall_obtained / $overall_total) * 100, 1) : 0;

                    $subject_data['final_semester'] = array(
                        'oral_total' => $oral_total,
                        'oral_obtained' => $oral_obtained,
                        'written_total' => $written_total,
                        'written_obtained' => $written_obtained,
                        'overall_total' => $overall_total,
                        'overall_obtained' => $overall_obtained,
                        'percentage' => $percentage,
                        'grade' => $this->calculate_grade($percentage),
                    );
                }

                $subjects[] = $subject_data;
            }

            update_post_meta($student_id, 'subjects', $subjects);
        }

        // Handle photo upload
        if (!empty($_FILES['student_photo']) && $_FILES['student_photo']['size'] > 0) {
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');

            $attachment_id = media_handle_upload('student_photo', $student_id);

            if (!is_wp_error($attachment_id)) {
                update_post_meta($student_id, 'student_photo', $attachment_id);
            }
        }

        // Log activity
        Helpers::log_activity(
            $student_id ? 'update_student' : 'create_student',
            'student',
            $student_id,
            sprintf('Student %s %s', $student_name, $student_id ? 'updated' : 'created')
        );

        wp_send_json_success(array(
            'message'    => __('Student saved successfully.', 'al-huffaz-portal'),
            'student_id' => $student_id,
        ));
    }

    /**
     * Enhanced student form - Save student with all 46+ fields
     */
    public function ahp_save_student() {
        // Verify nonce
        if (!isset($_POST['ahp_nonce']) || !wp_verify_nonce($_POST['ahp_nonce'], 'ahp_student_form')) {
            wp_send_json_error('Security check failed.');
        }

        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Permission denied.');
        }

        $student_id = isset($_POST['student_id']) ? intval($_POST['student_id']) : 0;
        $student_name = isset($_POST['student_name']) ? sanitize_text_field($_POST['student_name']) : '';
        $gr_number = isset($_POST['gr_number']) ? sanitize_text_field($_POST['gr_number']) : '';

        if (empty($student_name)) {
            wp_send_json_error('Student name is required.');
        }

        if (empty($gr_number)) {
            wp_send_json_error('GR Number is required.');
        }

        // Prepare post data
        $post_data = array(
            'post_title'  => $student_name,
            'post_type'   => 'student',
            'post_status' => 'publish',
        );

        if ($student_id) {
            $post_data['ID'] = $student_id;
            $result = wp_update_post($post_data);
        } else {
            $result = wp_insert_post($post_data);
        }

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        $student_id = $result;

        // List of all text/select fields to save
        $text_fields = array(
            'gr_number', 'roll_number', 'gender', 'date_of_birth', 'admission_date',
            'grade_level', 'islamic_studies_category', 'permanent_address', 'current_address',
            'father_name', 'father_cnic', 'father_email',
            'guardian_name', 'guardian_cnic', 'guardian_email', 'guardian_phone', 'guardian_whatsapp',
            'relationship_to_student', 'emergency_contact', 'emergency_whatsapp',
            'academic_year', 'academic_term',
            'blood_group', 'allergies', 'medical_conditions',
            'teacher_overall_comments', 'goal_1', 'goal_2', 'goal_3'
        );

        foreach ($text_fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($student_id, $field, sanitize_text_field($_POST[$field]));
            }
        }

        // Number fields
        $number_fields = array(
            'monthly_tuition_fee', 'course_fee', 'uniform_fee', 'annual_fee', 'admission_fee',
            'total_school_days', 'present_days',
            'health_rating', 'cleanness_rating', 'completes_homework', 'participates_in_class',
            'works_well_in_groups', 'problem_solving_skills', 'organization_preparedness'
        );

        foreach ($number_fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($student_id, $field, floatval($_POST[$field]));
            }
        }

        // Checkbox fields
        $checkbox_fields = array('zakat_eligible', 'donation_eligible');

        foreach ($checkbox_fields as $field) {
            $value = isset($_POST[$field]) && $_POST[$field] === 'yes' ? 'yes' : 'no';
            update_post_meta($student_id, $field, $value);
        }

        // Subjects array with nested monthly exams, mid/final semester
        if (isset($_POST['subjects']) && is_array($_POST['subjects'])) {
            $subjects = array();

            foreach ($_POST['subjects'] as $index => $subject) {
                $subject_data = array(
                    'name' => sanitize_text_field($subject['name'] ?? ''),
                    'strengths' => sanitize_textarea_field($subject['strengths'] ?? ''),
                    'areas_for_improvement' => sanitize_textarea_field($subject['areas_for_improvement'] ?? ''),
                    'teacher_comments' => sanitize_textarea_field($subject['teacher_comments'] ?? ''),
                    'monthly_exams' => array(),
                    'mid_semester' => array(),
                    'final_semester' => array(),
                );

                // Monthly exams
                if (isset($subject['monthly_exams']) && is_array($subject['monthly_exams'])) {
                    foreach ($subject['monthly_exams'] as $monthly) {
                        $oral_total = floatval($monthly['oral_total'] ?? 0);
                        $oral_obtained = floatval($monthly['oral_obtained'] ?? 0);
                        $written_total = floatval($monthly['written_total'] ?? 0);
                        $written_obtained = floatval($monthly['written_obtained'] ?? 0);
                        $overall_total = $oral_total + $written_total;
                        $overall_obtained = $oral_obtained + $written_obtained;
                        $percentage = $overall_total > 0 ? round(($overall_obtained / $overall_total) * 100, 1) : 0;
                        $grade = $this->calculate_grade($percentage);

                        $subject_data['monthly_exams'][] = array(
                            'month_name' => sanitize_text_field($monthly['month_name'] ?? ''),
                            'oral_total' => $oral_total,
                            'oral_obtained' => $oral_obtained,
                            'written_total' => $written_total,
                            'written_obtained' => $written_obtained,
                            'overall_total' => $overall_total,
                            'overall_obtained' => $overall_obtained,
                            'percentage' => $percentage,
                            'grade' => $grade,
                        );
                    }
                }

                // Mid semester
                if (isset($subject['mid_semester'])) {
                    $mid = $subject['mid_semester'];
                    $oral_total = floatval($mid['oral_total'] ?? 0);
                    $oral_obtained = floatval($mid['oral_obtained'] ?? 0);
                    $written_total = floatval($mid['written_total'] ?? 0);
                    $written_obtained = floatval($mid['written_obtained'] ?? 0);
                    $overall_total = $oral_total + $written_total;
                    $overall_obtained = $oral_obtained + $written_obtained;
                    $percentage = $overall_total > 0 ? round(($overall_obtained / $overall_total) * 100, 1) : 0;

                    $subject_data['mid_semester'] = array(
                        'oral_total' => $oral_total,
                        'oral_obtained' => $oral_obtained,
                        'written_total' => $written_total,
                        'written_obtained' => $written_obtained,
                        'overall_total' => $overall_total,
                        'overall_obtained' => $overall_obtained,
                        'percentage' => $percentage,
                        'grade' => $this->calculate_grade($percentage),
                    );
                }

                // Final semester
                if (isset($subject['final_semester'])) {
                    $final = $subject['final_semester'];
                    $oral_total = floatval($final['oral_total'] ?? 0);
                    $oral_obtained = floatval($final['oral_obtained'] ?? 0);
                    $written_total = floatval($final['written_total'] ?? 0);
                    $written_obtained = floatval($final['written_obtained'] ?? 0);
                    $overall_total = $oral_total + $written_total;
                    $overall_obtained = $oral_obtained + $written_obtained;
                    $percentage = $overall_total > 0 ? round(($overall_obtained / $overall_total) * 100, 1) : 0;

                    $subject_data['final_semester'] = array(
                        'oral_total' => $oral_total,
                        'oral_obtained' => $oral_obtained,
                        'written_total' => $written_total,
                        'written_obtained' => $written_obtained,
                        'overall_total' => $overall_total,
                        'overall_obtained' => $overall_obtained,
                        'percentage' => $percentage,
                        'grade' => $this->calculate_grade($percentage),
                    );
                }

                $subjects[] = $subject_data;
            }

            update_post_meta($student_id, 'subjects', $subjects);
        }

        // Handle photo upload
        if (!empty($_FILES['student_photo']) && $_FILES['student_photo']['size'] > 0) {
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');

            $attachment_id = media_handle_upload('student_photo', $student_id);

            if (!is_wp_error($attachment_id)) {
                update_post_meta($student_id, 'student_photo', $attachment_id);
            }
        }

        wp_send_json_success(array(
            'message' => $student_id ? __('Student updated successfully!', 'al-huffaz-portal') : __('Student enrolled successfully!', 'al-huffaz-portal'),
            'student_id' => $student_id,
            'redirect' => admin_url('admin.php?page=alhuffaz-students'),
        ));
    }

    /**
     * Calculate grade from percentage
     */
    private function calculate_grade($percentage) {
        if ($percentage >= 90) return 'A+';
        if ($percentage >= 80) return 'A';
        if ($percentage >= 70) return 'B';
        if ($percentage >= 60) return 'C';
        if ($percentage >= 50) return 'D';
        return 'F';
    }

    /**
     * Delete student
     */
    public function delete_student() {
        $this->verify_admin_nonce();

        $student_id = isset($_POST['student_id']) ? intval($_POST['student_id']) : 0;
        $force = isset($_POST['force']) && $_POST['force'] === 'true'; // Allow force delete

        if (!$student_id) {
            wp_send_json_error(array('message' => __('Invalid student ID.', 'al-huffaz-portal')));
        }

        $student = get_post($student_id);

        if (!$student || $student->post_type !== 'student') {
            wp_send_json_error(array('message' => __('Student not found.', 'al-huffaz-portal')));
        }

        // FIX #18: Check for active sponsorships before delete
        $active_sponsorships = get_posts(array(
            'post_type' => 'alhuffaz_sponsor',
            'posts_per_page' => -1,
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => '_student_id',
                    'value' => $student_id,
                    'compare' => '='
                ),
                array(
                    'key' => '_status',
                    'value' => 'approved',
                    'compare' => '='
                )
            ),
            'fields' => 'ids'
        ));

        if (!empty($active_sponsorships) && !$force) {
            // Return warning with sponsorship count
            wp_send_json_error(array(
                'message' => sprintf(
                    __('Cannot delete: Student has %d active sponsorship(s). Please cancel or unlink sponsorships first.', 'al-huffaz-portal'),
                    count($active_sponsorships)
                ),
                'has_sponsorships' => true,
                'sponsorship_count' => count($active_sponsorships)
            ));
        }

        // Delete student
        $result = wp_delete_post($student_id, true);

        if (!$result) {
            wp_send_json_error(array('message' => __('Failed to delete student.', 'al-huffaz-portal')));
        }

        // Log activity
        Helpers::log_activity('delete_student', 'student', $student_id, sprintf('Student %s deleted', $student->post_title));

        wp_send_json_success(array('message' => __('Student deleted successfully.', 'al-huffaz-portal')));
    }

    /**
     * Get single student
     */
    public function get_student() {
        $this->verify_admin_nonce();

        $student_id = isset($_POST['student_id']) ? intval($_POST['student_id']) : 0;

        if (!$student_id) {
            wp_send_json_error(array('message' => __('Invalid student ID.', 'al-huffaz-portal')));
        }

        $student = get_post($student_id);

        if (!$student || $student->post_type !== 'student') {
            wp_send_json_error(array('message' => __('Student not found.', 'al-huffaz-portal')));
        }

        // Get all meta - handle both underscore-prefixed and non-prefixed keys (CPT UI uses non-prefixed)
        $meta = get_post_meta($student_id);
        $data = array(
            'id'   => $student_id,
            'name' => $student->post_title,
        );

        foreach ($meta as $key => $value) {
            // Skip internal WordPress meta
            if (strpos($key, '_wp_') === 0 || strpos($key, '_edit_') === 0) {
                continue;
            }

            // Handle both prefixed and non-prefixed keys
            $clean_key = (strpos($key, '_') === 0) ? substr($key, 1) : $key;
            $data[$clean_key] = maybe_unserialize($value[0]);
        }

        wp_send_json_success($data);
    }

    /**
     * Get students list
     */
    public function get_students() {
        $this->verify_admin_nonce();

        $page      = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $per_page  = isset($_POST['per_page']) ? intval($_POST['per_page']) : 20;
        $search    = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        $grade     = isset($_POST['grade']) ? sanitize_text_field($_POST['grade']) : '';
        $category  = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '';
        $gender    = isset($_POST['gender']) ? sanitize_text_field($_POST['gender']) : '';
        $sponsored = isset($_POST['sponsored']) ? sanitize_text_field($_POST['sponsored']) : '';

        $args = array(
            'post_type'      => 'student',
            'posts_per_page' => $per_page,
            'paged'          => $page,
            'post_status'    => 'publish',
            'orderby'        => 'title',
            'order'          => 'ASC',
        );

        // Handle search - search by name or GR number
        if ($search) {
            // First try searching by GR number
            $gr_args = array(
                'post_type'      => 'student',
                'posts_per_page' => -1,
                'post_status'    => 'publish',
                'fields'         => 'ids',
                'meta_query'     => array(
                    array(
                        'key'     => 'gr_number',
                        'value'   => $search,
                        'compare' => 'LIKE',
                    ),
                ),
            );
            $gr_matches = get_posts($gr_args);

            if (!empty($gr_matches)) {
                $args['post__in'] = $gr_matches;
            } else {
                $args['s'] = $search;
            }
        }

        $meta_query = array();

        if ($grade) {
            $meta_query[] = array(
                'key'   => 'grade_level',
                'value' => $grade,
            );
        }

        if ($category) {
            $meta_query[] = array(
                'key'   => 'islamic_studies_category',
                'value' => $category,
            );
        }

        if ($gender) {
            $meta_query[] = array(
                'key'   => 'gender',
                'value' => $gender,
            );
        }

        if ($sponsored === 'yes' || $sponsored === 'no') {
            $meta_query[] = array(
                'key'   => 'is_sponsored',
                'value' => $sponsored,
            );
        }

        if (!empty($meta_query)) {
            $args['meta_query'] = $meta_query;
        }

        $query = new \WP_Query($args);

        $students = array();

        foreach ($query->posts as $post) {
            // Get photo URL
            $photo_id = get_post_meta($post->ID, 'student_photo', true);
            $photo_url = $photo_id ? wp_get_attachment_image_url($photo_id, 'thumbnail') : '';

            $students[] = array(
                'id'                       => $post->ID,
                'name'                     => $post->post_title,
                'gr_number'                => get_post_meta($post->ID, 'gr_number', true),
                'grade_level'              => get_post_meta($post->ID, 'grade_level', true),
                'islamic_studies_category' => get_post_meta($post->ID, 'islamic_studies_category', true),
                'gender'                   => get_post_meta($post->ID, 'gender', true),
                'father_name'              => get_post_meta($post->ID, 'father_name', true),
                'photo'                    => $photo_url,
                'permalink'                => get_permalink($post->ID),
                'is_sponsored'             => get_post_meta($post->ID, 'is_sponsored', true) === 'yes',
            );
        }

        wp_send_json_success(array(
            'students'    => $students,
            'total'       => $query->found_posts,
            'total_pages' => $query->max_num_pages,
            'page'        => $page,
        ));
    }

    /**
     * Search students
     */
    public function search_students() {
        $this->verify_admin_nonce();

        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';

        if (strlen($search) < 2) {
            wp_send_json_success(array('students' => array()));
        }

        $args = array(
            'post_type'      => 'student',
            'posts_per_page' => 10,
            's'              => $search,
            'post_status'    => 'publish',
        );

        $query = new \WP_Query($args);

        $students = array();

        foreach ($query->posts as $post) {
            $students[] = array(
                'id'   => $post->ID,
                'name' => $post->post_title,
                'gr'   => get_post_meta($post->ID, '_gr_number', true),
            );
        }

        wp_send_json_success(array('students' => $students));
    }

    /**
     * Approve sponsorship
     */
    public function approve_sponsorship() {
        $this->verify_admin_nonce();

        if (!current_user_can('alhuffaz_manage_sponsors')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'al-huffaz-portal')));
        }

        $sponsorship_id = isset($_POST['sponsorship_id']) ? intval($_POST['sponsorship_id']) : 0;

        if (!$sponsorship_id) {
            wp_send_json_error(array('message' => __('Invalid sponsorship ID.', 'al-huffaz-portal')));
        }

        // CRITICAL FIX: Use correct meta keys without underscore prefix
        update_post_meta($sponsorship_id, 'verification_status', 'approved');
        update_post_meta($sponsorship_id, 'verified_by', get_current_user_id());
        update_post_meta($sponsorship_id, 'verified_at', current_time('mysql'));

        // Automatically link sponsor to student on approval
        update_post_meta($sponsorship_id, 'linked', 'yes');

        // Mark student as sponsored (use correct meta key)
        $student_id = get_post_meta($sponsorship_id, 'student_id', true);
        if ($student_id) {
            update_post_meta($student_id, 'already_sponsored', 'yes');
            update_post_meta($student_id, 'sponsored_date', current_time('mysql'));
        }

        // Update post status to published
        wp_update_post(array(
            'ID' => $sponsorship_id,
            'post_status' => 'publish'
        ));

        // Log activity
        Helpers::log_activity('approve_sponsorship', 'sponsorship', $sponsorship_id, 'Sponsorship approved and student linked');

        // Sync with Ultimate Member if sponsor has user account
        $sponsor_user_id = get_post_meta($sponsorship_id, 'sponsor_user_id', true);
        if ($sponsor_user_id) {
            UM_Integration::approve_sponsor_in_um($sponsor_user_id);
        }

        // Send notification email
        $sponsor_email = get_post_meta($sponsorship_id, 'sponsor_email', true);
        if ($sponsor_email) {
            Helpers::send_notification(
                $sponsor_email,
                __('Sponsorship Approved', 'al-huffaz-portal'),
                __('Your sponsorship has been approved. Thank you for your support!', 'al-huffaz-portal')
            );
        }

        // Create in-app notification for sponsor
        if ($sponsor_user_id && class_exists('AlHuffaz\\Core\\Notifications')) {
            $student = get_post($student_id);
            $student_name = $student ? $student->post_title : __('student', 'al-huffaz-portal');

            Notifications::create(
                $sponsor_user_id,
                __('Sponsorship Approved!', 'al-huffaz-portal'),
                sprintf(
                    __('Congratulations! Your sponsorship for %s has been approved and is now active. You can now view the student\'s progress and academic records in your dashboard.', 'al-huffaz-portal'),
                    $student_name
                ),
                'success',
                $sponsorship_id,
                'sponsorship'
            );
        }

        wp_send_json_success(array('message' => __('Sponsorship approved successfully.', 'al-huffaz-portal')));
    }

    /**
     * Reject sponsorship
     */
    public function reject_sponsorship() {
        $this->verify_admin_nonce();

        if (!current_user_can('alhuffaz_manage_sponsors')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'al-huffaz-portal')));
        }

        $sponsorship_id = isset($_POST['sponsorship_id']) ? intval($_POST['sponsorship_id']) : 0;
        $reason = isset($_POST['reason']) ? sanitize_textarea_field($_POST['reason']) : '';

        if (!$sponsorship_id) {
            wp_send_json_error(array('message' => __('Invalid sponsorship ID.', 'al-huffaz-portal')));
        }

        // Get sponsorship details before rejection
        $student_id = get_post_meta($sponsorship_id, 'student_id', true);
        $sponsor_user_id = get_post_meta($sponsorship_id, 'sponsor_user_id', true);
        $sponsor_email = get_post_meta($sponsorship_id, 'sponsor_email', true);
        $student = get_post($student_id);
        $student_name = $student ? $student->post_title : __('student', 'al-huffaz-portal');

        // CRITICAL FIX: Update rejection status
        update_post_meta($sponsorship_id, 'verification_status', 'rejected');
        update_post_meta($sponsorship_id, 'rejection_reason', $reason);
        update_post_meta($sponsorship_id, 'rejected_by', get_current_user_id());
        update_post_meta($sponsorship_id, 'rejected_at', current_time('mysql'));
        update_post_meta($sponsorship_id, 'linked', 'no');

        // Make student available again
        if ($student_id) {
            delete_post_meta($student_id, 'already_sponsored');
            delete_post_meta($student_id, 'sponsored_date');
        }

        // Move to trash
        wp_update_post(array(
            'ID' => $sponsorship_id,
            'post_status' => 'trash'
        ));

        // Sync with Ultimate Member if sponsor has user account
        if ($sponsor_user_id) {
            UM_Integration::reject_sponsor_in_um($sponsor_user_id);
        }

        // Send notification email to sponsor
        if ($sponsor_email) {
            Helpers::send_notification(
                $sponsor_email,
                __('Sponsorship Payment Rejected', 'al-huffaz-portal'),
                sprintf(
                    __('We regret to inform you that your sponsorship payment for %s has been rejected.

Reason: %s

If you have any questions, please contact us.

Thank you for your interest in supporting our students.', 'al-huffaz-portal'),
                    $student_name,
                    $reason ?: __('No reason provided', 'al-huffaz-portal')
                )
            );
        }

        // Create in-app notification for sponsor
        if ($sponsor_user_id && class_exists('AlHuffaz\\Core\\Notifications')) {
            Notifications::create(
                $sponsor_user_id,
                __('Sponsorship Rejected', 'al-huffaz-portal'),
                sprintf(
                    __('Your sponsorship payment for %s has been rejected. Reason: %s. Please contact the school if you have questions.', 'al-huffaz-portal'),
                    $student_name,
                    $reason ?: __('No reason provided', 'al-huffaz-portal')
                ),
                'error',
                $sponsorship_id,
                'sponsorship'
            );
        }

        // Clear sponsor dashboard cache
        if ($sponsor_user_id) {
            wp_cache_delete('sponsor_dashboard_' . $sponsor_user_id, 'alhuffaz');
            wp_cache_flush();
        }

        // Log activity
        Helpers::log_activity('reject_sponsorship', 'sponsorship', $sponsorship_id, 'Sponsorship rejected: ' . $reason);

        wp_send_json_success(array('message' => __('Sponsorship rejected and sponsor notified.', 'al-huffaz-portal')));
    }

    /**
     * Link sponsor to student
     */
    public function link_sponsor() {
        $this->verify_admin_nonce();

        if (!current_user_can('alhuffaz_manage_sponsors')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'al-huffaz-portal')));
        }

        $sponsorship_id = isset($_POST['sponsorship_id']) ? intval($_POST['sponsorship_id']) : 0;

        if (!$sponsorship_id) {
            wp_send_json_error(array('message' => __('Invalid sponsorship ID.', 'al-huffaz-portal')));
        }

        // CRITICAL FIX: Use correct meta keys without underscore prefix
        update_post_meta($sponsorship_id, 'linked', 'yes');
        update_post_meta($sponsorship_id, 'linked_at', current_time('mysql'));

        // Mark student as sponsored
        $student_id = get_post_meta($sponsorship_id, 'student_id', true);
        if ($student_id) {
            update_post_meta($student_id, 'already_sponsored', 'yes');
            update_post_meta($student_id, 'sponsored_date', current_time('mysql'));
        }

        // Log activity
        Helpers::log_activity('link_sponsor', 'sponsorship', $sponsorship_id, 'Sponsor linked to student');

        wp_send_json_success(array('message' => __('Sponsor linked successfully.', 'al-huffaz-portal')));
    }

    /**
     * Unlink sponsor from student
     */
    public function unlink_sponsor() {
        $this->verify_admin_nonce();

        if (!current_user_can('alhuffaz_manage_sponsors')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'al-huffaz-portal')));
        }

        $sponsorship_id = isset($_POST['sponsorship_id']) ? intval($_POST['sponsorship_id']) : 0;

        if (!$sponsorship_id) {
            wp_send_json_error(array('message' => __('Invalid sponsorship ID.', 'al-huffaz-portal')));
        }

        // Get sponsorship details before unlinking
        $student_id = get_post_meta($sponsorship_id, 'student_id', true);
        $sponsor_user_id = get_post_meta($sponsorship_id, 'sponsor_user_id', true);
        $sponsor_email = get_post_meta($sponsorship_id, 'sponsor_email', true);
        $student = get_post($student_id);
        $student_name = $student ? $student->post_title : __('student', 'al-huffaz-portal');

        // CRITICAL FIX: Unlink sponsorship
        update_post_meta($sponsorship_id, 'linked', 'no');
        update_post_meta($sponsorship_id, 'unlinked_by', get_current_user_id());
        update_post_meta($sponsorship_id, 'unlinked_at', current_time('mysql'));

        // Make student available again
        if ($student_id) {
            delete_post_meta($student_id, 'already_sponsored');
            delete_post_meta($student_id, 'sponsored_date');
        }

        // Send notification email to sponsor
        if ($sponsor_email) {
            Helpers::send_notification(
                $sponsor_email,
                __('Sponsorship Unlinked', 'al-huffaz-portal'),
                sprintf(
                    __('Your sponsorship for %s has been unlinked by the school administration. The student is now available for other sponsors. If you have any questions, please contact us.

Thank you for your support.', 'al-huffaz-portal'),
                    $student_name
                )
            );
        }

        // Create in-app notification for sponsor
        if ($sponsor_user_id && class_exists('AlHuffaz\\Core\\Notifications')) {
            Notifications::create(
                $sponsor_user_id,
                __('Sponsorship Unlinked', 'al-huffaz-portal'),
                sprintf(
                    __('Your sponsorship for %s has been unlinked by the school. The student is now available for other sponsors. Please contact the school if you have questions.', 'al-huffaz-portal'),
                    $student_name
                ),
                'warning',
                $sponsorship_id,
                'sponsorship'
            );
        }

        // Clear cache for real-time update
        if ($sponsor_user_id) {
            wp_cache_delete('sponsor_dashboard_' . $sponsor_user_id, 'alhuffaz');
            wp_cache_flush();
        }
        clean_post_cache($sponsorship_id);

        // Log activity
        Helpers::log_activity('unlink_sponsor', 'sponsorship', $sponsorship_id, 'Sponsor unlinked from student');

        wp_send_json_success(array('message' => __('Sponsorship unlinked and sponsor notified.', 'al-huffaz-portal')));
    }

    /**
     * Get sponsorships
     */
    public function get_sponsorships() {
        $this->verify_admin_nonce();

        $page     = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 20;
        $status   = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';

        // CRITICAL FIX: Use 'sponsorship' post type (matches what submit_payment_proof creates)
        $args = array(
            'post_type'      => 'sponsorship',
            'posts_per_page' => $per_page,
            'paged'          => $page,
            'post_status'    => 'any',  // Include draft posts (newly submitted payments)
        );

        // CRITICAL FIX: Map status filter to correct meta key
        if ($status) {
            // Status can be: pending, approved, rejected, cancelled
            $args['meta_query'] = array(
                array(
                    'key'   => 'verification_status',  // Changed from '_status'
                    'value' => $status,
                ),
            );
        }

        $query = new \WP_Query($args);

        $sponsorships = array();

        foreach ($query->posts as $post) {
            // CRITICAL FIX: Use non-prefixed meta keys (matches submit_payment_proof)
            $student_id = get_post_meta($post->ID, 'student_id', true);
            $student = get_post($student_id);

            $verification_status = get_post_meta($post->ID, 'verification_status', true);

            $sponsorships[] = array(
                'id'             => $post->ID,
                'sponsor_name'   => get_post_meta($post->ID, 'sponsor_name', true),
                'sponsor_email'  => get_post_meta($post->ID, 'sponsor_email', true),
                'student_name'   => $student ? $student->post_title : '-',
                'amount'         => Helpers::format_currency(get_post_meta($post->ID, 'amount', true)),
                'type'           => get_post_meta($post->ID, 'sponsorship_type', true),
                'status'         => $verification_status,
                'status_badge'   => Helpers::get_status_badge($verification_status),
                'linked'         => get_post_meta($post->ID, 'linked', true) === 'yes',
                'date'           => Helpers::format_date($post->post_date),
            );
        }

        wp_send_json_success(array(
            'sponsorships' => $sponsorships,
            'total'        => $query->found_posts,
            'total_pages'  => $query->max_num_pages,
            'page'         => $page,
        ));
    }

    /**
     * Get sponsorship details for modal view
     */
    public function get_sponsorship_details() {
        $this->verify_admin_nonce();

        $sponsorship_id = isset($_POST['sponsorship_id']) ? intval($_POST['sponsorship_id']) : 0;

        if (!$sponsorship_id) {
            wp_send_json_error(array('message' => __('Invalid sponsorship ID.', 'al-huffaz-portal')));
        }

        $sponsorship = get_post($sponsorship_id);

        if (!$sponsorship || $sponsorship->post_type !== 'sponsorship') {
            wp_send_json_error(array('message' => __('Sponsorship not found.', 'al-huffaz-portal')));
        }

        // Get student details
        $student_id = get_post_meta($sponsorship_id, 'student_id', true);
        $student = get_post($student_id);

        // Get payment screenshot
        $screenshot_id = get_post_meta($sponsorship_id, 'payment_screenshot', true);
        $screenshot_url = $screenshot_id ? wp_get_attachment_url($screenshot_id) : '';

        $details = array(
            'id'                  => $sponsorship_id,
            'sponsor_name'        => get_post_meta($sponsorship_id, 'sponsor_name', true),
            'sponsor_email'       => get_post_meta($sponsorship_id, 'sponsor_email', true),
            'student_name'        => $student ? $student->post_title : '-',
            'amount'              => Helpers::format_currency(get_post_meta($sponsorship_id, 'amount', true)),
            'duration_months'     => get_post_meta($sponsorship_id, 'duration_months', true),
            'sponsorship_type'    => get_post_meta($sponsorship_id, 'sponsorship_type', true),
            'payment_method'      => get_post_meta($sponsorship_id, 'payment_method', true),
            'transaction_id'      => get_post_meta($sponsorship_id, 'transaction_id', true),
            'payment_date'        => Helpers::format_date(get_post_meta($sponsorship_id, 'payment_date', true)),
            'verification_status' => get_post_meta($sponsorship_id, 'verification_status', true),
            'linked'              => get_post_meta($sponsorship_id, 'linked', true) === 'yes',
            'notes'               => get_post_meta($sponsorship_id, 'notes', true),
            'payment_screenshot'  => $screenshot_url,
            'created_at'          => Helpers::format_date($sponsorship->post_date),
        );

        wp_send_json_success($details);
    }

    /**
     * Verify payment
     */
    public function verify_payment() {
        $this->verify_admin_nonce();

        if (!current_user_can('alhuffaz_manage_payments') && !current_user_can('edit_posts') && !current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'al-huffaz-portal')));
        }

        $payment_id = isset($_POST['payment_id']) ? intval($_POST['payment_id']) : 0;
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'approved';

        if (!$payment_id) {
            wp_send_json_error(array('message' => __('Invalid payment ID.', 'al-huffaz-portal')));
        }

        global $wpdb;
        $table = $wpdb->prefix . 'alhuffaz_payments';

        // Get payment details before update for notification
        $payment_record = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $payment_id));

        $wpdb->update(
            $table,
            array(
                'status'      => $status,
                'verified_by' => get_current_user_id(),
                'verified_at' => current_time('mysql'),
            ),
            array('id' => $payment_id),
            array('%s', '%d', '%s'),
            array('%d')
        );

        // Log activity
        Helpers::log_activity('verify_payment', 'payment', $payment_id, 'Payment ' . $status);

        // Create in-app notification for sponsor if payment is approved
        if ($status === 'approved' && $payment_record && class_exists('AlHuffaz\\Core\\Notifications')) {
            $student = get_post($payment_record->student_id);
            $student_name = $student ? $student->post_title : __('student', 'al-huffaz-portal');

            Notifications::create(
                $payment_record->sponsor_id,
                __('Payment Verified', 'al-huffaz-portal'),
                sprintf(
                    __('Your payment of %s for %s has been verified and approved. Thank you for your generous support!', 'al-huffaz-portal'),
                    Helpers::format_currency($payment_record->amount),
                    $student_name
                ),
                'success',
                $payment_id,
                'payment'
            );
        }

        wp_send_json_success(array('message' => __('Payment verified successfully.', 'al-huffaz-portal')));
    }

    /**
     * Get payments
     */
    public function get_payments() {
        $this->verify_admin_nonce();

        global $wpdb;
        $table = $wpdb->prefix . 'alhuffaz_payments';

        $page     = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 20;
        $status   = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
        $offset   = ($page - 1) * $per_page;

        $where = "1=1";
        if ($status) {
            $where .= $wpdb->prepare(" AND status = %s", $status);
        }

        $total = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE $where");

        $payments = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE $where ORDER BY created_at DESC LIMIT %d OFFSET %d",
            $per_page,
            $offset
        ));

        $result = array();

        foreach ($payments as $payment) {
            $sponsorship = get_post($payment->sponsorship_id);
            $student = get_post($payment->student_id);

            $result[] = array(
                'id'             => $payment->id,
                'sponsor_name'   => $sponsorship ? get_post_meta($sponsorship->ID, '_sponsor_name', true) : '-',
                'student_name'   => $student ? $student->post_title : '-',
                'amount'         => Helpers::format_currency($payment->amount),
                'method'         => $payment->payment_method,
                'transaction_id' => $payment->transaction_id,
                'date'           => Helpers::format_date($payment->payment_date),
                'status'         => $payment->status,
                'status_badge'   => Helpers::get_status_badge($payment->status),
            );
        }

        wp_send_json_success(array(
            'payments'    => $result,
            'total'       => intval($total),
            'total_pages' => ceil($total / $per_page),
            'page'        => $page,
        ));
    }

    /**
     * Get dashboard stats
     */
    public function get_dashboard_stats() {
        $this->verify_admin_nonce();

        // ENHANCED: Return ALL dashboard stats for frontend portal auto-refresh
        $student_counts = wp_count_posts('student');
        $total_students = isset($student_counts->publish) ? (int)$student_counts->publish : 0;

        $sponsor_counts = wp_count_posts('alhuffaz_sponsor');
        $total_sponsors = isset($sponsor_counts->publish) ? (int)$sponsor_counts->publish : 0;

        // Category counts
        $hifz_count = 0;
        $nazra_count = 0;
        $donation_eligible_count = 0;

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

        // Pending sponsor USER accounts (not sponsorships) - waiting for admin approval
        $pending_sponsor_users = get_users(array(
            'role' => 'alhuffaz_sponsor',
            'meta_query' => array(
                array(
                    'key' => 'account_status',
                    'value' => 'pending_approval',
                    'compare' => '='
                )
            ),
            'fields' => 'ID',
            'number' => -1
        ));
        $pending_sponsor_users_count = count($pending_sponsor_users);

        // Inactive sponsors count (sponsors with no active sponsorships)
        $inactive_sponsors_count = 0;
        $all_sponsor_users = get_users(array('role' => 'alhuffaz_sponsor'));
        foreach ($all_sponsor_users as $sponsor_user) {
            $active_sponsorships = get_posts(array(
                'post_type' => 'alhuffaz_sponsor',
                'posts_per_page' => -1,
                'meta_query' => array(
                    'relation' => 'AND',
                    array('key' => '_sponsor_user_id', 'value' => $sponsor_user->ID),
                    array('key' => '_status', 'value' => 'approved'),
                    array('key' => '_linked', 'value' => 'yes'),
                ),
                'fields' => 'ids',
            ));
            if (empty($active_sponsorships)) {
                $inactive_sponsors_count++;
            }
        }

        // Payments stats
        global $wpdb;
        $payments_table = $wpdb->prefix . 'alhuffaz_payments';

        $pending_payments_count = 0;
        $table_exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s",
            DB_NAME, $payments_table
        ));

        if ($table_exists) {
            $pending_payments_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM $payments_table WHERE status = 'pending'");
        }

        // Return comprehensive stats for frontend refresh
        wp_send_json_success(array(
            'total_students' => $total_students,
            'total_sponsors' => $total_sponsors,
            'hifz_count' => $hifz_count,
            'nazra_count' => $nazra_count,
            'inactive_sponsors_count' => $inactive_sponsors_count,
            'donation_eligible_count' => $donation_eligible_count,
            'pending_sponsor_users_count' => $pending_sponsor_users_count,
            'pending_payments_count' => $pending_payments_count,
        ));
    }

    /**
     * Submit sponsorship (public)
     */
    public function submit_sponsorship() {
        $this->verify_public_nonce();

        $data = isset($_POST['data']) ? $_POST['data'] : array();

        // Validate required fields
        $required = array('sponsor_name', 'sponsor_email', 'sponsor_phone', 'student_id', 'amount');

        foreach ($required as $field) {
            if (empty($data[$field])) {
                wp_send_json_error(array('message' => sprintf(__('%s is required.', 'al-huffaz-portal'), ucwords(str_replace('_', ' ', $field)))));
            }
        }

        // Create sponsorship post
        $sponsorship_id = wp_insert_post(array(
            'post_type'   => 'alhuffaz_sponsor',
            'post_title'  => sanitize_text_field($data['sponsor_name']) . ' - ' . date('Y-m-d H:i'),
            'post_status' => 'publish',
        ));

        if (is_wp_error($sponsorship_id)) {
            wp_send_json_error(array('message' => $sponsorship_id->get_error_message()));
        }

        // Save meta
        $fields = array(
            'student_id'       => intval($data['student_id']),
            'sponsor_name'     => sanitize_text_field($data['sponsor_name']),
            'sponsor_email'    => sanitize_email($data['sponsor_email']),
            'sponsor_phone'    => Helpers::sanitize_phone($data['sponsor_phone']),
            'sponsor_country'  => sanitize_text_field($data['sponsor_country'] ?? 'PK'),
            'amount'           => floatval($data['amount']),
            'sponsorship_type' => sanitize_text_field($data['sponsorship_type'] ?? 'monthly'),
            'payment_method'   => sanitize_text_field($data['payment_method'] ?? ''),
            'transaction_id'   => sanitize_text_field($data['transaction_id'] ?? ''),
            'notes'            => sanitize_textarea_field($data['notes'] ?? ''),
            'status'           => 'pending',
            'linked'           => 'no',
        );

        foreach ($fields as $key => $value) {
            update_post_meta($sponsorship_id, '_' . $key, $value);
        }

        // Handle screenshot upload
        if (!empty($_FILES['payment_screenshot'])) {
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');

            $attachment_id = media_handle_upload('payment_screenshot', $sponsorship_id);

            if (!is_wp_error($attachment_id)) {
                update_post_meta($sponsorship_id, '_payment_screenshot', $attachment_id);
            }
        }

        // Send notification to admin
        $admin_email = get_option('alhuffaz_admin_email', get_option('admin_email'));
        Helpers::send_notification(
            $admin_email,
            __('New Sponsorship Submission', 'al-huffaz-portal'),
            sprintf(
                __('A new sponsorship has been submitted by %s for %s. Please review it in the admin panel.', 'al-huffaz-portal'),
                $data['sponsor_name'],
                Helpers::format_currency($data['amount'])
            )
        );

        wp_send_json_success(array(
            'message' => __('Thank you! Your sponsorship request has been submitted and is pending approval.', 'al-huffaz-portal'),
            'id'      => $sponsorship_id,
        ));
    }

    /*
     * ========================================================================================
     * RECURRING PAYMENT / SUBSCRIPTION SYSTEM - IMPLEMENTATION NOTES
     * ========================================================================================
     *
     * CURRENT STATE:
     * - The system currently handles one-time payment submissions per sponsorship
     * - Each payment creates a 'sponsorship' post with duration_months (1, 3, 6, 12)
     * - Payments are manually submitted by sponsors with proof/screenshot
     * - School admin approves each payment submission individually
     *
     * SUBSCRIPTION SYSTEM REQUIREMENTS:
     *
     * 1. DATABASE SCHEMA CHANGES:
     *    - Add 'is_recurring' field to sponsorship post meta (yes/no)
     *    - Add 'next_payment_date' to track when next payment is due
     *    - Add 'payment_period_number' to track which payment period (1st month, 2nd month, etc.)
     *    - Add 'subscription_status' (active, paused, cancelled, completed)
     *    - Create new post type 'payment_record' to track individual payment submissions
     *      within a recurring sponsorship
     *
     * 2. PAYMENT GATEWAY INTEGRATION (RECOMMENDED):
     *    - Integrate Stripe Subscriptions API or PayPal Recurring Payments
     *    - Store customer_id and subscription_id from payment gateway
     *    - Handle webhook notifications for successful/failed payments
     *    - Automated charging vs manual recurring reminders
     *
     * 3. MANUAL RECURRING SYSTEM (CURRENT APPROACH):
     *    - Send email/in-app reminders before next_payment_date
     *    - Allow sponsors to submit payment proof for each period
     *    - Track payment status per period (paid, pending, overdue)
     *    - Option to pause or cancel recurring sponsorship
     *
     * 4. ADMIN PANEL ENHANCEMENTS:
     *    - View all recurring sponsorships with status
     *    - See payment history per sponsorship (all periods)
     *    - Handle overdue payments (send reminders, mark as at-risk)
     *    - Generate reports on recurring vs one-time sponsorships
     *
     * 5. SPONSOR DASHBOARD ENHANCEMENTS:
     *    - Show upcoming payment due dates
     *    - Quick payment submission for recurring sponsorships
     *    - Payment reminder notifications
     *    - Option to update payment method or amount
     *    - Pause/resume subscription functionality
     *
     * 6. AUTOMATED TASKS (WP-CRON):
     *    - Daily check for upcoming payments (7 days, 3 days, 1 day reminders)
     *    - Mark overdue payments and send notifications
     *    - Auto-complete subscriptions when duration is reached
     *
     * 7. NOTIFICATION SYSTEM:
     *    - Payment reminder emails (7 days before, 3 days before, on due date)
     *    - Overdue payment notifications
     *    - Successful payment confirmations
     *    - Subscription status changes (paused, resumed, cancelled)
     *
     * IMPLEMENTATION PRIORITY:
     * Phase 1: Manual recurring with reminders (no payment gateway)
     * Phase 2: Payment gateway integration for automated billing
     * Phase 3: Advanced subscription management features
     *
     * ========================================================================================
     */

    /**
     * Submit payment (public)
     */
    public function submit_payment() {
        $this->verify_public_nonce();

        $data = isset($_POST['data']) ? $_POST['data'] : array();

        // Validate
        if (empty($data['sponsorship_id']) || empty($data['amount'])) {
            wp_send_json_error(array('message' => __('Invalid payment data.', 'al-huffaz-portal')));
        }

        global $wpdb;
        $table = $wpdb->prefix . 'alhuffaz_payments';

        $sponsorship = get_post(intval($data['sponsorship_id']));

        if (!$sponsorship) {
            wp_send_json_error(array('message' => __('Invalid sponsorship.', 'al-huffaz-portal')));
        }

        $wpdb->insert($table, array(
            'sponsorship_id' => intval($data['sponsorship_id']),
            'sponsor_id'     => get_post_meta($sponsorship->ID, '_sponsor_user_id', true),
            'student_id'     => get_post_meta($sponsorship->ID, '_student_id', true),
            'amount'         => floatval($data['amount']),
            'payment_method' => sanitize_text_field($data['payment_method']),
            'transaction_id' => sanitize_text_field($data['transaction_id'] ?? ''),
            'payment_date'   => current_time('mysql'),
            'status'         => 'pending',
            'notes'          => sanitize_textarea_field($data['notes'] ?? ''),
        ));

        $payment_id = $wpdb->insert_id;

        // Handle screenshot
        if (!empty($_FILES['payment_screenshot'])) {
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');

            $attachment_id = media_handle_upload('payment_screenshot', 0);

            if (!is_wp_error($attachment_id)) {
                $wpdb->update($table, array('notes' => 'Screenshot: ' . $attachment_id), array('id' => $payment_id));
            }
        }

        // Send notification
        $admin_email = get_option('alhuffaz_admin_email', get_option('admin_email'));
        Helpers::send_notification(
            $admin_email,
            __('New Payment Submitted', 'al-huffaz-portal'),
            sprintf(__('A new payment of %s has been submitted. Please verify it in the admin panel.', 'al-huffaz-portal'), Helpers::format_currency($data['amount']))
        );

        wp_send_json_success(array(
            'message' => __('Payment submitted successfully. It will be verified shortly.', 'al-huffaz-portal'),
            'id'      => $payment_id,
        ));
    }

    /**
     * Submit payment proof for new sponsorship (public - from sponsor dashboard)
     */
    public function submit_payment_proof() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'alhuffaz_public_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'al-huffaz-portal')));
        }

        // Must be logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in.', 'al-huffaz-portal')));
        }

        $user_id = get_current_user_id();
        $user = wp_get_current_user();

        // Validate required fields
        $student_id = isset($_POST['student_id']) ? intval($_POST['student_id']) : 0;
        $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
        $payment_method = isset($_POST['payment_method']) ? sanitize_text_field($_POST['payment_method']) : '';
        $transaction_id = isset($_POST['transaction_id']) ? sanitize_text_field($_POST['transaction_id']) : '';
        $duration_months = isset($_POST['duration_months']) ? intval($_POST['duration_months']) : 1;
        $payment_date = isset($_POST['payment_date']) ? sanitize_text_field($_POST['payment_date']) : current_time('Y-m-d');
        $notes = isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '';

        // CRITICAL FIX: Calculate sponsorship_type based on duration_months (payment plan selected)
        $sponsorship_type_map = array(
            1  => 'monthly',
            3  => 'quarterly',
            6  => 'biannual',
            12 => 'yearly',
        );
        $sponsorship_type = isset($sponsorship_type_map[$duration_months]) ? $sponsorship_type_map[$duration_months] : 'monthly';

        // Transaction ID is optional - only validate student, amount, and payment method
        if (!$student_id || !$amount || !$payment_method) {
            wp_send_json_error(array('message' => __('Please fill in all required fields.', 'al-huffaz-portal')));
        }

        // If no transaction ID provided, generate a unique reference
        if (empty($transaction_id)) {
            $transaction_id = 'SP-' . strtoupper(substr(md5(uniqid()), 0, 10));
        }

        // Verify student exists and is eligible
        $student = get_post($student_id);
        if (!$student || $student->post_type !== 'student') {
            wp_send_json_error(array('message' => __('Invalid student selected.', 'al-huffaz-portal')));
        }

        // Check if student is eligible for donation
        $donation_eligible = get_post_meta($student_id, 'donation_eligible', true);
        if ($donation_eligible !== 'yes') {
            wp_send_json_error(array('message' => __('This student is not available for sponsorship.', 'al-huffaz-portal')));
        }

        // CRITICAL FIX: Use 'sponsorship' post type (not 'alhuffaz_sponsor') to match admin panel expectations
        $sponsorship_id = wp_insert_post(array(
            'post_type'   => 'sponsorship',
            'post_title'  => sprintf('%s - %s', $user->display_name, $student->post_title),
            'post_status' => 'draft', // Start as draft until admin verifies payment
            'post_author' => $user_id,
        ));

        if (is_wp_error($sponsorship_id)) {
            wp_send_json_error(array('message' => __('Failed to create sponsorship record.', 'al-huffaz-portal')));
        }

        // CRITICAL FIX: Use correct meta keys without underscore prefix to match admin panel
        update_post_meta($sponsorship_id, 'student_id', $student_id);
        update_post_meta($sponsorship_id, 'sponsor_user_id', $user_id);
        update_post_meta($sponsorship_id, 'sponsor_name', $user->display_name);
        update_post_meta($sponsorship_id, 'sponsor_email', $user->user_email);
        update_post_meta($sponsorship_id, 'amount', $amount);
        update_post_meta($sponsorship_id, 'duration_months', $duration_months); // Payment plan duration: 1, 3, 6, or 12 months
        update_post_meta($sponsorship_id, 'sponsorship_type', $sponsorship_type); // monthly, quarterly, biannual, yearly
        update_post_meta($sponsorship_id, 'payment_method', $payment_method);
        update_post_meta($sponsorship_id, 'transaction_id', $transaction_id);
        update_post_meta($sponsorship_id, 'payment_date', $payment_date);
        update_post_meta($sponsorship_id, 'verification_status', 'pending');
        update_post_meta($sponsorship_id, 'linked', 'no'); // Will be set to 'yes' when admin verifies
        update_post_meta($sponsorship_id, 'notes', $notes);
        update_post_meta($sponsorship_id, 'created_at', current_time('mysql'));

        // Handle payment screenshot upload
        if (!empty($_FILES['payment_screenshot']) && $_FILES['payment_screenshot']['error'] === UPLOAD_ERR_OK) {
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');

            $attachment_id = media_handle_upload('payment_screenshot', $sponsorship_id);

            if (!is_wp_error($attachment_id)) {
                update_post_meta($sponsorship_id, 'payment_screenshot', $attachment_id);
            }
        }

        // Log activity
        if (class_exists('AlHuffaz\\Core\\Helpers')) {
            Helpers::log_activity('new_sponsorship_request', 'sponsorship', $sponsorship_id,
                sprintf('New sponsorship request from %s for student %s - Amount: %s',
                    $user->display_name, $student->post_title, Helpers::format_currency($amount)
                )
            );
        }

        // Send notification to admin
        $admin_email = get_option('alhuffaz_admin_email', get_option('admin_email'));
        if (class_exists('AlHuffaz\\Core\\Helpers')) {
            Helpers::send_notification(
                $admin_email,
                __('New Sponsorship Payment Proof Submitted', 'al-huffaz-portal'),
                sprintf(
                    __('A new sponsorship payment proof has been submitted.

Sponsor: %s (%s)
Student: %s
Amount: %s
Plan: %s
Payment Method: %s
Transaction ID: %s

Please verify the payment in the admin portal.', 'al-huffaz-portal'),
                    $user->display_name,
                    $user->user_email,
                    $student->post_title,
                    Helpers::format_currency($amount),
                    ucfirst($sponsorship_type),
                    ucfirst(str_replace('_', ' ', $payment_method)),
                    $transaction_id
                )
            );
        }

        // Create in-app notification for sponsor
        if (class_exists('AlHuffaz\\Core\\Notifications')) {
            Notifications::create(
                $user_id,
                __('Payment Proof Submitted', 'al-huffaz-portal'),
                sprintf(
                    __('Your payment proof for %s has been submitted successfully. We will verify your payment within 24-48 hours and notify you once approved.', 'al-huffaz-portal'),
                    $student->post_title
                ),
                'success',
                $sponsorship_id,
                'sponsorship'
            );
        }

        // CRITICAL: Clear all caches to ensure fresh data on next load
        wp_cache_flush();
        wp_cache_delete('sponsor_dashboard_' . $user_id, 'alhuffaz');
        clean_post_cache($sponsorship_id);
        clean_post_cache($student_id);

        // Send no-cache headers
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');

        // Build dashboard redirect URL with success parameters and cache busting
        $dashboard_page = get_page_by_path('sponsor-dashboard');
        $redirect_url = home_url('/sponsor-dashboard/'); // Fallback

        if ($dashboard_page) {
            $redirect_url = add_query_arg(array(
                'payment_submitted' => 'success',
                'open_tab' => 'payments',
                'sponsorship_id' => $sponsorship_id,
                '_' => time() // Cache buster to force fresh data load
            ), get_permalink($dashboard_page->ID));
        } else {
            // Fallback with query params
            $redirect_url = add_query_arg(array(
                'payment_submitted' => 'success',
                'open_tab' => 'payments',
                'sponsorship_id' => $sponsorship_id,
                '_' => time()
            ), home_url('/sponsor-dashboard/'));
        }

        wp_send_json_success(array(
            'message' => __('Payment proof submitted successfully! Redirecting to your dashboard...', 'al-huffaz-portal'),
            'sponsorship_id' => $sponsorship_id,
            'redirect_url' => $redirect_url,
        ));
    }

    /**
     * Create sponsorship from dashboard plan selection (logged in sponsor only)
     */
    public function create_sponsorship() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'alhuffaz_public_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'al-huffaz-portal')));
        }

        // Must be logged in as sponsor
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in as a sponsor.', 'al-huffaz-portal')));
        }

        $user_id = get_current_user_id();
        $user = wp_get_current_user();

        // Check if user has sponsor role
        if (!in_array('alhuffaz_sponsor', $user->roles)) {
            wp_send_json_error(array('message' => __('Only sponsors can create sponsorships.', 'al-huffaz-portal')));
        }

        // Validate required fields
        $student_id = isset($_POST['student_id']) ? intval($_POST['student_id']) : 0;
        $duration = isset($_POST['duration']) ? intval($_POST['duration']) : 0;
        $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;

        if (!$student_id || !$duration || !$amount) {
            wp_send_json_error(array('message' => __('Missing required fields.', 'al-huffaz-portal')));
        }

        // Verify student exists and is eligible
        $student = get_post($student_id);
        if (!$student || $student->post_type !== 'student') {
            wp_send_json_error(array('message' => __('Invalid student selected.', 'al-huffaz-portal')));
        }

        // CRITICAL FIX: Check if student is already sponsored (use correct meta key)
        $is_sponsored = get_post_meta($student_id, 'already_sponsored', true);
        if ($is_sponsored === 'yes') {
            wp_send_json_error(array('message' => __('This student is already sponsored by someone else.', 'al-huffaz-portal')));
        }

        // Check if student is eligible for donation
        $donation_eligible = get_post_meta($student_id, 'donation_eligible', true);
        if ($donation_eligible !== 'yes') {
            wp_send_json_error(array('message' => __('This student is not available for sponsorship.', 'al-huffaz-portal')));
        }

        // Determine sponsorship type based on duration
        $sponsorship_type_map = array(
            1  => 'monthly',
            3  => 'quarterly',
            6  => 'biannual',
            12 => 'yearly',
        );
        $sponsorship_type = isset($sponsorship_type_map[$duration]) ? $sponsorship_type_map[$duration] : 'monthly';

        // Create sponsorship record
        $sponsorship_id = wp_insert_post(array(
            'post_type'   => 'alhuffaz_sponsor',
            'post_title'  => sprintf('%s - %s (%d months)', $user->display_name, $student->post_title, $duration),
            'post_status' => 'publish',
        ));

        if (is_wp_error($sponsorship_id)) {
            wp_send_json_error(array('message' => __('Failed to create sponsorship record.', 'al-huffaz-portal')));
        }

        // Save sponsorship meta
        update_post_meta($sponsorship_id, '_student_id', $student_id);
        update_post_meta($sponsorship_id, '_sponsor_user_id', $user_id);
        update_post_meta($sponsorship_id, '_sponsor_name', $user->display_name);
        update_post_meta($sponsorship_id, '_sponsor_email', $user->user_email);
        update_post_meta($sponsorship_id, '_sponsor_phone', get_user_meta($user_id, 'phone', true));
        update_post_meta($sponsorship_id, '_sponsor_country', get_user_meta($user_id, 'country', true));
        update_post_meta($sponsorship_id, '_amount', $amount);
        update_post_meta($sponsorship_id, '_duration_months', $duration);
        update_post_meta($sponsorship_id, '_sponsorship_type', $sponsorship_type);
        update_post_meta($sponsorship_id, '_status', 'pending');
        update_post_meta($sponsorship_id, '_linked', 'no');
        update_post_meta($sponsorship_id, '_created_at', current_time('mysql'));
        update_post_meta($sponsorship_id, '_payment_pending', 'yes'); // Mark as awaiting payment proof

        // Log activity
        if (class_exists('AlHuffaz\\Core\\Helpers')) {
            Helpers::log_activity('new_sponsorship_created', 'sponsorship', $sponsorship_id,
                sprintf('Sponsorship created by %s for student %s - %d months plan - Amount: %s',
                    $user->display_name, $student->post_title, $duration, Helpers::format_currency($amount)
                )
            );
        }

        // Send notification to admin
        $admin_email = get_option('alhuffaz_admin_email', get_option('admin_email'));
        if (class_exists('AlHuffaz\\Core\\Helpers')) {
            Helpers::send_notification(
                $admin_email,
                __('New Sponsorship Created - Payment Pending', 'al-huffaz-portal'),
                sprintf(
                    __('A new sponsorship has been created and is awaiting payment proof.

Sponsor: %s (%s)
Student: %s
Duration: %d months
Amount: %s

The sponsor needs to submit payment proof to complete the sponsorship.', 'al-huffaz-portal'),
                    $user->display_name,
                    $user->user_email,
                    $student->post_title,
                    $duration,
                    Helpers::format_currency($amount)
                )
            );
        }

        // Send notification to sponsor with payment instructions
        if (class_exists('AlHuffaz\\Core\\Helpers')) {
            Helpers::send_notification(
                $user->user_email,
                __('Sponsorship Created - Payment Required', 'al-huffaz-portal'),
                sprintf(
                    __('Thank you for choosing to sponsor %s!

Your sponsorship has been created successfully. Please submit your payment proof to complete the process.

Duration: %d months
Amount: %s

You can submit payment proof from your dashboard. Once verified by the school, your sponsorship will be activated.

Thank you for your generosity!', 'al-huffaz-portal'),
                    $student->post_title,
                    $duration,
                    Helpers::format_currency($amount)
                )
            );
        }

        wp_send_json_success(array(
            'message' => __('Sponsorship created successfully! Please submit payment proof to complete the process.', 'al-huffaz-portal'),
            'sponsorship_id' => $sponsorship_id,
            'redirect' => 'payment', // Tell frontend to redirect to payment panel
        ));
    }

    /**
     * Cancel sponsorship (from sponsor dashboard)
     */
    public function cancel_sponsorship() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'alhuffaz_public_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'al-huffaz-portal')));
        }

        // Must be logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in.', 'al-huffaz-portal')));
        }

        $user_id = get_current_user_id();
        $sponsorship_id = isset($_POST['sponsorship_id']) ? intval($_POST['sponsorship_id']) : 0;

        if (!$sponsorship_id) {
            wp_send_json_error(array('message' => __('Invalid sponsorship ID.', 'al-huffaz-portal')));
        }

        // CRITICAL FIX: Verify sponsorship exists (use correct post type)
        $sponsorship = get_post($sponsorship_id);
        if (!$sponsorship || $sponsorship->post_type !== 'sponsorship') {
            wp_send_json_error(array('message' => __('Sponsorship not found.', 'al-huffaz-portal')));
        }

        // Verify ownership - user must be the sponsor (use correct meta key)
        $sponsor_user_id = get_post_meta($sponsorship_id, 'sponsor_user_id', true);
        if ($sponsor_user_id != $user_id) {
            wp_send_json_error(array('message' => __('You do not have permission to cancel this sponsorship.', 'al-huffaz-portal')));
        }

        $student_id = get_post_meta($sponsorship_id, 'student_id', true);
        $student = get_post($student_id);
        $student_name = $student ? $student->post_title : 'Unknown';

        // CRITICAL FIX: Update sponsorship status to cancelled (use correct meta keys)
        update_post_meta($sponsorship_id, 'verification_status', 'cancelled');
        update_post_meta($sponsorship_id, 'linked', 'no');
        update_post_meta($sponsorship_id, 'cancelled_at', current_time('mysql'));
        update_post_meta($sponsorship_id, 'cancelled_by', 'sponsor');

        // Move to trash
        wp_update_post(array(
            'ID' => $sponsorship_id,
            'post_status' => 'trash'
        ));

        // Make student available again (use correct meta key)
        delete_post_meta($student_id, 'already_sponsored');
        delete_post_meta($student_id, 'sponsored_date');

        // Check if there are any other active sponsorships for this student
        $other_active_sponsorships = get_posts(array(
            'post_type' => 'sponsorship',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'post__not_in' => array($sponsorship_id),
            'meta_query' => array(
                'relation' => 'AND',
                array('key' => 'student_id', 'value' => $student_id),
                array('key' => 'linked', 'value' => 'yes'),
            ),
            'fields' => 'ids',
        ));

        // If there are other active sponsorships, keep student as sponsored
        if (!empty($other_active_sponsorships)) {
            update_post_meta($student_id, 'already_sponsored', 'yes');
            // Find the earliest sponsored date from remaining sponsorships
            foreach ($other_active_sponsorships as $other_sp_id) {
                $other_date = get_post_meta($other_sp_id, 'approved_date', true);
                if ($other_date) {
                    update_post_meta($student_id, 'sponsored_date', $other_date);
                    break;
                }
            }
        }

        // Log activity
        if (class_exists('AlHuffaz\\Core\\Helpers')) {
            $user = wp_get_current_user();
            Helpers::log_activity('sponsorship_cancelled', 'sponsorship', $sponsorship_id,
                sprintf('Sponsorship cancelled by sponsor %s for student %s', $user->display_name, $student_name)
            );
        }

        // Send notification to admin
        $admin_email = get_option('alhuffaz_admin_email', get_option('admin_email'));
        if (class_exists('AlHuffaz\\Core\\Helpers')) {
            $user = wp_get_current_user();
            Helpers::send_notification(
                $admin_email,
                __('Sponsorship Cancelled by Sponsor', 'al-huffaz-portal'),
                sprintf(
                    __('A sponsorship has been cancelled.

Sponsor: %s (%s)
Student: %s
Cancelled Date: %s

The student is now available for sponsorship again.', 'al-huffaz-portal'),
                    $user->display_name,
                    $user->user_email,
                    $student_name,
                    current_time('F j, Y g:i a')
                )
            );
        }

        // Create in-app notification for sponsor
        if (class_exists('AlHuffaz\\Core\\Notifications')) {
            Notifications::create(
                $user_id,
                __('Sponsorship Cancelled', 'al-huffaz-portal'),
                sprintf(
                    __('Your sponsorship for %s has been cancelled. The student is now available for other sponsors. Thank you for your past support.', 'al-huffaz-portal'),
                    $student_name
                ),
                'info',
                $sponsorship_id,
                'sponsorship'
            );
        }

        // CRITICAL: Clear all caches to ensure fresh data
        wp_cache_flush();
        wp_cache_delete('sponsor_dashboard_' . $user_id, 'alhuffaz');
        clean_post_cache($sponsorship_id);
        clean_post_cache($student_id);

        // Send no-cache headers
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');

        // Build dashboard redirect URL with cache busting
        $dashboard_page = get_page_by_path('sponsor-dashboard');
        $redirect_url = home_url('/sponsor-dashboard/'); // Fallback

        if ($dashboard_page) {
            $redirect_url = add_query_arg(array(
                'cancellation' => 'success',
                'open_tab' => 'sponsorships',
                '_' => time() // Cache buster
            ), get_permalink($dashboard_page->ID));
        } else {
            // Fallback with query params
            $redirect_url = add_query_arg(array(
                'cancellation' => 'success',
                'open_tab' => 'sponsorships',
                '_' => time()
            ), home_url('/sponsor-dashboard/'));
        }

        wp_send_json_success(array(
            'message' => sprintf(__('Sponsorship for %s has been cancelled. The student is now available for others to sponsor.', 'al-huffaz-portal'), $student_name),
            'redirect_url' => $redirect_url,
        ));
    }

    /**
     * Get student profile for sponsor (public - from sponsor dashboard)
     */
    public function get_student_profile() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'alhuffaz_public_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'al-huffaz-portal')));
        }

        // Must be logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in.', 'al-huffaz-portal')));
        }

        $user_id = get_current_user_id();
        $student_id = isset($_POST['student_id']) ? intval($_POST['student_id']) : 0;

        if (!$student_id) {
            wp_send_json_error(array('message' => __('Invalid student ID.', 'al-huffaz-portal')));
        }

        // Verify sponsor has access to this student (must have approved, linked sponsorship)
        $sponsorships = get_posts(array(
            'post_type'      => 'alhuffaz_sponsor',
            'posts_per_page' => 1,
            'post_status'    => 'publish',
            'meta_query'     => array(
                'relation' => 'AND',
                array(
                    'key'   => '_sponsor_user_id',
                    'value' => $user_id,
                ),
                array(
                    'key'   => '_student_id',
                    'value' => $student_id,
                ),
                array(
                    'key'   => '_status',
                    'value' => 'approved',
                ),
                array(
                    'key'   => '_linked',
                    'value' => 'yes',
                ),
            ),
        ));

        if (empty($sponsorships)) {
            wp_send_json_error(array('message' => __('You do not have access to this student profile.', 'al-huffaz-portal')));
        }

        // Get student data
        $student = get_post($student_id);
        if (!$student || $student->post_type !== 'student') {
            wp_send_json_error(array('message' => __('Student not found.', 'al-huffaz-portal')));
        }

        // Get student photo
        $photo_id = get_post_meta($student_id, 'student_photo', true);
        $photo_url = $photo_id ? wp_get_attachment_image_url($photo_id, 'medium') : '';

        // Get basic info
        $profile = array(
            'id' => $student_id,
            'name' => $student->post_title,
            'photo' => $photo_url,
            'gr_number' => get_post_meta($student_id, 'gr_number', true),
            'roll_number' => get_post_meta($student_id, 'roll_number', true),
            'gender' => get_post_meta($student_id, 'gender', true),
            'date_of_birth' => get_post_meta($student_id, 'date_of_birth', true),
            'grade_level' => get_post_meta($student_id, 'grade_level', true),
            'islamic_studies_category' => get_post_meta($student_id, 'islamic_studies_category', true),
            'academic_year' => get_post_meta($student_id, 'academic_year', true),
            'academic_term' => get_post_meta($student_id, 'academic_term', true),
        );

        // Get attendance
        $total_days = get_post_meta($student_id, 'total_school_days', true);
        $present_days = get_post_meta($student_id, 'present_days', true);
        $profile['attendance'] = array(
            'total_days' => intval($total_days) ?: 0,
            'present_days' => intval($present_days) ?: 0,
            'percentage' => $total_days > 0 ? round(($present_days / $total_days) * 100, 1) : 0,
        );

        // Get subjects with grades
        $subjects = get_post_meta($student_id, 'subjects', true);
        $profile['subjects'] = is_array($subjects) ? $subjects : array();

        // Get goals
        $profile['goals'] = array(
            get_post_meta($student_id, 'goal_1', true),
            get_post_meta($student_id, 'goal_2', true),
            get_post_meta($student_id, 'goal_3', true),
        );
        $profile['goals'] = array_filter($profile['goals']); // Remove empty goals

        // Get teacher comments
        $profile['teacher_comments'] = get_post_meta($student_id, 'teacher_overall_comments', true);

        // Get behavior/performance ratings
        $profile['ratings'] = array(
            'health' => intval(get_post_meta($student_id, 'health_rating', true)),
            'cleanness' => intval(get_post_meta($student_id, 'cleanness_rating', true)),
            'homework' => intval(get_post_meta($student_id, 'completes_homework', true)),
            'participation' => intval(get_post_meta($student_id, 'participates_in_class', true)),
            'teamwork' => intval(get_post_meta($student_id, 'works_well_in_groups', true)),
            'problem_solving' => intval(get_post_meta($student_id, 'problem_solving_skills', true)),
            'organization' => intval(get_post_meta($student_id, 'organization_preparedness', true)),
        );

        // Get sponsorship details
        $sponsorship = $sponsorships[0];
        $profile['sponsorship'] = array(
            'type' => get_post_meta($sponsorship->ID, '_sponsorship_type', true),
            'amount' => floatval(get_post_meta($sponsorship->ID, '_amount', true)),
            'start_date' => get_post_meta($sponsorship->ID, '_created_at', true) ?: $sponsorship->post_date,
        );

        wp_send_json_success(array(
            'profile' => $profile,
        ));
    }

    /**
     * Get available students (public)
     */
    public function get_available_students() {
        $this->verify_public_nonce();

        // CRITICAL FIX: Query students that are donation_eligible AND not already sponsored
        $args = array(
            'post_type'      => 'student',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'meta_query'     => array(
                'relation' => 'AND',
                // Must be donation eligible
                array(
                    'key'     => 'donation_eligible',
                    'value'   => 'yes',
                ),
                // Must NOT be already sponsored
                array(
                    'relation' => 'OR',
                    array(
                        'key'     => 'already_sponsored',
                        'value'   => 'yes',
                        'compare' => '!=',
                    ),
                    array(
                        'key'     => 'already_sponsored',
                        'compare' => 'NOT EXISTS',
                    ),
                ),
            ),
        );

        $query = new \WP_Query($args);

        $students = array();

        foreach ($query->posts as $post) {
            // Get all fee components for proper calculation
            $monthly_tuition = floatval(get_post_meta($post->ID, 'monthly_tuition_fee', true)) ?: 0;
            $course_fee = floatval(get_post_meta($post->ID, 'course_fee', true)) ?: 0;
            $uniform_fee = floatval(get_post_meta($post->ID, 'uniform_fee', true)) ?: 0;
            $annual_fee = floatval(get_post_meta($post->ID, 'annual_fee', true)) ?: 0;
            $admission_fee = floatval(get_post_meta($post->ID, 'admission_fee', true)) ?: 0;

            // Calculate one-time fees total
            $one_time_fees = $course_fee + $uniform_fee + $annual_fee + $admission_fee;

            $students[] = array(
                'id'              => $post->ID,
                'name'            => $post->post_title,
                'grade'           => Helpers::get_grade_label(get_post_meta($post->ID, 'grade_level', true)),
                'category'        => Helpers::get_islamic_category_label(get_post_meta($post->ID, 'islamic_studies_category', true)),
                'photo'           => Helpers::get_student_photo($post->ID, 'medium'),
                'monthly_fee'     => $monthly_tuition,
                'course_fee'      => $course_fee,
                'uniform_fee'     => $uniform_fee,
                'annual_fee'      => $annual_fee,
                'admission_fee'   => $admission_fee,
                'one_time_total'  => $one_time_fees,
            );
        }

        wp_send_json_success(array('students' => $students));
    }

    /**
     * Upload image
     */
    public function upload_image() {
        $this->verify_admin_nonce();

        if (empty($_FILES['file'])) {
            wp_send_json_error(array('message' => __('No file uploaded.', 'al-huffaz-portal')));
        }

        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $attachment_id = media_handle_upload('file', 0);

        if (is_wp_error($attachment_id)) {
            wp_send_json_error(array('message' => $attachment_id->get_error_message()));
        }

        wp_send_json_success(array(
            'id'  => $attachment_id,
            'url' => wp_get_attachment_url($attachment_id),
        ));
    }

    /**
     * Export data
     */
    public function export_data() {
        $this->verify_admin_nonce();

        if (!current_user_can('alhuffaz_view_reports')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'al-huffaz-portal')));
        }

        $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : 'students';

        $data = array();

        if ($type === 'students') {
            $students = get_posts(array(
                'post_type'      => 'student',
                'posts_per_page' => -1,
                'post_status'    => 'publish',
            ));

            foreach ($students as $student) {
                $data[] = array(
                    'ID'             => $student->ID,
                    'Name'           => $student->post_title,
                    'GR Number'      => get_post_meta($student->ID, '_gr_number', true),
                    'Grade'          => get_post_meta($student->ID, '_grade_level', true),
                    'Gender'         => get_post_meta($student->ID, '_gender', true),
                    'Father Name'    => get_post_meta($student->ID, '_father_name', true),
                    'Phone'          => get_post_meta($student->ID, '_guardian_phone', true),
                    'Is Sponsored'   => get_post_meta($student->ID, 'already_sponsored', true),
                );
            }
        }

        wp_send_json_success(array('data' => $data));
    }

    /**
     * Bulk import
     */
    public function bulk_import() {
        $this->verify_admin_nonce();

        if (!current_user_can('alhuffaz_bulk_import')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'al-huffaz-portal')));
        }

        if (empty($_FILES['csv_file'])) {
            wp_send_json_error(array('message' => __('No file uploaded.', 'al-huffaz-portal')));
        }

        $file = $_FILES['csv_file']['tmp_name'];

        if (($handle = fopen($file, 'r')) === false) {
            wp_send_json_error(array('message' => __('Failed to read file.', 'al-huffaz-portal')));
        }

        $headers = fgetcsv($handle);
        $imported = 0;
        $errors = array();

        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($headers, $row);

            if (empty($data['student_name'])) {
                $errors[] = 'Row missing student name';
                continue;
            }

            $student_id = wp_insert_post(array(
                'post_type'   => 'student',
                'post_title'  => sanitize_text_field($data['student_name']),
                'post_status' => 'publish',
            ));

            if (is_wp_error($student_id)) {
                $errors[] = $student_id->get_error_message();
                continue;
            }

            // Save meta
            foreach ($data as $key => $value) {
                if ($key !== 'student_name' && !empty($value)) {
                    update_post_meta($student_id, '_' . $key, sanitize_text_field($value));
                }
            }

            $imported++;
        }

        fclose($handle);

        wp_send_json_success(array(
            'message'  => sprintf(__('Imported %d students.', 'al-huffaz-portal'), $imported),
            'imported' => $imported,
            'errors'   => $errors,
        ));
    }

    /**
     * Get staff users
     */
    public function get_staff_users() {
        $this->verify_admin_nonce();

        // Only admins can manage staff
        if (!Roles::can_manage_staff()) {
            wp_send_json_error(array('message' => __('Permission denied.', 'al-huffaz-portal')));
        }

        $staff_users = Roles::get_staff_users();
        $users_data = array();

        foreach ($staff_users as $user) {
            $users_data[] = array(
                'id'           => $user->ID,
                'display_name' => $user->display_name,
                'email'        => $user->user_email,
                'registered'   => date_i18n(get_option('date_format'), strtotime($user->user_registered)),
                'avatar'       => get_avatar_url($user->ID, array('size' => 40)),
            );
        }

        wp_send_json_success(array(
            'staff' => $users_data,
            'count' => count($users_data),
        ));
    }

    /**
     * Get eligible users for staff role
     */
    public function get_eligible_users() {
        $this->verify_admin_nonce();

        // Only admins can manage staff
        if (!Roles::can_manage_staff()) {
            wp_send_json_error(array('message' => __('Permission denied.', 'al-huffaz-portal')));
        }

        $eligible_users = Roles::get_eligible_staff_users();
        $users_data = array();

        foreach ($eligible_users as $user) {
            $users_data[] = array(
                'id'           => $user->ID,
                'display_name' => $user->display_name,
                'email'        => $user->user_email,
                'registered'   => date_i18n(get_option('date_format'), strtotime($user->user_registered)),
            );
        }

        wp_send_json_success(array(
            'users' => $users_data,
            'count' => count($users_data),
        ));
    }

    /**
     * Grant staff role to a user
     */
    public function grant_staff_role() {
        $this->verify_admin_nonce();

        // Only admins can manage staff
        if (!Roles::can_manage_staff()) {
            wp_send_json_error(array('message' => __('Permission denied.', 'al-huffaz-portal')));
        }

        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;

        if (!$user_id) {
            wp_send_json_error(array('message' => __('Invalid user ID.', 'al-huffaz-portal')));
        }

        $user = get_user_by('id', $user_id);
        if (!$user) {
            wp_send_json_error(array('message' => __('User not found.', 'al-huffaz-portal')));
        }

        // Don't allow promoting admins
        if (user_can($user_id, 'manage_options')) {
            wp_send_json_error(array('message' => __('Cannot modify administrator accounts.', 'al-huffaz-portal')));
        }

        $result = Roles::grant_staff_role($user_id);

        if ($result) {
            wp_send_json_success(array(
                'message' => sprintf(__('%s has been granted staff access.', 'al-huffaz-portal'), $user->display_name),
            ));
        } else {
            wp_send_json_error(array('message' => __('Failed to grant staff role.', 'al-huffaz-portal')));
        }
    }

    /**
     * Revoke staff role from a user
     */
    public function revoke_staff_role() {
        $this->verify_admin_nonce();

        // Only admins can manage staff
        if (!Roles::can_manage_staff()) {
            wp_send_json_error(array('message' => __('Permission denied.', 'al-huffaz-portal')));
        }

        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;

        if (!$user_id) {
            wp_send_json_error(array('message' => __('Invalid user ID.', 'al-huffaz-portal')));
        }

        $user = get_user_by('id', $user_id);
        if (!$user) {
            wp_send_json_error(array('message' => __('User not found.', 'al-huffaz-portal')));
        }

        // Don't allow demoting self
        if ($user_id === get_current_user_id()) {
            wp_send_json_error(array('message' => __('Cannot remove your own staff access.', 'al-huffaz-portal')));
        }

        $result = Roles::revoke_staff_role($user_id);

        if ($result) {
            wp_send_json_success(array(
                'message' => sprintf(__('%s staff access has been revoked.', 'al-huffaz-portal'), $user->display_name),
            ));
        } else {
            wp_send_json_error(array('message' => __('Failed to revoke staff role.', 'al-huffaz-portal')));
        }
    }

    /**
     * Get sponsor users with filtering and search
     */
    public function get_sponsor_users() {
        $this->verify_admin_nonce();

        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';

        // Get all users with sponsor role
        $args = array(
            'role' => 'alhuffaz_sponsor',
            'orderby' => 'registered',
            'order' => 'DESC',
        );

        if ($search) {
            $args['search'] = '*' . $search . '*';
            $args['search_columns'] = array('user_login', 'user_email', 'display_name');
        }

        $users = get_users($args);
        $users_data = array();

        foreach ($users as $user) {
            // Get user meta
            $um_status = get_user_meta($user->ID, 'account_status', true);
            $phone = get_user_meta($user->ID, 'sponsor_phone', true);
            $country = get_user_meta($user->ID, 'sponsor_country', true);

            // Count active sponsorships (approved + linked)
            $active_sponsorships = get_posts(array(
                'post_type' => 'alhuffaz_sponsor',
                'posts_per_page' => -1,
                'meta_query' => array(
                    'relation' => 'AND',
                    array('key' => '_sponsor_user_id', 'value' => $user->ID),
                    array('key' => '_status', 'value' => 'approved'),
                    array('key' => '_linked', 'value' => 'yes'),
                ),
                'fields' => 'ids',
            ));

            // Count total sponsorships
            $total_sponsorships = get_posts(array(
                'post_type' => 'alhuffaz_sponsor',
                'posts_per_page' => -1,
                'meta_query' => array(
                    array('key' => '_sponsor_user_id', 'value' => $user->ID),
                ),
                'fields' => 'ids',
            ));

            $active_count = count($active_sponsorships);
            $total_count = count($total_sponsorships);

            // Determine status
            $user_status = 'approved';
            if ($um_status === 'pending_approval') {
                $user_status = 'pending';
            } elseif ($um_status === 'rejected') {
                $user_status = 'rejected';
            } elseif ($active_count === 0 && $total_count === 0 && $um_status === 'approved') {
                $user_status = 'inactive';
            }

            // Apply status filter
            if ($status && $user_status !== $status) {
                continue;
            }

            $users_data[] = array(
                'id' => $user->ID,
                'username' => $user->user_login,
                'display_name' => $user->display_name,
                'email' => $user->user_email,
                'phone' => $phone,
                'country' => $country,
                'status' => $user_status,
                'active_sponsorships' => $active_count,
                'total_sponsorships' => $total_count,
                'registered' => date('M d, Y', strtotime($user->user_registered)),
            );
        }

        // Calculate stats
        $stats = array(
            'pending' => 0,
            'active' => 0,
            'inactive' => 0,
            'total' => count($users),
        );

        foreach ($users_data as $u) {
            if ($u['status'] === 'pending') $stats['pending']++;
            elseif ($u['status'] === 'inactive') $stats['inactive']++;
            else $stats['active']++;
        }

        wp_send_json_success(array(
            'users' => $users_data,
            'stats' => $stats,
        ));
    }

    /**
     * Get sponsor user details
     */
    public function get_sponsor_user_details() {
        $this->verify_admin_nonce();

        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        if (!$user_id) {
            wp_send_json_error(array('message' => __('Invalid user ID.', 'al-huffaz-portal')));
        }

        $user = get_userdata($user_id);
        if (!$user) {
            wp_send_json_error(array('message' => __('User not found.', 'al-huffaz-portal')));
        }

        $um_status = get_user_meta($user_id, 'account_status', true);
        $phone = get_user_meta($user_id, 'sponsor_phone', true);
        $country = get_user_meta($user_id, 'sponsor_country', true);
        $whatsapp = get_user_meta($user_id, 'sponsor_whatsapp', true);

        // Count sponsorships
        $active_sponsorships = get_posts(array(
            'post_type' => 'alhuffaz_sponsor',
            'posts_per_page' => -1,
            'meta_query' => array(
                'relation' => 'AND',
                array('key' => '_sponsor_user_id', 'value' => $user_id),
                array('key' => '_status', 'value' => 'approved'),
                array('key' => '_linked', 'value' => 'yes'),
            ),
            'fields' => 'ids',
        ));

        // Calculate total donated
        global $wpdb;
        $payments_table = $wpdb->prefix . 'alhuffaz_payments';
        $total_donated = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(amount) FROM $payments_table WHERE sponsor_id = %d AND status = 'approved'",
            $user_id
        ));

        // Determine user status
        $user_status = 'approved';
        if ($um_status === 'pending_approval') {
            $user_status = 'pending';
        } elseif ($um_status === 'rejected') {
            $user_status = 'rejected';
        }

        wp_send_json_success(array(
            'display_name' => $user->display_name,
            'email' => $user->user_email,
            'phone' => $phone,
            'country' => $country,
            'whatsapp' => $whatsapp,
            'status' => $user_status,
            'active_sponsorships' => count($active_sponsorships),
            'total_donated' => Helpers::format_currency($total_donated ?: 0),
            'registered' => date('F j, Y', strtotime($user->user_registered)),
        ));
    }

    /**
     * Approve sponsor user
     */
    public function approve_sponsor_user() {
        $this->verify_admin_nonce();

        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        if (!$user_id) {
            wp_send_json_error(array('message' => __('Invalid user ID.', 'al-huffaz-portal')));
        }

        $user = get_userdata($user_id);
        if (!$user) {
            wp_send_json_error(array('message' => __('User not found.', 'al-huffaz-portal')));
        }

        // Update UM status
        update_user_meta($user_id, 'account_status', 'approved');
        update_user_meta($user_id, 'account_status_date', current_time('mysql'));

        // Log activity
        if (class_exists('AlHuffaz\\Core\\Helpers')) {
            Helpers::log_activity('sponsor_user_approved', 'user', $user_id,
                sprintf('Sponsor user account approved: %s', $user->user_email)
            );
        }

        // Send approval email
        if (class_exists('AlHuffaz\\Core\\Helpers')) {
            Helpers::send_notification(
                $user->user_email,
                __('Your Sponsor Account Has Been Approved', 'al-huffaz-portal'),
                sprintf(
                    __('Congratulations! Your sponsor account has been approved.

You can now log in and start sponsoring students who need your support.

Login at: %s

Thank you for your generosity!', 'al-huffaz-portal'),
                    home_url('/login/?approved=yes')
                )
            );
        }

        wp_send_json_success(array(
            'message' => sprintf(__('Sponsor user %s has been approved.', 'al-huffaz-portal'), $user->display_name),
        ));
    }

    /**
     * Reject sponsor user
     */
    public function reject_sponsor_user() {
        $this->verify_admin_nonce();

        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $reason = isset($_POST['reason']) ? sanitize_textarea_field($_POST['reason']) : '';

        if (!$user_id) {
            wp_send_json_error(array('message' => __('Invalid user ID.', 'al-huffaz-portal')));
        }

        $user = get_userdata($user_id);
        if (!$user) {
            wp_send_json_error(array('message' => __('User not found.', 'al-huffaz-portal')));
        }

        // Update UM status
        update_user_meta($user_id, 'account_status', 'rejected');
        update_user_meta($user_id, 'rejection_reason', $reason);
        update_user_meta($user_id, 'account_status_date', current_time('mysql'));

        // Log activity
        if (class_exists('AlHuffaz\\Core\\Helpers')) {
            Helpers::log_activity('sponsor_user_rejected', 'user', $user_id,
                sprintf('Sponsor user account rejected: %s - Reason: %s', $user->user_email, $reason)
            );
        }

        // Send rejection email
        if (class_exists('AlHuffaz\\Core\\Helpers')) {
            Helpers::send_notification(
                $user->user_email,
                __('Your Sponsor Account Application', 'al-huffaz-portal'),
                sprintf(
                    __('Thank you for your interest in sponsoring students.

Unfortunately, we are unable to approve your account at this time.

%s

If you have any questions, please contact us.', 'al-huffaz-portal'),
                    $reason ? "Reason: $reason" : ''
                )
            );
        }

        wp_send_json_success(array(
            'message' => sprintf(__('Sponsor user %s has been rejected.', 'al-huffaz-portal'), $user->display_name),
        ));
    }

    /**
     * Delete sponsor user
     */
    public function delete_sponsor_user() {
        $this->verify_admin_nonce();

        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        if (!$user_id) {
            wp_send_json_error(array('message' => __('Invalid user ID.', 'al-huffaz-portal')));
        }

        $user = get_userdata($user_id);
        if (!$user) {
            wp_send_json_error(array('message' => __('User not found.', 'al-huffaz-portal')));
        }

        // Check if user has active sponsorships
        $active_sponsorships = get_posts(array(
            'post_type' => 'alhuffaz_sponsor',
            'posts_per_page' => -1,
            'meta_query' => array(
                'relation' => 'AND',
                array('key' => '_sponsor_user_id', 'value' => $user_id),
                array('key' => '_status', 'value' => 'approved'),
                array('key' => '_linked', 'value' => 'yes'),
            ),
            'fields' => 'ids',
        ));

        if (!empty($active_sponsorships)) {
            wp_send_json_error(array(
                'message' => __('Cannot delete user with active sponsorships. Please cancel all sponsorships first.', 'al-huffaz-portal')
            ));
        }

        // Delete all sponsorship records
        $all_sponsorships = get_posts(array(
            'post_type' => 'alhuffaz_sponsor',
            'posts_per_page' => -1,
            'meta_query' => array(
                array('key' => '_sponsor_user_id', 'value' => $user_id),
            ),
            'fields' => 'ids',
        ));

        foreach ($all_sponsorships as $sponsorship_id) {
            wp_delete_post($sponsorship_id, true);
        }

        // Delete payments
        global $wpdb;
        $payments_table = $wpdb->prefix . 'alhuffaz_payments';
        $wpdb->delete($payments_table, array('sponsor_id' => $user_id));

        // Log activity before deleting
        if (class_exists('AlHuffaz\\Core\\Helpers')) {
            Helpers::log_activity('sponsor_user_deleted', 'user', $user_id,
                sprintf('Sponsor user account deleted: %s (%s)', $user->display_name, $user->user_email)
            );
        }

        // Delete user
        require_once(ABSPATH . 'wp-admin/includes/user.php');
        $result = wp_delete_user($user_id);

        if ($result) {
            wp_send_json_success(array(
                'message' => sprintf(__('Sponsor user %s has been deleted.', 'al-huffaz-portal'), $user->display_name),
            ));
        } else {
            wp_send_json_error(array('message' => __('Failed to delete user.', 'al-huffaz-portal')));
        }
    }

    /**
     * Send re-engagement email to inactive sponsor
     */
    public function send_reengagement_email() {
        $this->verify_admin_nonce();

        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        if (!$user_id) {
            wp_send_json_error(array('message' => __('Invalid user ID.', 'al-huffaz-portal')));
        }

        $user = get_userdata($user_id);
        if (!$user) {
            wp_send_json_error(array('message' => __('User not found.', 'al-huffaz-portal')));
        }

        // Count available students
        // CRITICAL FIX: Query available students using correct meta keys
        $available_students = get_posts(array(
            'post_type' => 'student',
            'posts_per_page' => -1,
            'meta_query' => array(
                'relation' => 'AND',
                array('key' => 'donation_eligible', 'value' => 'yes'),
                array(
                    'relation' => 'OR',
                    array('key' => 'already_sponsored', 'value' => 'yes', 'compare' => '!='),
                    array('key' => 'already_sponsored', 'compare' => 'NOT EXISTS'),
                ),
            ),
            'fields' => 'ids',
        ));

        // Send re-engagement email
        if (class_exists('AlHuffaz\\Core\\Helpers')) {
            $school_name = get_option('alhuffaz_school_name', 'Al-Huffaz Islamic School');
            Helpers::send_notification(
                $user->user_email,
                __('Make a Difference Today - Sponsor a Student', 'al-huffaz-portal'),
                sprintf(
                    __('Dear %s,

We hope this message finds you well!

We noticed that you haven\'t sponsored a student recently. We currently have %d students who are waiting for generous sponsors like you to help them continue their education.

Your support can truly change a life. Every contribution helps a child gain access to quality Islamic education and build a brighter future.

Would you consider sponsoring a student today?

Visit your dashboard to browse available students: %s

Thank you for being part of the %s family!

With gratitude,
%s Team', 'al-huffaz-portal'),
                    $user->display_name,
                    count($available_students),
                    home_url('/sponsor-dashboard'),
                    $school_name,
                    $school_name
                )
            );
        }

        // Log activity
        if (class_exists('AlHuffaz\\Core\\Helpers')) {
            Helpers::log_activity('reengagement_email_sent', 'user', $user_id,
                sprintf('Re-engagement email sent to: %s', $user->user_email)
            );
        }

        wp_send_json_success(array(
            'message' => sprintf(__('Re-engagement email sent to %s.', 'al-huffaz-portal'), $user->display_name),
        ));
    }

    /**
     * Get notifications for current user
     */
    public function get_notifications() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'alhuffaz_public_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'al-huffaz-portal')));
        }

        // Must be logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in.', 'al-huffaz-portal')));
        }

        $user_id = get_current_user_id();
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 10;
        $unread_only = isset($_POST['unread_only']) && $_POST['unread_only'] === 'true';

        // Get notifications using Notifications class
        if (!class_exists('AlHuffaz\\Core\\Notifications')) {
            wp_send_json_error(array('message' => __('Notifications system not available.', 'al-huffaz-portal')));
        }

        $notifications = Notifications::get_user_notifications($user_id, $limit, $unread_only);
        $unread_count = Notifications::get_unread_count($user_id);

        // Format notifications for response
        $formatted_notifications = array();
        foreach ($notifications as $notification) {
            $formatted_notifications[] = array(
                'id' => $notification->id,
                'title' => $notification->title,
                'message' => $notification->message,
                'type' => $notification->type,
                'related_id' => $notification->related_id,
                'related_type' => $notification->related_type,
                'is_read' => (bool) $notification->is_read,
                'created_at' => $notification->created_at,
                'time_ago' => human_time_diff(strtotime($notification->created_at), current_time('timestamp')) . ' ago',
            );
        }

        wp_send_json_success(array(
            'notifications' => $formatted_notifications,
            'unread_count' => $unread_count,
        ));
    }

    /**
     * Mark notification as read
     */
    public function mark_notification_read() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'alhuffaz_public_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'al-huffaz-portal')));
        }

        // Must be logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in.', 'al-huffaz-portal')));
        }

        $user_id = get_current_user_id();
        $notification_id = isset($_POST['notification_id']) ? intval($_POST['notification_id']) : 0;

        if (!$notification_id) {
            wp_send_json_error(array('message' => __('Invalid notification ID.', 'al-huffaz-portal')));
        }

        // Check if Notifications class exists
        if (!class_exists('AlHuffaz\\Core\\Notifications')) {
            wp_send_json_error(array('message' => __('Notifications system not available.', 'al-huffaz-portal')));
        }

        // Mark as read (only for this user's notification)
        $result = Notifications::mark_as_read($notification_id, $user_id);

        if ($result === false) {
            wp_send_json_error(array('message' => __('Failed to mark notification as read.', 'al-huffaz-portal')));
        }

        // Get updated unread count
        $unread_count = Notifications::get_unread_count($user_id);

        wp_send_json_success(array(
            'message' => __('Notification marked as read.', 'al-huffaz-portal'),
            'unread_count' => $unread_count,
        ));
    }

    /**
     * Mark all notifications as read
     */
    public function mark_all_notifications_read() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'alhuffaz_public_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'al-huffaz-portal')));
        }

        // Must be logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in.', 'al-huffaz-portal')));
        }

        $user_id = get_current_user_id();

        // Check if Notifications class exists
        if (!class_exists('AlHuffaz\\Core\\Notifications')) {
            wp_send_json_error(array('message' => __('Notifications system not available.', 'al-huffaz-portal')));
        }

        // Mark all as read
        $result = Notifications::mark_all_as_read($user_id);

        if ($result === false) {
            wp_send_json_error(array('message' => __('Failed to mark all notifications as read.', 'al-huffaz-portal')));
        }

        wp_send_json_success(array(
            'message' => __('All notifications marked as read.', 'al-huffaz-portal'),
            'unread_count' => 0,
        ));
    }

    /**
     * Register new sponsor account (Public AJAX)
     * Creates user with alhuffaz_sponsor role and pending_approval status
     */
    public function register_sponsor() {
        // Verify nonce
        if (!isset($_POST['sponsor_register_nonce']) ||
            !wp_verify_nonce($_POST['sponsor_register_nonce'], 'alhuffaz_sponsor_registration')) {
            wp_send_json_error(array('message' => __('Security verification failed.', 'al-huffaz-portal')));
        }

        // Get form data
        $sponsor_name = isset($_POST['sponsor_name']) ? sanitize_text_field($_POST['sponsor_name']) : '';
        $sponsor_email = isset($_POST['sponsor_email']) ? sanitize_email($_POST['sponsor_email']) : '';
        $sponsor_password = isset($_POST['sponsor_password']) ? $_POST['sponsor_password'] : '';
        $sponsor_phone = isset($_POST['sponsor_phone']) ? sanitize_text_field($_POST['sponsor_phone']) : '';
        $sponsor_country = isset($_POST['sponsor_country']) ? sanitize_text_field($_POST['sponsor_country']) : '';
        $sponsor_whatsapp = isset($_POST['sponsor_whatsapp']) ? sanitize_text_field($_POST['sponsor_whatsapp']) : '';
        $agree_terms = isset($_POST['agree_terms']) ? true : false;

        // Validate required fields
        if (empty($sponsor_name)) {
            wp_send_json_error(array('message' => __('Full name is required.', 'al-huffaz-portal')));
        }

        if (empty($sponsor_email) || !is_email($sponsor_email)) {
            wp_send_json_error(array('message' => __('Valid email address is required.', 'al-huffaz-portal')));
        }

        if (empty($sponsor_password) || strlen($sponsor_password) < 8) {
            wp_send_json_error(array('message' => __('Password must be at least 8 characters long.', 'al-huffaz-portal')));
        }

        if (empty($sponsor_phone)) {
            wp_send_json_error(array('message' => __('Phone number is required.', 'al-huffaz-portal')));
        }

        if (empty($sponsor_country)) {
            wp_send_json_error(array('message' => __('Country is required.', 'al-huffaz-portal')));
        }

        if (!$agree_terms) {
            wp_send_json_error(array('message' => __('You must agree to the terms and conditions.', 'al-huffaz-portal')));
        }

        // Check if email already exists
        if (email_exists($sponsor_email)) {
            wp_send_json_error(array('message' => __('This email address is already registered.', 'al-huffaz-portal')));
        }

        // Create WordPress user
        $user_id = wp_create_user($sponsor_email, $sponsor_password, $sponsor_email);

        if (is_wp_error($user_id)) {
            wp_send_json_error(array('message' => $user_id->get_error_message()));
        }

        // Update user data
        wp_update_user(array(
            'ID' => $user_id,
            'display_name' => $sponsor_name,
            'first_name' => $sponsor_name,
        ));

        // Add sponsor role
        $user = new \WP_User($user_id);
        $user->set_role('alhuffaz_sponsor');

        // Add user meta
        update_user_meta($user_id, 'account_status', 'pending_approval');
        update_user_meta($user_id, 'sponsor_phone', $sponsor_phone);
        update_user_meta($user_id, 'sponsor_country', $sponsor_country);
        if (!empty($sponsor_whatsapp)) {
            update_user_meta($user_id, 'sponsor_whatsapp', $sponsor_whatsapp);
        }

        // Send notification email to admins
        $this->send_admin_notification_new_sponsor($user_id, $sponsor_name, $sponsor_email);

        // Send confirmation email to sponsor
        $this->send_sponsor_registration_confirmation($user_id, $sponsor_name, $sponsor_email);

        // Log activity
        Helpers::log_activity('sponsor_registered', 'user', $user_id,
            sprintf('New sponsor registered: %s (%s)', $sponsor_name, $sponsor_email));

        wp_send_json_success(array(
            'message' => __('Registration successful! Your account is pending approval.', 'al-huffaz-portal'),
            'redirect' => home_url('/login/?registered=success'),
        ));
    }

    /**
     * Send admin notification for new sponsor registration
     */
    private function send_admin_notification_new_sponsor($user_id, $name, $email) {
        // Get admin emails
        $admin_email = get_option('admin_email');

        // Get all admins
        $admins = get_users(array(
            'role__in' => array('administrator', 'alhuffaz_admin'),
        ));

        $admin_emails = array($admin_email);
        foreach ($admins as $admin) {
            if (!in_array($admin->user_email, $admin_emails)) {
                $admin_emails[] = $admin->user_email;
            }
        }

        // Email subject
        $subject = sprintf(__('[%s] New Sponsor Registration Pending Approval', 'al-huffaz-portal'),
            get_bloginfo('name'));

        // Email message
        $message = sprintf(
            __("A new sponsor has registered and is awaiting approval:\n\n", 'al-huffaz-portal') .
            __("Name: %s\n", 'al-huffaz-portal') .
            __("Email: %s\n\n", 'al-huffaz-portal') .
            __("Please review and approve this account:\n%s\n\n", 'al-huffaz-portal') .
            __("Thank you!", 'al-huffaz-portal'),
            $name,
            $email,
            home_url('/admin-portal/')
        );

        // Send email to all admins
        foreach ($admin_emails as $to_email) {
            wp_mail($to_email, $subject, $message);
        }
    }

    /**
     * Send registration confirmation email to sponsor
     */
    private function send_sponsor_registration_confirmation($user_id, $name, $email) {
        // Email subject
        $subject = sprintf(__('[%s] Registration Received - Pending Approval', 'al-huffaz-portal'),
            get_bloginfo('name'));

        // Email message
        $message = sprintf(
            __("Dear %s,\n\n", 'al-huffaz-portal') .
            __("Thank you for registering as a sponsor with %s!\n\n", 'al-huffaz-portal') .
            __("Your account has been created and is currently pending approval by our team. ", 'al-huffaz-portal') .
            __("This usually takes 24 hours.\n\n", 'al-huffaz-portal') .
            __("You will receive an email notification once your account has been approved.\n\n", 'al-huffaz-portal') .
            __("If you have any questions, please don't hesitate to contact us.\n\n", 'al-huffaz-portal') .
            __("Thank you for your support!\n\n", 'al-huffaz-portal') .
            __("Best regards,\n%s Team", 'al-huffaz-portal'),
            $name,
            get_bloginfo('name'),
            get_bloginfo('name')
        );

        // Send email
        wp_mail($email, $subject, $message);
    }

    /**
     * Get sponsor's sponsored students
     * For sponsor details modal
     */
    public function get_sponsor_students() {
        $this->verify_admin_nonce();

        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        if (!$user_id) {
            wp_send_json_error(array('message' => __('Invalid user ID.', 'al-huffaz-portal')));
        }

        // Get active sponsorships
        $sponsorships = get_posts(array(
            'post_type' => 'alhuffaz_sponsor',
            'posts_per_page' => -1,
            'meta_query' => array(
                'relation' => 'AND',
                array('key' => '_sponsor_user_id', 'value' => $user_id),
                array('key' => '_status', 'value' => 'approved'),
                array('key' => '_linked', 'value' => 'yes'),
            ),
        ));

        $students = array();
        foreach ($sponsorships as $sponsorship) {
            $student_id = get_post_meta($sponsorship->ID, '_student_id', true);
            $student = get_post($student_id);

            if ($student) {
                // Get student photo
                $student_photo_id = get_post_meta($student_id, 'student_photo', true);
                $student_photo = '';
                if ($student_photo_id) {
                    $student_photo = wp_get_attachment_image_url($student_photo_id, 'medium');
                }
                if (empty($student_photo)) {
                    // Default placeholder
                    $student_photo = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="200" height="200"%3E%3Crect fill="%23ddd" width="200" height="200"/%3E%3Ctext fill="%23999" font-family="sans-serif" font-size="60" dy="10.5" font-weight="bold" x="50%25" y="50%25" text-anchor="middle"%3E' . substr($student->post_title, 0, 1) . '%3C/text%3E%3C/svg%3E';
                }

                $students[] = array(
                    'id' => $student_id,
                    'name' => $student->post_title,
                    'photo' => $student_photo,
                    'grade' => get_post_meta($student_id, 'grade_level', true),
                    'amount' => number_format(floatval(get_post_meta($sponsorship->ID, '_amount', true)), 2),
                    'linked_date' => date_i18n(get_option('date_format'), strtotime($sponsorship->post_date)),
                );
            }
        }

        wp_send_json_success(array('students' => $students));
    }

    /**
     * Get sponsor's payment history
     * For sponsor details modal
     */
    public function get_sponsor_payments() {
        $this->verify_admin_nonce();

        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        if (!$user_id) {
            wp_send_json_error(array('message' => __('Invalid user ID.', 'al-huffaz-portal')));
        }

        // Get payments from custom table
        global $wpdb;
        $payments_table = $wpdb->prefix . 'alhuffaz_payments';

        $payments_data = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $payments_table WHERE sponsor_id = %d ORDER BY payment_date DESC LIMIT 50",
            $user_id
        ));

        $payments = array();
        foreach ($payments_data as $payment) {
            // Get student name
            $student = get_post($payment->student_id);
            $student_name = $student ? $student->post_title : __('Unknown Student', 'al-huffaz-portal');

            // Format status badge
            $status_badge = '';
            switch ($payment->status) {
                case 'approved':
                    $status_badge = '<span style="font-size:11px;padding:4px 8px;background:#10b981;color:#fff;border-radius:4px;">' . __('Approved', 'al-huffaz-portal') . '</span>';
                    break;
                case 'pending':
                    $status_badge = '<span style="font-size:11px;padding:4px 8px;background:#f59e0b;color:#fff;border-radius:4px;">' . __('Pending', 'al-huffaz-portal') . '</span>';
                    break;
                case 'rejected':
                    $status_badge = '<span style="font-size:11px;padding:4px 8px;background:#ef4444;color:#fff;border-radius:4px;">' . __('Rejected', 'al-huffaz-portal') . '</span>';
                    break;
                default:
                    $status_badge = '<span style="font-size:11px;padding:4px 8px;background:#6b7280;color:#fff;border-radius:4px;">' . ucfirst($payment->status) . '</span>';
            }

            $payments[] = array(
                'id' => $payment->id,
                'date' => date_i18n(get_option('date_format'), strtotime($payment->payment_date)),
                'student_name' => $student_name,
                'amount' => number_format($payment->amount, 2),
                'method' => ucfirst(str_replace('_', ' ', $payment->payment_method)),
                'status' => $payment->status,
                'status_badge' => $status_badge,
                'transaction_id' => $payment->transaction_id,
            );
        }

        wp_send_json_success(array('payments' => $payments));
    }
}
