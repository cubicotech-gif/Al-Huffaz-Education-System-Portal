<?php
/**
 * Shortcodes
 *
 * @package AlHuffaz
 */

namespace AlHuffaz\Frontend;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Shortcodes
 */
class Shortcodes {

    /**
     * Constructor
     */
    public function __construct() {
        add_shortcode('alhuffaz_students', array($this, 'students_display'));
        add_shortcode('alhuffaz_student_display', array($this, 'students_display'));
        add_shortcode('alhuffaz_sponsor_dashboard', array($this, 'sponsor_dashboard'));
        add_shortcode('alhuffaz_sponsorship_form', array($this, 'sponsorship_form'));
        add_shortcode('alhuffaz_payment_form', array($this, 'payment_form'));
        add_shortcode('alhuffaz_student_card', array($this, 'student_card'));
    }

    /**
     * Students display shortcode
     */
    public function students_display($atts) {
        $atts = shortcode_atts(array(
            'limit'    => 12,
            'grade'    => '',
            'category' => '',
            'columns'  => 3,
            'show_filters' => 'yes',
        ), $atts);

        ob_start();
        include ALHUFFAZ_TEMPLATES_DIR . 'public/students-grid.php';
        return ob_get_clean();
    }

    /**
     * Sponsor dashboard shortcode
     */
    public function sponsor_dashboard($atts) {
        if (!is_user_logged_in()) {
            return $this->login_form();
        }

        if (!\AlHuffaz\Core\Roles::is_sponsor()) {
            return '<div class="alhuffaz-notice notice-error">' . __('Access denied. This page is for sponsors only.', 'al-huffaz-portal') . '</div>';
        }

        ob_start();
        include ALHUFFAZ_TEMPLATES_DIR . 'public/sponsor-dashboard.php';
        return ob_get_clean();
    }

    /**
     * Sponsorship form shortcode
     */
    public function sponsorship_form($atts) {
        $atts = shortcode_atts(array(
            'student_id' => 0,
        ), $atts);

        ob_start();
        include ALHUFFAZ_TEMPLATES_DIR . 'public/sponsorship-form.php';
        return ob_get_clean();
    }

    /**
     * Payment form shortcode
     */
    public function payment_form($atts) {
        if (!is_user_logged_in()) {
            return $this->login_form();
        }

        if (!\AlHuffaz\Core\Roles::is_sponsor()) {
            return '<div class="alhuffaz-notice notice-error">' . __('Access denied. This page is for sponsors only.', 'al-huffaz-portal') . '</div>';
        }

        ob_start();
        include ALHUFFAZ_TEMPLATES_DIR . 'public/payment-form.php';
        return ob_get_clean();
    }

    /**
     * Single student card shortcode
     */
    public function student_card($atts) {
        $atts = shortcode_atts(array(
            'id' => 0,
        ), $atts);

        if (!$atts['id']) {
            return '';
        }

        $student = get_post($atts['id']);

        if (!$student || $student->post_type !== 'student') {
            return '';
        }

        ob_start();
        include ALHUFFAZ_TEMPLATES_DIR . 'public/student-card.php';
        return ob_get_clean();
    }

    /**
     * Login form
     */
    private function login_form() {
        ob_start();
        ?>
        <div class="alhuffaz-login-wrapper">
            <div class="alhuffaz-login-box">
                <h2><?php _e('Sponsor Login', 'al-huffaz-portal'); ?></h2>
                <p><?php _e('Please login to access your dashboard.', 'al-huffaz-portal'); ?></p>
                <?php
                wp_login_form(array(
                    'redirect' => get_permalink(),
                    'form_id' => 'alhuffaz-login-form',
                    'label_username' => __('Email or Username', 'al-huffaz-portal'),
                    'label_password' => __('Password', 'al-huffaz-portal'),
                    'label_remember' => __('Remember Me', 'al-huffaz-portal'),
                    'label_log_in' => __('Login', 'al-huffaz-portal'),
                ));
                ?>
                <p class="alhuffaz-login-links">
                    <a href="<?php echo wp_lostpassword_url(); ?>"><?php _e('Forgot Password?', 'al-huffaz-portal'); ?></a>
                </p>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
