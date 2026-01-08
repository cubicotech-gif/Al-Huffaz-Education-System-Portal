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
        add_action('wp_ajax_alhuffaz_get_sponsorships', array($this, 'get_sponsorships'));

        add_action('wp_ajax_alhuffaz_verify_payment', array($this, 'verify_payment'));
        add_action('wp_ajax_alhuffaz_get_payments', array($this, 'get_payments'));

        add_action('wp_ajax_alhuffaz_get_dashboard_stats', array($this, 'get_dashboard_stats'));
        add_action('wp_ajax_alhuffaz_export_data', array($this, 'export_data'));
        add_action('wp_ajax_alhuffaz_bulk_import', array($this, 'bulk_import'));

        add_action('wp_ajax_alhuffaz_upload_image', array($this, 'upload_image'));

        // Public AJAX actions
        add_action('wp_ajax_alhuffaz_submit_sponsorship', array($this, 'submit_sponsorship'));
        add_action('wp_ajax_nopriv_alhuffaz_submit_sponsorship', array($this, 'submit_sponsorship'));

        add_action('wp_ajax_alhuffaz_submit_payment', array($this, 'submit_payment'));
        add_action('wp_ajax_nopriv_alhuffaz_submit_payment', array($this, 'submit_payment'));

        add_action('wp_ajax_alhuffaz_submit_payment_proof', array($this, 'submit_payment_proof'));

        add_action('wp_ajax_alhuffaz_get_student_profile', array($this, 'get_student_profile'));

        add_action('wp_ajax_alhuffaz_get_available_students', array($this, 'get_available_students'));
        add_action('wp_ajax_nopriv_alhuffaz_get_available_students', array($this, 'get_available_students'));

        // Staff management AJAX actions (admin only)
        add_action('wp_ajax_alhuffaz_get_staff_users', array($this, 'get_staff_users'));
        add_action('wp_ajax_alhuffaz_grant_staff_role', array($this, 'grant_staff_role'));
        add_action('wp_ajax_alhuffaz_revoke_staff_role', array($this, 'revoke_staff_role'));
        add_action('wp_ajax_alhuffaz_get_eligible_users', array($this, 'get_eligible_users'));
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

        if (!$student_id) {
            wp_send_json_error(array('message' => __('Invalid student ID.', 'al-huffaz-portal')));
        }

        $student = get_post($student_id);

        if (!$student || $student->post_type !== 'student') {
            wp_send_json_error(array('message' => __('Student not found.', 'al-huffaz-portal')));
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

        update_post_meta($sponsorship_id, '_status', 'approved');
        update_post_meta($sponsorship_id, '_verified_by', get_current_user_id());
        update_post_meta($sponsorship_id, '_verified_at', current_time('mysql'));

        // Automatically link sponsor to student on approval
        update_post_meta($sponsorship_id, '_linked', 'yes');

        // Mark student as sponsored
        $student_id = get_post_meta($sponsorship_id, '_student_id', true);
        if ($student_id) {
            update_post_meta($student_id, '_is_sponsored', 'yes');
            update_post_meta($student_id, '_sponsor_id', get_post_meta($sponsorship_id, '_sponsor_user_id', true));
        }

        // Log activity
        Helpers::log_activity('approve_sponsorship', 'sponsorship', $sponsorship_id, 'Sponsorship approved and student linked');

        // Sync with Ultimate Member if sponsor has user account
        $sponsor_user_id = get_post_meta($sponsorship_id, '_sponsor_user_id', true);
        if ($sponsor_user_id) {
            UM_Integration::approve_sponsor_in_um($sponsor_user_id);
        }

        // Send notification email
        $sponsor_email = get_post_meta($sponsorship_id, '_sponsor_email', true);
        if ($sponsor_email) {
            Helpers::send_notification(
                $sponsor_email,
                __('Sponsorship Approved', 'al-huffaz-portal'),
                __('Your sponsorship has been approved. Thank you for your support!', 'al-huffaz-portal')
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

        update_post_meta($sponsorship_id, '_status', 'rejected');
        update_post_meta($sponsorship_id, '_rejection_reason', $reason);

        // Sync with Ultimate Member if sponsor has user account
        $sponsor_user_id = get_post_meta($sponsorship_id, '_sponsor_user_id', true);
        if ($sponsor_user_id) {
            UM_Integration::reject_sponsor_in_um($sponsor_user_id);
        }

        // Log activity
        Helpers::log_activity('reject_sponsorship', 'sponsorship', $sponsorship_id, 'Sponsorship rejected: ' . $reason);

        wp_send_json_success(array('message' => __('Sponsorship rejected.', 'al-huffaz-portal')));
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

        update_post_meta($sponsorship_id, '_linked', 'yes');

        // Log activity
        Helpers::log_activity('link_sponsor', 'sponsorship', $sponsorship_id, 'Sponsor linked to student');

        wp_send_json_success(array('message' => __('Sponsor linked successfully.', 'al-huffaz-portal')));
    }

    /**
     * Get sponsorships
     */
    public function get_sponsorships() {
        $this->verify_admin_nonce();

        $page     = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 20;
        $status   = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';

        $args = array(
            'post_type'      => 'alhuffaz_sponsor',
            'posts_per_page' => $per_page,
            'paged'          => $page,
            'post_status'    => 'publish',
        );

        if ($status) {
            $args['meta_query'] = array(
                array(
                    'key'   => '_status',
                    'value' => $status,
                ),
            );
        }

        $query = new \WP_Query($args);

        $sponsorships = array();

        foreach ($query->posts as $post) {
            $student_id = get_post_meta($post->ID, '_student_id', true);
            $student = get_post($student_id);

            $sponsorships[] = array(
                'id'             => $post->ID,
                'sponsor_name'   => get_post_meta($post->ID, '_sponsor_name', true),
                'sponsor_email'  => get_post_meta($post->ID, '_sponsor_email', true),
                'student_name'   => $student ? $student->post_title : '-',
                'amount'         => Helpers::format_currency(get_post_meta($post->ID, '_amount', true)),
                'type'           => get_post_meta($post->ID, '_sponsorship_type', true),
                'status'         => get_post_meta($post->ID, '_status', true),
                'status_badge'   => Helpers::get_status_badge(get_post_meta($post->ID, '_status', true)),
                'linked'         => get_post_meta($post->ID, '_linked', true) === 'yes',
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

        $student_count = wp_count_posts('student')->publish;

        $sponsored_count = get_posts(array(
            'post_type'      => 'student',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'meta_query'     => array(
                array(
                    'key'   => '_is_sponsored',
                    'value' => 'yes',
                ),
            ),
        ));

        $pending_sponsorships = get_posts(array(
            'post_type'      => 'alhuffaz_sponsor',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'meta_query'     => array(
                array(
                    'key'   => '_status',
                    'value' => 'pending',
                ),
            ),
        ));

        global $wpdb;
        $payments_table = $wpdb->prefix . 'alhuffaz_payments';

        $total_revenue = $wpdb->get_var("SELECT SUM(amount) FROM $payments_table WHERE status = 'approved'");

        $pending_payments = $wpdb->get_var("SELECT COUNT(*) FROM $payments_table WHERE status = 'pending'");

        wp_send_json_success(array(
            'students'             => intval($student_count),
            'sponsored'            => count($sponsored_count),
            'pending_sponsorships' => count($pending_sponsorships),
            'total_revenue'        => Helpers::format_currency($total_revenue ?: 0),
            'pending_payments'     => intval($pending_payments),
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
        $sponsorship_type = isset($_POST['sponsorship_type']) ? sanitize_text_field($_POST['sponsorship_type']) : 'monthly';
        $payment_date = isset($_POST['payment_date']) ? sanitize_text_field($_POST['payment_date']) : current_time('Y-m-d');
        $notes = isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '';

        if (!$student_id || !$amount || !$payment_method || !$transaction_id) {
            wp_send_json_error(array('message' => __('Please fill in all required fields.', 'al-huffaz-portal')));
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

        // Create sponsorship record
        $sponsorship_id = wp_insert_post(array(
            'post_type'   => 'alhuffaz_sponsor',
            'post_title'  => sprintf('%s - %s', $user->display_name, $student->post_title),
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
        update_post_meta($sponsorship_id, '_amount', $amount);
        update_post_meta($sponsorship_id, '_sponsorship_type', $sponsorship_type);
        update_post_meta($sponsorship_id, '_payment_method', $payment_method);
        update_post_meta($sponsorship_id, '_transaction_id', $transaction_id);
        update_post_meta($sponsorship_id, '_payment_date', $payment_date);
        update_post_meta($sponsorship_id, '_status', 'pending');
        update_post_meta($sponsorship_id, '_linked', 'no');
        update_post_meta($sponsorship_id, '_notes', $notes);
        update_post_meta($sponsorship_id, '_created_at', current_time('mysql'));

        // Handle payment screenshot upload
        if (!empty($_FILES['payment_screenshot']) && $_FILES['payment_screenshot']['error'] === UPLOAD_ERR_OK) {
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');

            $attachment_id = media_handle_upload('payment_screenshot', $sponsorship_id);

            if (!is_wp_error($attachment_id)) {
                update_post_meta($sponsorship_id, '_payment_screenshot', $attachment_id);
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

        wp_send_json_success(array(
            'message' => __('Payment proof submitted successfully! The school will verify your payment and notify you once approved.', 'al-huffaz-portal'),
            'sponsorship_id' => $sponsorship_id,
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

        $args = array(
            'post_type'      => 'student',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'meta_query'     => array(
                'relation' => 'OR',
                array(
                    'key'     => '_is_sponsored',
                    'value'   => 'no',
                ),
                array(
                    'key'     => '_is_sponsored',
                    'compare' => 'NOT EXISTS',
                ),
            ),
        );

        $query = new \WP_Query($args);

        $students = array();

        foreach ($query->posts as $post) {
            $students[] = array(
                'id'         => $post->ID,
                'name'       => $post->post_title,
                'grade'      => Helpers::get_grade_label(get_post_meta($post->ID, '_grade_level', true)),
                'category'   => Helpers::get_islamic_category_label(get_post_meta($post->ID, '_islamic_category', true)),
                'photo'      => Helpers::get_student_photo($post->ID, 'medium'),
                'monthly_fee'=> get_post_meta($post->ID, '_monthly_fee', true),
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
                    'Is Sponsored'   => get_post_meta($student->ID, '_is_sponsored', true),
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
}
