<?php
/**
 * Admin Dashboard
 *
 * @package AlHuffaz
 */

namespace AlHuffaz\Admin;

use AlHuffaz\Core\Helpers;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Dashboard
 */
class Dashboard {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_ajax_alhuffaz_get_chart_data', array($this, 'get_chart_data'));
    }

    /**
     * Get dashboard statistics
     */
    public static function get_stats() {
        global $wpdb;

        // Total students
        $total_students = wp_count_posts('alhuffaz_student')->publish;

        // Sponsored students
        $sponsored = get_posts(array(
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
        $sponsored_count = count($sponsored);

        // Active sponsorships
        $active_sponsorships = get_posts(array(
            'post_type'      => 'alhuffaz_sponsor',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'meta_query'     => array(
                array(
                    'key'   => '_status',
                    'value' => 'approved',
                ),
            ),
        ));

        // Pending sponsorships
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

        // Payments
        $payments_table = $wpdb->prefix . 'alhuffaz_payments';

        $total_revenue = $wpdb->get_var("SELECT COALESCE(SUM(amount), 0) FROM $payments_table WHERE status = 'approved'");
        $pending_payments = $wpdb->get_var("SELECT COUNT(*) FROM $payments_table WHERE status = 'pending'");
        $this_month_revenue = $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(amount), 0) FROM $payments_table WHERE status = 'approved' AND MONTH(payment_date) = %d AND YEAR(payment_date) = %d",
            date('n'),
            date('Y')
        ));

        // Students by grade
        $grades = array();
        $grade_levels = get_option('alhuffaz_grade_levels', array());

        foreach ($grade_levels as $key => $label) {
            $count = get_posts(array(
                'post_type'      => 'alhuffaz_student',
                'posts_per_page' => -1,
                'fields'         => 'ids',
                'meta_query'     => array(
                    array(
                        'key'   => '_grade_level',
                        'value' => $key,
                    ),
                ),
            ));
            $grades[$label] = count($count);
        }

        // Students by gender
        $male = get_posts(array(
            'post_type'      => 'alhuffaz_student',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'meta_query'     => array(
                array(
                    'key'   => '_gender',
                    'value' => 'male',
                ),
            ),
        ));

        $female = get_posts(array(
            'post_type'      => 'alhuffaz_student',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'meta_query'     => array(
                array(
                    'key'   => '_gender',
                    'value' => 'female',
                ),
            ),
        ));

        return array(
            'total_students'       => intval($total_students),
            'sponsored_students'   => $sponsored_count,
            'unsponsored_students' => intval($total_students) - $sponsored_count,
            'active_sponsorships'  => count($active_sponsorships),
            'pending_sponsorships' => count($pending_sponsorships),
            'total_revenue'        => floatval($total_revenue),
            'this_month_revenue'   => floatval($this_month_revenue),
            'pending_payments'     => intval($pending_payments),
            'students_by_grade'    => $grades,
            'male_students'        => count($male),
            'female_students'      => count($female),
        );
    }

    /**
     * Get recent activities
     */
    public static function get_recent_activities($limit = 10) {
        global $wpdb;

        $table = $wpdb->prefix . 'alhuffaz_activity_log';

        $activities = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table ORDER BY created_at DESC LIMIT %d",
            $limit
        ));

        $result = array();

        foreach ($activities as $activity) {
            $user = get_user_by('id', $activity->user_id);

            $result[] = array(
                'id'          => $activity->id,
                'user'        => $user ? $user->display_name : __('Unknown', 'al-huffaz-portal'),
                'action'      => self::format_action($activity->action),
                'object_type' => $activity->object_type,
                'object_id'   => $activity->object_id,
                'details'     => $activity->details,
                'time'        => Helpers::time_ago($activity->created_at),
            );
        }

        return $result;
    }

    /**
     * Format action label
     */
    private static function format_action($action) {
        $actions = array(
            'create_student'      => __('Created student', 'al-huffaz-portal'),
            'update_student'      => __('Updated student', 'al-huffaz-portal'),
            'delete_student'      => __('Deleted student', 'al-huffaz-portal'),
            'approve_sponsorship' => __('Approved sponsorship', 'al-huffaz-portal'),
            'reject_sponsorship'  => __('Rejected sponsorship', 'al-huffaz-portal'),
            'link_sponsor'        => __('Linked sponsor', 'al-huffaz-portal'),
            'verify_payment'      => __('Verified payment', 'al-huffaz-portal'),
        );

        return isset($actions[$action]) ? $actions[$action] : ucwords(str_replace('_', ' ', $action));
    }

    /**
     * Get pending items for notifications
     */
    public static function get_pending_items() {
        global $wpdb;

        $pending_sponsorships = get_posts(array(
            'post_type'      => 'alhuffaz_sponsor',
            'posts_per_page' => 5,
            'meta_query'     => array(
                array(
                    'key'   => '_status',
                    'value' => 'pending',
                ),
            ),
            'orderby'        => 'date',
            'order'          => 'DESC',
        ));

        $payments_table = $wpdb->prefix . 'alhuffaz_payments';
        $pending_payments = $wpdb->get_results(
            "SELECT * FROM $payments_table WHERE status = 'pending' ORDER BY created_at DESC LIMIT 5"
        );

        return array(
            'sponsorships' => $pending_sponsorships,
            'payments'     => $pending_payments,
        );
    }

    /**
     * Get chart data via AJAX
     */
    public function get_chart_data() {
        check_ajax_referer('alhuffaz_admin_nonce', 'nonce');

        $type = isset($_POST['chart_type']) ? sanitize_text_field($_POST['chart_type']) : 'revenue';

        $data = array();

        switch ($type) {
            case 'revenue':
                $data = $this->get_revenue_chart_data();
                break;
            case 'students':
                $data = $this->get_students_chart_data();
                break;
            case 'sponsorships':
                $data = $this->get_sponsorships_chart_data();
                break;
        }

        wp_send_json_success($data);
    }

    /**
     * Get revenue chart data
     */
    private function get_revenue_chart_data() {
        global $wpdb;

        $table = $wpdb->prefix . 'alhuffaz_payments';

        $data = $wpdb->get_results(
            "SELECT DATE_FORMAT(payment_date, '%Y-%m') as month, SUM(amount) as total
             FROM $table
             WHERE status = 'approved' AND payment_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
             GROUP BY DATE_FORMAT(payment_date, '%Y-%m')
             ORDER BY month ASC"
        );

        $labels = array();
        $values = array();

        foreach ($data as $row) {
            $labels[] = date('M Y', strtotime($row->month . '-01'));
            $values[] = floatval($row->total);
        }

        return array(
            'labels' => $labels,
            'data'   => $values,
        );
    }

    /**
     * Get students chart data
     */
    private function get_students_chart_data() {
        $stats = self::get_stats();

        return array(
            'labels' => array_keys($stats['students_by_grade']),
            'data'   => array_values($stats['students_by_grade']),
        );
    }

    /**
     * Get sponsorships chart data
     */
    private function get_sponsorships_chart_data() {
        $stats = self::get_stats();

        return array(
            'labels' => array(__('Sponsored', 'al-huffaz-portal'), __('Not Sponsored', 'al-huffaz-portal')),
            'data'   => array($stats['sponsored_students'], $stats['unsponsored_students']),
        );
    }
}
