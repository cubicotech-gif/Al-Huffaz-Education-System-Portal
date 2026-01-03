<?php
/**
 * Helper Functions
 *
 * @package AlHuffaz
 */

namespace AlHuffaz\Core;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Helpers
 */
class Helpers {

    /**
     * Format currency
     */
    public static function format_currency($amount) {
        $symbol = get_option('alhuffaz_currency_symbol', 'Rs.');
        return $symbol . ' ' . number_format((float) $amount, 0);
    }

    /**
     * Format date
     */
    public static function format_date($date, $format = null) {
        if (empty($date)) {
            return '-';
        }

        if (!$format) {
            $format = get_option('date_format', 'F j, Y');
        }

        $timestamp = is_numeric($date) ? $date : strtotime($date);
        return date_i18n($format, $timestamp);
    }

    /**
     * Get grade level label
     */
    public static function get_grade_label($grade_key) {
        $grades = get_option('alhuffaz_grade_levels', array());

        if (isset($grades[$grade_key])) {
            return $grades[$grade_key];
        }

        // Default mapping
        $default = array(
            'kg1'    => 'KG-1',
            'kg2'    => 'KG-2',
            'class1' => 'Class 1',
            'class2' => 'Class 2',
            'class3' => 'Class 3',
            'level1' => 'Level 1',
            'level2' => 'Level 2',
            'level3' => 'Level 3',
            'shb'    => 'SHB',
            'shg'    => 'SHG',
        );

        return isset($default[$grade_key]) ? $default[$grade_key] : ucfirst($grade_key);
    }

    /**
     * Get Islamic category label
     */
    public static function get_islamic_category_label($cat_key) {
        $categories = get_option('alhuffaz_islamic_categories', array());

        if (isset($categories[$cat_key])) {
            return $categories[$cat_key];
        }

        $default = array(
            'hifz'   => 'Hifz',
            'nazra'  => 'Nazra',
            'qaidah' => 'Qaidah',
        );

        return isset($default[$cat_key]) ? $default[$cat_key] : ucfirst($cat_key);
    }

    /**
     * Get sponsorship status label
     */
    public static function get_status_label($status) {
        $statuses = array(
            'pending'               => __('Pending', 'al-huffaz-portal'),
            'approved'              => __('Approved', 'al-huffaz-portal'),
            'rejected'              => __('Rejected', 'al-huffaz-portal'),
            'awaiting_admin_review' => __('Awaiting Review', 'al-huffaz-portal'),
            'active'                => __('Active', 'al-huffaz-portal'),
            'inactive'              => __('Inactive', 'al-huffaz-portal'),
            'completed'             => __('Completed', 'al-huffaz-portal'),
        );

        return isset($statuses[$status]) ? $statuses[$status] : ucfirst($status);
    }

    /**
     * Get status badge HTML
     */
    public static function get_status_badge($status) {
        $label = self::get_status_label($status);

        $classes = array(
            'pending'               => 'badge-warning',
            'approved'              => 'badge-success',
            'rejected'              => 'badge-danger',
            'awaiting_admin_review' => 'badge-info',
            'active'                => 'badge-success',
            'inactive'              => 'badge-secondary',
            'completed'             => 'badge-primary',
        );

        $class = isset($classes[$status]) ? $classes[$status] : 'badge-secondary';

        return '<span class="alhuffaz-badge ' . esc_attr($class) . '">' . esc_html($label) . '</span>';
    }

    /**
     * Calculate percentage
     */
    public static function calculate_percentage($obtained, $total) {
        if ($total <= 0) {
            return 0;
        }

        return round(($obtained / $total) * 100, 2);
    }

    /**
     * Get grade from percentage
     */
    public static function get_grade_from_percentage($percentage) {
        if ($percentage >= 90) return 'A+';
        if ($percentage >= 80) return 'A';
        if ($percentage >= 70) return 'B';
        if ($percentage >= 60) return 'C';
        if ($percentage >= 50) return 'D';
        return 'F';
    }

    /**
     * Get student photo URL
     */
    public static function get_student_photo($student_id, $size = 'thumbnail') {
        $photo_id = get_post_meta($student_id, '_student_photo', true);

        if ($photo_id) {
            $image = wp_get_attachment_image_src($photo_id, $size);
            if ($image) {
                return $image[0];
            }
        }

        // Return placeholder
        return ALHUFFAZ_ASSETS_URL . 'images/student-placeholder.png';
    }

    /**
     * Get payment methods
     */
    public static function get_payment_methods() {
        return array(
            'bank_transfer' => __('Bank Transfer', 'al-huffaz-portal'),
            'jazzcash'      => __('JazzCash', 'al-huffaz-portal'),
            'easypaisa'     => __('EasyPaisa', 'al-huffaz-portal'),
            'sadapay'       => __('SadaPay', 'al-huffaz-portal'),
            'nayapay'       => __('NayaPay', 'al-huffaz-portal'),
            'cash'          => __('Cash', 'al-huffaz-portal'),
            'other'         => __('Other', 'al-huffaz-portal'),
        );
    }

    /**
     * Get sponsorship types
     */
    public static function get_sponsorship_types() {
        return array(
            'monthly'   => __('Monthly', 'al-huffaz-portal'),
            'quarterly' => __('Quarterly', 'al-huffaz-portal'),
            'biannual'  => __('Bi-Annual', 'al-huffaz-portal'),
            'yearly'    => __('Yearly', 'al-huffaz-portal'),
            'one_time'  => __('One-Time', 'al-huffaz-portal'),
        );
    }

    /**
     * Sanitize phone number
     */
    public static function sanitize_phone($phone) {
        return preg_replace('/[^0-9+]/', '', $phone);
    }

    /**
     * Validate CNIC
     */
    public static function validate_cnic($cnic) {
        $cnic = preg_replace('/[^0-9]/', '', $cnic);
        return strlen($cnic) === 13;
    }

    /**
     * Format CNIC
     */
    public static function format_cnic($cnic) {
        $cnic = preg_replace('/[^0-9]/', '', $cnic);

        if (strlen($cnic) === 13) {
            return substr($cnic, 0, 5) . '-' . substr($cnic, 5, 7) . '-' . substr($cnic, 12, 1);
        }

        return $cnic;
    }

    /**
     * Generate random password
     */
    public static function generate_password($length = 12) {
        return wp_generate_password($length, true, false);
    }

    /**
     * Send email notification
     */
    public static function send_notification($to, $subject, $message, $type = 'general') {
        if (get_option('alhuffaz_enable_email_notifications') !== 'yes') {
            return false;
        }

        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_option('alhuffaz_school_name', 'Al-Huffaz') . ' <' . get_option('alhuffaz_admin_email', get_option('admin_email')) . '>',
        );

        // Wrap message in template
        $message = self::get_email_template($message, $subject);

        return wp_mail($to, $subject, $message, $headers);
    }

    /**
     * Get email template
     */
    private static function get_email_template($content, $subject) {
        $school_name = get_option('alhuffaz_school_name', 'Al-Huffaz Education System');

        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
        </head>
        <body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4;">
            <table cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color: #f4f4f4; padding: 20px;">
                <tr>
                    <td align="center">
                        <table cellpadding="0" cellspacing="0" border="0" width="600" style="background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                            <tr>
                                <td style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); padding: 30px; text-align: center;">
                                    <h1 style="color: #ffffff; margin: 0; font-size: 24px;">' . esc_html($school_name) . '</h1>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding: 30px;">
                                    <h2 style="color: #1f2937; margin-top: 0;">' . esc_html($subject) . '</h2>
                                    <div style="color: #4b5563; line-height: 1.6;">
                                        ' . $content . '
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td style="background-color: #f9fafb; padding: 20px; text-align: center; border-top: 1px solid #e5e7eb;">
                                    <p style="color: #6b7280; margin: 0; font-size: 14px;">
                                        &copy; ' . date('Y') . ' ' . esc_html($school_name) . '. All rights reserved.
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>';
    }

    /**
     * Log activity
     */
    public static function log_activity($action, $object_type, $object_id, $details = '') {
        global $wpdb;

        $table = $wpdb->prefix . 'alhuffaz_activity_log';

        $wpdb->insert($table, array(
            'user_id'     => get_current_user_id(),
            'action'      => $action,
            'object_type' => $object_type,
            'object_id'   => $object_id,
            'details'     => $details,
            'ip_address'  => self::get_client_ip(),
            'created_at'  => current_time('mysql'),
        ));
    }

    /**
     * Get client IP address
     */
    public static function get_client_ip() {
        $ip = '';

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = sanitize_text_field($_SERVER['HTTP_CLIENT_IP']);
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = sanitize_text_field($_SERVER['HTTP_X_FORWARDED_FOR']);
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = sanitize_text_field($_SERVER['REMOTE_ADDR']);
        }

        return $ip;
    }

    /**
     * Get time ago string
     */
    public static function time_ago($datetime) {
        $timestamp = is_numeric($datetime) ? $datetime : strtotime($datetime);
        return human_time_diff($timestamp, current_time('timestamp')) . ' ' . __('ago', 'al-huffaz-portal');
    }

    /**
     * Render template
     */
    public static function render_template($template, $data = array()) {
        $file = ALHUFFAZ_TEMPLATES_DIR . $template . '.php';

        if (!file_exists($file)) {
            return '';
        }

        extract($data);

        ob_start();
        include $file;
        return ob_get_clean();
    }

    /**
     * Get countries list
     */
    public static function get_countries() {
        return array(
            'PK' => 'Pakistan',
            'SA' => 'Saudi Arabia',
            'AE' => 'United Arab Emirates',
            'US' => 'United States',
            'GB' => 'United Kingdom',
            'CA' => 'Canada',
            'AU' => 'Australia',
            'QA' => 'Qatar',
            'KW' => 'Kuwait',
            'BH' => 'Bahrain',
            'OM' => 'Oman',
            'MY' => 'Malaysia',
            'SG' => 'Singapore',
            'DE' => 'Germany',
            'FR' => 'France',
            'IT' => 'Italy',
            'ES' => 'Spain',
            'NL' => 'Netherlands',
            'BE' => 'Belgium',
            'SE' => 'Sweden',
            'NO' => 'Norway',
            'DK' => 'Denmark',
            'CH' => 'Switzerland',
            'AT' => 'Austria',
            'OTHER' => 'Other',
        );
    }

    /**
     * Check if valid JSON
     */
    public static function is_json($string) {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Safe JSON decode
     */
    public static function json_decode($string, $assoc = true) {
        if (empty($string)) {
            return $assoc ? array() : new \stdClass();
        }

        $result = json_decode($string, $assoc);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return $assoc ? array() : new \stdClass();
        }

        return $result;
    }
}
