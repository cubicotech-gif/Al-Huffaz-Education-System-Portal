<?php
/**
 * Payment Form
 *
 * @package AlHuffaz
 */

namespace AlHuffaz\Frontend;

use AlHuffaz\Core\Helpers;
use AlHuffaz\Core\Roles;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Payment_Form
 */
class Payment_Form {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_ajax_alhuffaz_get_sponsor_sponsorships', array($this, 'get_sponsorships'));
    }

    /**
     * Get sponsorships for payment form
     */
    public function get_sponsorships() {
        check_ajax_referer('alhuffaz_public_nonce', 'nonce');

        if (!Roles::is_sponsor()) {
            wp_send_json_error(array('message' => __('Access denied.', 'al-huffaz-portal')));
        }

        $user_id = get_current_user_id();

        $sponsorships = get_posts(array(
            'post_type'      => 'alhuffaz_sponsor',
            'posts_per_page' => -1,
            'meta_query'     => array(
                array(
                    'key'   => '_sponsor_user_id',
                    'value' => $user_id,
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

        $result = array();

        foreach ($sponsorships as $sponsorship) {
            $student_id = get_post_meta($sponsorship->ID, '_student_id', true);
            $student = get_post($student_id);

            $result[] = array(
                'id'           => $sponsorship->ID,
                'student_id'   => $student_id,
                'student_name' => $student ? $student->post_title : '-',
                'amount'       => get_post_meta($sponsorship->ID, '_amount', true),
                'type'         => get_post_meta($sponsorship->ID, '_sponsorship_type', true),
            );
        }

        wp_send_json_success(array('sponsorships' => $result));
    }

    /**
     * Get payment methods
     */
    public static function get_payment_methods() {
        $enabled = get_option('alhuffaz_payment_methods', array('bank_transfer', 'jazzcash', 'easypaisa'));
        $all_methods = Helpers::get_payment_methods();

        $methods = array();

        foreach ($enabled as $key) {
            if (isset($all_methods[$key])) {
                $methods[$key] = $all_methods[$key];
            }
        }

        return $methods;
    }

    /**
     * Get bank details
     */
    public static function get_bank_details() {
        return get_option('alhuffaz_bank_details', '');
    }

    /**
     * Validate payment data
     */
    public static function validate($data) {
        $errors = array();

        if (empty($data['sponsorship_id'])) {
            $errors[] = __('Please select a sponsorship.', 'al-huffaz-portal');
        }

        if (empty($data['amount']) || floatval($data['amount']) <= 0) {
            $errors[] = __('Please enter a valid amount.', 'al-huffaz-portal');
        }

        if (empty($data['payment_method'])) {
            $errors[] = __('Please select a payment method.', 'al-huffaz-portal');
        }

        return $errors;
    }
}
