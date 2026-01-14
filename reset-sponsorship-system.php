<?php
/**
 * COMPLETE SPONSORSHIP SYSTEM RESET TOOL
 *
 * WARNING: This will DELETE ALL sponsor data permanently!
 *
 * What this script does:
 * 1. Deletes all sponsor users (alhuffaz_sponsor role)
 * 2. Deletes all sponsorship CPTs
 * 3. Deletes all sponsor CPTs
 * 4. Frees all students (removes all sponsorship meta)
 * 5. Cleans payment database table
 * 6. Resets cleanup options
 *
 * HOW TO USE:
 * 1. Upload this file to your WordPress root directory
 * 2. Visit: https://yoursite.com/reset-sponsorship-system.php?confirm=yes
 * 3. Delete this file after use for security
 */

// Load WordPress
require_once('wp-load.php');

// Security check - must be administrator
if (!current_user_can('administrator')) {
    wp_die('Access denied. You must be an administrator to run this tool.');
}

// Require confirmation parameter
if (!isset($_GET['confirm']) || $_GET['confirm'] !== 'yes') {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Reset Sponsorship System</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
            .warning { background: #fee; border: 2px solid #f00; padding: 20px; margin: 20px 0; border-radius: 8px; }
            .warning h2 { color: #c00; margin-top: 0; }
            .actions { margin: 30px 0; }
            .btn { display: inline-block; padding: 12px 24px; margin: 10px; text-decoration: none; border-radius: 6px; font-weight: bold; }
            .btn-danger { background: #dc3545; color: white; }
            .btn-secondary { background: #6c757d; color: white; }
            ul { line-height: 2; }
        </style>
    </head>
    <body>
        <h1>⚠️ Reset Sponsorship System</h1>

        <div class="warning">
            <h2>⚠️ DANGER: This action is IRREVERSIBLE!</h2>
            <p><strong>This will permanently delete:</strong></p>
            <ul>
                <li>All sponsor user accounts</li>
                <li>All sponsorship records</li>
                <li>All sponsor CPT posts</li>
                <li>All student sponsorship metadata</li>
                <li>All payment records in database table</li>
            </ul>
            <p><strong>This CANNOT be undone!</strong></p>
        </div>

        <div class="actions">
            <a href="?confirm=yes" class="btn btn-danger" onclick="return confirm('Are you ABSOLUTELY SURE? This will delete ALL sponsor data permanently!');">
                🗑️ YES, DELETE EVERYTHING
            </a>
            <a href="<?php echo admin_url(); ?>" class="btn btn-secondary">
                ← Cancel and Go Back
            </a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Start reset process
?>
<!DOCTYPE html>
<html>
<head>
    <title>Resetting Sponsorship System...</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .log { background: #f5f5f5; padding: 20px; border-radius: 8px; font-family: monospace; }
        .success { color: #28a745; }
        .info { color: #17a2b8; }
        .warning { color: #ffc107; }
        h1 { color: #333; }
        .complete { background: #d4edda; border: 2px solid #28a745; padding: 20px; margin: 20px 0; border-radius: 8px; }
    </style>
</head>
<body>
    <h1>🔄 Resetting Sponsorship System...</h1>
    <div class="log">
<?php

global $wpdb;

echo "<p class='info'>Starting reset process...</p>\n";
flush();

// STEP 1: Delete all sponsor users
echo "<p class='info'><strong>Step 1:</strong> Deleting sponsor users...</p>\n";
flush();

$sponsor_users = get_users(array('role' => 'alhuffaz_sponsor'));
$sponsor_count = 0;

foreach ($sponsor_users as $user) {
    require_once(ABSPATH . 'wp-admin/includes/user.php');
    $result = wp_delete_user($user->ID);
    if ($result) {
        $sponsor_count++;
        echo "<p class='success'>✓ Deleted sponsor user: {$user->display_name} ({$user->user_email})</p>\n";
    } else {
        echo "<p class='warning'>⚠ Failed to delete user: {$user->display_name}</p>\n";
    }
    flush();
}

echo "<p class='success'><strong>✓ Deleted {$sponsor_count} sponsor users</strong></p>\n";
flush();

// STEP 2: Delete all sponsorship CPTs
echo "<p class='info'><strong>Step 2:</strong> Deleting sponsorship CPTs...</p>\n";
flush();

$sponsorships = get_posts(array(
    'post_type' => 'sponsorship',
    'post_status' => 'any',
    'posts_per_page' => -1,
    'fields' => 'ids'
));

$sponsorship_count = 0;
foreach ($sponsorships as $sponsorship_id) {
    wp_delete_post($sponsorship_id, true); // Force delete (bypass trash)
    $sponsorship_count++;
}

echo "<p class='success'><strong>✓ Deleted {$sponsorship_count} sponsorship records</strong></p>\n";
flush();

// STEP 3: Delete all sponsor CPTs
echo "<p class='info'><strong>Step 3:</strong> Deleting sponsor CPTs...</p>\n";
flush();

$sponsor_cpts = get_posts(array(
    'post_type' => 'sponsor',
    'post_status' => 'any',
    'posts_per_page' => -1,
    'fields' => 'ids'
));

$sponsor_cpt_count = 0;
foreach ($sponsor_cpts as $sponsor_cpt_id) {
    wp_delete_post($sponsor_cpt_id, true); // Force delete
    $sponsor_cpt_count++;
}

echo "<p class='success'><strong>✓ Deleted {$sponsor_cpt_count} sponsor CPT records</strong></p>\n";
flush();

// STEP 4: Free all students - clean all sponsorship meta
echo "<p class='info'><strong>Step 4:</strong> Freeing all students...</p>\n";
flush();

$all_students = get_posts(array(
    'post_type' => 'student',
    'post_status' => 'any',
    'posts_per_page' => -1,
    'fields' => 'ids'
));

$student_count = 0;
foreach ($all_students as $student_id) {
    // Delete all sponsorship-related meta (current keys)
    delete_post_meta($student_id, 'already_sponsored');
    delete_post_meta($student_id, 'sponsored_date');
    delete_post_meta($student_id, 'sponsor_cpt_id');

    // Delete legacy keys
    delete_post_meta($student_id, 'is_sponsored');
    delete_post_meta($student_id, 'sponsor_id');

    $student_count++;
}

echo "<p class='success'><strong>✓ Freed {$student_count} students (removed all sponsorship metadata)</strong></p>\n";
flush();

// STEP 5: Clean payment database table
echo "<p class='info'><strong>Step 5:</strong> Cleaning payment database table...</p>\n";
flush();

$payments_table = $wpdb->prefix . 'alhuffaz_payments';
$table_exists = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s",
    DB_NAME, $payments_table
));

$payment_count = 0;
if ($table_exists) {
    $payment_count = $wpdb->get_var("SELECT COUNT(*) FROM $payments_table");
    $wpdb->query("TRUNCATE TABLE $payments_table");
    echo "<p class='success'><strong>✓ Deleted {$payment_count} payment records from database table</strong></p>\n";
} else {
    echo "<p class='info'>ℹ Payment table does not exist (skipped)</p>\n";
}
flush();

// STEP 6: Reset cleanup options
echo "<p class='info'><strong>Step 6:</strong> Resetting cleanup options...</p>\n";
flush();

delete_option('sponsor_last_cleanup');
echo "<p class='success'><strong>✓ Reset cleanup options</strong></p>\n";
flush();

// STEP 7: Clean orphaned user meta
echo "<p class='info'><strong>Step 7:</strong> Cleaning orphaned user meta...</p>\n";
flush();

$deleted_meta = $wpdb->query("
    DELETE FROM {$wpdb->usermeta}
    WHERE meta_key IN (
        'sponsor_cpt_id',
        'account_status',
        'account_status_date',
        'sponsor_phone',
        'sponsor_country',
        'sponsor_whatsapp',
        'rejection_reason'
    )
");

echo "<p class='success'><strong>✓ Cleaned {$deleted_meta} orphaned user meta entries</strong></p>\n";
flush();

// Complete!
echo "<p class='success' style='font-size: 18px; margin-top: 30px;'><strong>✅ RESET COMPLETE!</strong></p>\n";

?>
    </div>

    <div class="complete">
        <h2>✅ Sponsorship System Reset Complete</h2>
        <p><strong>Summary:</strong></p>
        <ul>
            <li>Sponsor users deleted: <?php echo $sponsor_count; ?></li>
            <li>Sponsorship records deleted: <?php echo $sponsorship_count; ?></li>
            <li>Sponsor CPTs deleted: <?php echo $sponsor_cpt_count; ?></li>
            <li>Students freed: <?php echo $student_count; ?></li>
            <li>Payment records deleted: <?php echo $payment_count; ?></li>
            <li>Orphaned meta entries cleaned: <?php echo $deleted_meta; ?></li>
        </ul>

        <p><strong>⚠️ IMPORTANT: Delete this file now for security!</strong></p>
        <p>File location: <code>/reset-sponsorship-system.php</code></p>

        <p style="margin-top: 20px;">
            <a href="<?php echo admin_url(); ?>" style="display: inline-block; padding: 12px 24px; background: #28a745; color: white; text-decoration: none; border-radius: 6px; font-weight: bold;">
                ← Return to WordPress Admin
            </a>
        </p>
    </div>
</body>
</html>
