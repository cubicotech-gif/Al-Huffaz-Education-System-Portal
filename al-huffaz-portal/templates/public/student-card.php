<?php
use AlHuffaz\Frontend\Student_Display;
if (!defined('ABSPATH')) exit;
$s = Student_Display::format_student_for_display($student);
if (!$s) return;
?>
<div class="alhuffaz-student-card">
    <div class="alhuffaz-student-card-image"><img src="<?php echo esc_url($s['photo']); ?>" alt="<?php echo esc_attr($s['name']); ?>"><span class="alhuffaz-student-card-badge <?php echo $s['is_sponsored'] ? 'sponsored' : 'available'; ?>"><?php echo $s['is_sponsored'] ? 'Sponsored' : 'Available'; ?></span></div>
    <div class="alhuffaz-student-card-body">
        <h3 class="alhuffaz-student-card-name"><?php echo esc_html($s['name']); ?></h3>
        <div class="alhuffaz-student-card-meta"><span><?php echo esc_html($s['grade']); ?></span><span><?php echo esc_html($s['category']); ?></span></div>
        <p class="alhuffaz-student-card-description"><?php echo esc_html($s['description']); ?></p>
        <div class="alhuffaz-student-card-footer">
            <div class="alhuffaz-student-card-amount"><?php echo esc_html($s['monthly_fee']); ?><span>/month</span></div>
            <?php if (!$s['is_sponsored']): ?><a href="?sponsor=<?php echo $s['id']; ?>" class="alhuffaz-btn alhuffaz-btn-primary"><?php _e('Sponsor', 'al-huffaz-portal'); ?></a><?php endif; ?>
        </div>
    </div>
</div>
