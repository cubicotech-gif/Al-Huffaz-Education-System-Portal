<?php
/**
 * User Roles and Capabilities
 *
 * @package AlHuffaz
 */

namespace AlHuffaz\Core;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Roles
 *
 * Three main roles:
 * - Admin: Full access to everything in portal (except WP admin)
 * - Staff: Limited access to add/edit students only (granted by admin)
 * - Sponsor: View sponsored students, make payments (created via UM registration)
 */
class Roles {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'add_capabilities'));

        // Extend session timeout for portal users (teachers/admins working 30-40 mins on forms)
        add_filter('auth_cookie_expiration', array($this, 'extend_session_timeout'), 10, 3);
    }

    /**
     * Create custom roles
     */
    public static function create_roles() {
        // Remove old roles first to refresh capabilities
        remove_role('alhuffaz_sponsor');
        remove_role('alhuffaz_teacher');
        remove_role('alhuffaz_admin');
        remove_role('alhuffaz_staff');

        // Sponsor Role - Created via Ultimate Member registration
        // Can view their sponsored students and make payments
        add_role('alhuffaz_sponsor', __('Sponsor', 'al-huffaz-portal'), array(
            'read'                       => true,
            'alhuffaz_view_dashboard'    => true,
            'alhuffaz_view_sponsorships' => true,
            'alhuffaz_make_payments'     => true,
            'upload_files'               => true,
        ));

        // Staff Role - Limited access (granted by Admin via portal UI)
        // Can only add and edit students
        add_role('alhuffaz_staff', __('Staff', 'al-huffaz-portal'), array(
            'read'                       => true,
            'edit_posts'                 => true, // Required for portal access
            'alhuffaz_view_dashboard'    => true,
            'alhuffaz_view_students'     => true,
            'alhuffaz_manage_students'   => true,
            'upload_files'               => true,
        ));

        // School Admin Role - Full portal access
        // Can manage everything: students, sponsors, payments, staff, settings
        add_role('alhuffaz_admin', __('School Admin', 'al-huffaz-portal'), array(
            'read'                       => true,
            'edit_posts'                 => true, // Required for portal access
            'alhuffaz_view_dashboard'    => true,
            'alhuffaz_manage_students'   => true,
            'alhuffaz_manage_sponsors'   => true,
            'alhuffaz_manage_payments'   => true,
            'alhuffaz_manage_staff'      => true,
            'alhuffaz_view_reports'      => true,
            'alhuffaz_manage_settings'   => true,
            'alhuffaz_bulk_import'       => true,
            'upload_files'               => true,
        ));

        // Keep Teacher role for backwards compatibility
        add_role('alhuffaz_teacher', __('Teacher', 'al-huffaz-portal'), array(
            'read'                       => true,
            'alhuffaz_view_dashboard'    => true,
            'alhuffaz_view_students'     => true,
            'alhuffaz_edit_academics'    => true,
            'alhuffaz_add_assessments'   => true,
            'upload_files'               => true,
        ));
    }

    /**
     * Add capabilities to WordPress administrator role
     */
    public function add_capabilities() {
        $admin = get_role('administrator');

        if ($admin) {
            $capabilities = array(
                'alhuffaz_view_dashboard',
                'alhuffaz_manage_students',
                'alhuffaz_manage_sponsors',
                'alhuffaz_manage_payments',
                'alhuffaz_manage_staff',
                'alhuffaz_view_reports',
                'alhuffaz_manage_settings',
                'alhuffaz_bulk_import',
                'alhuffaz_view_students',
                'alhuffaz_edit_academics',
                'alhuffaz_add_assessments',
                'alhuffaz_view_sponsorships',
                'alhuffaz_make_payments',
            );

            foreach ($capabilities as $cap) {
                $admin->add_cap($cap);
            }
        }
    }

    /**
     * Remove roles on plugin uninstall
     */
    public static function remove_roles() {
        remove_role('alhuffaz_sponsor');
        remove_role('alhuffaz_teacher');
        remove_role('alhuffaz_admin');
        remove_role('alhuffaz_staff');
    }

    /**
     * Check if current user is staff member
     */
    public static function is_staff($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        $user = get_user_by('id', $user_id);

        if (!$user) {
            return false;
        }

        return in_array('alhuffaz_staff', (array) $user->roles);
    }

    /**
     * Check if user can manage staff (admin only)
     */
    public static function can_manage_staff($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        return user_can($user_id, 'alhuffaz_manage_staff') || user_can($user_id, 'manage_options');
    }

    /**
     * Check if user can manage sponsors (admin only)
     */
    public static function can_manage_sponsors($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        return user_can($user_id, 'alhuffaz_manage_sponsors') || user_can($user_id, 'manage_options');
    }

    /**
     * Check if user can manage payments (admin only)
     */
    public static function can_manage_payments($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        return user_can($user_id, 'alhuffaz_manage_payments') || user_can($user_id, 'manage_options');
    }

    /**
     * Grant staff role to a user
     */
    public static function grant_staff_role($user_id) {
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return false;
        }

        // Remove subscriber role if present
        $user->remove_role('subscriber');
        $user->add_role('alhuffaz_staff');

        // Log the action
        if (class_exists('AlHuffaz\\Core\\Helpers')) {
            Helpers::log_activity('grant_staff', 'user', $user_id, sprintf('Staff role granted by user %d', get_current_user_id()));
        }

        return true;
    }

    /**
     * Revoke staff role from a user
     */
    public static function revoke_staff_role($user_id) {
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return false;
        }

        $user->remove_role('alhuffaz_staff');

        // Add subscriber back if no other roles
        if (empty($user->roles)) {
            $user->add_role('subscriber');
        }

        // Log the action
        if (class_exists('AlHuffaz\\Core\\Helpers')) {
            Helpers::log_activity('revoke_staff', 'user', $user_id, sprintf('Staff role revoked by user %d', get_current_user_id()));
        }

        return true;
    }

    /**
     * Get all staff users
     */
    public static function get_staff_users() {
        return get_users(array(
            'role' => 'alhuffaz_staff',
            'orderby' => 'display_name',
            'order' => 'ASC',
        ));
    }

    /**
     * Get users eligible to become staff (subscribers, etc.)
     */
    public static function get_eligible_staff_users() {
        return get_users(array(
            'role__in' => array('subscriber', 'contributor'),
            'role__not_in' => array('alhuffaz_staff', 'alhuffaz_admin', 'alhuffaz_sponsor', 'administrator'),
            'orderby' => 'display_name',
            'order' => 'ASC',
        ));
    }

    /**
     * Check if user has capability
     */
    public static function user_can($capability, $user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        $user = get_user_by('id', $user_id);

        if (!$user) {
            return false;
        }

        return user_can($user, $capability);
    }

    /**
     * Check if current user is sponsor
     */
    public static function is_sponsor($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        $user = get_user_by('id', $user_id);

        if (!$user) {
            return false;
        }

        return in_array('alhuffaz_sponsor', (array) $user->roles);
    }

    /**
     * Check if current user is teacher
     */
    public static function is_teacher($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        $user = get_user_by('id', $user_id);

        if (!$user) {
            return false;
        }

        return in_array('alhuffaz_teacher', (array) $user->roles);
    }

    /**
     * Check if current user is school admin
     */
    public static function is_school_admin($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        $user = get_user_by('id', $user_id);

        if (!$user) {
            return false;
        }

        return in_array('alhuffaz_admin', (array) $user->roles) ||
               in_array('administrator', (array) $user->roles);
    }

    /**
     * Get sponsor's linked students
     */
    public static function get_sponsor_students($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        // FIXED: Query 'sponsorship' CPT with correct meta keys (no underscore prefix)
        $sponsorships = get_posts(array(
            'post_type'      => 'sponsorship',
            'posts_per_page' => -1,
            'meta_query'     => array(
                array(
                    'key'   => 'sponsor_user_id',
                    'value' => $user_id,
                ),
                array(
                    'key'   => 'linked',
                    'value' => 'yes',
                ),
                array(
                    'key'   => 'verification_status',
                    'value' => 'approved',
                ),
            ),
        ));

        $student_ids = array();
        foreach ($sponsorships as $sponsorship) {
            $student_id = get_post_meta($sponsorship->ID, 'student_id', true);
            if ($student_id) {
                $student_ids[] = $student_id;
            }
        }

        return array_unique($student_ids);
    }

    /**
     * Extend session timeout for portal users
     *
     * Default WordPress timeout:
     * - Without "Remember Me": 2 days
     * - With "Remember Me": 14 days
     *
     * Extended for school portal:
     * - Without "Remember Me": 7 days (168 hours) - Teachers can work comfortably
     * - With "Remember Me": 30 days (720 hours) - Long-term convenience
     *
     * This ensures teachers/admins working 30-40 minutes on student forms
     * won't lose their session or data.
     *
     * @param int $length Cookie expiration length in seconds
     * @param int $user_id User ID
     * @param bool $remember Whether "Remember Me" was checked
     * @return int Extended expiration time
     */
    public function extend_session_timeout($length, $user_id, $remember) {
        // Only extend for portal users (not regular subscribers or customers)
        $user = get_user_by('id', $user_id);

        if (!$user) {
            return $length;
        }

        $portal_roles = array('alhuffaz_admin', 'alhuffaz_staff', 'alhuffaz_teacher', 'alhuffaz_sponsor', 'administrator');
        $user_roles = (array) $user->roles;

        // Check if user has any portal role
        $is_portal_user = !empty(array_intersect($portal_roles, $user_roles));

        if (!$is_portal_user) {
            return $length; // Default timeout for non-portal users
        }

        // Extended timeouts for portal users
        if ($remember) {
            // "Remember Me" checked: 30 days (720 hours)
            return 30 * DAY_IN_SECONDS;
        } else {
            // "Remember Me" NOT checked: 7 days (168 hours)
            // This gives teachers plenty of time to work on forms without worry
            return 7 * DAY_IN_SECONDS;
        }
    }
}
