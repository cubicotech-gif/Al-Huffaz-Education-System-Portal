<?php
/**
 * Notifications System
 *
 * @package AlHuffaz
 */

namespace AlHuffaz\Core;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Notifications
 */
class Notifications {

    /**
     * Create notifications table
     */
    public static function create_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'alhuffaz_notifications';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            title varchar(255) NOT NULL,
            message text NOT NULL,
            type varchar(50) DEFAULT 'info',
            related_id bigint(20) DEFAULT NULL,
            related_type varchar(50) DEFAULT NULL,
            is_read tinyint(1) DEFAULT 0,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY is_read (is_read),
            KEY created_at (created_at)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Create notification
     */
    public static function create($user_id, $title, $message, $type = 'info', $related_id = null, $related_type = null) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'alhuffaz_notifications';

        $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'title' => $title,
                'message' => $message,
                'type' => $type,
                'related_id' => $related_id,
                'related_type' => $related_type,
                'created_at' => current_time('mysql'),
            ),
            array('%d', '%s', '%s', '%s', '%d', '%s', '%s')
        );

        return $wpdb->insert_id;
    }

    /**
     * Get user notifications
     */
    public static function get_user_notifications($user_id, $limit = 10, $unread_only = false) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'alhuffaz_notifications';

        $where = $wpdb->prepare("user_id = %d", $user_id);
        if ($unread_only) {
            $where .= " AND is_read = 0";
        }

        $notifications = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE $where ORDER BY created_at DESC LIMIT %d",
                $limit
            )
        );

        return $notifications;
    }

    /**
     * Get unread count
     */
    public static function get_unread_count($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'alhuffaz_notifications';

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE user_id = %d AND is_read = 0",
            $user_id
        ));
    }

    /**
     * Mark as read
     */
    public static function mark_as_read($notification_id, $user_id = null) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'alhuffaz_notifications';

        $where = array('id' => $notification_id);
        if ($user_id) {
            $where['user_id'] = $user_id;
        }

        return $wpdb->update(
            $table_name,
            array('is_read' => 1),
            $where,
            array('%d'),
            array('%d', '%d')
        );
    }

    /**
     * Mark all as read
     */
    public static function mark_all_as_read($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'alhuffaz_notifications';

        return $wpdb->update(
            $table_name,
            array('is_read' => 1),
            array('user_id' => $user_id, 'is_read' => 0),
            array('%d'),
            array('%d', '%d')
        );
    }
}
