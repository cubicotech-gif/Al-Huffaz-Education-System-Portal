<?php 
use AlHuffaz\Admin\Settings;
if (!defined('ABSPATH')) exit;
$settings = Settings::get_all();
?>
<div class="alhuffaz-wrap">
    <div class="alhuffaz-header"><h1><span class="dashicons dashicons-admin-generic"></span> <?php _e('Settings', 'al-huffaz-portal'); ?></h1></div>
    <form id="alhuffaz-settings-form" class="alhuffaz-card">
        <div class="alhuffaz-form-section"><h3 class="alhuffaz-form-section-title"><span class="dashicons dashicons-admin-home"></span> <?php _e('School Information', 'al-huffaz-portal'); ?></h3>
            <div class="alhuffaz-form-row">
                <div class="alhuffaz-form-group"><label class="alhuffaz-form-label"><?php _e('School Name', 'al-huffaz-portal'); ?></label><input type="text" name="alhuffaz_school_name" class="alhuffaz-form-input" value="<?php echo esc_attr($settings['school_name']); ?>"></div>
                <div class="alhuffaz-form-group"><label class="alhuffaz-form-label"><?php _e('Admin Email', 'al-huffaz-portal'); ?></label><input type="email" name="alhuffaz_admin_email" class="alhuffaz-form-input" value="<?php echo esc_attr($settings['admin_email']); ?>"></div>
            </div>
        </div>
        <div class="alhuffaz-form-section"><h3 class="alhuffaz-form-section-title"><span class="dashicons dashicons-money-alt"></span> <?php _e('Currency Settings', 'al-huffaz-portal'); ?></h3>
            <div class="alhuffaz-form-row">
                <div class="alhuffaz-form-group"><label class="alhuffaz-form-label"><?php _e('Currency Code', 'al-huffaz-portal'); ?></label><input type="text" name="alhuffaz_currency" class="alhuffaz-form-input" value="<?php echo esc_attr($settings['currency']); ?>"></div>
                <div class="alhuffaz-form-group"><label class="alhuffaz-form-label"><?php _e('Currency Symbol', 'al-huffaz-portal'); ?></label><input type="text" name="alhuffaz_currency_symbol" class="alhuffaz-form-input" value="<?php echo esc_attr($settings['currency_symbol']); ?>"></div>
            </div>
        </div>
        <div class="alhuffaz-form-section" style="text-align: right;"><button type="submit" class="alhuffaz-btn alhuffaz-btn-lg alhuffaz-btn-primary"><span class="dashicons dashicons-saved"></span> <?php _e('Save Settings', 'al-huffaz-portal'); ?></button></div>
    </form>
</div>
