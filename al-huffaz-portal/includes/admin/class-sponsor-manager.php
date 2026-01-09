<?php
/**
 * Sponsor Manager
 *
 * @package AlHuffaz
 */

namespace AlHuffaz\Admin;

use AlHuffaz\Core\Helpers;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Sponsor_Manager
 */
class Sponsor_Manager {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'schedule_cleanup'));
        add_action('alhuffaz_cleanup_orphaned_sponsorships', array($this, 'cleanup_orphaned'));
    }

    /**
     * Schedule cleanup
     */
    public function schedule_cleanup() {
        if (!wp_next_scheduled('alhuffaz_cleanup_orphaned_sponsorships')) {
            wp_schedule_event(time(), 'daily', 'alhuffaz_cleanup_orphaned_sponsorships');
        }
    }

    /**
     * Get sponsorships list
     */
    public static function get_sponsorships($args = array()) {
        $defaults = array(
            'page'     => 1,
            'per_page' => 20,
            'status'   => '',
            'linked'   => '',
            'orderby'  => 'date',
            'order'    => 'DESC',
        );

        $args = wp_parse_args($args, $defaults);

        $query_args = array(
            'post_type'      => 'alhuffaz_sponsor',
            'posts_per_page' => $args['per_page'],
            'paged'          => $args['page'],
            'post_status'    => 'publish',
            'orderby'        => $args['orderby'],
            'order'          => $args['order'],
        );

        $meta_query = array();

        if (!empty($args['status'])) {
            $meta_query[] = array(
                'key'   => '_status',
                'value' => $args['status'],
            );
        }

        if ($args['linked'] === 'yes' || $args['linked'] === 'no') {
            $meta_query[] = array(
                'key'   => '_linked',
                'value' => $args['linked'],
            );
        }

        if (!empty($meta_query)) {
            $query_args['meta_query'] = $meta_query;
        }

        $query = new \WP_Query($query_args);

        $sponsorships = array();

        foreach ($query->posts as $post) {
            $student_id = get_post_meta($post->ID, '_student_id', true);
            $student = get_post($student_id);

            $sponsorships[] = array(
                'id'               => $post->ID,
                'sponsor_name'     => get_post_meta($post->ID, '_sponsor_name', true),
                'sponsor_email'    => get_post_meta($post->ID, '_sponsor_email', true),
                'sponsor_phone'    => get_post_meta($post->ID, '_sponsor_phone', true),
                'sponsor_country'  => get_post_meta($post->ID, '_sponsor_country', true),
                'student_id'       => $student_id,
                'student_name'     => $student ? $student->post_title : '-',
                'student_photo'    => $student ? Helpers::get_student_photo($student_id) : '',
                'amount'           => get_post_meta($post->ID, '_amount', true),
                'amount_formatted' => Helpers::format_currency(get_post_meta($post->ID, '_amount', true)),
                'type'             => get_post_meta($post->ID, '_sponsorship_type', true),
                'payment_method'   => get_post_meta($post->ID, '_payment_method', true),
                'transaction_id'   => get_post_meta($post->ID, '_transaction_id', true),
                'status'           => get_post_meta($post->ID, '_status', true),
                'status_badge'     => Helpers::get_status_badge(get_post_meta($post->ID, '_status', true)),
                'linked'           => get_post_meta($post->ID, '_linked', true) === 'yes',
                'screenshot'       => wp_get_attachment_url(get_post_meta($post->ID, '_payment_screenshot', true)),
                'notes'            => get_post_meta($post->ID, '_notes', true),
                'created_at'       => $post->post_date,
                'created_at_formatted' => Helpers::format_date($post->post_date),
            );
        }

        return array(
            'sponsorships' => $sponsorships,
            'total'        => $query->found_posts,
            'total_pages'  => $query->max_num_pages,
            'page'         => $args['page'],
        );
    }

    /**
     * Get single sponsorship
     */
    public static function get_sponsorship($sponsorship_id) {
        $post = get_post($sponsorship_id);

        if (!$post || $post->post_type !== 'alhuffaz_sponsor') {
            return null;
        }

        $student_id = get_post_meta($post->ID, '_student_id', true);
        $student = get_post($student_id);

        return array(
            'id'               => $post->ID,
            'sponsor_name'     => get_post_meta($post->ID, '_sponsor_name', true),
            'sponsor_email'    => get_post_meta($post->ID, '_sponsor_email', true),
            'sponsor_phone'    => get_post_meta($post->ID, '_sponsor_phone', true),
            'sponsor_country'  => get_post_meta($post->ID, '_sponsor_country', true),
            'sponsor_user_id'  => get_post_meta($post->ID, '_sponsor_user_id', true),
            'student_id'       => $student_id,
            'student_name'     => $student ? $student->post_title : '-',
            'amount'           => get_post_meta($post->ID, '_amount', true),
            'type'             => get_post_meta($post->ID, '_sponsorship_type', true),
            'payment_method'   => get_post_meta($post->ID, '_payment_method', true),
            'transaction_id'   => get_post_meta($post->ID, '_transaction_id', true),
            'status'           => get_post_meta($post->ID, '_status', true),
            'linked'           => get_post_meta($post->ID, '_linked', true) === 'yes',
            'screenshot'       => get_post_meta($post->ID, '_payment_screenshot', true),
            'notes'            => get_post_meta($post->ID, '_notes', true),
            'verified_by'      => get_post_meta($post->ID, '_verified_by', true),
            'verified_at'      => get_post_meta($post->ID, '_verified_at', true),
            'created_at'       => $post->post_date,
        );
    }

    /**
     * Approve sponsorship
     */
    public static function approve($sponsorship_id) {
        $sponsorship = get_post($sponsorship_id);

        if (!$sponsorship || $sponsorship->post_type !== 'alhuffaz_sponsor') {
            return new \WP_Error('not_found', __('Sponsorship not found.', 'al-huffaz-portal'));
        }

        update_post_meta($sponsorship_id, '_status', 'approved');
        update_post_meta($sponsorship_id, '_verified_by', get_current_user_id());
        update_post_meta($sponsorship_id, '_verified_at', current_time('mysql'));

        // Mark student as sponsored
        $student_id = get_post_meta($sponsorship_id, '_student_id', true);
        if ($student_id) {
            update_post_meta($student_id, '_is_sponsored', 'yes');
            update_post_meta($student_id, '_sponsor_id', $sponsorship_id);
        }

        // Create sponsor user account if needed
        $sponsor_email = get_post_meta($sponsorship_id, '_sponsor_email', true);
        $sponsor_name = get_post_meta($sponsorship_id, '_sponsor_name', true);

        $user = get_user_by('email', $sponsor_email);

        if (!$user) {
            $password = Helpers::generate_password();

            $user_id = wp_create_user(
                sanitize_user($sponsor_email),
                $password,
                $sponsor_email
            );

            if (!is_wp_error($user_id)) {
                wp_update_user(array(
                    'ID'           => $user_id,
                    'display_name' => $sponsor_name,
                    'role'         => 'alhuffaz_sponsor',
                ));

                update_post_meta($sponsorship_id, '_sponsor_user_id', $user_id);

                // Send welcome email
                Helpers::send_notification(
                    $sponsor_email,
                    __('Welcome to Al-Huffaz Sponsor Portal', 'al-huffaz-portal'),
                    sprintf(
                        __('Your sponsorship has been approved! You can now login to your dashboard.<br><br>Email: %s<br>Password: %s<br><br>Please change your password after logging in.', 'al-huffaz-portal'),
                        $sponsor_email,
                        $password
                    )
                );
            }
        } else {
            update_post_meta($sponsorship_id, '_sponsor_user_id', $user->ID);

            // Add sponsor role if not already
            $user->add_role('alhuffaz_sponsor');

            Helpers::send_notification(
                $sponsor_email,
                __('Sponsorship Approved', 'al-huffaz-portal'),
                __('Your sponsorship has been approved! You can login to your dashboard to view your sponsored student.', 'al-huffaz-portal')
            );
        }

        // Log activity
        Helpers::log_activity('approve_sponsorship', 'sponsorship', $sponsorship_id, 'Approved sponsorship');

        // CRITICAL: Clear sponsor dashboard cache for real-time update
        $sponsor_user_id = get_post_meta($sponsorship_id, '_sponsor_user_id', true);
        if ($sponsor_user_id) {
            wp_cache_delete('sponsor_dashboard_' . $sponsor_user_id, 'alhuffaz');
            wp_cache_flush(); // Force fresh data on next load
            clean_post_cache($sponsorship_id);
        }

        return true;
    }

    /**
     * Reject sponsorship
     */
    public static function reject($sponsorship_id, $reason = '') {
        $sponsorship = get_post($sponsorship_id);

        if (!$sponsorship || $sponsorship->post_type !== 'alhuffaz_sponsor') {
            return new \WP_Error('not_found', __('Sponsorship not found.', 'al-huffaz-portal'));
        }

        update_post_meta($sponsorship_id, '_status', 'rejected');
        update_post_meta($sponsorship_id, '_rejection_reason', $reason);
        update_post_meta($sponsorship_id, '_verified_by', get_current_user_id());
        update_post_meta($sponsorship_id, '_verified_at', current_time('mysql'));

        // Send notification
        $sponsor_email = get_post_meta($sponsorship_id, '_sponsor_email', true);

        Helpers::send_notification(
            $sponsor_email,
            __('Sponsorship Update', 'al-huffaz-portal'),
            sprintf(
                __('Unfortunately, your sponsorship request could not be approved at this time.<br><br>Reason: %s<br><br>Please contact us for more information.', 'al-huffaz-portal'),
                $reason ?: 'Not specified'
            )
        );

        // Log activity
        Helpers::log_activity('reject_sponsorship', 'sponsorship', $sponsorship_id, 'Rejected: ' . $reason);

        return true;
    }

    /**
     * Link sponsor to student
     */
    public static function link($sponsorship_id) {
        update_post_meta($sponsorship_id, '_linked', 'yes');

        Helpers::log_activity('link_sponsor', 'sponsorship', $sponsorship_id, 'Linked sponsor to student');

        // CRITICAL: Clear sponsor dashboard cache
        $sponsor_user_id = get_post_meta($sponsorship_id, '_sponsor_user_id', true);
        if ($sponsor_user_id) {
            wp_cache_delete('sponsor_dashboard_' . $sponsor_user_id, 'alhuffaz');
            wp_cache_flush();
            clean_post_cache($sponsorship_id);
        }

        return true;
    }

    /**
     * Unlink sponsor from student
     */
    public static function unlink($sponsorship_id) {
        update_post_meta($sponsorship_id, '_linked', 'no');

        // Check if student has other active sponsorships
        $student_id = get_post_meta($sponsorship_id, '_student_id', true);

        $other_sponsorships = get_posts(array(
            'post_type'      => 'alhuffaz_sponsor',
            'posts_per_page' => 1,
            'post__not_in'   => array($sponsorship_id),
            'meta_query'     => array(
                array(
                    'key'   => '_student_id',
                    'value' => $student_id,
                ),
                array(
                    'key'   => '_status',
                    'value' => 'approved',
                ),
                array(
                    'key'   => '_linked',
                    'value' => 'yes',
                ),
            ),
        ));

        if (empty($other_sponsorships)) {
            update_post_meta($student_id, '_is_sponsored', 'no');
        }

        Helpers::log_activity('unlink_sponsor', 'sponsorship', $sponsorship_id, 'Unlinked sponsor from student');

        return true;
    }

    /**
     * Get counts by status
     */
    public static function get_counts() {
        $statuses = array('pending', 'approved', 'rejected');
        $counts = array();

        foreach ($statuses as $status) {
            $posts = get_posts(array(
                'post_type'      => 'alhuffaz_sponsor',
                'posts_per_page' => -1,
                'fields'         => 'ids',
                'meta_query'     => array(
                    array(
                        'key'   => '_status',
                        'value' => $status,
                    ),
                ),
            ));

            $counts[$status] = count($posts);
        }

        // Linked count
        $linked = get_posts(array(
            'post_type'      => 'alhuffaz_sponsor',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'meta_query'     => array(
                array(
                    'key'   => '_linked',
                    'value' => 'yes',
                ),
            ),
        ));

        $counts['linked'] = count($linked);

        return $counts;
    }

    /**
     * Cleanup orphaned sponsorship markers
     */
    public function cleanup_orphaned() {
        // Find students marked as sponsored but with no active sponsorship
        $sponsored_students = get_posts(array(
            'post_type'      => 'alhuffaz_student',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'meta_query'     => array(
                array(
                    'key'   => '_is_sponsored',
                    'value' => 'yes',
                ),
            ),
        ));

        foreach ($sponsored_students as $student_id) {
            $active_sponsorship = get_posts(array(
                'post_type'      => 'alhuffaz_sponsor',
                'posts_per_page' => 1,
                'fields'         => 'ids',
                'meta_query'     => array(
                    array(
                        'key'   => '_student_id',
                        'value' => $student_id,
                    ),
                    array(
                        'key'   => '_status',
                        'value' => 'approved',
                    ),
                    array(
                        'key'   => '_linked',
                        'value' => 'yes',
                    ),
                ),
            ));

            if (empty($active_sponsorship)) {
                update_post_meta($student_id, '_is_sponsored', 'no');
                delete_post_meta($student_id, '_sponsor_id');
            }
        }
    }
}
