<?php
/**
 * Admin Dashboard Template
 *
 * @package AlHuffaz
 */

use AlHuffaz\Admin\Dashboard;
use AlHuffaz\Core\Helpers;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$stats = Dashboard::get_stats();
$activities = Dashboard::get_recent_activities(10);
$pending = Dashboard::get_pending_items();
$show_welcome = isset($_GET['welcome']) && $_GET['welcome'] === '1';
?>

<div class="alhuffaz-wrap">
    <?php if ($show_welcome): ?>
    <div class="alhuffaz-welcome">
        <div class="alhuffaz-welcome-content">
            <h2><?php _e('Welcome to Al-Huffaz Portal!', 'al-huffaz-portal'); ?></h2>
            <p><?php _e('Your education management system is ready. Start by adding students or configuring settings.', 'al-huffaz-portal'); ?></p>
        </div>
        <a href="<?php echo admin_url('admin.php?page=alhuffaz-add-student'); ?>" class="alhuffaz-btn">
            <?php _e('Add First Student', 'al-huffaz-portal'); ?>
        </a>
    </div>
    <?php endif; ?>

    <div class="alhuffaz-header">
        <h1>
            <span class="dashicons dashicons-welcome-learn-more"></span>
            <?php _e('Dashboard', 'al-huffaz-portal'); ?>
        </h1>
        <div class="alhuffaz-header-actions">
            <a href="<?php echo admin_url('admin.php?page=alhuffaz-add-student'); ?>" class="alhuffaz-btn alhuffaz-btn-primary">
                <span class="dashicons dashicons-plus-alt"></span>
                <?php _e('Add Student', 'al-huffaz-portal'); ?>
            </a>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="alhuffaz-stats-grid">
        <div class="alhuffaz-stat-card">
            <div class="alhuffaz-stat-icon primary">
                <span class="dashicons dashicons-groups"></span>
            </div>
            <div class="alhuffaz-stat-content">
                <div class="alhuffaz-stat-label"><?php _e('Total Students', 'al-huffaz-portal'); ?></div>
                <div class="alhuffaz-stat-value"><?php echo number_format($stats['total_students']); ?></div>
            </div>
        </div>

        <div class="alhuffaz-stat-card">
            <div class="alhuffaz-stat-icon secondary">
                <span class="dashicons dashicons-heart"></span>
            </div>
            <div class="alhuffaz-stat-content">
                <div class="alhuffaz-stat-label"><?php _e('Sponsored Students', 'al-huffaz-portal'); ?></div>
                <div class="alhuffaz-stat-value"><?php echo number_format($stats['sponsored_students']); ?></div>
            </div>
        </div>

        <div class="alhuffaz-stat-card">
            <div class="alhuffaz-stat-icon warning">
                <span class="dashicons dashicons-clock"></span>
            </div>
            <div class="alhuffaz-stat-content">
                <div class="alhuffaz-stat-label"><?php _e('Pending Sponsorships', 'al-huffaz-portal'); ?></div>
                <div class="alhuffaz-stat-value"><?php echo number_format($stats['pending_sponsorships']); ?></div>
            </div>
        </div>

        <div class="alhuffaz-stat-card">
            <div class="alhuffaz-stat-icon primary">
                <span class="dashicons dashicons-money-alt"></span>
            </div>
            <div class="alhuffaz-stat-content">
                <div class="alhuffaz-stat-label"><?php _e('Total Revenue', 'al-huffaz-portal'); ?></div>
                <div class="alhuffaz-stat-value"><?php echo Helpers::format_currency($stats['total_revenue']); ?></div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="alhuffaz-quick-actions">
        <a href="<?php echo admin_url('admin.php?page=alhuffaz-students'); ?>" class="alhuffaz-quick-action">
            <span class="dashicons dashicons-groups"></span>
            <span><?php _e('View Students', 'al-huffaz-portal'); ?></span>
        </a>
        <a href="<?php echo admin_url('admin.php?page=alhuffaz-sponsors'); ?>" class="alhuffaz-quick-action">
            <span class="dashicons dashicons-heart"></span>
            <span><?php _e('Manage Sponsors', 'al-huffaz-portal'); ?></span>
        </a>
        <a href="<?php echo admin_url('admin.php?page=alhuffaz-payments'); ?>" class="alhuffaz-quick-action">
            <span class="dashicons dashicons-money-alt"></span>
            <span><?php _e('View Payments', 'al-huffaz-portal'); ?></span>
        </a>
        <a href="<?php echo admin_url('admin.php?page=alhuffaz-import'); ?>" class="alhuffaz-quick-action">
            <span class="dashicons dashicons-upload"></span>
            <span><?php _e('Bulk Import', 'al-huffaz-portal'); ?></span>
        </a>
        <a href="<?php echo admin_url('admin.php?page=alhuffaz-reports'); ?>" class="alhuffaz-quick-action">
            <span class="dashicons dashicons-chart-bar"></span>
            <span><?php _e('Reports', 'al-huffaz-portal'); ?></span>
        </a>
        <a href="<?php echo admin_url('admin.php?page=alhuffaz-settings'); ?>" class="alhuffaz-quick-action">
            <span class="dashicons dashicons-admin-generic"></span>
            <span><?php _e('Settings', 'al-huffaz-portal'); ?></span>
        </a>
    </div>

    <!-- Charts -->
    <div class="alhuffaz-charts-grid">
        <div class="alhuffaz-chart-container">
            <h3 class="alhuffaz-chart-title"><?php _e('Revenue (Last 12 Months)', 'al-huffaz-portal'); ?></h3>
            <div style="height: 300px;">
                <canvas id="alhuffaz-revenue-chart"></canvas>
            </div>
        </div>

        <div class="alhuffaz-chart-container">
            <h3 class="alhuffaz-chart-title"><?php _e('Students by Grade', 'al-huffaz-portal'); ?></h3>
            <div style="height: 300px;">
                <canvas id="alhuffaz-grade-chart"></canvas>
            </div>
        </div>
    </div>

    <div class="alhuffaz-charts-grid">
        <div class="alhuffaz-chart-container">
            <h3 class="alhuffaz-chart-title"><?php _e('Sponsorship Status', 'al-huffaz-portal'); ?></h3>
            <div style="height: 300px;">
                <canvas id="alhuffaz-sponsorship-chart"></canvas>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="alhuffaz-card">
            <div class="alhuffaz-card-header">
                <h3 class="alhuffaz-card-title"><?php _e('Recent Activity', 'al-huffaz-portal'); ?></h3>
            </div>

            <?php if (empty($activities)): ?>
                <div class="alhuffaz-empty">
                    <span class="dashicons dashicons-clipboard"></span>
                    <p><?php _e('No recent activity', 'al-huffaz-portal'); ?></p>
                </div>
            <?php else: ?>
                <ul class="alhuffaz-activity-list">
                    <?php foreach ($activities as $activity): ?>
                        <li class="alhuffaz-activity-item">
                            <div class="alhuffaz-activity-icon">
                                <span class="dashicons dashicons-marker"></span>
                            </div>
                            <div class="alhuffaz-activity-content">
                                <div class="alhuffaz-activity-text">
                                    <strong><?php echo esc_html($activity['user']); ?></strong>
                                    <?php echo esc_html($activity['action']); ?>
                                </div>
                                <div class="alhuffaz-activity-time"><?php echo esc_html($activity['time']); ?></div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>

    <!-- Pending Items -->
    <?php if (!empty($pending['sponsorships']) || !empty($pending['payments'])): ?>
    <div class="alhuffaz-card">
        <div class="alhuffaz-card-header">
            <h3 class="alhuffaz-card-title">
                <?php _e('Pending Approvals', 'al-huffaz-portal'); ?>
                <span class="alhuffaz-notification-badge"><?php echo count($pending['sponsorships']) + count($pending['payments']); ?></span>
            </h3>
            <a href="<?php echo admin_url('admin.php?page=alhuffaz-sponsors'); ?>" class="alhuffaz-btn alhuffaz-btn-sm alhuffaz-btn-secondary">
                <?php _e('View All', 'al-huffaz-portal'); ?>
            </a>
        </div>

        <div class="alhuffaz-table-wrapper">
            <table class="alhuffaz-table">
                <thead>
                    <tr>
                        <th><?php _e('Type', 'al-huffaz-portal'); ?></th>
                        <th><?php _e('Name', 'al-huffaz-portal'); ?></th>
                        <th><?php _e('Amount', 'al-huffaz-portal'); ?></th>
                        <th><?php _e('Date', 'al-huffaz-portal'); ?></th>
                        <th><?php _e('Actions', 'al-huffaz-portal'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pending['sponsorships'] as $sponsorship): ?>
                        <tr>
                            <td><span class="alhuffaz-badge badge-info"><?php _e('Sponsorship', 'al-huffaz-portal'); ?></span></td>
                            <td><?php echo esc_html(get_post_meta($sponsorship->ID, '_sponsor_name', true)); ?></td>
                            <td><?php echo Helpers::format_currency(get_post_meta($sponsorship->ID, '_amount', true)); ?></td>
                            <td><?php echo Helpers::format_date($sponsorship->post_date); ?></td>
                            <td>
                                <button class="alhuffaz-btn alhuffaz-btn-sm alhuffaz-btn-primary alhuffaz-approve-sponsorship" data-id="<?php echo $sponsorship->ID; ?>">
                                    <?php _e('Approve', 'al-huffaz-portal'); ?>
                                </button>
                                <button class="alhuffaz-btn alhuffaz-btn-sm alhuffaz-btn-secondary alhuffaz-reject-sponsorship" data-id="<?php echo $sponsorship->ID; ?>">
                                    <?php _e('Reject', 'al-huffaz-portal'); ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>
