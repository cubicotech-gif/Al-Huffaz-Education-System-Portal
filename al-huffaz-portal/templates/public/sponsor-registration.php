<?php
/**
 * Sponsor Registration Page Template
 *
 * Public registration for new sponsors
 * Account created with pending_approval status
 * Admin must approve before sponsor can login
 *
 * Usage: Create a WordPress page and use shortcode [alhuffaz_sponsor_registration]
 *
 * @package AlHuffaz
 */

use AlHuffaz\Core\Helpers;

defined('ABSPATH') || exit;

// If already logged in, redirect to dashboard
if (is_user_logged_in()) {
    $user = wp_get_current_user();
    if (in_array('alhuffaz_sponsor', $user->roles)) {
        wp_redirect(Helpers::get_sponsor_dashboard_url());
        exit;
    }
    if (in_array('alhuffaz_admin', $user->roles) || in_array('administrator', $user->roles)) {
        wp_redirect(Helpers::get_admin_portal_url());
        exit;
    }
}

$countries = Helpers::get_countries();
?>

<div class="alhuffaz-register-page">
    <div class="alhuffaz-register-container">
        <div class="alhuffaz-register-header">
            <img src="https://portal.alhuffazeducationsystem.com/wp-content/uploads/2026/01/cropped-AlHuffaz-Logo-1.png" alt="<?php bloginfo('name'); ?>" class="alhuffaz-register-logo">
            <h1><?php _e('Become a Sponsor', 'al-huffaz-portal'); ?></h1>
            <p><?php _e('Create an account to sponsor students and make a difference', 'al-huffaz-portal'); ?></p>
        </div>

        <div id="alhuffaz-register-messages"></div>

        <form id="alhuffaz-sponsor-registration-form" method="post">
            <?php wp_nonce_field('alhuffaz_sponsor_registration', 'sponsor_register_nonce'); ?>

            <div class="alhuffaz-form-row">
                <div class="alhuffaz-form-group">
                    <label for="sponsor_name">
                        <?php _e('Full Name', 'al-huffaz-portal'); ?> <span class="required">*</span>
                    </label>
                    <input type="text" id="sponsor_name" name="sponsor_name" required>
                </div>
            </div>

            <div class="alhuffaz-form-row">
                <div class="alhuffaz-form-group">
                    <label for="sponsor_email">
                        <?php _e('Email Address', 'al-huffaz-portal'); ?> <span class="required">*</span>
                    </label>
                    <input type="email" id="sponsor_email" name="sponsor_email" required>
                    <small><?php _e('This will be your username for login', 'al-huffaz-portal'); ?></small>
                </div>
            </div>

            <div class="alhuffaz-form-row">
                <div class="alhuffaz-form-group">
                    <label for="sponsor_password">
                        <?php _e('Password', 'al-huffaz-portal'); ?> <span class="required">*</span>
                    </label>
                    <input type="password" id="sponsor_password" name="sponsor_password" required minlength="8">
                    <small><?php _e('Minimum 8 characters', 'al-huffaz-portal'); ?></small>
                </div>
            </div>

            <div class="alhuffaz-form-row">
                <div class="alhuffaz-form-group">
                    <label for="sponsor_phone">
                        <?php _e('Phone Number', 'al-huffaz-portal'); ?> <span class="required">*</span>
                    </label>
                    <input type="tel" id="sponsor_phone" name="sponsor_phone" required>
                </div>
            </div>

            <div class="alhuffaz-form-row">
                <div class="alhuffaz-form-group">
                    <label for="sponsor_country">
                        <?php _e('Country', 'al-huffaz-portal'); ?> <span class="required">*</span>
                    </label>
                    <select id="sponsor_country" name="sponsor_country" required>
                        <option value=""><?php _e('Select Country', 'al-huffaz-portal'); ?></option>
                        <?php foreach ($countries as $code => $name): ?>
                            <option value="<?php echo esc_attr($code); ?>"><?php echo esc_html($name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="alhuffaz-form-row">
                <div class="alhuffaz-form-group">
                    <label for="sponsor_whatsapp">
                        <?php _e('WhatsApp Number', 'al-huffaz-portal'); ?> <span class="optional">(<?php _e('Optional', 'al-huffaz-portal'); ?>)</span>
                    </label>
                    <input type="tel" id="sponsor_whatsapp" name="sponsor_whatsapp">
                    <small><?php _e('For easy communication', 'al-huffaz-portal'); ?></small>
                </div>
            </div>

            <div class="alhuffaz-form-row">
                <div class="alhuffaz-form-group checkbox">
                    <label>
                        <input type="checkbox" name="agree_terms" required>
                        <span>
                            <?php _e('I agree to the', 'al-huffaz-portal'); ?>
                            <a href="<?php echo home_url('/terms/'); ?>" target="_blank"><?php _e('Terms & Conditions', 'al-huffaz-portal'); ?></a>
                            <?php _e('and', 'al-huffaz-portal'); ?>
                            <a href="<?php echo home_url('/privacy/'); ?>" target="_blank"><?php _e('Privacy Policy', 'al-huffaz-portal'); ?></a>
                        </span>
                    </label>
                </div>
            </div>

            <button type="submit" class="alhuffaz-btn-submit">
                <i class="fas fa-user-plus"></i> <?php _e('Register Account', 'al-huffaz-portal'); ?>
            </button>
        </form>

        <div class="alhuffaz-register-footer">
            <p>
                <?php _e('Already have an account?', 'al-huffaz-portal'); ?>
                <a href="<?php echo esc_url(Helpers::get_login_url()); ?>"><?php _e('Login here', 'al-huffaz-portal'); ?></a>
            </p>
        </div>

        <div class="alhuffaz-register-info">
            <h3><i class="fas fa-info-circle"></i> <?php _e('What happens next?', 'al-huffaz-portal'); ?></h3>
            <ol>
                <li><?php _e('Submit this registration form', 'al-huffaz-portal'); ?></li>
                <li><?php _e('Our team will review your account (usually within 24 hours)', 'al-huffaz-portal'); ?></li>
                <li><?php _e('You will receive an email notification once approved', 'al-huffaz-portal'); ?></li>
                <li><?php _e('Login and start browsing students available for sponsorship', 'al-huffaz-portal'); ?></li>
            </ol>
        </div>
    </div>
</div>

<style>
.alhuffaz-register-page {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 100vh;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    padding: 40px 20px;
}

.alhuffaz-register-container {
    background: white;
    padding: 40px;
    border-radius: 16px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    max-width: 600px;
    width: 100%;
    animation: slideUp 0.4s ease-out;
}

@keyframes slideUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.alhuffaz-register-header {
    text-align: center;
    margin-bottom: 30px;
}

.alhuffaz-register-logo {
    width: 80px;
    height: 80px;
    margin-bottom: 20px;
    object-fit: contain;
}

.alhuffaz-register-header h1 {
    margin: 0 0 10px 0;
    font-size: 28px;
    color: #1d1d1f;
}

.alhuffaz-register-header p {
    margin: 0;
    color: #666;
    font-size: 14px;
}

#alhuffaz-register-messages {
    margin-bottom: 20px;
}

#alhuffaz-register-messages .message {
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 14px;
}

#alhuffaz-register-messages .message.success {
    background: #d1fae5;
    color: #065f46;
    border: 1px solid #6ee7b7;
}

#alhuffaz-register-messages .message.error {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fca5a5;
}

.alhuffaz-form-row {
    margin-bottom: 20px;
}

.alhuffaz-form-group label {
    display: block;
    margin-bottom: 6px;
    font-weight: 500;
    color: #374151;
    font-size: 14px;
}

.alhuffaz-form-group .required {
    color: #ef4444;
}

.alhuffaz-form-group .optional {
    color: #9ca3af;
    font-weight: normal;
    font-size: 12px;
}

.alhuffaz-form-group input[type="text"],
.alhuffaz-form-group input[type="email"],
.alhuffaz-form-group input[type="password"],
.alhuffaz-form-group input[type="tel"],
.alhuffaz-form-group select {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    font-size: 16px;
    transition: all 0.3s;
    box-sizing: border-box;
}

.alhuffaz-form-group input:focus,
.alhuffaz-form-group select:focus {
    outline: none;
    border-color: #10b981;
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
}

.alhuffaz-form-group small {
    display: block;
    margin-top: 4px;
    font-size: 12px;
    color: #6b7280;
}

.alhuffaz-form-group.checkbox {
    margin-top: 20px;
}

.alhuffaz-form-group.checkbox label {
    display: flex;
    align-items: start;
    gap: 10px;
    font-weight: normal;
    cursor: pointer;
}

.alhuffaz-form-group.checkbox input[type="checkbox"] {
    margin-top: 3px;
    width: auto;
}

.alhuffaz-form-group.checkbox a {
    color: #10b981;
    text-decoration: underline;
}

.alhuffaz-btn-submit {
    width: 100%;
    padding: 14px;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin-top: 20px;
}

.alhuffaz-btn-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
}

.alhuffaz-btn-submit:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

.alhuffaz-register-footer {
    text-align: center;
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #e5e7eb;
}

.alhuffaz-register-footer p {
    margin: 0;
    color: #6b7280;
    font-size: 14px;
}

.alhuffaz-register-footer a {
    color: #10b981;
    font-weight: 600;
    text-decoration: none;
}

.alhuffaz-register-footer a:hover {
    text-decoration: underline;
}

.alhuffaz-register-info {
    margin-top: 20px;
    padding: 20px;
    background: #f0fdf4;
    border-radius: 8px;
    border: 1px solid #bbf7d0;
}

.alhuffaz-register-info h3 {
    margin: 0 0 12px 0;
    color: #166534;
    font-size: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.alhuffaz-register-info ol {
    margin: 0;
    padding-left: 20px;
}

.alhuffaz-register-info ol li {
    margin-bottom: 8px;
    color: #374151;
    font-size: 14px;
}

/* Responsive */
@media (max-width: 640px) {
    .alhuffaz-register-container {
        padding: 30px 20px;
    }

    .alhuffaz-register-header h1 {
        font-size: 24px;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    $('#alhuffaz-sponsor-registration-form').on('submit', function(e) {
        e.preventDefault();

        const $form = $(this);
        const $btn = $form.find('button[type="submit"]');
        const $messages = $('#alhuffaz-register-messages');

        // Disable button
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> <?php _e('Creating Account...', 'al-huffaz-portal'); ?>');

        // Clear previous messages
        $messages.html('');

        const formData = new FormData(this);
        formData.append('action', 'alhuffaz_register_sponsor');

        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    // Success - redirect to login with success message
                    window.location.href = '<?php echo esc_url(add_query_arg('registered', 'success', Helpers::get_login_url())); ?>';
                } else {
                    // Error - show message
                    $messages.html('<div class="message error"><i class="fas fa-exclamation-circle"></i> <span>' + response.data.message + '</span></div>');
                    $btn.prop('disabled', false).html('<i class="fas fa-user-plus"></i> <?php _e('Register Account', 'al-huffaz-portal'); ?>');

                    // Scroll to message
                    $('html, body').animate({
                        scrollTop: $messages.offset().top - 100
                    }, 300);
                }
            },
            error: function() {
                $messages.html('<div class="message error"><i class="fas fa-exclamation-circle"></i> <span><?php _e('An error occurred. Please try again.', 'al-huffaz-portal'); ?></span></div>');
                $btn.prop('disabled', false).html('<i class="fas fa-user-plus"></i> <?php _e('Register Account', 'al-huffaz-portal'); ?>');
            }
        });
    });
});
</script>
