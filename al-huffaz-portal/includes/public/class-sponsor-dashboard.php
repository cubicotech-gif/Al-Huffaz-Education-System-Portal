<?php
/**
 * Sponsor Dashboard
 *
 * @package AlHuffaz
 */

namespace AlHuffaz\Frontend;

use AlHuffaz\Core\Helpers;
use AlHuffaz\Core\Roles;
use AlHuffaz\Admin\Payment_Manager;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Sponsor_Dashboard
 */
class Sponsor_Dashboard {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_ajax_alhuffaz_get_sponsor_data', array($this, 'get_sponsor_data'));
    }

    /**
     * Get sponsor data via AJAX
     */
    public function get_sponsor_data() {
        check_ajax_referer('alhuffaz_public_nonce', 'nonce');

        if (!Roles::is_sponsor()) {
            wp_send_json_error(array('message' => __('Access denied.', 'al-huffaz-portal')));
        }

        $user_id = get_current_user_id();

        $data = self::get_dashboard_data($user_id);

        wp_send_json_success($data);
    }

    /**
     * Get dashboard data for sponsor
     */
    public static function get_dashboard_data($user_id) {
        // CRITICAL: Clear all WordPress caches for this user to force fresh data
        wp_cache_delete('sponsor_dashboard_' . $user_id, 'alhuffaz');
        wp_cache_flush(); // Flush object cache

        // Get sponsorships - DISABLE ALL CACHING
        $sponsorships = get_posts(array(
            'post_type'      => 'alhuffaz_sponsor',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'meta_query'     => array(
                array(
                    'key'   => '_sponsor_user_id',
                    'value' => $user_id,
                ),
            ),
            'orderby'        => 'date',
            'order'          => 'DESC',
            'suppress_filters' => false,
            'cache_results'  => false,  // DISABLE POST CACHE
            'update_post_meta_cache' => false, // DISABLE META CACHE
            'update_post_term_cache' => false, // DISABLE TERM CACHE
        ));

        $active_sponsorships = array();
        $students = array();
        $total_contributed = 0;

        foreach ($sponsorships as $sponsorship) {
            $status = get_post_meta($sponsorship->ID, '_status', true);
            $linked = get_post_meta($sponsorship->ID, '_linked', true);

            if ($status === 'approved' && $linked === 'yes') {
                $student_id = get_post_meta($sponsorship->ID, '_student_id', true);
                $student = get_post($student_id);

                if ($student) {
                    $active_sponsorships[] = array(
                        'id'           => $sponsorship->ID,
                        'student_id'   => $student_id,
                        'student_name' => $student->post_title,
                        'student_photo'=> Helpers::get_student_photo($student_id, 'medium'),
                        'grade'        => Helpers::get_grade_label(get_post_meta($student_id, 'grade_level', true)),
                        'category'     => Helpers::get_islamic_category_label(get_post_meta($student_id, 'islamic_studies_category', true)),
                        'amount'       => get_post_meta($sponsorship->ID, '_amount', true),
                        'type'         => get_post_meta($sponsorship->ID, '_sponsorship_type', true),
                        'start_date'   => Helpers::format_date($sponsorship->post_date),
                    );

                    $students[] = $student_id;
                }
            }
        }

        // Get payments
        $payments_result = Payment_Manager::get_sponsor_payments($user_id, array('per_page' => 10));

        // Calculate total contribution
        global $wpdb;
        $payments_table = $wpdb->prefix . 'alhuffaz_payments';

        $total_contributed = $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(amount), 0) FROM $payments_table WHERE sponsor_id = %d AND status = 'approved'",
            $user_id
        ));

        // Get pending payments count
        $pending_payments = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $payments_table WHERE sponsor_id = %d AND status = 'pending'",
            $user_id
        ));

        return array(
            'sponsorships'      => $active_sponsorships,
            'students_count'    => count(array_unique($students)),
            'total_contributed' => Helpers::format_currency($total_contributed),
            'pending_payments'  => intval($pending_payments),
            'recent_payments'   => $payments_result['payments'],
        );
    }

    /**
     * Get sponsored students for sponsor
     */
    public static function get_sponsored_students($user_id) {
        $student_ids = Roles::get_sponsor_students($user_id);

        if (empty($student_ids)) {
            return array();
        }

        $students = array();

        foreach ($student_ids as $student_id) {
            $student = get_post($student_id);

            if (!$student) {
                continue;
            }

            $students[] = array(
                'id'               => $student_id,
                'name'             => $student->post_title,
                'photo'            => Helpers::get_student_photo($student_id, 'medium'),
                'grade'            => Helpers::get_grade_label(get_post_meta($student_id, 'grade_level', true)),
                'category'         => Helpers::get_islamic_category_label(get_post_meta($student_id, 'islamic_studies_category', true)),
                'overall_percentage' => get_post_meta($student_id, 'overall_percentage', true),
                'attendance'       => \AlHuffaz\Admin\Student_Manager::calculate_attendance_percentage($student_id),
            );
        }

        return $students;
    }

    /**
     * Get student progress for sponsor view
     */
    public static function get_student_progress($student_id, $user_id) {
        // Verify sponsor has access to this student
        $student_ids = Roles::get_sponsor_students($user_id);

        if (!in_array($student_id, $student_ids)) {
            return null;
        }

        $student = get_post($student_id);

        if (!$student) {
            return null;
        }

        return array(
            'id'                => $student_id,
            'name'              => $student->post_title,
            'photo'             => Helpers::get_student_photo($student_id, 'large'),
            'grade'             => Helpers::get_grade_label(get_post_meta($student_id, 'grade_level', true)),
            'category'          => Helpers::get_islamic_category_label(get_post_meta($student_id, 'islamic_studies_category', true)),
            'overall_percentage'=> get_post_meta($student_id, 'overall_percentage', true),
            'overall_grade'     => Helpers::get_grade_from_percentage(get_post_meta($student_id, 'overall_percentage', true)),
            'attendance'        => \AlHuffaz\Admin\Student_Manager::get_attendance($student_id),
            'subjects'          => get_post_meta($student_id, 'subjects', true) ?: array(),
            'teacher_comments'  => get_post_meta($student_id, 'teacher_overall_comments', true),
            'goals'             => array(
                get_post_meta($student_id, 'goal_1', true),
                get_post_meta($student_id, 'goal_2', true),
                get_post_meta($student_id, 'goal_3', true),
            ),
        );
    }
}
