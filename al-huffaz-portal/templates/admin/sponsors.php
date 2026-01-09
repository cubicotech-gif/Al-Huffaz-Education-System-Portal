<?php
/**
 * Sponsors List Template
 */

use AlHuffaz\Admin\Sponsor_Manager;
use AlHuffaz\Core\Helpers;

if (!defined('ABSPATH')) exit;

$page = isset($_GET['paged']) ? intval($_GET['paged']) : 1;
$view = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'active';
$status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';

$result = Sponsor_Manager::get_sponsorships(array('page' => $page, 'per_page' => 20, 'status' => $status));
$counts = Sponsor_Manager::get_counts();

// Get active sponsors data
$active_sponsors = array();
if ($view === 'active') {
    global $wpdb;

    // Get all approved and linked sponsorships grouped by sponsor
    $sponsorships = get_posts(array(
        'post_type' => 'alhuffaz_sponsor',
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                'key' => '_status',
                'value' => 'approved',
            ),
            array(
                'key' => '_linked',
                'value' => 'yes',
            ),
        ),
    ));

    // Group by sponsor
    $sponsors_data = array();
    foreach ($sponsorships as $sponsorship) {
        $sponsor_user_id = get_post_meta($sponsorship->ID, '_sponsor_user_id', true);
        $sponsor_email = get_post_meta($sponsorship->ID, '_sponsor_email', true);
        $sponsor_key = $sponsor_user_id ? 'user_' . $sponsor_user_id : 'email_' . $sponsor_email;

        if (!isset($sponsors_data[$sponsor_key])) {
            $sponsors_data[$sponsor_key] = array(
                'sponsor_name' => get_post_meta($sponsorship->ID, '_sponsor_name', true),
                'sponsor_email' => $sponsor_email,
                'sponsor_phone' => get_post_meta($sponsorship->ID, '_sponsor_phone', true),
                'sponsor_country' => get_post_meta($sponsorship->ID, '_sponsor_country', true),
                'sponsor_user_id' => $sponsor_user_id,
                'total_amount' => 0,
                'students' => array(),
                'sponsorship_count' => 0,
            );
        }

        $student_id = get_post_meta($sponsorship->ID, '_student_id', true);
        $student = get_post($student_id);
        $amount = floatval(get_post_meta($sponsorship->ID, '_amount', true));

        if ($student) {
            $sponsors_data[$sponsor_key]['students'][] = array(
                'student_id' => $student_id,
                'student_name' => $student->post_title,
                'student_photo' => Helpers::get_student_photo($student_id),
                'grade_level' => Helpers::get_grade_label(get_post_meta($student_id, 'grade_level', true)),
                'amount' => $amount,
                'amount_formatted' => Helpers::format_currency($amount),
                'sponsorship_id' => $sponsorship->ID,
                'linked_date' => $sponsorship->post_date,
            );
        }

        $sponsors_data[$sponsor_key]['total_amount'] += $amount;
        $sponsors_data[$sponsor_key]['sponsorship_count']++;
    }

    $active_sponsors = array_values($sponsors_data);
}
?>

<div class="alhuffaz-wrap">
    <div class="alhuffaz-header">
        <h1><span class="dashicons dashicons-heart"></span> <?php _e('Sponsors & Sponsorships', 'al-huffaz-portal'); ?></h1>
    </div>

    <div class="alhuffaz-tabs">
        <a href="?page=alhuffaz-sponsors&view=active" class="alhuffaz-tab <?php echo $view === 'active' ? 'active' : ''; ?>"><?php _e('Active Sponsors', 'al-huffaz-portal'); ?> <span class="alhuffaz-notification-badge"><?php echo $counts['linked']; ?></span></a>
        <a href="?page=alhuffaz-sponsors&view=requests" class="alhuffaz-tab <?php echo $view === 'requests' && !$status ? 'active' : ''; ?>"><?php _e('All Requests', 'al-huffaz-portal'); ?></a>
        <a href="?page=alhuffaz-sponsors&view=requests&status=pending" class="alhuffaz-tab <?php echo $view === 'requests' && $status === 'pending' ? 'active' : ''; ?>"><?php _e('Pending', 'al-huffaz-portal'); ?> <span class="alhuffaz-notification-badge"><?php echo $counts['pending']; ?></span></a>
        <a href="?page=alhuffaz-sponsors&view=requests&status=approved" class="alhuffaz-tab <?php echo $view === 'requests' && $status === 'approved' ? 'active' : ''; ?>"><?php _e('Approved', 'al-huffaz-portal'); ?></a>
    </div>

    <?php if ($view === 'active'): ?>
        <!-- Active Sponsors View -->
        <?php if (empty($active_sponsors)): ?>
            <div class="alhuffaz-card">
                <div class="alhuffaz-empty">
                    <span class="dashicons dashicons-heart"></span>
                    <h3><?php _e('No active sponsors found', 'al-huffaz-portal'); ?></h3>
                    <p><?php _e('Active sponsors will appear here once sponsorships are approved and linked.', 'al-huffaz-portal'); ?></p>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($active_sponsors as $sponsor): ?>
                <div class="alhuffaz-card" style="margin-bottom: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #e0e0e0;">
                        <div>
                            <h3 style="margin: 0 0 8px 0; display: flex; align-items: center; gap: 8px;">
                                <span class="dashicons dashicons-businessperson" style="color: var(--alhuffaz-primary);"></span>
                                <?php echo esc_html($sponsor['sponsor_name']); ?>
                            </h3>
                            <div style="display: flex; gap: 20px; flex-wrap: wrap; color: #666;">
                                <span><span class="dashicons dashicons-email" style="font-size: 14px;"></span> <?php echo esc_html($sponsor['sponsor_email']); ?></span>
                                <?php if ($sponsor['sponsor_phone']): ?>
                                    <span><span class="dashicons dashicons-phone" style="font-size: 14px;"></span> <?php echo esc_html($sponsor['sponsor_phone']); ?></span>
                                <?php endif; ?>
                                <?php if ($sponsor['sponsor_country']): ?>
                                    <span><span class="dashicons dashicons-location" style="font-size: 14px;"></span> <?php echo esc_html($sponsor['sponsor_country']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-size: 24px; font-weight: bold; color: var(--alhuffaz-primary);">
                                <?php echo Helpers::format_currency($sponsor['total_amount']); ?>
                            </div>
                            <div style="font-size: 12px; color: #666;">
                                <?php printf(_n('%s Student', '%s Students', $sponsor['sponsorship_count'], 'al-huffaz-portal'), $sponsor['sponsorship_count']); ?>
                            </div>
                        </div>
                    </div>

                    <h4 style="margin: 15px 0 10px 0; color: #333;"><?php _e('Sponsored Students', 'al-huffaz-portal'); ?></h4>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 15px;">
                        <?php foreach ($sponsor['students'] as $student): ?>
                            <div style="border: 1px solid #e0e0e0; border-radius: 8px; padding: 15px; background: #fafafa; transition: all 0.2s;" onmouseover="this.style.boxShadow='0 4px 8px rgba(0,0,0,0.1)'" onmouseout="this.style.boxShadow='none'">
                                <div style="display: flex; gap: 12px; align-items: start;">
                                    <img src="<?php echo esc_url($student['student_photo']); ?>" alt="" style="width: 60px; height: 60px; border-radius: 8px; object-fit: cover;">
                                    <div style="flex: 1;">
                                        <h5 style="margin: 0 0 5px 0; font-size: 14px;">
                                            <a href="<?php echo get_permalink($student['student_id']); ?>" target="_blank" style="color: #333; text-decoration: none;">
                                                <?php echo esc_html($student['student_name']); ?>
                                            </a>
                                        </h5>
                                        <div style="font-size: 12px; color: #666; margin-bottom: 5px;">
                                            <span class="dashicons dashicons-welcome-learn-more" style="font-size: 12px;"></span> <?php echo esc_html($student['grade_level']); ?>
                                        </div>
                                        <div style="font-size: 14px; font-weight: bold; color: var(--alhuffaz-primary);">
                                            <?php echo esc_html($student['amount_formatted']); ?>/<?php _e('month', 'al-huffaz-portal'); ?>
                                        </div>
                                        <div style="margin-top: 8px;">
                                            <a href="<?php echo admin_url('admin.php?page=alhuffaz-add-student&id=' . $student['student_id']); ?>" class="alhuffaz-btn alhuffaz-btn-sm alhuffaz-btn-secondary" style="font-size: 11px; padding: 4px 10px;">
                                                <?php _e('View Details', 'al-huffaz-portal'); ?>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    <?php else: ?>
        <!-- Sponsorship Requests View -->
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
    <?php endif; ?>
</div>
