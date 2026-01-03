<?php
use AlHuffaz\Frontend\Payment_Form;
use AlHuffaz\Core\Helpers;
if (!defined('ABSPATH')) exit;
$payment_methods = Payment_Form::get_payment_methods();
?>
<div class="alhuffaz-container">
    <form id="alhuffaz-payment-form" class="alhuffaz-sponsorship-form" enctype="multipart/form-data">
        <h2 class="alhuffaz-form-title"><?php _e('Make a Payment', 'al-huffaz-portal'); ?></h2>
        <p class="alhuffaz-form-subtitle"><?php _e('Submit your payment for an existing sponsorship.', 'al-huffaz-portal'); ?></p>
        <div class="alhuffaz-form-group"><label class="alhuffaz-form-label"><?php _e('Sponsorship', 'al-huffaz-portal'); ?> <span class="required">*</span></label><select name="sponsorship_id" class="alhuffaz-form-select" required><option value=""><?php _e('Loading...', 'al-huffaz-portal'); ?></option></select></div>
        <div class="alhuffaz-form-row">
            <div class="alhuffaz-form-group"><label class="alhuffaz-form-label"><?php _e('Amount', 'al-huffaz-portal'); ?> <span class="required">*</span></label><input type="number" name="amount" class="alhuffaz-form-input" required></div>
            <div class="alhuffaz-form-group"><label class="alhuffaz-form-label"><?php _e('Payment Method', 'al-huffaz-portal'); ?> <span class="required">*</span></label><select name="payment_method" class="alhuffaz-form-select" required><?php foreach ($payment_methods as $key => $label): ?><option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option><?php endforeach; ?></select></div>
        </div>
        <div class="alhuffaz-form-group"><label class="alhuffaz-form-label"><?php _e('Transaction ID', 'al-huffaz-portal'); ?></label><input type="text" name="transaction_id" class="alhuffaz-form-input"></div>
        <div class="alhuffaz-form-group"><label class="alhuffaz-form-label"><?php _e('Payment Screenshot', 'al-huffaz-portal'); ?></label><div class="alhuffaz-file-upload"><input type="file" name="payment_screenshot" accept="image/*"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg><p><?php _e('Upload payment proof', 'al-huffaz-portal'); ?></p></div></div>
        <button type="submit" class="alhuffaz-btn alhuffaz-btn-lg alhuffaz-btn-primary" style="width: 100%;"><?php _e('Submit Payment', 'al-huffaz-portal'); ?></button>
    </form>
</div>
