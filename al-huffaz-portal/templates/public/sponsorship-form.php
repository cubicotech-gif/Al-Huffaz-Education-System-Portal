<?php
use AlHuffaz\Frontend\Student_Display;
use AlHuffaz\Core\Helpers;
if (!defined('ABSPATH')) exit;

// Accept student from URL (sponsor or student param)
$selected_student_id = isset($_GET['student']) ? intval($_GET['student']) : (isset($_GET['sponsor']) ? intval($_GET['sponsor']) : ($atts['student_id'] ?? 0));
$selected_student = $selected_student_id ? Student_Display::format_student_for_display($selected_student_id) : null;
$available_students = Student_Display::get_available_students(20);
$payment_methods = Helpers::get_payment_methods();
$countries = Helpers::get_countries();

// Get pre-selected plan and amount from URL
$selected_plan = isset($_GET['plan']) ? sanitize_text_field($_GET['plan']) : 'monthly';
$selected_amount = isset($_GET['amount']) ? floatval($_GET['amount']) : ($selected_student['monthly_fee'] ?? '');
?>
<div class="alhuffaz-container">
    <form id="alhuffaz-sponsorship-form" class="alhuffaz-sponsorship-form" enctype="multipart/form-data">
        <h2 class="alhuffaz-form-title"><?php _e('Sponsor a Student', 'al-huffaz-portal'); ?></h2>
        <p class="alhuffaz-form-subtitle"><?php _e('Fill in the form below to sponsor a student\'s education.', 'al-huffaz-portal'); ?></p>

        <input type="hidden" name="student_id" value="<?php echo esc_attr($selected_student_id); ?>">

        <?php if ($selected_student): ?>
        <div class="alhuffaz-selected-student">
            <img src="<?php echo esc_url($selected_student['photo']); ?>" alt="">
            <div class="alhuffaz-selected-student-info">
                <h4><?php echo esc_html($selected_student['name']); ?></h4>
                <p><?php echo esc_html($selected_student['description']); ?></p>
            </div>
        </div>
        <?php else: ?>
        <div class="alhuffaz-form-group">
            <label class="alhuffaz-form-label"><?php _e('Select a Student', 'al-huffaz-portal'); ?> <span class="required">*</span></label>
            <div class="alhuffaz-student-select-grid">
                <?php foreach ($available_students['students'] as $s): ?>
                <div class="alhuffaz-student-select-card" data-student-id="<?php echo $s['id']; ?>">
                    <img src="<?php echo esc_url($s['photo']); ?>" alt="">
                    <h4><?php echo esc_html($s['name']); ?></h4>
                    <p><?php echo esc_html($s['grade']); ?> - <?php echo esc_html($s['category']); ?></p>
                    <div class="check-icon"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="alhuffaz-form-row">
            <div class="alhuffaz-form-group"><label class="alhuffaz-form-label"><?php _e('Your Name', 'al-huffaz-portal'); ?> <span class="required">*</span></label><input type="text" name="sponsor_name" class="alhuffaz-form-input" required></div>
            <div class="alhuffaz-form-group"><label class="alhuffaz-form-label"><?php _e('Email', 'al-huffaz-portal'); ?> <span class="required">*</span></label><input type="email" name="sponsor_email" class="alhuffaz-form-input" required></div>
        </div>
        <div class="alhuffaz-form-row">
            <div class="alhuffaz-form-group"><label class="alhuffaz-form-label"><?php _e('Phone', 'al-huffaz-portal'); ?> <span class="required">*</span></label><input type="tel" name="sponsor_phone" class="alhuffaz-form-input" required></div>
            <div class="alhuffaz-form-group"><label class="alhuffaz-form-label"><?php _e('Country', 'al-huffaz-portal'); ?></label><select name="sponsor_country" class="alhuffaz-form-select"><?php foreach ($countries as $code => $name): ?><option value="<?php echo esc_attr($code); ?>" <?php selected($code, 'PK'); ?>><?php echo esc_html($name); ?></option><?php endforeach; ?></select></div>
        </div>
        <div class="alhuffaz-form-row">
            <div class="alhuffaz-form-group"><label class="alhuffaz-form-label"><?php _e('Amount', 'al-huffaz-portal'); ?> <span class="required">*</span></label><input type="number" name="amount" class="alhuffaz-form-input" required value="<?php echo esc_attr($selected_amount); ?>"></div>
            <div class="alhuffaz-form-group"><label class="alhuffaz-form-label"><?php _e('Sponsorship Type', 'al-huffaz-portal'); ?></label><select name="sponsorship_type" class="alhuffaz-form-select"><option value="monthly" <?php selected($selected_plan, 'monthly'); ?>><?php _e('Monthly', 'al-huffaz-portal'); ?></option><option value="quarterly" <?php selected($selected_plan, 'quarterly'); ?>><?php _e('Quarterly', 'al-huffaz-portal'); ?></option><option value="yearly" <?php selected($selected_plan, 'yearly'); ?>><?php _e('Yearly', 'al-huffaz-portal'); ?></option></select></div>
        </div>
        <div class="alhuffaz-form-group"><label class="alhuffaz-form-label"><?php _e('Payment Method', 'al-huffaz-portal'); ?></label><select name="payment_method" class="alhuffaz-form-select"><?php foreach ($payment_methods as $key => $label): ?><option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option><?php endforeach; ?></select></div>
        <div class="alhuffaz-form-group"><label class="alhuffaz-form-label"><?php _e('Transaction ID (if paid)', 'al-huffaz-portal'); ?></label><input type="text" name="transaction_id" class="alhuffaz-form-input"></div>
        <div class="alhuffaz-form-group"><label class="alhuffaz-form-label"><?php _e('Payment Screenshot', 'al-huffaz-portal'); ?></label><div class="alhuffaz-file-upload"><input type="file" name="payment_screenshot" accept="image/*"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg><p class="alhuffaz-file-upload-text"><?php _e('Upload payment screenshot', 'al-huffaz-portal'); ?></p></div></div>
        <div class="alhuffaz-form-group"><label class="alhuffaz-form-label"><?php _e('Notes (Optional)', 'al-huffaz-portal'); ?></label><textarea name="notes" class="alhuffaz-form-textarea" rows="3"></textarea></div>
        <button type="submit" class="alhuffaz-btn alhuffaz-btn-lg alhuffaz-btn-primary" style="width: 100%;"><?php _e('Submit Sponsorship', 'al-huffaz-portal'); ?></button>
    </form>
</div>
