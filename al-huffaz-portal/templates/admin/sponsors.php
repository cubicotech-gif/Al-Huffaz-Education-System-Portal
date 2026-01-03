<?php
/**
 * Sponsors List Template
 */

use AlHuffaz\Admin\Sponsor_Manager;
use AlHuffaz\Core\Helpers;

if (!defined('ABSPATH')) exit;

$page = isset($_GET['paged']) ? intval($_GET['paged']) : 1;
$status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
$result = Sponsor_Manager::get_sponsorships(array('page' => $page, 'per_page' => 20, 'status' => $status));
$counts = Sponsor_Manager::get_counts();
?>

<div class="alhuffaz-wrap">
    <div class="alhuffaz-header">
        <h1><span class="dashicons dashicons-heart"></span> <?php _e('Sponsors & Sponsorships', 'al-huffaz-portal'); ?></h1>
    </div>

    <div class="alhuffaz-tabs">
        <a href="?page=alhuffaz-sponsors" class="alhuffaz-tab <?php echo !$status ? 'active' : ''; ?>"><?php _e('All', 'al-huffaz-portal'); ?></a>
        <a href="?page=alhuffaz-sponsors&status=pending" class="alhuffaz-tab <?php echo $status === 'pending' ? 'active' : ''; ?>"><?php _e('Pending', 'al-huffaz-portal'); ?> <span class="alhuffaz-notification-badge"><?php echo $counts['pending']; ?></span></a>
        <a href="?page=alhuffaz-sponsors&status=approved" class="alhuffaz-tab <?php echo $status === 'approved' ? 'active' : ''; ?>"><?php _e('Approved', 'al-huffaz-portal'); ?></a>
    </div>

    <div class="alhuffaz-card">
        <?php if (empty($result['sponsorships'])): ?>
            <div class="alhuffaz-empty">
                <span class="dashicons dashicons-heart"></span>
                <h3><?php _e('No sponsorships found', 'al-huffaz-portal'); ?></h3>
            </div>
        <?php else: ?>
            <div class="alhuffaz-table-wrapper">
                <table class="alhuffaz-table">
                    <thead>
                        <tr>
                            <th><?php _e('Sponsor', 'al-huffaz-portal'); ?></th>
                            <th><?php _e('Student', 'al-huffaz-portal'); ?></th>
                            <th><?php _e('Amount', 'al-huffaz-portal'); ?></th>
                            <th><?php _e('Type', 'al-huffaz-portal'); ?></th>
                            <th><?php _e('Status', 'al-huffaz-portal'); ?></th>
                            <th><?php _e('Date', 'al-huffaz-portal'); ?></th>
                            <th><?php _e('Actions', 'al-huffaz-portal'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($result['sponsorships'] as $s): ?>
                            <tr>
                                <td><strong><?php echo esc_html($s['sponsor_name']); ?></strong><br><small><?php echo esc_html($s['sponsor_email']); ?></small></td>
                                <td><?php echo esc_html($s['student_name']); ?></td>
                                <td><?php echo esc_html($s['amount_formatted']); ?></td>
                                <td><?php echo ucfirst($s['type']); ?></td>
                                <td><?php echo $s['status_badge']; ?></td>
                                <td><?php echo esc_html($s['created_at_formatted']); ?></td>
                                <td>
                                    <?php if ($s['status'] === 'pending'): ?>
                                        <button class="alhuffaz-btn alhuffaz-btn-sm alhuffaz-btn-primary alhuffaz-approve-sponsorship" data-id="<?php echo $s['id']; ?>"><?php _e('Approve', 'al-huffaz-portal'); ?></button>
                                        <button class="alhuffaz-btn alhuffaz-btn-sm alhuffaz-btn-danger alhuffaz-reject-sponsorship" data-id="<?php echo $s['id']; ?>"><?php _e('Reject', 'al-huffaz-portal'); ?></button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
