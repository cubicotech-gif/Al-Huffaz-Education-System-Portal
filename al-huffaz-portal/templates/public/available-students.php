<?php
/**
 * Available Students for Sponsorship Template
 * Al-Huffaz Education System Portal
 *
 * Shows ONLY donation-eligible students that are not yet sponsored
 * With fee calculation for monthly/quarterly/yearly plans
 */

use AlHuffaz\Core\Helpers;

if (!defined('ABSPATH')) exit;

// Get donation eligible students (not sponsored)
$args = array(
    'post_type' => 'student',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'meta_query' => array(
        'relation' => 'AND',
        array(
            'key' => 'donation_eligible',
            'value' => 'yes',
        ),
        array(
            'relation' => 'OR',
            array(
                'key' => '_is_sponsored',
                'value' => 'no',
            ),
            array(
                'key' => '_is_sponsored',
                'compare' => 'NOT EXISTS',
            ),
        ),
    ),
    'orderby' => 'title',
    'order' => 'ASC',
);

$students = get_posts($args);
$currency = get_option('alhuffaz_currency_symbol', 'Rs.');
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

<style>
/* AVAILABLE STUDENTS STYLES */
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

.ahp-available-students {
    max-width: 1400px;
    margin: 40px auto;
    padding: 0 24px;
    font-family: 'Poppins', sans-serif;
}

.ahp-page-header {
    text-align: center;
    margin-bottom: 48px;
}

.ahp-page-header h1 {
    font-size: 36px;
    font-weight: 800;
    color: var(--ahp-text-dark);
    margin: 0 0 16px 0;
}

.ahp-page-header p {
    font-size: 18px;
    color: var(--ahp-text-muted);
    max-width: 600px;
    margin: 0 auto;
}

.ahp-stats-bar {
    display: flex;
    justify-content: center;
    gap: 40px;
    margin-bottom: 40px;
    flex-wrap: wrap;
}

.ahp-stats-bar .stat {
    text-align: center;
}

.ahp-stats-bar .stat-value {
    font-size: 36px;
    font-weight: 800;
    color: var(--ahp-primary);
}

.ahp-stats-bar .stat-label {
    font-size: 14px;
    color: var(--ahp-text-muted);
}

/* Student Grid */
.ahp-students-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
    gap: 28px;
}

.ahp-student-card {
    background: white;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0, 128, 255, 0.1);
    border: 2px solid var(--ahp-border);
    transition: transform 0.3s, box-shadow 0.3s;
}

.ahp-student-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 12px 40px rgba(0, 128, 255, 0.2);
}

.ahp-card-header {
    background: linear-gradient(135deg, var(--ahp-bg-light), white);
    padding: 24px;
    display: flex;
    align-items: center;
    gap: 16px;
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

.ahp-photo-placeholder {
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
    font-size: 18px;
    font-weight: 700;
    color: var(--ahp-text-dark);
}

.ahp-badges {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.ahp-badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 11px;
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

/* Fee Calculator */
.ahp-fee-calculator {
    padding: 20px 24px;
}

.ahp-fee-title {
    font-size: 14px;
    font-weight: 700;
    color: var(--ahp-text-dark);
    margin: 0 0 16px 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.ahp-fee-title i {
    color: var(--ahp-primary);
}

.ahp-fee-plans {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
}

.ahp-fee-plan {
    background: var(--ahp-bg-light);
    border: 2px solid var(--ahp-border);
    border-radius: 12px;
    padding: 16px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s;
}

.ahp-fee-plan:hover,
.ahp-fee-plan.selected {
    border-color: var(--ahp-primary);
    background: white;
}

.ahp-fee-plan.selected {
    box-shadow: 0 0 0 3px rgba(0, 128, 255, 0.2);
}

.ahp-plan-label {
    font-size: 12px;
    font-weight: 600;
    color: var(--ahp-text-muted);
    text-transform: uppercase;
    margin-bottom: 4px;
}

.ahp-plan-amount {
    font-size: 18px;
    font-weight: 800;
    color: var(--ahp-text-dark);
}

.ahp-plan-period {
    font-size: 11px;
    color: var(--ahp-text-muted);
}

/* One Time Fees Info */
.ahp-one-time-info {
    margin-top: 12px;
    padding: 12px;
    background: #fef3c7;
    border-radius: 8px;
    font-size: 12px;
    color: #92400e;
}

.ahp-one-time-info i {
    margin-right: 6px;
}

/* Card Footer */
.ahp-card-footer {
    padding: 16px 24px;
    background: var(--ahp-bg-light);
    border-top: 2px solid var(--ahp-border);
    display: flex;
    gap: 12px;
}

.ahp-card-footer .ahp-btn {
    flex: 1;
    justify-content: center;
}

/* Buttons */
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
    background: white;
    color: var(--ahp-primary);
    border: 2px solid var(--ahp-border);
}

.ahp-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

/* Empty State */
.ahp-empty-state {
    text-align: center;
    padding: 80px 20px;
    background: var(--ahp-bg-light);
    border-radius: 20px;
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
    margin: 0;
    color: var(--ahp-text-muted);
    font-size: 16px;
}

/* Responsive */
@media (max-width: 768px) {
    .ahp-available-students { padding: 16px; margin: 20px auto; }
    .ahp-page-header h1 { font-size: 28px; }
    .ahp-students-grid { grid-template-columns: 1fr; }
    .ahp-fee-plans { grid-template-columns: 1fr; }
    .ahp-card-footer { flex-direction: column; }
}
</style>

<div class="ahp-available-students">
    <div class="ahp-page-header">
        <h1><i class="fas fa-hand-holding-heart"></i> <?php _e('Students Awaiting Sponsorship', 'al-huffaz-portal'); ?></h1>
        <p><?php _e('These students are eligible for sponsorship. Your support can change their lives through quality Islamic education.', 'al-huffaz-portal'); ?></p>
    </div>

    <div class="ahp-stats-bar">
        <div class="stat">
            <div class="stat-value"><?php echo count($students); ?></div>
            <div class="stat-label"><?php _e('Students Need Support', 'al-huffaz-portal'); ?></div>
        </div>
    </div>

    <?php if (empty($students)): ?>
        <div class="ahp-empty-state">
            <i class="fas fa-heart"></i>
            <h3><?php _e('All Students Are Sponsored!', 'al-huffaz-portal'); ?></h3>
            <p><?php _e('Thanks to generous donors like you, all our students currently have sponsors.', 'al-huffaz-portal'); ?></p>
        </div>
    <?php else: ?>
        <div class="ahp-students-grid">
            <?php foreach ($students as $student):
                $photo_id = get_post_meta($student->ID, 'student_photo', true);
                $photo_url = $photo_id ? wp_get_attachment_image_url($photo_id, 'medium') : '';
                $grade = get_post_meta($student->ID, 'grade_level', true);
                $category = get_post_meta($student->ID, 'islamic_studies_category', true);

                // Fee calculation
                $monthly_fee = floatval(get_post_meta($student->ID, 'monthly_tuition_fee', true)) ?: 0;
                $course_fee = floatval(get_post_meta($student->ID, 'course_fee', true)) ?: 0;
                $uniform_fee = floatval(get_post_meta($student->ID, 'uniform_fee', true)) ?: 0;
                $annual_fee = floatval(get_post_meta($student->ID, 'annual_fee', true)) ?: 0;
                $admission_fee = floatval(get_post_meta($student->ID, 'admission_fee', true)) ?: 0;

                $one_time_total = $course_fee + $uniform_fee + $annual_fee + $admission_fee;

                // Calculate plans
                $monthly_amount = $monthly_fee;
                $quarterly_amount = $monthly_fee * 3;
                $yearly_amount = ($monthly_fee * 12) + $one_time_total;
            ?>
            <div class="ahp-student-card">
                <div class="ahp-card-header">
                    <?php if ($photo_url): ?>
                        <img src="<?php echo esc_url($photo_url); ?>" alt="<?php echo esc_attr($student->post_title); ?>" class="ahp-student-photo">
                    <?php else: ?>
                        <div class="ahp-photo-placeholder">
                            <?php echo esc_html(strtoupper(substr($student->post_title, 0, 1))); ?>
                        </div>
                    <?php endif; ?>
                    <div class="ahp-student-info">
                        <h3><?php echo esc_html($student->post_title); ?></h3>
                        <div class="ahp-badges">
                            <?php if ($grade): ?>
                            <span class="ahp-badge ahp-badge-grade"><?php echo esc_html(Helpers::get_grade_label($grade)); ?></span>
                            <?php endif; ?>
                            <?php if ($category): ?>
                            <span class="ahp-badge ahp-badge-category"><?php echo esc_html(Helpers::get_islamic_category_label($category)); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="ahp-fee-calculator">
                    <h4 class="ahp-fee-title"><i class="fas fa-calculator"></i> <?php _e('Sponsorship Plans', 'al-huffaz-portal'); ?></h4>

                    <div class="ahp-fee-plans" data-student-id="<?php echo $student->ID; ?>">
                        <div class="ahp-fee-plan" data-plan="monthly" data-amount="<?php echo $monthly_amount; ?>">
                            <div class="ahp-plan-label"><?php _e('Monthly', 'al-huffaz-portal'); ?></div>
                            <div class="ahp-plan-amount"><?php echo $currency; ?> <?php echo number_format($monthly_amount); ?></div>
                            <div class="ahp-plan-period"><?php _e('/month', 'al-huffaz-portal'); ?></div>
                        </div>

                        <div class="ahp-fee-plan" data-plan="quarterly" data-amount="<?php echo $quarterly_amount; ?>">
                            <div class="ahp-plan-label"><?php _e('Quarterly', 'al-huffaz-portal'); ?></div>
                            <div class="ahp-plan-amount"><?php echo $currency; ?> <?php echo number_format($quarterly_amount); ?></div>
                            <div class="ahp-plan-period"><?php _e('/3 months', 'al-huffaz-portal'); ?></div>
                        </div>

                        <div class="ahp-fee-plan selected" data-plan="yearly" data-amount="<?php echo $yearly_amount; ?>">
                            <div class="ahp-plan-label"><?php _e('Yearly', 'al-huffaz-portal'); ?></div>
                            <div class="ahp-plan-amount"><?php echo $currency; ?> <?php echo number_format($yearly_amount); ?></div>
                            <div class="ahp-plan-period"><?php _e('/year + one-time', 'al-huffaz-portal'); ?></div>
                        </div>
                    </div>

                    <?php if ($one_time_total > 0): ?>
                    <div class="ahp-one-time-info">
                        <i class="fas fa-info-circle"></i>
                        <?php printf(__('Yearly plan includes one-time fees: Course (%s), Uniform (%s), Annual (%s), Admission (%s)', 'al-huffaz-portal'),
                            $currency . ' ' . number_format($course_fee),
                            $currency . ' ' . number_format($uniform_fee),
                            $currency . ' ' . number_format($annual_fee),
                            $currency . ' ' . number_format($admission_fee)
                        ); ?>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="ahp-card-footer">
                    <a href="<?php echo get_permalink($student->ID); ?>" class="ahp-btn ahp-btn-secondary" target="_blank">
                        <i class="fas fa-eye"></i> <?php _e('View Profile', 'al-huffaz-portal'); ?>
                    </a>
                    <a href="<?php echo home_url('/sponsor-a-student/?student=' . $student->ID); ?>" class="ahp-btn ahp-btn-primary sponsor-btn" data-student="<?php echo $student->ID; ?>">
                        <i class="fas fa-heart"></i> <?php _e('Sponsor Now', 'al-huffaz-portal'); ?>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle plan selection
    document.querySelectorAll('.ahp-fee-plan').forEach(plan => {
        plan.addEventListener('click', function() {
            const container = this.closest('.ahp-fee-plans');
            container.querySelectorAll('.ahp-fee-plan').forEach(p => p.classList.remove('selected'));
            this.classList.add('selected');

            // Update the sponsor link with selected plan
            const card = this.closest('.ahp-student-card');
            const sponsorBtn = card.querySelector('.sponsor-btn');
            const studentId = this.closest('.ahp-fee-plans').dataset.studentId;
            const planType = this.dataset.plan;
            const amount = this.dataset.amount;

            sponsorBtn.href = `<?php echo home_url('/sponsor-a-student/'); ?>?student=${studentId}&plan=${planType}&amount=${amount}`;
        });
    });
});
</script>
