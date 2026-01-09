<?php
/**
 * Notifications Bell Template
 */

use AlHuffaz\Core\Notifications;
use AlHuffaz\Core\Helpers;

if (!defined('ABSPATH')) exit;

$user_id = get_current_user_id();
$unread_count = Notifications::get_unread_count($user_id);
$recent_notifications = Notifications::get_user_notifications($user_id, 5);

// Get recent activity log
global $wpdb;
$activity_table = $wpdb->prefix . 'alhuffaz_activity_log';
$recent_activity = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM $activity_table ORDER BY created_at DESC LIMIT %d",
    10
));
?>

<style>
.alhuffaz-notification-bell {
    position: fixed;
    top: 32px;
    right: 20px;
    z-index: 99999;
}

.alhuffaz-notification-icon {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    background: var(--alhuffaz-primary, #2563eb);
    border-radius: 50%;
    cursor: pointer;
    transition: all 0.3s;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}

.alhuffaz-notification-icon:hover {
    transform: scale(1.1);
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}

.alhuffaz-notification-icon .dashicons {
    color: white;
    font-size: 20px;
    width: 20px;
    height: 20px;
}

.alhuffaz-notification-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background: #e74c3c;
    color: white;
    border-radius: 12px;
    padding: 2px 6px;
    font-size: 11px;
    font-weight: bold;
    min-width: 20px;
    text-align: center;
    border: 2px solid white;
}

.alhuffaz-notification-panel {
    display: none;
    position: fixed;
    top: 80px;
    right: 20px;
    width: 400px;
    max-height: 600px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.2);
    overflow: hidden;
    z-index: 99998;
}

.alhuffaz-notification-panel.active {
    display: flex;
    flex-direction: column;
}

.alhuffaz-notification-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    background: linear-gradient(135deg, var(--alhuffaz-primary, #2563eb) 0%, var(--alhuffaz-secondary, #1e40af) 100%);
    color: white;
}

.alhuffaz-notification-header h3 {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
}

.alhuffaz-notification-tabs {
    display: flex;
    border-bottom: 1px solid #e0e0e0;
    background: #f8f9fa;
}

.alhuffaz-notification-tab {
    flex: 1;
    padding: 12px 16px;
    text-align: center;
    cursor: pointer;
    border: none;
    background: transparent;
    font-size: 13px;
    font-weight: 500;
    color: #666;
    transition: all 0.2s;
}

.alhuffaz-notification-tab:hover {
    background: #e9ecef;
}

.alhuffaz-notification-tab.active {
    color: var(--alhuffaz-primary, #2563eb);
    border-bottom: 2px solid var(--alhuffaz-primary, #2563eb);
    background: white;
}

.alhuffaz-notification-content {
    flex: 1;
    overflow-y: auto;
    max-height: 500px;
}

.alhuffaz-notification-item {
    padding: 15px 20px;
    border-bottom: 1px solid #f0f0f0;
    transition: background 0.2s;
    cursor: pointer;
}

.alhuffaz-notification-item:hover {
    background: #f8f9fa;
}

.alhuffaz-notification-item.unread {
    background: #f0f7ff;
    border-left: 3px solid var(--alhuffaz-primary, #2563eb);
}

.alhuffaz-notification-item-title {
    font-weight: 600;
    margin-bottom: 5px;
    color: #2c3e50;
    font-size: 14px;
}

.alhuffaz-notification-item-message {
    font-size: 13px;
    color: #666;
    margin-bottom: 5px;
}

.alhuffaz-notification-item-time {
    font-size: 11px;
    color: #999;
}

.alhuffaz-activity-item {
    padding: 12px 20px;
    border-bottom: 1px solid #f0f0f0;
    font-size: 13px;
}

.alhuffaz-activity-item-action {
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 4px;
}

.alhuffaz-activity-item-details {
    color: #666;
    margin-bottom: 4px;
}

.alhuffaz-activity-item-time {
    font-size: 11px;
    color: #999;
}

.alhuffaz-notification-empty {
    padding: 40px 20px;
    text-align: center;
    color: #999;
}

.alhuffaz-notification-empty .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
    opacity: 0.3;
    margin-bottom: 10px;
}

.alhuffaz-notification-footer {
    padding: 12px 20px;
    border-top: 1px solid #e0e0e0;
    text-align: center;
}

.alhuffaz-notification-footer button {
    background: transparent;
    border: none;
    color: var(--alhuffaz-primary, #2563eb);
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    padding: 5px 10px;
}

.alhuffaz-notification-footer button:hover {
    text-decoration: underline;
}
</style>

<div class="alhuffaz-notification-bell">
    <div class="alhuffaz-notification-icon" id="alhuffaz-notification-bell">
        <span class="dashicons dashicons-bell"></span>
        <?php if ($unread_count > 0): ?>
            <span class="alhuffaz-notification-badge"><?php echo $unread_count; ?></span>
        <?php endif; ?>
    </div>

    <div class="alhuffaz-notification-panel" id="alhuffaz-notification-panel">
        <div class="alhuffaz-notification-header">
            <h3><?php _e('Notifications & Activity', 'al-huffaz-portal'); ?></h3>
            <button style="background: transparent; border: none; color: white; cursor: pointer; font-size: 20px; padding: 0; width: 24px; height: 24px;" id="alhuffaz-close-notifications">
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        </div>

        <div class="alhuffaz-notification-tabs">
            <button class="alhuffaz-notification-tab active" data-tab="alerts">
                <?php _e('Alerts', 'al-huffaz-portal'); ?>
                <?php if ($unread_count > 0): ?>
                    <span style="display: inline-block; background: #e74c3c; color: white; border-radius: 10px; padding: 2px 6px; font-size: 10px; margin-left: 5px;"><?php echo $unread_count; ?></span>
                <?php endif; ?>
            </button>
            <button class="alhuffaz-notification-tab" data-tab="history">
                <?php _e('Activity History', 'al-huffaz-portal'); ?>
            </button>
        </div>

        <div class="alhuffaz-notification-content">
            <!-- Alerts Tab -->
            <div class="alhuffaz-notification-tab-content" data-content="alerts">
                <?php if (empty($recent_notifications)): ?>
                    <div class="alhuffaz-notification-empty">
                        <span class="dashicons dashicons-bell"></span>
                        <p><?php _e('No notifications yet', 'al-huffaz-portal'); ?></p>
                    </div>
                <?php else: ?>
                    <?php foreach ($recent_notifications as $notification): ?>
                        <div class="alhuffaz-notification-item <?php echo $notification->is_read ? '' : 'unread'; ?>" data-notification-id="<?php echo $notification->id; ?>">
                            <div class="alhuffaz-notification-item-title"><?php echo esc_html($notification->title); ?></div>
                            <div class="alhuffaz-notification-item-message"><?php echo esc_html($notification->message); ?></div>
                            <div class="alhuffaz-notification-item-time"><?php echo Helpers::time_ago($notification->created_at); ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Activity History Tab -->
            <div class="alhuffaz-notification-tab-content" data-content="history" style="display: none;">
                <?php if (empty($recent_activity)): ?>
                    <div class="alhuffaz-notification-empty">
                        <span class="dashicons dashicons-backup"></span>
                        <p><?php _e('No activity logged yet', 'al-huffaz-portal'); ?></p>
                    </div>
                <?php else: ?>
                    <?php foreach ($recent_activity as $activity): ?>
                        <div class="alhuffaz-activity-item">
                            <div class="alhuffaz-activity-item-action">
                                <span class="dashicons dashicons-admin-generic" style="font-size: 14px; width: 14px; height: 14px; color: var(--alhuffaz-primary);"></span>
                                <?php echo esc_html(ucwords(str_replace('_', ' ', $activity->action))); ?>
                            </div>
                            <div class="alhuffaz-activity-item-details"><?php echo esc_html($activity->details); ?></div>
                            <div class="alhuffaz-activity-item-time"><?php echo Helpers::time_ago($activity->created_at); ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($recent_notifications)): ?>
            <div class="alhuffaz-notification-footer">
                <button id="alhuffaz-mark-all-read"><?php _e('Mark all as read', 'al-huffaz-portal'); ?></button>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Toggle notification panel
    $('#alhuffaz-notification-bell').on('click', function() {
        $('#alhuffaz-notification-panel').toggleClass('active');
    });

    // Close notification panel
    $('#alhuffaz-close-notifications').on('click', function(e) {
        e.stopPropagation();
        $('#alhuffaz-notification-panel').removeClass('active');
    });

    // Close on outside click
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.alhuffaz-notification-bell').length) {
            $('#alhuffaz-notification-panel').removeClass('active');
        }
    });

    // Switch tabs
    $('.alhuffaz-notification-tab').on('click', function() {
        var tab = $(this).data('tab');

        $('.alhuffaz-notification-tab').removeClass('active');
        $(this).addClass('active');

        $('.alhuffaz-notification-tab-content').hide();
        $('.alhuffaz-notification-tab-content[data-content="' + tab + '"]').show();
    });

    // Mark notification as read on click
    $('.alhuffaz-notification-item').on('click', function() {
        var notificationId = $(this).data('notification-id');
        var $item = $(this);

        if ($item.hasClass('unread')) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'alhuffaz_mark_notification_read',
                    notification_id: notificationId,
                    nonce: '<?php echo wp_create_nonce('alhuffaz_notifications'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        $item.removeClass('unread');
                        updateNotificationBadge();
                    }
                }
            });
        }
    });

    // Mark all as read
    $('#alhuffaz-mark-all-read').on('click', function() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'alhuffaz_mark_all_notifications_read',
                nonce: '<?php echo wp_create_nonce('alhuffaz_notifications'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    $('.alhuffaz-notification-item').removeClass('unread');
                    $('.alhuffaz-notification-badge').remove();
                    $('#alhuffaz-mark-all-read').parent().hide();
                }
            }
        });
    });

    // Update notification badge
    function updateNotificationBadge() {
        var unreadCount = $('.alhuffaz-notification-item.unread').length;
        var $badge = $('.alhuffaz-notification-badge');

        if (unreadCount > 0) {
            if ($badge.length) {
                $badge.text(unreadCount);
            }
        } else {
            $badge.remove();
        }
    }
});
</script>
