<?php if (!defined('ABSPATH')) exit; ?>
<div class="alhuffaz-wrap">
    <div class="alhuffaz-header"><h1><span class="dashicons dashicons-chart-bar"></span> <?php _e('Reports', 'al-huffaz-portal'); ?></h1></div>
    <div class="alhuffaz-card">
        <h3><?php _e('Generate Report', 'al-huffaz-portal'); ?></h3>
        <div class="alhuffaz-form-row">
            <div class="alhuffaz-form-group"><label class="alhuffaz-form-label"><?php _e('Report Type', 'al-huffaz-portal'); ?></label><select name="report_type" class="alhuffaz-form-select"><option value="students"><?php _e('Students Report', 'al-huffaz-portal'); ?></option><option value="sponsorships"><?php _e('Sponsorships Report', 'al-huffaz-portal'); ?></option><option value="payments"><?php _e('Payments Report', 'al-huffaz-portal'); ?></option><option value="financial"><?php _e('Financial Report', 'al-huffaz-portal'); ?></option></select></div>
            <div class="alhuffaz-form-group"><label class="alhuffaz-form-label"><?php _e('Date From', 'al-huffaz-portal'); ?></label><input type="text" name="date_from" class="alhuffaz-form-input alhuffaz-datepicker"></div>
            <div class="alhuffaz-form-group"><label class="alhuffaz-form-label"><?php _e('Date To', 'al-huffaz-portal'); ?></label><input type="text" name="date_to" class="alhuffaz-form-input alhuffaz-datepicker"></div>
        </div>
        <button class="alhuffaz-btn alhuffaz-btn-primary"><span class="dashicons dashicons-media-spreadsheet"></span> <?php _e('Generate Report', 'al-huffaz-portal'); ?></button>
    </div>
</div>
