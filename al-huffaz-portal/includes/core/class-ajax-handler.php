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

        add_action('wp_ajax_alhuffaz_get_available_students', array($this, 'get_available_students'));
        add_action('wp_ajax_nopriv_alhuffaz_get_available_students', array($this, 'get_available_students'));
    }

    /**
     * Verify admin nonce
     */
    private function verify_admin_nonce() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'alhuffaz_admin_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'al-huffaz-portal')));
        }

        if (!current_user_can('alhuffaz_manage_students')) {
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
        $data = isset($_POST['data']) ? $_POST['data'] : array();

        if (empty($data['student_name'])) {
            wp_send_json_error(array('message' => __('Student name is required.', 'al-huffaz-portal')));
        }

        // Prepare post data
        $post_data = array(
            'post_title'  => sanitize_text_field($data['student_name']),
            'post_type'   => 'alhuffaz_student',
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

        // Save meta fields
        $fields = Post_Types::get_student_fields();

        foreach ($fields as $key => $field) {
            if (isset($data[$key])) {
                $value = $data[$key];

                // Sanitize based on type
                switch ($field['type']) {
                    case 'integer':
                        $value = intval($value);
                        break;
                    case 'number':
                        $value = floatval($value);
                        break;
                    case 'boolean':
                        $value = $value === 'yes' || $value === true || $value === '1' ? 'yes' : 'no';
                        break;
                    case 'array':
                        $value = is_array($value) ? $value : array();
                        break;
                    default:
                        $value = sanitize_text_field($value);
                }

                update_post_meta($student_id, '_' . $key, $value);
            }
        }

        // Log activity
        Helpers::log_activity(
            $student_id ? 'update_student' : 'create_student',
            'student',
            $student_id,
            sprintf('Student %s %s', $data['student_name'], $student_id ? 'updated' : 'created')
        );

        wp_send_json_success(array(
            'message'    => __('Student saved successfully.', 'al-huffaz-portal'),
            'student_id' => $student_id,
        ));
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

        if (!$student || $student->post_type !== 'alhuffaz_student') {
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

        if (!$student || $student->post_type !== 'alhuffaz_student') {
            wp_send_json_error(array('message' => __('Student not found.', 'al-huffaz-portal')));
        }

        // Get all meta
        $meta = get_post_meta($student_id);
        $data = array(
            'id'           => $student_id,
            'student_name' => $student->post_title,
        );

        foreach ($meta as $key => $value) {
            if (strpos($key, '_') === 0) {
                $clean_key = substr($key, 1);
                $data[$clean_key] = maybe_unserialize($value[0]);
            }
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
        $gender    = isset($_POST['gender']) ? sanitize_text_field($_POST['gender']) : '';
        $sponsored = isset($_POST['sponsored']) ? sanitize_text_field($_POST['sponsored']) : '';

        $args = array(
            'post_type'      => 'alhuffaz_student',
            'posts_per_page' => $per_page,
            'paged'          => $page,
            'post_status'    => 'publish',
        );

        if ($search) {
            $args['s'] = $search;
        }

        $meta_query = array();

        if ($grade) {
            $meta_query[] = array(
                'key'   => '_grade_level',
                'value' => $grade,
            );
        }

        if ($gender) {
            $meta_query[] = array(
                'key'   => '_gender',
                'value' => $gender,
            );
        }

        if ($sponsored === 'yes' || $sponsored === 'no') {
            $meta_query[] = array(
                'key'   => '_is_sponsored',
                'value' => $sponsored,
            );
        }

        if (!empty($meta_query)) {
            $args['meta_query'] = $meta_query;
        }

        $query = new \WP_Query($args);

        $students = array();

        foreach ($query->posts as $post) {
            $students[] = array(
                'id'           => $post->ID,
                'name'         => $post->post_title,
                'gr_number'    => get_post_meta($post->ID, '_gr_number', true),
                'grade_level'  => Helpers::get_grade_label(get_post_meta($post->ID, '_grade_level', true)),
                'gender'       => get_post_meta($post->ID, '_gender', true),
                'photo'        => Helpers::get_student_photo($post->ID),
                'is_sponsored' => get_post_meta($post->ID, '_is_sponsored', true) === 'yes',
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
            'post_type'      => 'alhuffaz_student',
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

        // Mark student as sponsored
        $student_id = get_post_meta($sponsorship_id, '_student_id', true);
        if ($student_id) {
            update_post_meta($student_id, '_is_sponsored', 'yes');
        }

        // Log activity
        Helpers::log_activity('approve_sponsorship', 'sponsorship', $sponsorship_id, 'Sponsorship approved');

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

        if (!current_user_can('alhuffaz_manage_payments')) {
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

        $student_count = wp_count_posts('alhuffaz_student')->publish;

        $sponsored_count = get_posts(array(
            'post_type'      => 'alhuffaz_student',
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
     * Get available students (public)
     */
    public function get_available_students() {
        $this->verify_public_nonce();

        $args = array(
            'post_type'      => 'alhuffaz_student',
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
                'post_type'      => 'alhuffaz_student',
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
                'post_type'   => 'alhuffaz_student',
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
}
