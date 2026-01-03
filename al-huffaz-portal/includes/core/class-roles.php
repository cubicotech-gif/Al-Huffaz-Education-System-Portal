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
 */
class Roles {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'add_capabilities'));
    }

    /**
     * Create custom roles
     */
    public static function create_roles() {
        // Sponsor Role
        add_role('alhuffaz_sponsor', __('Sponsor', 'al-huffaz-portal'), array(
            'read'                      => true,
            'alhuffaz_view_dashboard'   => true,
            'alhuffaz_view_sponsorships'=> true,
            'alhuffaz_make_payments'    => true,
            'upload_files'              => true,
        ));

        // Teacher Role
        add_role('alhuffaz_teacher', __('Teacher', 'al-huffaz-portal'), array(
            'read'                      => true,
            'alhuffaz_view_dashboard'   => true,
            'alhuffaz_view_students'    => true,
            'alhuffaz_edit_academics'   => true,
            'alhuffaz_add_assessments'  => true,
            'upload_files'              => true,
        ));

        // School Admin Role
        add_role('alhuffaz_admin', __('School Admin', 'al-huffaz-portal'), array(
            'read'                      => true,
            'alhuffaz_view_dashboard'   => true,
            'alhuffaz_manage_students'  => true,
            'alhuffaz_manage_sponsors'  => true,
            'alhuffaz_manage_payments'  => true,
            'alhuffaz_view_reports'     => true,
            'alhuffaz_manage_settings'  => true,
            'alhuffaz_bulk_import'      => true,
            'upload_files'              => true,
        ));
    }

    /**
     * Add capabilities to admin role
     */
    public function add_capabilities() {
        $admin = get_role('administrator');

        if ($admin) {
            $capabilities = array(
                'alhuffaz_view_dashboard',
                'alhuffaz_manage_students',
                'alhuffaz_manage_sponsors',
                'alhuffaz_manage_payments',
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

        $sponsorships = get_posts(array(
            'post_type'      => 'alhuffaz_sponsor',
            'posts_per_page' => -1,
            'meta_query'     => array(
                array(
                    'key'   => '_sponsor_user_id',
                    'value' => $user_id,
                ),
                array(
                    'key'   => '_linked',
                    'value' => 'yes',
                ),
                array(
                    'key'   => '_status',
                    'value' => 'approved',
                ),
            ),
        ));

        $student_ids = array();
        foreach ($sponsorships as $sponsorship) {
            $student_id = get_post_meta($sponsorship->ID, '_student_id', true);
            if ($student_id) {
                $student_ids[] = $student_id;
            }
        }

        return array_unique($student_ids);
    }
}
