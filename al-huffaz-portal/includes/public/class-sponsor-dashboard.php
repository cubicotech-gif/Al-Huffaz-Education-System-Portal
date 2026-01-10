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

        // CRITICAL FIX: Get sponsorships from unified 'sponsorship' post type (not 'alhuffaz_sponsor')
        $sponsorships = get_posts(array(
            'post_type'      => 'sponsorship',
            'posts_per_page' => -1,
            'post_status'    => array('publish', 'draft', 'pending'), // Include all statuses
            'meta_query'     => array(
                array(
                    'key'   => 'sponsor_user_id', // No underscore prefix
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
        $pending_sponsorships = array();
        $students = array();
        $total_contributed = 0;
        $monthly_total = 0;
        $quarterly_total = 0;
        $yearly_total = 0;

        foreach ($sponsorships as $sponsorship) {
            // CRITICAL FIX: Use correct meta keys without underscore prefix
            $verification_status = get_post_meta($sponsorship->ID, 'verification_status', true);
            $linked = get_post_meta($sponsorship->ID, 'linked', true);
            $amount = floatval(get_post_meta($sponsorship->ID, 'amount', true));
            $type = get_post_meta($sponsorship->ID, 'sponsorship_type', true);
            $duration_months = get_post_meta($sponsorship->ID, 'duration_months', true);

            // Active sponsorships: verified/approved AND linked to student
            if ($linked === 'yes' && ($verification_status === 'approved' || $verification_status === 'verified')) {
                $student_id = get_post_meta($sponsorship->ID, 'student_id', true);
                $student = get_post($student_id);

                if ($student) {
                    $active_sponsorships[] = array(
                        'id'            => $sponsorship->ID,
                        'student_id'    => $student_id,
                        'student_name'  => $student->post_title,
                        'student_photo' => Helpers::get_student_photo($student_id, 'medium'),
                        'grade'         => Helpers::get_grade_label(get_post_meta($student_id, 'grade_level', true)),
                        'category'      => Helpers::get_islamic_category_label(get_post_meta($student_id, 'islamic_studies_category', true)),
                        'amount'        => $amount,
                        'type'          => $type,
                        'duration'      => $duration_months,
                        'start_date'    => Helpers::format_date($sponsorship->post_date),
                    );

                    $students[] = $student_id;
                    $total_contributed += $amount;

                    // Calculate totals by type
                    if ($type === 'monthly') {
                        $monthly_total += $amount;
                    } elseif ($type === 'quarterly') {
                        $quarterly_total += $amount;
                    } elseif ($type === 'yearly') {
                        $yearly_total += $amount;
                    }
                }
            } else {
                // Pending sponsorship: payment proof submitted, awaiting verification
                $student_id = get_post_meta($sponsorship->ID, 'student_id', true);
                $student = get_post($student_id);

                if ($student) {
                    $pending_sponsorships[] = array(
                        'id'           => $sponsorship->ID,
                        'student_id'   => $student_id,
                        'student_name' => $student->post_title,
                        'amount'       => $amount,
                        'type'         => $type,
                        'duration'     => $duration_months,
                        'status'       => $verification_status ?: 'pending',
                        'submitted_at' => Helpers::format_date($sponsorship->post_date),
                    );
                }
            }
        }

        // Build payment history from sponsorships (all submissions)
        $payment_history = array();
        foreach ($sponsorships as $sponsorship) {
            $student_id = get_post_meta($sponsorship->ID, 'student_id', true);
            $student = get_post($student_id);
            $verification_status = get_post_meta($sponsorship->ID, 'verification_status', true);
            $payment_date = get_post_meta($sponsorship->ID, 'payment_date', true);

            $payment_history[] = array(
                'id'          => $sponsorship->ID,
                'student'     => $student ? $student->post_title : 'Unknown',
                'amount'      => get_post_meta($sponsorship->ID, 'amount', true),
                'type'        => get_post_meta($sponsorship->ID, 'sponsorship_type', true),
                'duration'    => get_post_meta($sponsorship->ID, 'duration_months', true),
                'method'      => get_post_meta($sponsorship->ID, 'payment_method', true),
                'status'      => $verification_status ?: 'pending',
                'date'        => $payment_date ? date('M d, Y', strtotime($payment_date)) : date('M d, Y', strtotime($sponsorship->post_date)),
            );
        }

        return array(
            'sponsorships'         => $active_sponsorships,
            'pending_sponsorships' => $pending_sponsorships,
            'students_count'       => count(array_unique($students)),
            'total_contributed'    => Helpers::format_currency($total_contributed),
            'monthly_total'        => Helpers::format_currency($monthly_total),
            'quarterly_total'      => Helpers::format_currency($quarterly_total),
            'yearly_total'         => Helpers::format_currency($yearly_total),
            'pending_count'        => count($pending_sponsorships),
            'payment_history'      => $payment_history,
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
