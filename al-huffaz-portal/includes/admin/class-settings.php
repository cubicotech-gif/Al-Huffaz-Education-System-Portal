<?php
/**
 * Settings
 *
 * @package AlHuffaz
 */

namespace AlHuffaz\Admin;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Settings
 */
class Settings {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_ajax_alhuffaz_save_settings', array($this, 'save_settings'));
    }

    /**
     * Register settings
     */
    public function register_settings() {
        // General settings
        register_setting('alhuffaz_settings', 'alhuffaz_school_name');
        register_setting('alhuffaz_settings', 'alhuffaz_school_email');
        register_setting('alhuffaz_settings', 'alhuffaz_school_phone');
        register_setting('alhuffaz_settings', 'alhuffaz_school_address');
        register_setting('alhuffaz_settings', 'alhuffaz_school_logo');

        // Currency settings
        register_setting('alhuffaz_settings', 'alhuffaz_currency');
        register_setting('alhuffaz_settings', 'alhuffaz_currency_symbol');

        // Email settings
        register_setting('alhuffaz_settings', 'alhuffaz_enable_email_notifications');
        register_setting('alhuffaz_settings', 'alhuffaz_admin_email');

        // Academic settings
        register_setting('alhuffaz_settings', 'alhuffaz_academic_year');
        register_setting('alhuffaz_settings', 'alhuffaz_grade_levels');
        register_setting('alhuffaz_settings', 'alhuffaz_islamic_categories');

        // Payment settings
        register_setting('alhuffaz_settings', 'alhuffaz_payment_methods');
        register_setting('alhuffaz_settings', 'alhuffaz_bank_details');

        // Appearance settings
        register_setting('alhuffaz_settings', 'alhuffaz_primary_color');
        register_setting('alhuffaz_settings', 'alhuffaz_secondary_color');
    }

    /**
     * Save settings via AJAX
     */
    public function save_settings() {
        check_ajax_referer('alhuffaz_admin_nonce', 'nonce');

        if (!current_user_can('alhuffaz_manage_settings')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'al-huffaz-portal')));
        }

        $settings = isset($_POST['settings']) ? $_POST['settings'] : array();

        foreach ($settings as $key => $value) {
            if (strpos($key, 'alhuffaz_') === 0) {
                if (is_array($value)) {
                    update_option($key, $value);
                } else {
                    update_option($key, sanitize_text_field($value));
                }
            }
        }

        wp_send_json_success(array('message' => __('Settings saved successfully.', 'al-huffaz-portal')));
    }

    /**
     * Get all settings
     */
    public static function get_all() {
        return array(
            'school_name'              => get_option('alhuffaz_school_name', 'Al-Huffaz Education System'),
            'school_email'             => get_option('alhuffaz_school_email', ''),
            'school_phone'             => get_option('alhuffaz_school_phone', ''),
            'school_address'           => get_option('alhuffaz_school_address', ''),
            'school_logo'              => get_option('alhuffaz_school_logo', ''),
            'currency'                 => get_option('alhuffaz_currency', 'PKR'),
            'currency_symbol'          => get_option('alhuffaz_currency_symbol', 'Rs.'),
            'enable_email_notifications' => get_option('alhuffaz_enable_email_notifications', 'yes'),
            'admin_email'              => get_option('alhuffaz_admin_email', get_option('admin_email')),
            'academic_year'            => get_option('alhuffaz_academic_year', date('Y') . '-' . (date('Y') + 1)),
            'grade_levels'             => get_option('alhuffaz_grade_levels', self::get_default_grades()),
            'islamic_categories'       => get_option('alhuffaz_islamic_categories', self::get_default_categories()),
            'payment_methods'          => get_option('alhuffaz_payment_methods', array('bank_transfer', 'jazzcash', 'easypaisa')),
            'bank_details'             => get_option('alhuffaz_bank_details', ''),
            'primary_color'            => get_option('alhuffaz_primary_color', '#10b981'),
            'secondary_color'          => get_option('alhuffaz_secondary_color', '#3b82f6'),
        );
    }

    /**
     * Get default grade levels
     */
    public static function get_default_grades() {
        return array(
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
    }

    /**
     * Get default Islamic categories
     */
    public static function get_default_categories() {
        return array(
            'hifz'   => 'Hifz',
            'nazra'  => 'Nazra',
            'qaidah' => 'Qaidah',
        );
    }

    /**
     * Get setting
     */
    public static function get($key, $default = '') {
        return get_option('alhuffaz_' . $key, $default);
    }

    /**
     * Update setting
     */
    public static function update($key, $value) {
        return update_option('alhuffaz_' . $key, $value);
    }
}
