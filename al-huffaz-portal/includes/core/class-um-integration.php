<?php
/**
 * Ultimate Member Integration
 *
 * Prepares hooks for integrating with Ultimate Member plugin
 * when sponsor registration requires verification.
 *
 * @package AlHuffaz
 */

namespace AlHuffaz\Core;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class UM_Integration
 *
 * Handles integration with Ultimate Member plugin:
 * - Assigns alhuffaz_sponsor role to new sponsors
 * - Manages sponsor verification/approval status
 * - Syncs UM account status with portal sponsor status
 */
class UM_Integration {

    /**
     * Constructor
     */
    public function __construct() {
        // Only load if Ultimate Member is active
        if (!$this->is_um_active()) {
            return;
        }

        // Hook into UM registration
        add_action('um_registration_complete', array($this, 'on_sponsor_registration'), 10, 2);

        // Hook into UM account activation
        add_action('um_after_user_status_is_changed', array($this, 'on_status_changed'), 10, 2);

        // Hook into UM user approval
        add_action('um_after_user_is_approved', array($this, 'on_user_approved'), 10, 1);

        // Add custom UM fields
        add_filter('um_predefined_fields_hook', array($this, 'add_custom_fields'));

        // Redirect sponsors after login
        add_filter('um_login_redirect_url', array($this, 'sponsor_login_redirect'), 10, 2);
    }

    /**
     * Check if Ultimate Member is active
     */
    public function is_um_active() {
        return class_exists('UM') || function_exists('UM');
    }

    /**
     * Handle sponsor registration complete
     *
     * @param int $user_id
     * @param array $args
     */
    public function on_sponsor_registration($user_id, $args) {
        // Check if this is a sponsor registration form
        $form_id = isset($args['form_id']) ? $args['form_id'] : 0;
        $sponsor_form_id = get_option('alhuffaz_um_sponsor_form_id', 0);

        // If specific form ID is set, only process that form
        if ($sponsor_form_id && $form_id != $sponsor_form_id) {
            return;
        }

        // Check if role is sponsor based on UM form settings
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return;
        }

        // If user has sponsor role or form is marked as sponsor form
        $is_sponsor_form = get_post_meta($form_id, '_alhuffaz_sponsor_form', true) === 'yes';
        $has_sponsor_role = in_array('alhuffaz_sponsor', (array) $user->roles);

        if ($is_sponsor_form || $has_sponsor_role) {
            // Ensure sponsor role is assigned
            if (!$has_sponsor_role) {
                $user->add_role('alhuffaz_sponsor');
            }

            // Create sponsor record in our system
            $this->create_sponsor_record($user_id, $args);

            // Log the registration
            Helpers::log_activity('sponsor_registered', 'user', $user_id, 'Sponsor registered via UM form');
        }
    }

    /**
     * Create sponsor record when user registers
     *
     * @param int $user_id
     * @param array $args
     */
    private function create_sponsor_record($user_id, $args) {
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return;
        }

        // Check if sponsor record already exists
        $existing = get_posts(array(
            'post_type' => 'alhuffaz_sponsor',
            'posts_per_page' => 1,
            'meta_query' => array(
                array(
                    'key' => '_sponsor_user_id',
                    'value' => $user_id,
                ),
            ),
        ));

        if (!empty($existing)) {
            return;
        }

        // Create sponsor post
        $sponsor_id = wp_insert_post(array(
            'post_type' => 'alhuffaz_sponsor',
            'post_title' => $user->display_name,
            'post_status' => 'publish',
        ));

        if (is_wp_error($sponsor_id)) {
            return;
        }

        // Save sponsor meta
        update_post_meta($sponsor_id, '_sponsor_user_id', $user_id);
        update_post_meta($sponsor_id, '_sponsor_email', $user->user_email);
        update_post_meta($sponsor_id, '_sponsor_phone', isset($args['phone_number']) ? sanitize_text_field($args['phone_number']) : '');
        update_post_meta($sponsor_id, '_sponsor_country', isset($args['country']) ? sanitize_text_field($args['country']) : '');
        update_post_meta($sponsor_id, '_status', 'pending'); // Requires admin approval
        update_post_meta($sponsor_id, '_registered_at', current_time('mysql'));

        // Send notification to admin
        $this->notify_admin_new_sponsor($user_id, $sponsor_id);
    }

    /**
     * Handle UM status change
     *
     * @param string $new_status
     * @param int $user_id
     */
    public function on_status_changed($new_status, $user_id) {
        // Find sponsor record
        $sponsors = get_posts(array(
            'post_type' => 'alhuffaz_sponsor',
            'posts_per_page' => 1,
            'meta_query' => array(
                array(
                    'key' => '_sponsor_user_id',
                    'value' => $user_id,
                ),
            ),
        ));

        if (empty($sponsors)) {
            return;
        }

        $sponsor_id = $sponsors[0]->ID;

        // Sync status
        switch ($new_status) {
            case 'approved':
                update_post_meta($sponsor_id, '_status', 'approved');
                break;
            case 'rejected':
                update_post_meta($sponsor_id, '_status', 'rejected');
                break;
            case 'awaiting_email_confirmation':
            case 'awaiting_admin_review':
                update_post_meta($sponsor_id, '_status', 'pending');
                break;
        }
    }

    /**
     * Handle UM user approval
     *
     * @param int $user_id
     */
    public function on_user_approved($user_id) {
        // Find and approve sponsor record
        $sponsors = get_posts(array(
            'post_type' => 'alhuffaz_sponsor',
            'posts_per_page' => 1,
            'meta_query' => array(
                array(
                    'key' => '_sponsor_user_id',
                    'value' => $user_id,
                ),
            ),
        ));

        if (empty($sponsors)) {
            return;
        }

        $sponsor_id = $sponsors[0]->ID;
        update_post_meta($sponsor_id, '_status', 'approved');
        update_post_meta($sponsor_id, '_approved_at', current_time('mysql'));
        update_post_meta($sponsor_id, '_approved_by', get_current_user_id());

        // Log
        Helpers::log_activity('sponsor_approved', 'sponsor', $sponsor_id, 'Sponsor approved via UM');
    }

    /**
     * Add custom UM fields for sponsors
     *
     * @param array $fields
     * @return array
     */
    public function add_custom_fields($fields) {
        $fields['sponsor_phone'] = array(
            'title' => __('Phone Number', 'al-huffaz-portal'),
            'metakey' => 'sponsor_phone',
            'type' => 'tel',
            'label' => __('Phone Number', 'al-huffaz-portal'),
            'required' => 1,
            'public' => 1,
            'editable' => 1,
        );

        $fields['sponsor_country'] = array(
            'title' => __('Country', 'al-huffaz-portal'),
            'metakey' => 'sponsor_country',
            'type' => 'select',
            'label' => __('Country', 'al-huffaz-portal'),
            'options' => Helpers::get_countries(),
            'required' => 0,
            'public' => 1,
            'editable' => 1,
        );

        $fields['sponsor_whatsapp'] = array(
            'title' => __('WhatsApp Number', 'al-huffaz-portal'),
            'metakey' => 'sponsor_whatsapp',
            'type' => 'tel',
            'label' => __('WhatsApp (Optional)', 'al-huffaz-portal'),
            'required' => 0,
            'public' => 1,
            'editable' => 1,
        );

        return $fields;
    }

    /**
     * Redirect sponsors to dashboard after login
     *
     * @param string $url
     * @param int $user_id
     * @return string
     */
    public function sponsor_login_redirect($url, $user_id) {
        if (Roles::is_sponsor($user_id)) {
            $dashboard_page = get_option('alhuffaz_sponsor_dashboard_page', 0);
            if ($dashboard_page) {
                return get_permalink($dashboard_page);
            }
        }

        return $url;
    }

    /**
     * Notify admin of new sponsor registration
     *
     * @param int $user_id
     * @param int $sponsor_id
     */
    private function notify_admin_new_sponsor($user_id, $sponsor_id) {
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return;
        }

        $admin_email = get_option('alhuffaz_admin_email', get_option('admin_email'));
        $subject = __('New Sponsor Registration', 'al-huffaz-portal');

        $message = sprintf(
            __('A new sponsor has registered and requires verification:

Name: %s
Email: %s
Registration Date: %s

Please review and approve in the Admin Portal.', 'al-huffaz-portal'),
            $user->display_name,
            $user->user_email,
            current_time('F j, Y g:i a')
        );

        Helpers::send_notification($admin_email, $subject, nl2br($message), 'sponsor_registration');
    }

    /**
     * Approve sponsor from portal (sync to UM)
     *
     * @param int $user_id
     * @return bool
     */
    public static function approve_sponsor_in_um($user_id) {
        if (!class_exists('UM') && !function_exists('um_fetch_user')) {
            return false;
        }

        // Set UM user status to approved
        update_user_meta($user_id, 'account_status', 'approved');

        // Remove pending flag
        delete_user_meta($user_id, 'um_email_confirmation_key');

        // Trigger UM approval hooks
        do_action('um_after_user_is_approved', $user_id);

        return true;
    }

    /**
     * Reject sponsor from portal (sync to UM)
     *
     * @param int $user_id
     * @return bool
     */
    public static function reject_sponsor_in_um($user_id) {
        if (!class_exists('UM') && !function_exists('um_fetch_user')) {
            return false;
        }

        // Set UM user status to rejected
        update_user_meta($user_id, 'account_status', 'rejected');

        return true;
    }

    /**
     * Get sponsor UM status
     *
     * @param int $user_id
     * @return string
     */
    public static function get_um_status($user_id) {
        if (!class_exists('UM') && !function_exists('um_fetch_user')) {
            return 'unknown';
        }

        $status = get_user_meta($user_id, 'account_status', true);
        return $status ?: 'approved';
    }
}
