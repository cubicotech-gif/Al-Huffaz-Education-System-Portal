<?php
/**
 * Reports
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
 * Class Reports
 */
class Reports {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_ajax_alhuffaz_generate_report', array($this, 'generate_report'));
        add_action('wp_ajax_alhuffaz_export_report', array($this, 'export_report'));
    }

    /**
     * Generate report via AJAX
     */
    public function generate_report() {
        check_ajax_referer('alhuffaz_admin_nonce', 'nonce');

        if (!current_user_can('alhuffaz_view_reports')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'al-huffaz-portal')));
        }

        $type = isset($_POST['report_type']) ? sanitize_text_field($_POST['report_type']) : 'overview';
        $date_from = isset($_POST['date_from']) ? sanitize_text_field($_POST['date_from']) : '';
        $date_to = isset($_POST['date_to']) ? sanitize_text_field($_POST['date_to']) : '';

        $data = array();

        switch ($type) {
            case 'overview':
                $data = self::get_overview_report();
                break;
            case 'students':
                $data = self::get_students_report($date_from, $date_to);
                break;
            case 'sponsorships':
                $data = self::get_sponsorships_report($date_from, $date_to);
                break;
            case 'payments':
                $data = self::get_payments_report($date_from, $date_to);
                break;
            case 'financial':
                $data = self::get_financial_report($date_from, $date_to);
                break;
        }

        wp_send_json_success($data);
    }

    /**
     * Get overview report
     */
    public static function get_overview_report() {
        $stats = Dashboard::get_stats();

        return array(
            'title'  => __('Overview Report', 'al-huffaz-portal'),
            'date'   => Helpers::format_date(current_time('mysql')),
            'stats'  => $stats,
            'charts' => array(
                'students_by_grade' => array(
                    'labels' => array_keys($stats['students_by_grade']),
                    'data'   => array_values($stats['students_by_grade']),
                ),
                'sponsorship_status' => array(
                    'labels' => array(__('Sponsored', 'al-huffaz-portal'), __('Not Sponsored', 'al-huffaz-portal')),
                    'data'   => array($stats['sponsored_students'], $stats['unsponsored_students']),
                ),
                'gender_distribution' => array(
                    'labels' => array(__('Male', 'al-huffaz-portal'), __('Female', 'al-huffaz-portal')),
                    'data'   => array($stats['male_students'], $stats['female_students']),
                ),
            ),
        );
    }

    /**
     * Get students report
     */
    public static function get_students_report($date_from = '', $date_to = '') {
        $args = array(
            'post_type'      => 'alhuffaz_student',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
        );

        if ($date_from || $date_to) {
            $args['date_query'] = array();

            if ($date_from) {
                $args['date_query']['after'] = $date_from;
            }

            if ($date_to) {
                $args['date_query']['before'] = $date_to;
            }
        }

        $query = new \WP_Query($args);

        $students = array();
        $by_grade = array();
        $by_gender = array('male' => 0, 'female' => 0);
        $by_category = array();
        $sponsored = 0;

        foreach ($query->posts as $post) {
            $grade = get_post_meta($post->ID, '_grade_level', true);
            $gender = get_post_meta($post->ID, '_gender', true);
            $category = get_post_meta($post->ID, '_islamic_category', true);
            $is_sponsored = get_post_meta($post->ID, '_is_sponsored', true) === 'yes';

            $students[] = array(
                'id'          => $post->ID,
                'name'        => $post->post_title,
                'grade'       => Helpers::get_grade_label($grade),
                'gender'      => $gender,
                'category'    => Helpers::get_islamic_category_label($category),
                'sponsored'   => $is_sponsored,
                'created'     => $post->post_date,
            );

            // Count by grade
            $grade_label = Helpers::get_grade_label($grade);
            $by_grade[$grade_label] = isset($by_grade[$grade_label]) ? $by_grade[$grade_label] + 1 : 1;

            // Count by gender
            if ($gender === 'male' || $gender === 'female') {
                $by_gender[$gender]++;
            }

            // Count by category
            $cat_label = Helpers::get_islamic_category_label($category);
            $by_category[$cat_label] = isset($by_category[$cat_label]) ? $by_category[$cat_label] + 1 : 1;

            if ($is_sponsored) {
                $sponsored++;
            }
        }

        return array(
            'title'      => __('Students Report', 'al-huffaz-portal'),
            'date_range' => array('from' => $date_from, 'to' => $date_to),
            'total'      => count($students),
            'sponsored'  => $sponsored,
            'by_grade'   => $by_grade,
            'by_gender'  => $by_gender,
            'by_category'=> $by_category,
            'students'   => $students,
        );
    }

    /**
     * Get sponsorships report
     */
    public static function get_sponsorships_report($date_from = '', $date_to = '') {
        $args = array(
            'post_type'      => 'alhuffaz_sponsor',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
        );

        if ($date_from || $date_to) {
            $args['date_query'] = array();

            if ($date_from) {
                $args['date_query']['after'] = $date_from;
            }

            if ($date_to) {
                $args['date_query']['before'] = $date_to;
            }
        }

        $query = new \WP_Query($args);

        $sponsorships = array();
        $by_status = array('pending' => 0, 'approved' => 0, 'rejected' => 0);
        $by_type = array();
        $total_amount = 0;

        foreach ($query->posts as $post) {
            $status = get_post_meta($post->ID, '_status', true);
            $type = get_post_meta($post->ID, '_sponsorship_type', true);
            $amount = floatval(get_post_meta($post->ID, '_amount', true));

            $student_id = get_post_meta($post->ID, '_student_id', true);
            $student = get_post($student_id);

            $sponsorships[] = array(
                'id'           => $post->ID,
                'sponsor_name' => get_post_meta($post->ID, '_sponsor_name', true),
                'student_name' => $student ? $student->post_title : '-',
                'amount'       => Helpers::format_currency($amount),
                'type'         => $type,
                'status'       => $status,
                'created'      => $post->post_date,
            );

            // Count by status
            if (isset($by_status[$status])) {
                $by_status[$status]++;
            }

            // Count by type
            $by_type[$type] = isset($by_type[$type]) ? $by_type[$type] + 1 : 1;

            if ($status === 'approved') {
                $total_amount += $amount;
            }
        }

        return array(
            'title'        => __('Sponsorships Report', 'al-huffaz-portal'),
            'date_range'   => array('from' => $date_from, 'to' => $date_to),
            'total'        => count($sponsorships),
            'total_amount' => Helpers::format_currency($total_amount),
            'by_status'    => $by_status,
            'by_type'      => $by_type,
            'sponsorships' => $sponsorships,
        );
    }

    /**
     * Get payments report
     */
    public static function get_payments_report($date_from = '', $date_to = '') {
        global $wpdb;

        $table = $wpdb->prefix . 'alhuffaz_payments';

        $where = "1=1";

        if ($date_from) {
            $where .= $wpdb->prepare(" AND payment_date >= %s", $date_from);
        }

        if ($date_to) {
            $where .= $wpdb->prepare(" AND payment_date <= %s", $date_to);
        }

        $payments = $wpdb->get_results("SELECT * FROM $table WHERE $where ORDER BY payment_date DESC");

        $by_status = array('pending' => 0, 'approved' => 0, 'rejected' => 0);
        $by_method = array();
        $total_approved = 0;
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
                'status'         => $payment->status,
                'date'           => Helpers::format_date($payment->payment_date),
            );

            $by_status[$payment->status]++;

            $by_method[$payment->payment_method] = isset($by_method[$payment->payment_method])
                ? $by_method[$payment->payment_method] + floatval($payment->amount)
                : floatval($payment->amount);

            if ($payment->status === 'approved') {
                $total_approved += floatval($payment->amount);
            }
        }

        return array(
            'title'          => __('Payments Report', 'al-huffaz-portal'),
            'date_range'     => array('from' => $date_from, 'to' => $date_to),
            'total'          => count($payments),
            'total_approved' => Helpers::format_currency($total_approved),
            'by_status'      => $by_status,
            'by_method'      => $by_method,
            'payments'       => $result,
        );
    }

    /**
     * Get financial report
     */
    public static function get_financial_report($date_from = '', $date_to = '') {
        global $wpdb;

        $table = $wpdb->prefix . 'alhuffaz_payments';

        $where = "status = 'approved'";

        if ($date_from) {
            $where .= $wpdb->prepare(" AND payment_date >= %s", $date_from);
        }

        if ($date_to) {
            $where .= $wpdb->prepare(" AND payment_date <= %s", $date_to);
        }

        // Monthly breakdown
        $monthly = $wpdb->get_results(
            "SELECT DATE_FORMAT(payment_date, '%Y-%m') as month, SUM(amount) as total, COUNT(*) as count
             FROM $table WHERE $where
             GROUP BY DATE_FORMAT(payment_date, '%Y-%m')
             ORDER BY month ASC"
        );

        $monthly_data = array();

        foreach ($monthly as $row) {
            $monthly_data[] = array(
                'month' => date('F Y', strtotime($row->month . '-01')),
                'total' => Helpers::format_currency($row->total),
                'count' => $row->count,
            );
        }

        // Total
        $total = $wpdb->get_var("SELECT COALESCE(SUM(amount), 0) FROM $table WHERE $where");

        // By payment method
        $by_method = $wpdb->get_results(
            "SELECT payment_method, SUM(amount) as total, COUNT(*) as count
             FROM $table WHERE $where
             GROUP BY payment_method"
        );

        $method_data = array();

        foreach ($by_method as $row) {
            $method_data[$row->payment_method] = array(
                'total' => Helpers::format_currency($row->total),
                'count' => $row->count,
            );
        }

        return array(
            'title'        => __('Financial Report', 'al-huffaz-portal'),
            'date_range'   => array('from' => $date_from, 'to' => $date_to),
            'total_revenue'=> Helpers::format_currency($total),
            'monthly'      => $monthly_data,
            'by_method'    => $method_data,
        );
    }

    /**
     * Export report
     */
    public function export_report() {
        check_ajax_referer('alhuffaz_admin_nonce', 'nonce');

        if (!current_user_can('alhuffaz_view_reports')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'al-huffaz-portal')));
        }

        $type = isset($_POST['report_type']) ? sanitize_text_field($_POST['report_type']) : 'students';
        $format = isset($_POST['format']) ? sanitize_text_field($_POST['format']) : 'csv';

        $data = array();

        switch ($type) {
            case 'students':
                $result = Student_Manager::get_students(array('per_page' => -1));
                $data = $result['students'];
                break;
            case 'sponsorships':
                $result = Sponsor_Manager::get_sponsorships(array('per_page' => -1));
                $data = $result['sponsorships'];
                break;
            case 'payments':
                $result = Payment_Manager::get_payments(array('per_page' => -1));
                $data = $result['payments'];
                break;
        }

        wp_send_json_success(array('data' => $data));
    }
}
