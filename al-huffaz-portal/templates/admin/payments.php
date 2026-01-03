<?php
use AlHuffaz\Admin\Payment_Manager;
use AlHuffaz\Core\Helpers;
if (!defined('ABSPATH')) exit;

$result = Payment_Manager::get_payments(array('page' => isset($_GET['paged']) ? intval($_GET['paged']) : 1));
$counts = Payment_Manager::get_counts();
?>
<div class="alhuffaz-wrap">
    <div class="alhuffaz-header">
        <h1><span class="dashicons dashicons-money-alt"></span> <?php _e('Payments', 'al-huffaz-portal'); ?></h1>
    </div>
    <div class="alhuffaz-stats-grid">
        <div class="alhuffaz-stat-card"><div class="alhuffaz-stat-icon warning"><span class="dashicons dashicons-clock"></span></div><div class="alhuffaz-stat-content"><div class="alhuffaz-stat-label"><?php _e('Pending', 'al-huffaz-portal'); ?></div><div class="alhuffaz-stat-value"><?php echo $counts['pending']; ?></div></div></div>
        <div class="alhuffaz-stat-card"><div class="alhuffaz-stat-icon primary"><span class="dashicons dashicons-yes-alt"></span></div><div class="alhuffaz-stat-content"><div class="alhuffaz-stat-label"><?php _e('Approved', 'al-huffaz-portal'); ?></div><div class="alhuffaz-stat-value"><?php echo $counts['approved']; ?></div></div></div>
        <div class="alhuffaz-stat-card"><div class="alhuffaz-stat-icon secondary"><span class="dashicons dashicons-money-alt"></span></div><div class="alhuffaz-stat-content"><div class="alhuffaz-stat-label"><?php _e('Total Revenue', 'al-huffaz-portal'); ?></div><div class="alhuffaz-stat-value"><?php echo Helpers::format_currency(Payment_Manager::get_total_revenue()); ?></div></div></div>
    </div>
    <div class="alhuffaz-card">
        <div class="alhuffaz-table-wrapper">
            <table class="alhuffaz-table">
                <thead><tr><th><?php _e('Sponsor', 'al-huffaz-portal'); ?></th><th><?php _e('Student', 'al-huffaz-portal'); ?></th><th><?php _e('Amount', 'al-huffaz-portal'); ?></th><th><?php _e('Method', 'al-huffaz-portal'); ?></th><th><?php _e('Date', 'al-huffaz-portal'); ?></th><th><?php _e('Status', 'al-huffaz-portal'); ?></th><th><?php _e('Actions', 'al-huffaz-portal'); ?></th></tr></thead>
                <tbody>
                    <?php foreach ($result['payments'] as $p): ?>
                    <tr><td><?php echo esc_html($p['sponsor_name']); ?></td><td><?php echo esc_html($p['student_name']); ?></td><td><?php echo esc_html($p['amount_formatted']); ?></td><td><?php echo esc_html($p['payment_method']); ?></td><td><?php echo esc_html($p['payment_date_formatted']); ?></td><td><?php echo $p['status_badge']; ?></td>
                    <td><?php if ($p['status'] === 'pending'): ?><button class="alhuffaz-btn alhuffaz-btn-sm alhuffaz-btn-primary alhuffaz-verify-payment" data-id="<?php echo $p['id']; ?>"><?php _e('Verify', 'al-huffaz-portal'); ?></button><?php endif; ?></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
