<?php if (!defined('ABSPATH')) exit; ?>
<div class="alhuffaz-wrap">
    <div class="alhuffaz-header"><h1><span class="dashicons dashicons-upload"></span> <?php _e('Bulk Import', 'al-huffaz-portal'); ?></h1></div>
    <div class="alhuffaz-card">
        <h3><?php _e('Import Students from CSV', 'al-huffaz-portal'); ?></h3>
        <p><?php _e('Upload a CSV file to import multiple students at once.', 'al-huffaz-portal'); ?></p>
        <div class="alhuffaz-file-upload" style="margin: 20px 0;">
            <input type="file" name="import_file" accept=".csv">
            <span class="dashicons dashicons-upload" style="font-size: 48px; color: #9ca3af;"></span>
            <p><?php _e('Drop CSV file here or click to upload', 'al-huffaz-portal'); ?></p>
            <small><?php _e('Maximum file size: 5MB', 'al-huffaz-portal'); ?></small>
        </div>
        <button class="alhuffaz-btn alhuffaz-btn-primary" id="alhuffaz-import-btn"><span class="dashicons dashicons-upload"></span> <?php _e('Start Import', 'al-huffaz-portal'); ?></button>
        <a href="#" class="alhuffaz-btn alhuffaz-btn-secondary"><?php _e('Download Template', 'al-huffaz-portal'); ?></a>
    </div>
</div>
