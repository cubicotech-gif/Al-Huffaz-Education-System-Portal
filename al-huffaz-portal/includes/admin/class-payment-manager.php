<?php
/**
 * Payment Manager
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
 * Class Payment_Manager
 */
class Payment_Manager {

    /**
     * Constructor
     */
    public function __construct() {
        // Additional hooks if needed
    }

    /**
     * Get payments list
     */
    public static function get_payments($args = array()) {
        global $wpdb;

        $defaults = array(
            'page'     => 1,
            'per_page' => 20,
            'status'   => '',
            'sponsor'  => '',
            'student'  => '',
            'orderby'  => 'created_at',
            'order'    => 'DESC',
        );

        $args = wp_parse_args($args, $defaults);

        $table = $wpdb->prefix . 'alhuffaz_payments';
        $offset = ($args['page'] - 1) * $args['per_page'];

        $where = "1=1";
        $values = array();

        if (!empty($args['status'])) {
            $where .= " AND status = %s";
            $values[] = $args['status'];
        }

        if (!empty($args['sponsor'])) {
            $where .= " AND sponsor_id = %d";
            $values[] = $args['sponsor'];
        }

        if (!empty($args['student'])) {
            $where .= " AND student_id = %d";
            $values[] = $args['student'];
        }

        // Get total count
        if (!empty($values)) {
            $total = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE $where", $values));
        } else {
            $total = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE $where");
        }

        // Get payments
        $order = sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']);

        $query_values = $values;
        $query_values[] = $args['per_page'];
        $query_values[] = $offset;

        if (!empty($values)) {
            $payments = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table WHERE $where ORDER BY $order LIMIT %d OFFSET %d",
                $query_values
            ));
        } else {
            $payments = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table WHERE $where ORDER BY $order LIMIT %d OFFSET %d",
                $args['per_page'],
                $offset
            ));
        }

        $result = array();

        foreach ($payments as $payment) {
            $sponsorship = get_post($payment->sponsorship_id);
            $student = get_post($payment->student_id);
            $verified_by = $payment->verified_by ? get_user_by('id', $payment->verified_by) : null;

            $result[] = array(
                'id'               => $payment->id,
                'sponsorship_id'   => $payment->sponsorship_id,
                'sponsor_id'       => $payment->sponsor_id,
                'sponsor_name'     => $sponsorship ? get_post_meta($sponsorship->ID, '_sponsor_name', true) : '-',
                'student_id'       => $payment->student_id,
                'student_name'     => $student ? $student->post_title : '-',
                'amount'           => $payment->amount,
                'amount_formatted' => Helpers::format_currency($payment->amount),
                'payment_method'   => $payment->payment_method,
                'transaction_id'   => $payment->transaction_id,
                'payment_date'     => $payment->payment_date,
                'payment_date_formatted' => Helpers::format_date($payment->payment_date),
                'status'           => $payment->status,
                'status_badge'     => Helpers::get_status_badge($payment->status),
                'verified_by'      => $verified_by ? $verified_by->display_name : null,
                'verified_at'      => $payment->verified_at,
                'notes'            => $payment->notes,
                'created_at'       => $payment->created_at,
            );
        }

        return array(
            'payments'    => $result,
            'total'       => intval($total),
            'total_pages' => ceil($total / $args['per_page']),
            'page'        => $args['page'],
        );
    }

    /**
     * Get single payment
     */
    public static function get_payment($payment_id) {
        global $wpdb;

        $table = $wpdb->prefix . 'alhuffaz_payments';

        $payment = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $payment_id
        ));

        if (!$payment) {
            return null;
        }

        $sponsorship = get_post($payment->sponsorship_id);
        $student = get_post($payment->student_id);

        return array(
            'id'               => $payment->id,
            'sponsorship_id'   => $payment->sponsorship_id,
            'sponsor_id'       => $payment->sponsor_id,
            'sponsor_name'     => $sponsorship ? get_post_meta($sponsorship->ID, '_sponsor_name', true) : '-',
            'student_id'       => $payment->student_id,
            'student_name'     => $student ? $student->post_title : '-',
            'amount'           => $payment->amount,
            'payment_method'   => $payment->payment_method,
            'transaction_id'   => $payment->transaction_id,
            'payment_date'     => $payment->payment_date,
            'status'           => $payment->status,
            'verified_by'      => $payment->verified_by,
            'verified_at'      => $payment->verified_at,
            'notes'            => $payment->notes,
            'created_at'       => $payment->created_at,
        );
    }

    /**
     * Verify payment
     */
    public static function verify($payment_id, $status = 'approved') {
        global $wpdb;

        $table = $wpdb->prefix . 'alhuffaz_payments';

        $result = $wpdb->update(
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

        if ($result === false) {
            return new \WP_Error('update_failed', __('Failed to update payment.', 'al-huffaz-portal'));
        }

        // Log activity
        Helpers::log_activity('verify_payment', 'payment', $payment_id, 'Payment ' . $status);

        // Send notification to sponsor
        $payment = self::get_payment($payment_id);

        if ($payment && $payment['sponsor_id']) {
            $sponsor = get_user_by('id', $payment['sponsor_id']);

            if ($sponsor) {
                Helpers::send_notification(
                    $sponsor->user_email,
                    __('Payment Verified', 'al-huffaz-portal'),
                    sprintf(
                        __('Your payment of %s has been verified. Thank you for your support!', 'al-huffaz-portal'),
                        Helpers::format_currency($payment['amount'])
                    )
                );

                // CRITICAL: Clear sponsor dashboard cache for instant update
                wp_cache_delete('sponsor_dashboard_' . $payment['sponsor_id'], 'alhuffaz');
                wp_cache_flush();
            }
        }

        return true;
    }

    /**
     * Create payment record
     */
    public static function create($data) {
        global $wpdb;

        $table = $wpdb->prefix . 'alhuffaz_payments';

        $result = $wpdb->insert($table, array(
            'sponsorship_id' => intval($data['sponsorship_id']),
            'sponsor_id'     => intval($data['sponsor_id']),
            'student_id'     => intval($data['student_id']),
            'amount'         => floatval($data['amount']),
            'payment_method' => sanitize_text_field($data['payment_method']),
            'transaction_id' => sanitize_text_field($data['transaction_id'] ?? ''),
            'payment_date'   => $data['payment_date'] ?? current_time('mysql'),
            'status'         => $data['status'] ?? 'pending',
            'notes'          => sanitize_textarea_field($data['notes'] ?? ''),
            'created_at'     => current_time('mysql'),
        ));

        if ($result === false) {
            return new \WP_Error('insert_failed', __('Failed to create payment.', 'al-huffaz-portal'));
        }

        $payment_id = $wpdb->insert_id;

        // Log activity
        Helpers::log_activity('create_payment', 'payment', $payment_id, 'Created payment');

        return $payment_id;
    }

    /**
     * Get counts by status
     */
    public static function get_counts() {
        global $wpdb;

        $table = $wpdb->prefix . 'alhuffaz_payments';

        $pending = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status = 'pending'");
        $approved = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status = 'approved'");
        $rejected = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status = 'rejected'");

        return array(
            'pending'  => intval($pending),
            'approved' => intval($approved),
            'rejected' => intval($rejected),
            'total'    => intval($pending) + intval($approved) + intval($rejected),
        );
    }

    /**
     * Get total revenue
     */
    public static function get_total_revenue() {
        global $wpdb;

        $table = $wpdb->prefix . 'alhuffaz_payments';

        $total = $wpdb->get_var("SELECT COALESCE(SUM(amount), 0) FROM $table WHERE status = 'approved'");

        return floatval($total);
    }

    /**
     * Get revenue by period
     */
    public static function get_revenue_by_period($period = 'month') {
        global $wpdb;

        $table = $wpdb->prefix . 'alhuffaz_payments';

        switch ($period) {
            case 'today':
                $where = "DATE(payment_date) = CURDATE()";
                break;
            case 'week':
                $where = "payment_date >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
                break;
            case 'month':
                $where = "MONTH(payment_date) = MONTH(NOW()) AND YEAR(payment_date) = YEAR(NOW())";
                break;
            case 'year':
                $where = "YEAR(payment_date) = YEAR(NOW())";
                break;
            default:
                $where = "1=1";
        }

        $total = $wpdb->get_var("SELECT COALESCE(SUM(amount), 0) FROM $table WHERE status = 'approved' AND $where");

        return floatval($total);
    }

    /**
     * Get payments for sponsor
     */
    public static function get_sponsor_payments($sponsor_id, $args = array()) {
        return self::get_payments(array_merge($args, array('sponsor' => $sponsor_id)));
    }

    /**
     * Get payments for student
     */
    public static function get_student_payments($student_id, $args = array()) {
        return self::get_payments(array_merge($args, array('student' => $student_id)));
    }
}
