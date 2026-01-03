<?php
/**
 * Plugin Name: Al-Huffaz Education Portal
 * Plugin URI: https://github.com/cubicotech-gif/Al-Huffaz-Education-System-Portal
 * Description: A comprehensive educational management system for Al-Huffaz Islamic School - Managing students, sponsors, payments, and academic records.
 * Version: 2.0.0
 * Author: Al-Huffaz Development Team
 * Author URI: https://alhuffaz.edu
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: al-huffaz-portal
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('ALHUFFAZ_VERSION', '2.0.0');
define('ALHUFFAZ_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ALHUFFAZ_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ALHUFFAZ_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('ALHUFFAZ_ASSETS_URL', ALHUFFAZ_PLUGIN_URL . 'assets/');
define('ALHUFFAZ_TEMPLATES_DIR', ALHUFFAZ_PLUGIN_DIR . 'templates/');

/**
 * Main Plugin Class
 */
final class Al_Huffaz_Portal {

    /**
     * Single instance of the class
     */
    private static $instance = null;

    /**
     * Plugin components
     */
    public $student;
    public $sponsor;
    public $payment;
    public $dashboard;
    public $ajax;

    /**
     * Get single instance
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }

    /**
     * Load required files
     */
    private function load_dependencies() {
        // Core classes
        require_once ALHUFFAZ_PLUGIN_DIR . 'includes/core/class-autoloader.php';
        require_once ALHUFFAZ_PLUGIN_DIR . 'includes/core/class-post-types.php';
        require_once ALHUFFAZ_PLUGIN_DIR . 'includes/core/class-roles.php';
        require_once ALHUFFAZ_PLUGIN_DIR . 'includes/core/class-assets.php';
        require_once ALHUFFAZ_PLUGIN_DIR . 'includes/core/class-ajax-handler.php';
        require_once ALHUFFAZ_PLUGIN_DIR . 'includes/core/class-helpers.php';

        // Admin classes
        require_once ALHUFFAZ_PLUGIN_DIR . 'includes/admin/class-admin-menu.php';
        require_once ALHUFFAZ_PLUGIN_DIR . 'includes/admin/class-dashboard.php';
        require_once ALHUFFAZ_PLUGIN_DIR . 'includes/admin/class-student-manager.php';
        require_once ALHUFFAZ_PLUGIN_DIR . 'includes/admin/class-sponsor-manager.php';
        require_once ALHUFFAZ_PLUGIN_DIR . 'includes/admin/class-payment-manager.php';
        require_once ALHUFFAZ_PLUGIN_DIR . 'includes/admin/class-reports.php';
        require_once ALHUFFAZ_PLUGIN_DIR . 'includes/admin/class-settings.php';
        require_once ALHUFFAZ_PLUGIN_DIR . 'includes/admin/class-bulk-import.php';

        // Public classes
        require_once ALHUFFAZ_PLUGIN_DIR . 'includes/public/class-shortcodes.php';
        require_once ALHUFFAZ_PLUGIN_DIR . 'includes/public/class-sponsor-dashboard.php';
        require_once ALHUFFAZ_PLUGIN_DIR . 'includes/public/class-student-display.php';
        require_once ALHUFFAZ_PLUGIN_DIR . 'includes/public/class-payment-form.php';
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Activation/Deactivation
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        // Initialize plugin
        add_action('plugins_loaded', array($this, 'init_plugin'));

        // Load text domain
        add_action('init', array($this, 'load_textdomain'));
    }

    /**
     * Initialize plugin components
     */
    public function init_plugin() {
        // Initialize post types
        new AlHuffaz\Core\Post_Types();

        // Initialize roles
        new AlHuffaz\Core\Roles();

        // Initialize assets
        new AlHuffaz\Core\Assets();

        // Initialize AJAX handler
        $this->ajax = new AlHuffaz\Core\Ajax_Handler();

        // Admin components
        if (is_admin()) {
            new AlHuffaz\Admin\Admin_Menu();
            $this->dashboard = new AlHuffaz\Admin\Dashboard();
            $this->student = new AlHuffaz\Admin\Student_Manager();
            $this->sponsor = new AlHuffaz\Admin\Sponsor_Manager();
            $this->payment = new AlHuffaz\Admin\Payment_Manager();
            new AlHuffaz\Admin\Reports();
            new AlHuffaz\Admin\Settings();
            new AlHuffaz\Admin\Bulk_Import();
        }

        // Public components
        new AlHuffaz\Frontend\Shortcodes();
        new AlHuffaz\Frontend\Sponsor_Dashboard();
        new AlHuffaz\Frontend\Student_Display();
        new AlHuffaz\Frontend\Payment_Form();
    }

    /**
     * Plugin activation
     */
    public function activate() {
        // Create database tables if needed
        $this->create_tables();

        // Add default options
        $this->add_default_options();

        // Create custom roles
        AlHuffaz\Core\Roles::create_roles();

        // Flush rewrite rules
        flush_rewrite_rules();

        // Set activation flag
        set_transient('alhuffaz_activation_redirect', true, 30);
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Create custom database tables
     */
    private function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Payment history table
        $table_payments = $wpdb->prefix . 'alhuffaz_payments';
        $sql_payments = "CREATE TABLE IF NOT EXISTS $table_payments (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            sponsorship_id bigint(20) NOT NULL,
            sponsor_id bigint(20) NOT NULL,
            student_id bigint(20) NOT NULL,
            amount decimal(10,2) NOT NULL,
            payment_method varchar(50) NOT NULL,
            transaction_id varchar(100) DEFAULT NULL,
            payment_date datetime NOT NULL,
            status varchar(20) DEFAULT 'pending',
            verified_by bigint(20) DEFAULT NULL,
            verified_at datetime DEFAULT NULL,
            notes text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY sponsorship_id (sponsorship_id),
            KEY sponsor_id (sponsor_id),
            KEY student_id (student_id),
            KEY status (status)
        ) $charset_collate;";

        // Activity log table
        $table_logs = $wpdb->prefix . 'alhuffaz_activity_log';
        $sql_logs = "CREATE TABLE IF NOT EXISTS $table_logs (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            action varchar(100) NOT NULL,
            object_type varchar(50) NOT NULL,
            object_id bigint(20) NOT NULL,
            details text DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY action (action),
            KEY object_type (object_type)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_payments);
        dbDelta($sql_logs);
    }

    /**
     * Add default plugin options
     */
    private function add_default_options() {
        $defaults = array(
            'alhuffaz_currency' => 'PKR',
            'alhuffaz_currency_symbol' => 'Rs.',
            'alhuffaz_school_name' => 'Al-Huffaz Education System',
            'alhuffaz_school_email' => '',
            'alhuffaz_school_phone' => '',
            'alhuffaz_school_address' => '',
            'alhuffaz_enable_email_notifications' => 'yes',
            'alhuffaz_admin_email' => get_option('admin_email'),
            'alhuffaz_payment_methods' => array('bank_transfer', 'jazzcash', 'easypaisa'),
            'alhuffaz_academic_year' => date('Y') . '-' . (date('Y') + 1),
            'alhuffaz_grade_levels' => array(
                'kg1' => 'KG-1',
                'kg2' => 'KG-2',
                'class1' => 'Class 1',
                'class2' => 'Class 2',
                'class3' => 'Class 3',
                'level1' => 'Level 1',
                'level2' => 'Level 2',
                'level3' => 'Level 3',
                'shb' => 'SHB',
                'shg' => 'SHG'
            ),
            'alhuffaz_islamic_categories' => array(
                'hifz' => 'Hifz',
                'nazra' => 'Nazra',
                'qaidah' => 'Qaidah'
            )
        );

        foreach ($defaults as $key => $value) {
            if (get_option($key) === false) {
                add_option($key, $value);
            }
        }
    }

    /**
     * Load plugin text domain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'al-huffaz-portal',
            false,
            dirname(ALHUFFAZ_PLUGIN_BASENAME) . '/languages'
        );
    }
}

/**
 * Initialize the plugin
 */
function alhuffaz_portal() {
    return Al_Huffaz_Portal::instance();
}

// Start the plugin
alhuffaz_portal();
