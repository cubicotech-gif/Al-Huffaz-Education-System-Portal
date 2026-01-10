<?php
/**
 * Login Redirects Handler
 *
 * Handles smart routing for unified login system:
 * - Detects user role and redirects to appropriate dashboard
 * - Prevents sponsors from accessing WP admin
 * - Handles logout redirects
 * - Checks account approval status for sponsors
 *
 * @package AlHuffaz
 */

namespace AlHuffaz\Core;

defined('ABSPATH') || exit;

class Login_Redirects {

    /**
     * Constructor
     */
    public function __construct() {
        // Handle successful login redirects
        add_filter('login_redirect', array($this, 'handle_login_redirect'), 10, 3);

        // Handle logout redirects
        add_action('wp_logout', array($this, 'handle_logout_redirect'));

        // Prevent sponsors from accessing WP admin
        add_action('admin_init', array($this, 'prevent_sponsor_admin_access'));

        // Handle failed login redirects
        add_action('wp_login_failed', array($this, 'handle_login_failed'));

        // Check account approval status before login
        add_filter('authenticate', array($this, 'check_account_approval'), 30, 3);
    }

    /**
     * Handle login redirect based on user role
     *
     * @param string $redirect_to URL to redirect to
     * @param string $request Requested redirect URL
     * @param WP_User|WP_Error $user User object or error
     * @return string Redirect URL
     */
    public function handle_login_redirect($redirect_to, $request, $user) {
        // If not a valid user, return default
        if (!isset($user->roles) || is_wp_error($user)) {
            return $redirect_to;
        }

        // Get user roles
        $user_roles = (array) $user->roles;

        // Admin or Staff → Admin Portal
        if (in_array('alhuffaz_admin', $user_roles) ||
            in_array('alhuffaz_staff', $user_roles) ||
            in_array('alhuffaz_teacher', $user_roles) ||
            in_array('administrator', $user_roles)) {
            return Helpers::get_admin_portal_url();
        }

        // Sponsor → Check approval status
        if (in_array('alhuffaz_sponsor', $user_roles)) {
            $status = get_user_meta($user->ID, 'account_status', true);

            // If approved or empty (legacy accounts), go to dashboard
            if ($status === 'approved' || empty($status)) {
                return Helpers::get_sponsor_dashboard_url();
            }

            // If pending, logout and redirect to login with message
            if ($status === 'pending_approval') {
                wp_logout();
                return add_query_arg('login', 'pending', Helpers::get_login_url());
            }

            // If rejected, logout and redirect to login with message
            if ($status === 'rejected') {
                wp_logout();
                return add_query_arg('login', 'rejected', Helpers::get_login_url());
            }
        }

        // Default redirect for other users
        return $redirect_to;
    }

    /**
     * Handle logout redirect
     * Redirect all logouts to unified login page
     */
    public function handle_logout_redirect() {
        wp_safe_redirect(add_query_arg('logout', 'success', Helpers::get_login_url()));
        exit;
    }

    /**
     * Prevent sponsors from accessing WP admin
     * Sponsors should only use the frontend sponsor dashboard
     */
    public function prevent_sponsor_admin_access() {
        // Skip if not in admin area or doing AJAX
        if (!is_admin() || wp_doing_ajax()) {
            return;
        }

        // Get current user
        $user = wp_get_current_user();

        // Check if user is a sponsor
        if (in_array('alhuffaz_sponsor', $user->roles)) {
            // Redirect to sponsor dashboard
            wp_safe_redirect(Helpers::get_sponsor_dashboard_url());
            exit;
        }
    }

    /**
     * Handle failed login attempts
     * Redirect to unified login page with error message
     *
     * @param string $username Username used in login attempt
     */
    public function handle_login_failed($username) {
        // Get the referrer (where the login attempt came from)
        $referrer = wp_get_referer();

        // Only redirect if coming from our unified login page
        if ($referrer && strpos($referrer, '/login/') !== false) {
            wp_safe_redirect(home_url('/login/?login=failed'));
            exit;
        }
    }

    /**
     * Check account approval status before allowing login
     * For sponsors with pending_approval or rejected status
     *
     * @param WP_User|WP_Error|null $user User object or error
     * @param string $username Username
     * @param string $password Password
     * @return WP_User|WP_Error User object or error
     */
    public function check_account_approval($user, $username, $password) {
        // If already an error, return it
        if (is_wp_error($user)) {
            return $user;
        }

        // If valid user, check if they're a sponsor
        if ($user instanceof \WP_User) {
            $user_roles = (array) $user->roles;

            // Only check sponsors
            if (in_array('alhuffaz_sponsor', $user_roles)) {
                $status = get_user_meta($user->ID, 'account_status', true);

                // Block pending approval
                if ($status === 'pending_approval') {
                    return new \WP_Error(
                        'pending_approval',
                        __('Your account is pending approval. You will receive an email once approved.', 'al-huffaz-portal')
                    );
                }

                // Block rejected
                if ($status === 'rejected') {
                    return new \WP_Error(
                        'account_rejected',
                        __('Your account has been rejected. Please contact the administrator.', 'al-huffaz-portal')
                    );
                }
            }
        }

        return $user;
    }

    /**
     * Get redirect URL for a specific user role
     * Utility method for getting the correct dashboard URL
     *
     * @param string $role User role
     * @return string Dashboard URL
     */
    public static function get_dashboard_url_by_role($role) {
        $admin_url = Helpers::get_admin_portal_url();
        $sponsor_url = Helpers::get_sponsor_dashboard_url();
        $login_url = Helpers::get_login_url();

        $role_urls = array(
            'alhuffaz_admin'   => $admin_url,
            'alhuffaz_staff'   => $admin_url,
            'alhuffaz_teacher' => $admin_url,
            'alhuffaz_sponsor' => $sponsor_url,
            'administrator'    => $admin_url,
        );

        return isset($role_urls[$role]) ? $role_urls[$role] : $login_url;
    }
}
