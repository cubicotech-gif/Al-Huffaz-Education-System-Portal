<?php
use AlHuffaz\Frontend\Sponsor_Dashboard;
use AlHuffaz\Core\Helpers;
if (!defined('ABSPATH')) exit;
$user = wp_get_current_user();
$data = Sponsor_Dashboard::get_dashboard_data($user->ID);
?>
<div class="alhuffaz-container alhuffaz-dashboard">
    <div class="alhuffaz-dashboard-header">
        <h1 class="alhuffaz-dashboard-welcome"><?php printf(__('Welcome, %s', 'al-huffaz-portal'), esc_html($user->display_name)); ?></h1>
        <p class="alhuffaz-dashboard-subtitle"><?php _e('Manage your sponsorships and track your contributions.', 'al-huffaz-portal'); ?></p>
    </div>

    <div class="alhuffaz-dashboard-stats">
        <div class="alhuffaz-dashboard-stat"><div class="alhuffaz-dashboard-stat-label"><?php _e('Students Sponsored', 'al-huffaz-portal'); ?></div><div class="alhuffaz-dashboard-stat-value primary"><?php echo $data['students_count']; ?></div></div>
        <div class="alhuffaz-dashboard-stat"><div class="alhuffaz-dashboard-stat-label"><?php _e('Total Contributed', 'al-huffaz-portal'); ?></div><div class="alhuffaz-dashboard-stat-value"><?php echo $data['total_contributed']; ?></div></div>
        <div class="alhuffaz-dashboard-stat"><div class="alhuffaz-dashboard-stat-label"><?php _e('Pending Payments', 'al-huffaz-portal'); ?></div><div class="alhuffaz-dashboard-stat-value"><?php echo $data['pending_payments']; ?></div></div>
    </div>

    <h2><?php _e('Your Sponsored Students', 'al-huffaz-portal'); ?></h2>
    <div class="alhuffaz-sponsored-students">
        <?php if (empty($data['sponsorships'])): ?>
            <div class="alhuffaz-empty"><p><?php _e('You have no active sponsorships.', 'al-huffaz-portal'); ?></p><a href="<?php echo home_url('/sponsor-a-student'); ?>" class="alhuffaz-btn alhuffaz-btn-primary"><?php _e('Sponsor a Student', 'al-huffaz-portal'); ?></a></div>
        <?php else: ?>
            <?php foreach ($data['sponsorships'] as $s): ?>
            <div class="alhuffaz-sponsored-student">
                <img src="<?php echo esc_url($s['student_photo']); ?>" alt="" class="alhuffaz-sponsored-student-photo">
                <div class="alhuffaz-sponsored-student-info">
                    <h3 class="alhuffaz-sponsored-student-name"><?php echo esc_html($s['student_name']); ?></h3>
                    <div class="alhuffaz-sponsored-student-details">
                        <span><?php echo esc_html($s['grade']); ?></span>
                        <span><?php echo esc_html($s['category']); ?></span>
                        <span><?php echo Helpers::format_currency($s['amount']); ?>/<?php echo $s['type']; ?></span>
                    </div>
                    <p><?php printf(__('Sponsored since %s', 'al-huffaz-portal'), $s['start_date']); ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="alhuffaz-payment-history">
        <div class="alhuffaz-payment-history-header">
            <h3 class="alhuffaz-payment-history-title"><?php _e('Recent Payments', 'al-huffaz-portal'); ?></h3>
        </div>
        <ul class="alhuffaz-payment-list">
            <?php if (empty($data['recent_payments'])): ?>
                <li class="alhuffaz-payment-item"><p><?php _e('No payment history.', 'al-huffaz-portal'); ?></p></li>
            <?php else: ?>
                <?php foreach ($data['recent_payments'] as $p): ?>
                <li class="alhuffaz-payment-item">
                    <div class="alhuffaz-payment-info"><span class="alhuffaz-payment-student"><?php echo esc_html($p['student_name']); ?></span><span class="alhuffaz-payment-date"><?php echo esc_html($p['payment_date_formatted']); ?></span></div>
                    <span class="alhuffaz-payment-amount"><?php echo esc_html($p['amount_formatted']); ?></span>
                </li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>
    </div>
</div>
