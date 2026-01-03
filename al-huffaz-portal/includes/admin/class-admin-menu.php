<?php
/**
 * Admin Menu
 *
 * @package AlHuffaz
 */

namespace AlHuffaz\Admin;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Admin_Menu
 */
class Admin_Menu {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'register_menu'));
        add_action('admin_init', array($this, 'handle_activation_redirect'));
    }

    /**
     * Register admin menu
     */
    public function register_menu() {
        // Main menu
        add_menu_page(
            __('Al-Huffaz Portal', 'al-huffaz-portal'),
            __('Al-Huffaz', 'al-huffaz-portal'),
            'alhuffaz_view_dashboard',
            'alhuffaz-portal',
            array($this, 'render_dashboard'),
            'dashicons-welcome-learn-more',
            25
        );

        // Dashboard submenu
        add_submenu_page(
            'alhuffaz-portal',
            __('Dashboard', 'al-huffaz-portal'),
            __('Dashboard', 'al-huffaz-portal'),
            'alhuffaz_view_dashboard',
            'alhuffaz-portal',
            array($this, 'render_dashboard')
        );

        // Students submenu
        add_submenu_page(
            'alhuffaz-portal',
            __('Students', 'al-huffaz-portal'),
            __('Students', 'al-huffaz-portal'),
            'alhuffaz_manage_students',
            'alhuffaz-students',
            array($this, 'render_students')
        );

        // Add Student submenu
        add_submenu_page(
            'alhuffaz-portal',
            __('Add Student', 'al-huffaz-portal'),
            __('Add Student', 'al-huffaz-portal'),
            'alhuffaz_manage_students',
            'alhuffaz-add-student',
            array($this, 'render_add_student')
        );

        // Sponsors submenu
        add_submenu_page(
            'alhuffaz-portal',
            __('Sponsors', 'al-huffaz-portal'),
            __('Sponsors', 'al-huffaz-portal'),
            'alhuffaz_manage_sponsors',
            'alhuffaz-sponsors',
            array($this, 'render_sponsors')
        );

        // Payments submenu
        add_submenu_page(
            'alhuffaz-portal',
            __('Payments', 'al-huffaz-portal'),
            __('Payments', 'al-huffaz-portal'),
            'alhuffaz_manage_payments',
            'alhuffaz-payments',
            array($this, 'render_payments')
        );

        // Reports submenu
        add_submenu_page(
            'alhuffaz-portal',
            __('Reports', 'al-huffaz-portal'),
            __('Reports', 'al-huffaz-portal'),
            'alhuffaz_view_reports',
            'alhuffaz-reports',
            array($this, 'render_reports')
        );

        // Bulk Import submenu
        add_submenu_page(
            'alhuffaz-portal',
            __('Bulk Import', 'al-huffaz-portal'),
            __('Bulk Import', 'al-huffaz-portal'),
            'alhuffaz_bulk_import',
            'alhuffaz-import',
            array($this, 'render_import')
        );

        // Settings submenu
        add_submenu_page(
            'alhuffaz-portal',
            __('Settings', 'al-huffaz-portal'),
            __('Settings', 'al-huffaz-portal'),
            'alhuffaz_manage_settings',
            'alhuffaz-settings',
            array($this, 'render_settings')
        );
    }

    /**
     * Handle activation redirect
     */
    public function handle_activation_redirect() {
        if (get_transient('alhuffaz_activation_redirect')) {
            delete_transient('alhuffaz_activation_redirect');
            wp_redirect(admin_url('admin.php?page=alhuffaz-portal&welcome=1'));
            exit;
        }
    }

    /**
     * Render dashboard page
     */
    public function render_dashboard() {
        include ALHUFFAZ_TEMPLATES_DIR . 'admin/dashboard.php';
    }

    /**
     * Render students page
     */
    public function render_students() {
        include ALHUFFAZ_TEMPLATES_DIR . 'admin/students.php';
    }

    /**
     * Render add student page
     */
    public function render_add_student() {
        include ALHUFFAZ_TEMPLATES_DIR . 'admin/student-form.php';
    }

    /**
     * Render sponsors page
     */
    public function render_sponsors() {
        include ALHUFFAZ_TEMPLATES_DIR . 'admin/sponsors.php';
    }

    /**
     * Render payments page
     */
    public function render_payments() {
        include ALHUFFAZ_TEMPLATES_DIR . 'admin/payments.php';
    }

    /**
     * Render reports page
     */
    public function render_reports() {
        include ALHUFFAZ_TEMPLATES_DIR . 'admin/reports.php';
    }

    /**
     * Render import page
     */
    public function render_import() {
        include ALHUFFAZ_TEMPLATES_DIR . 'admin/import.php';
    }

    /**
     * Render settings page
     */
    public function render_settings() {
        include ALHUFFAZ_TEMPLATES_DIR . 'admin/settings.php';
    }
}
