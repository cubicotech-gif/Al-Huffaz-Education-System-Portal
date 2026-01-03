<?php
/**
 * Assets Management - CSS and JavaScript
 *
 * @package AlHuffaz
 */

namespace AlHuffaz\Core;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Assets
 */
class Assets {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_enqueue_scripts', array($this, 'admin_assets'));
        add_action('wp_enqueue_scripts', array($this, 'public_assets'));
    }

    /**
     * Register and enqueue admin assets
     */
    public function admin_assets($hook) {
        // Only load on our plugin pages
        if (strpos($hook, 'alhuffaz') === false && strpos($hook, 'al-huffaz') === false) {
            return;
        }

        // Admin CSS
        wp_enqueue_style(
            'alhuffaz-admin',
            ALHUFFAZ_ASSETS_URL . 'css/admin.css',
            array(),
            ALHUFFAZ_VERSION
        );

        // Chart.js
        wp_enqueue_script(
            'chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js',
            array(),
            '4.4.1',
            true
        );

        // Flatpickr for date picking
        wp_enqueue_style(
            'flatpickr',
            'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css',
            array(),
            '4.6.13'
        );

        wp_enqueue_script(
            'flatpickr',
            'https://cdn.jsdelivr.net/npm/flatpickr',
            array(),
            '4.6.13',
            true
        );

        // Admin JS
        wp_enqueue_script(
            'alhuffaz-admin',
            ALHUFFAZ_ASSETS_URL . 'js/admin.js',
            array('jquery', 'chartjs', 'flatpickr'),
            ALHUFFAZ_VERSION,
            true
        );

        // Localize script
        wp_localize_script('alhuffaz-admin', 'alhuffazAdmin', array(
            'ajaxUrl'       => admin_url('admin-ajax.php'),
            'nonce'         => wp_create_nonce('alhuffaz_admin_nonce'),
            'strings'       => array(
                'confirm_delete'  => __('Are you sure you want to delete this?', 'al-huffaz-portal'),
                'saving'          => __('Saving...', 'al-huffaz-portal'),
                'saved'           => __('Saved successfully!', 'al-huffaz-portal'),
                'error'           => __('An error occurred. Please try again.', 'al-huffaz-portal'),
                'loading'         => __('Loading...', 'al-huffaz-portal'),
            ),
            'currency'      => get_option('alhuffaz_currency_symbol', 'Rs.'),
        ));

        // Media uploader
        wp_enqueue_media();
    }

    /**
     * Register and enqueue public assets
     */
    public function public_assets() {
        // Only load when needed (shortcodes will set this)
        if (!$this->should_load_public_assets()) {
            return;
        }

        // Public CSS
        wp_enqueue_style(
            'alhuffaz-public',
            ALHUFFAZ_ASSETS_URL . 'css/public.css',
            array(),
            ALHUFFAZ_VERSION
        );

        // Flatpickr
        wp_enqueue_style(
            'flatpickr',
            'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css',
            array(),
            '4.6.13'
        );

        wp_enqueue_script(
            'flatpickr',
            'https://cdn.jsdelivr.net/npm/flatpickr',
            array(),
            '4.6.13',
            true
        );

        // Public JS
        wp_enqueue_script(
            'alhuffaz-public',
            ALHUFFAZ_ASSETS_URL . 'js/public.js',
            array('jquery', 'flatpickr'),
            ALHUFFAZ_VERSION,
            true
        );

        // Localize script
        wp_localize_script('alhuffaz-public', 'alhuffazPublic', array(
            'ajaxUrl'       => admin_url('admin-ajax.php'),
            'nonce'         => wp_create_nonce('alhuffaz_public_nonce'),
            'strings'       => array(
                'confirm_submit'  => __('Are you sure you want to submit?', 'al-huffaz-portal'),
                'submitting'      => __('Submitting...', 'al-huffaz-portal'),
                'success'         => __('Submitted successfully!', 'al-huffaz-portal'),
                'error'           => __('An error occurred. Please try again.', 'al-huffaz-portal'),
                'loading'         => __('Loading...', 'al-huffaz-portal'),
                'required_field'  => __('This field is required', 'al-huffaz-portal'),
            ),
            'currency'      => get_option('alhuffaz_currency_symbol', 'Rs.'),
        ));
    }

    /**
     * Check if public assets should be loaded
     */
    private function should_load_public_assets() {
        global $post;

        if (!$post) {
            return false;
        }

        // Check for our shortcodes
        $shortcodes = array(
            'alhuffaz_student_display',
            'alhuffaz_sponsor_dashboard',
            'alhuffaz_payment_form',
            'alhuffaz_sponsorship_form',
        );

        foreach ($shortcodes as $shortcode) {
            if (has_shortcode($post->post_content, $shortcode)) {
                return true;
            }
        }

        // Check for specific page templates
        $template = get_page_template_slug($post->ID);
        if (strpos($template, 'alhuffaz') !== false) {
            return true;
        }

        return false;
    }

    /**
     * Get inline CSS variables
     */
    public static function get_css_variables() {
        $primary_color = get_option('alhuffaz_primary_color', '#10b981');
        $secondary_color = get_option('alhuffaz_secondary_color', '#3b82f6');

        return "
            :root {
                --alhuffaz-primary: {$primary_color};
                --alhuffaz-primary-dark: " . self::darken_color($primary_color, 10) . ";
                --alhuffaz-primary-light: " . self::lighten_color($primary_color, 40) . ";
                --alhuffaz-secondary: {$secondary_color};
                --alhuffaz-secondary-dark: " . self::darken_color($secondary_color, 10) . ";
                --alhuffaz-success: #10b981;
                --alhuffaz-warning: #f59e0b;
                --alhuffaz-danger: #ef4444;
                --alhuffaz-info: #3b82f6;
                --alhuffaz-gray-50: #f9fafb;
                --alhuffaz-gray-100: #f3f4f6;
                --alhuffaz-gray-200: #e5e7eb;
                --alhuffaz-gray-300: #d1d5db;
                --alhuffaz-gray-400: #9ca3af;
                --alhuffaz-gray-500: #6b7280;
                --alhuffaz-gray-600: #4b5563;
                --alhuffaz-gray-700: #374151;
                --alhuffaz-gray-800: #1f2937;
                --alhuffaz-gray-900: #111827;
            }
        ";
    }

    /**
     * Darken a hex color
     */
    private static function darken_color($hex, $percent) {
        $hex = ltrim($hex, '#');
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        $r = max(0, $r - ($r * $percent / 100));
        $g = max(0, $g - ($g * $percent / 100));
        $b = max(0, $b - ($b * $percent / 100));

        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }

    /**
     * Lighten a hex color
     */
    private static function lighten_color($hex, $percent) {
        $hex = ltrim($hex, '#');
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        $r = min(255, $r + ((255 - $r) * $percent / 100));
        $g = min(255, $g + ((255 - $g) * $percent / 100));
        $b = min(255, $b + ((255 - $b) * $percent / 100));

        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }
}
