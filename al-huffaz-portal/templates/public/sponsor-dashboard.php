<?php
/**
 * Sponsor Dashboard Template
 * Al-Huffaz Education System Portal
 *
 * Displays sponsor's dashboard with sponsored students and payment history
 */

use AlHuffaz\Frontend\Sponsor_Dashboard;
use AlHuffaz\Core\Helpers;

if (!defined('ABSPATH')) exit;

// Check if user is logged in
if (!is_user_logged_in()) {
    ?>
    <div class="ahp-sponsor-login">
        <div class="ahp-login-card">
            <div class="ahp-login-header">
                <i class="fas fa-hand-holding-heart"></i>
                <h2><?php _e('Sponsor Portal', 'al-huffaz-portal'); ?></h2>
                <p><?php _e('Please login to access your sponsor dashboard', 'al-huffaz-portal'); ?></p>
            </div>
            <div class="ahp-login-form">
                <?php
                wp_login_form(array(
                    'redirect' => get_permalink(),
                    'form_id' => 'ahp-sponsor-login-form',
                    'label_username' => __('Email or Username', 'al-huffaz-portal'),
                    'label_password' => __('Password', 'al-huffaz-portal'),
                    'label_remember' => __('Remember Me', 'al-huffaz-portal'),
                    'label_log_in' => __('Login', 'al-huffaz-portal'),
                ));
                ?>
            </div>
            <div class="ahp-login-footer">
                <p><?php _e('Not a sponsor yet?', 'al-huffaz-portal'); ?></p>
                <a href="<?php echo home_url('/sponsor-a-student'); ?>" class="ahp-btn ahp-btn-primary">
                    <i class="fas fa-heart"></i> <?php _e('Become a Sponsor', 'al-huffaz-portal'); ?>
                </a>
            </div>
        </div>
    </div>
    <?php
    return;
}

$user = wp_get_current_user();
$data = Sponsor_Dashboard::get_dashboard_data($user->ID);
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

<style>
/* SPONSOR DASHBOARD STYLES */
:root {
    --ahp-primary: #0080ff;
    --ahp-primary-dark: #004d99;
    --ahp-success: #10b981;
    --ahp-warning: #f59e0b;
    --ahp-danger: #ef4444;
    --ahp-text-dark: #001a33;
    --ahp-text-muted: #64748b;
    --ahp-border: #cce6ff;
    --ahp-bg-light: #f0f8ff;
}

.ahp-sponsor-dashboard {
    max-width: 1200px;
    margin: 40px auto;
    padding: 0 24px;
    font-family: 'Poppins', sans-serif;
}

/* LOGIN STYLES */
.ahp-sponsor-login {
    min-height: 60vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px 20px;
    font-family: 'Poppins', sans-serif;
}

.ahp-login-card {
    background: white;
    border-radius: 20px;
    box-shadow: 0 10px 40px rgba(0, 128, 255, 0.15);
    padding: 48px;
    max-width: 420px;
    width: 100%;
    text-align: center;
}

.ahp-login-header i {
    font-size: 64px;
    color: var(--ahp-primary);
    margin-bottom: 20px;
}

.ahp-login-header h2 {
    margin: 0 0 12px 0;
    font-size: 28px;
    font-weight: 700;
    color: var(--ahp-text-dark);
}

.ahp-login-header p {
    margin: 0 0 32px 0;
    color: var(--ahp-text-muted);
}

.ahp-login-form #ahp-sponsor-login-form {
    text-align: left;
}

.ahp-login-form label {
    display: block;
    font-weight: 600;
    color: var(--ahp-text-dark);
    margin-bottom: 8px;
    font-size: 14px;
}

.ahp-login-form input[type="text"],
.ahp-login-form input[type="password"] {
    width: 100%;
    padding: 14px 16px;
    border: 2px solid var(--ahp-border);
    border-radius: 10px;
    font-size: 15px;
    margin-bottom: 20px;
    transition: border-color 0.3s;
    font-family: 'Poppins', sans-serif;
    box-sizing: border-box;
}

.ahp-login-form input:focus {
    outline: none;
    border-color: var(--ahp-primary);
}

.ahp-login-form input[type="submit"] {
    width: 100%;
    padding: 14px;
    background: linear-gradient(135deg, var(--ahp-primary) 0%, var(--ahp-primary-dark) 100%);
    color: white;
    border: none;
    border-radius: 10px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: transform 0.3s, box-shadow 0.3s;
    font-family: 'Poppins', sans-serif;
}

.ahp-login-form input[type="submit"]:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0, 128, 255, 0.3);
}

.ahp-login-footer {
    margin-top: 32px;
    padding-top: 24px;
    border-top: 2px solid var(--ahp-bg-light);
}

.ahp-login-footer p {
    margin: 0 0 16px 0;
    color: var(--ahp-text-muted);
}

/* DASHBOARD HEADER */
.ahp-dashboard-header {
    background: linear-gradient(135deg, var(--ahp-primary) 0%, var(--ahp-primary-dark) 100%);
    color: white;
    padding: 40px;
    border-radius: 20px;
    margin-bottom: 32px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 20px;
}

.ahp-dashboard-welcome {
    margin: 0 0 8px 0;
    font-size: 32px;
    font-weight: 700;
}

.ahp-dashboard-subtitle {
    margin: 0;
    opacity: 0.9;
    font-size: 16px;
}

.ahp-dashboard-actions {
    display: flex;
    gap: 12px;
}

/* STATS GRID */
.ahp-dashboard-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 24px;
    margin-bottom: 40px;
}

.ahp-stat-card {
    background: white;
    border-radius: 16px;
    padding: 28px;
    box-shadow: 0 4px 20px rgba(0, 128, 255, 0.1);
    border: 2px solid var(--ahp-border);
    display: flex;
    align-items: center;
    gap: 20px;
    transition: transform 0.3s, box-shadow 0.3s;
}

.ahp-stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 30px rgba(0, 128, 255, 0.15);
}

.ahp-stat-icon {
    width: 72px;
    height: 72px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    flex-shrink: 0;
}

.ahp-stat-icon.students {
    background: linear-gradient(135deg, #dbeafe, #bfdbfe);
    color: #1e40af;
}

.ahp-stat-icon.amount {
    background: linear-gradient(135deg, #d1fae5, #a7f3d0);
    color: #065f46;
}

.ahp-stat-icon.pending {
    background: linear-gradient(135deg, #fef3c7, #fde68a);
    color: #92400e;
}

.ahp-stat-content {
    flex: 1;
}

.ahp-stat-label {
    font-size: 14px;
    color: var(--ahp-text-muted);
    margin-bottom: 4px;
    font-weight: 500;
}

.ahp-stat-value {
    font-size: 32px;
    font-weight: 800;
    color: var(--ahp-text-dark);
    line-height: 1.1;
}

/* SECTION TITLE */
.ahp-section-title {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 24px;
    flex-wrap: wrap;
    gap: 16px;
}

.ahp-section-title h2 {
    margin: 0;
    font-size: 24px;
    font-weight: 700;
    color: var(--ahp-text-dark);
    display: flex;
    align-items: center;
    gap: 12px;
}

.ahp-section-title h2 i {
    color: var(--ahp-primary);
}

/* SPONSORED STUDENTS */
.ahp-sponsored-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 24px;
    margin-bottom: 48px;
}

.ahp-sponsored-card {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0, 128, 255, 0.1);
    border: 2px solid var(--ahp-border);
    transition: transform 0.3s, box-shadow 0.3s;
}

.ahp-sponsored-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 30px rgba(0, 128, 255, 0.15);
}

.ahp-sponsored-header {
    background: linear-gradient(135deg, var(--ahp-bg-light), white);
    padding: 24px;
    display: flex;
    align-items: center;
    gap: 20px;
    border-bottom: 2px solid var(--ahp-border);
}

.ahp-student-photo {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid white;
    box-shadow: 0 4px 12px rgba(0, 128, 255, 0.2);
}

.ahp-student-photo-placeholder {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--ahp-primary), var(--ahp-primary-dark));
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    font-weight: 800;
    flex-shrink: 0;
}

.ahp-student-info h3 {
    margin: 0 0 8px 0;
    font-size: 20px;
    font-weight: 700;
    color: var(--ahp-text-dark);
}

.ahp-student-badges {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.ahp-badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.ahp-badge-grade {
    background: var(--ahp-primary);
    color: white;
}

.ahp-badge-category {
    background: #d1fae5;
    color: #065f46;
}

.ahp-sponsored-body {
    padding: 24px;
}

.ahp-sponsorship-details {
    display: grid;
    gap: 12px;
}

.ahp-detail-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 16px;
    background: var(--ahp-bg-light);
    border-radius: 10px;
}

.ahp-detail-label {
    font-size: 13px;
    color: var(--ahp-text-muted);
    font-weight: 500;
}

.ahp-detail-value {
    font-size: 15px;
    font-weight: 600;
    color: var(--ahp-text-dark);
}

.ahp-sponsored-footer {
    padding: 16px 24px;
    background: var(--ahp-bg-light);
    border-top: 2px solid var(--ahp-border);
    display: flex;
    gap: 12px;
}

.ahp-sponsored-footer .ahp-btn {
    flex: 1;
    justify-content: center;
}

/* PAYMENT HISTORY */
.ahp-payment-section {
    background: white;
    border-radius: 16px;
    padding: 32px;
    box-shadow: 0 4px 20px rgba(0, 128, 255, 0.1);
    border: 2px solid var(--ahp-border);
}

.ahp-payment-table {
    width: 100%;
    border-collapse: collapse;
}

.ahp-payment-table th,
.ahp-payment-table td {
    padding: 16px;
    text-align: left;
    border-bottom: 1px solid var(--ahp-border);
}

.ahp-payment-table thead th {
    background: var(--ahp-bg-light);
    font-weight: 700;
    color: var(--ahp-text-dark);
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.ahp-payment-table tbody tr:hover {
    background: var(--ahp-bg-light);
}

.ahp-payment-status {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.ahp-status-completed {
    background: #d1fae5;
    color: #065f46;
}

.ahp-status-pending {
    background: #fef3c7;
    color: #92400e;
}

.ahp-status-failed {
    background: #fee2e2;
    color: #991b1b;
}

/* EMPTY STATE */
.ahp-empty-state {
    text-align: center;
    padding: 60px 20px;
    background: var(--ahp-bg-light);
    border-radius: 16px;
    margin-bottom: 40px;
}

.ahp-empty-state i {
    font-size: 80px;
    color: var(--ahp-border);
    margin-bottom: 24px;
}

.ahp-empty-state h3 {
    margin: 0 0 12px 0;
    font-size: 24px;
    color: var(--ahp-text-dark);
}

.ahp-empty-state p {
    margin: 0 0 24px 0;
    color: var(--ahp-text-muted);
    font-size: 16px;
}

/* BUTTONS */
.ahp-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 14px;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.3s;
    border: none;
    font-family: 'Poppins', sans-serif;
}

.ahp-btn-primary {
    background: linear-gradient(135deg, var(--ahp-primary), var(--ahp-primary-dark));
    color: white;
}

.ahp-btn-secondary {
    background: var(--ahp-bg-light);
    color: var(--ahp-primary);
    border: 2px solid var(--ahp-border);
}

.ahp-btn-outline {
    background: transparent;
    color: white;
    border: 2px solid rgba(255,255,255,0.5);
}

.ahp-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.ahp-btn-sm {
    padding: 8px 16px;
    font-size: 13px;
}

/* RESPONSIVE */
@media (max-width: 768px) {
    .ahp-sponsor-dashboard { padding: 16px; margin: 20px auto; }
    .ahp-dashboard-header { padding: 24px; flex-direction: column; text-align: center; }
    .ahp-dashboard-welcome { font-size: 24px; }
    .ahp-sponsored-grid { grid-template-columns: 1fr; }
    .ahp-payment-table { font-size: 13px; }
    .ahp-payment-table th, .ahp-payment-table td { padding: 12px 8px; }
    .ahp-stat-card { flex-direction: column; text-align: center; }
}
</style>

<div class="ahp-sponsor-dashboard">
    <!-- Dashboard Header -->
    <div class="ahp-dashboard-header">
        <div>
            <h1 class="ahp-dashboard-welcome">
                <?php printf(__('Welcome back, %s', 'al-huffaz-portal'), esc_html($user->display_name)); ?>
            </h1>
            <p class="ahp-dashboard-subtitle">
                <i class="fas fa-hand-holding-heart"></i>
                <?php _e('Thank you for your generous support in educating our students.', 'al-huffaz-portal'); ?>
            </p>
        </div>
        <div class="ahp-dashboard-actions">
            <a href="<?php echo home_url('/sponsor-a-student'); ?>" class="ahp-btn ahp-btn-outline">
                <i class="fas fa-plus"></i> <?php _e('Sponsor Another', 'al-huffaz-portal'); ?>
            </a>
            <a href="<?php echo wp_logout_url(home_url()); ?>" class="ahp-btn ahp-btn-outline">
                <i class="fas fa-sign-out-alt"></i> <?php _e('Logout', 'al-huffaz-portal'); ?>
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="ahp-dashboard-stats">
        <div class="ahp-stat-card">
            <div class="ahp-stat-icon students">
                <i class="fas fa-user-graduate"></i>
            </div>
            <div class="ahp-stat-content">
                <div class="ahp-stat-label"><?php _e('Students Sponsored', 'al-huffaz-portal'); ?></div>
                <div class="ahp-stat-value"><?php echo intval($data['students_count']); ?></div>
            </div>
        </div>

        <div class="ahp-stat-card">
            <div class="ahp-stat-icon amount">
                <i class="fas fa-donate"></i>
            </div>
            <div class="ahp-stat-content">
                <div class="ahp-stat-label"><?php _e('Total Contributed', 'al-huffaz-portal'); ?></div>
                <div class="ahp-stat-value"><?php echo esc_html($data['total_contributed']); ?></div>
            </div>
        </div>

        <div class="ahp-stat-card">
            <div class="ahp-stat-icon pending">
                <i class="fas fa-clock"></i>
            </div>
            <div class="ahp-stat-content">
                <div class="ahp-stat-label"><?php _e('Pending Payments', 'al-huffaz-portal'); ?></div>
                <div class="ahp-stat-value"><?php echo intval($data['pending_payments']); ?></div>
            </div>
        </div>
    </div>

    <!-- Sponsored Students -->
    <div class="ahp-section-title">
        <h2><i class="fas fa-users"></i> <?php _e('Your Sponsored Students', 'al-huffaz-portal'); ?></h2>
    </div>

    <?php if (empty($data['sponsorships'])): ?>
        <div class="ahp-empty-state">
            <i class="fas fa-heart"></i>
            <h3><?php _e('No Active Sponsorships', 'al-huffaz-portal'); ?></h3>
            <p><?php _e('You haven\'t sponsored any students yet. Start making a difference today!', 'al-huffaz-portal'); ?></p>
            <a href="<?php echo home_url('/sponsor-a-student'); ?>" class="ahp-btn ahp-btn-primary">
                <i class="fas fa-hand-holding-heart"></i> <?php _e('Sponsor a Student', 'al-huffaz-portal'); ?>
            </a>
        </div>
    <?php else: ?>
        <div class="ahp-sponsored-grid">
            <?php foreach ($data['sponsorships'] as $s): ?>
            <div class="ahp-sponsored-card">
                <div class="ahp-sponsored-header">
                    <?php if (!empty($s['student_photo'])): ?>
                        <img src="<?php echo esc_url($s['student_photo']); ?>" alt="<?php echo esc_attr($s['student_name']); ?>" class="ahp-student-photo">
                    <?php else: ?>
                        <div class="ahp-student-photo-placeholder">
                            <?php echo esc_html(strtoupper(substr($s['student_name'], 0, 1))); ?>
                        </div>
                    <?php endif; ?>
                    <div class="ahp-student-info">
                        <h3><?php echo esc_html($s['student_name']); ?></h3>
                        <div class="ahp-student-badges">
                            <?php if (!empty($s['grade'])): ?>
                            <span class="ahp-badge ahp-badge-grade"><?php echo esc_html($s['grade']); ?></span>
                            <?php endif; ?>
                            <?php if (!empty($s['category'])): ?>
                            <span class="ahp-badge ahp-badge-category"><?php echo esc_html($s['category']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="ahp-sponsored-body">
                    <div class="ahp-sponsorship-details">
                        <div class="ahp-detail-row">
                            <span class="ahp-detail-label"><?php _e('Sponsorship Amount', 'al-huffaz-portal'); ?></span>
                            <span class="ahp-detail-value"><?php echo Helpers::format_currency($s['amount']); ?>/<?php echo esc_html($s['type']); ?></span>
                        </div>
                        <div class="ahp-detail-row">
                            <span class="ahp-detail-label"><?php _e('Sponsored Since', 'al-huffaz-portal'); ?></span>
                            <span class="ahp-detail-value"><?php echo esc_html($s['start_date']); ?></span>
                        </div>
                        <div class="ahp-detail-row">
                            <span class="ahp-detail-label"><?php _e('Status', 'al-huffaz-portal'); ?></span>
                            <span class="ahp-payment-status ahp-status-completed">
                                <i class="fas fa-check-circle"></i> <?php _e('Active', 'al-huffaz-portal'); ?>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="ahp-sponsored-footer">
                    <?php if (!empty($s['student_id'])): ?>
                    <a href="<?php echo get_permalink($s['student_id']); ?>" class="ahp-btn ahp-btn-secondary ahp-btn-sm">
                        <i class="fas fa-eye"></i> <?php _e('View Profile', 'al-huffaz-portal'); ?>
                    </a>
                    <?php endif; ?>
                    <a href="<?php echo home_url('/make-payment/?student=' . $s['student_id']); ?>" class="ahp-btn ahp-btn-primary ahp-btn-sm">
                        <i class="fas fa-credit-card"></i> <?php _e('Make Payment', 'al-huffaz-portal'); ?>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Payment History -->
    <div class="ahp-section-title">
        <h2><i class="fas fa-history"></i> <?php _e('Payment History', 'al-huffaz-portal'); ?></h2>
    </div>

    <div class="ahp-payment-section">
        <?php if (empty($data['recent_payments'])): ?>
            <div class="ahp-empty-state" style="margin: 0; background: transparent;">
                <i class="fas fa-receipt"></i>
                <h3><?php _e('No Payment History', 'al-huffaz-portal'); ?></h3>
                <p><?php _e('Your payment records will appear here once you make your first contribution.', 'al-huffaz-portal'); ?></p>
            </div>
        <?php else: ?>
            <table class="ahp-payment-table">
                <thead>
                    <tr>
                        <th><?php _e('Date', 'al-huffaz-portal'); ?></th>
                        <th><?php _e('Student', 'al-huffaz-portal'); ?></th>
                        <th><?php _e('Amount', 'al-huffaz-portal'); ?></th>
                        <th><?php _e('Method', 'al-huffaz-portal'); ?></th>
                        <th><?php _e('Status', 'al-huffaz-portal'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['recent_payments'] as $p): ?>
                    <tr>
                        <td><?php echo esc_html($p['payment_date_formatted']); ?></td>
                        <td><strong><?php echo esc_html($p['student_name']); ?></strong></td>
                        <td><strong><?php echo esc_html($p['amount_formatted']); ?></strong></td>
                        <td><?php echo esc_html(ucfirst($p['payment_method'] ?? 'N/A')); ?></td>
                        <td>
                            <?php
                            $status = $p['status'] ?? 'pending';
                            $status_class = 'ahp-status-' . $status;
                            $status_icon = $status === 'completed' ? 'check-circle' : ($status === 'pending' ? 'clock' : 'times-circle');
                            ?>
                            <span class="ahp-payment-status <?php echo esc_attr($status_class); ?>">
                                <i class="fas fa-<?php echo $status_icon; ?>"></i>
                                <?php echo esc_html(ucfirst($status)); ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
