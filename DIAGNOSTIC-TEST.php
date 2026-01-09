<?php
/**
 * DIAGNOSTIC TEST FILE
 * Place this in your WordPress root and access it via browser
 * URL: yourdomain.com/DIAGNOSTIC-TEST.php
 *
 * This will tell you exactly what's wrong
 */

// Load WordPress
require_once('wp-load.php');

echo "<h1>🔍 Al-Huffaz Plugin Diagnostic Test</h1>";
echo "<style>body{font-family:sans-serif;padding:20px;} .success{color:green;} .error{color:red;} .info{color:blue;} code{background:#f4f4f4;padding:2px 6px;}</style>";

// Test 1: Check if plugin is active
echo "<h2>1. Plugin Activation Status</h2>";
if (is_plugin_active('al-huffaz-portal/al-huffaz-portal.php')) {
    echo "<p class='success'>✅ Plugin is ACTIVE</p>";
} else {
    echo "<p class='error'>❌ Plugin is NOT ACTIVE - Activate it in WP Admin!</p>";
}

// Test 2: Check if plugin file exists
echo "<h2>2. Plugin Files Check</h2>";
$plugin_path = WP_PLUGIN_DIR . '/al-huffaz-portal/al-huffaz-portal.php';
if (file_exists($plugin_path)) {
    echo "<p class='success'>✅ Main plugin file exists</p>";
    echo "<p class='info'>Path: <code>$plugin_path</code></p>";
} else {
    echo "<p class='error'>❌ Main plugin file NOT FOUND!</p>";
}

// Test 3: Check template file
echo "<h2>3. Template Files Check</h2>";
$template_path = WP_PLUGIN_DIR . '/al-huffaz-portal/templates/public/sponsor-dashboard.php';
if (file_exists($template_path)) {
    echo "<p class='success'>✅ Sponsor dashboard template exists</p>";

    // Check file modification time
    $mod_time = filemtime($template_path);
    $mod_date = date('Y-m-d H:i:s', $mod_time);
    echo "<p class='info'>Last modified: <code>$mod_date</code></p>";

    // Check for our new code
    $content = file_get_contents($template_path);
    if (strpos($content, 'payment_submitted') !== false) {
        echo "<p class='success'>✅ SUCCESS BANNER CODE FOUND in template!</p>";
    } else {
        echo "<p class='error'>❌ Success banner code NOT FOUND - changes not deployed?</p>";
    }

    if (strpos($content, 'pending_sponsorships') !== false) {
        echo "<p class='success'>✅ PENDING PAYMENTS CODE FOUND in template!</p>";
    } else {
        echo "<p class='error'>❌ Pending payments code NOT FOUND</p>";
    }

    if (strpos($content, 'Financial Summary') !== false) {
        echo "<p class='success'>✅ FINANCIAL BREAKDOWN CODE FOUND in template!</p>";
    } else {
        echo "<p class='error'>❌ Financial breakdown code NOT FOUND</p>";
    }
} else {
    echo "<p class='error'>❌ Template file NOT FOUND!</p>";
}

// Test 4: Check if shortcode is registered
echo "<h2>4. Shortcode Registration</h2>";
global $shortcode_tags;
if (isset($shortcode_tags['alhuffaz_sponsor_dashboard'])) {
    echo "<p class='success'>✅ Shortcode <code>[alhuffaz_sponsor_dashboard]</code> is registered</p>";
} else {
    echo "<p class='error'>❌ Shortcode NOT registered!</p>";
}

// Test 5: Check JavaScript file
echo "<h2>5. JavaScript Files Check</h2>";
$js_path = WP_PLUGIN_DIR . '/al-huffaz-portal/assets/js/public.js';
if (file_exists($js_path)) {
    echo "<p class='success'>✅ Public JavaScript file exists</p>";
    $mod_time = filemtime($js_path);
    $mod_date = date('Y-m-d H:i:s', $mod_time);
    echo "<p class='info'>Last modified: <code>$mod_date</code></p>";

    $js_content = file_get_contents($js_path);
    if (strpos($js_content, 'redirect_url') !== false) {
        echo "<p class='success'>✅ REDIRECT CODE FOUND in JavaScript!</p>";
    } else {
        echo "<p class='error'>❌ Redirect code NOT FOUND in JavaScript</p>";
    }
} else {
    echo "<p class='error'>❌ JavaScript file NOT FOUND!</p>";
}

// Test 6: Check AJAX handler
echo "<h2>6. AJAX Handler Check</h2>";
$ajax_path = WP_PLUGIN_DIR . '/al-huffaz-portal/includes/core/class-ajax-handler.php';
if (file_exists($ajax_path)) {
    echo "<p class='success'>✅ AJAX handler file exists</p>";
    $ajax_content = file_get_contents($ajax_path);
    if (strpos($ajax_content, 'redirect_url') !== false) {
        echo "<p class='success'>✅ REDIRECT URL CODE FOUND in AJAX handler!</p>";
    } else {
        echo "<p class='error'>❌ Redirect URL code NOT FOUND in AJAX handler</p>";
    }
}

// Test 7: Check Dashboard Class
echo "<h2>7. Sponsor Dashboard Class Check</h2>";
$dashboard_class_path = WP_PLUGIN_DIR . '/al-huffaz-portal/includes/public/class-sponsor-dashboard.php';
if (file_exists($dashboard_class_path)) {
    echo "<p class='success'>✅ Dashboard class file exists</p>";
    $class_content = file_get_contents($dashboard_class_path);
    if (strpos($class_content, 'pending_sponsorships') !== false) {
        echo "<p class='success'>✅ PENDING SPONSORSHIPS CODE FOUND in class!</p>";
    } else {
        echo "<p class='error'>❌ Pending sponsorships code NOT FOUND</p>";
    }

    if (strpos($class_content, 'monthly_total') !== false) {
        echo "<p class='success'>✅ FINANCIAL BREAKDOWN CODE FOUND in class!</p>";
    } else {
        echo "<p class='error'>❌ Financial breakdown code NOT FOUND</p>";
    }
}

// Test 8: Cache status
echo "<h2>8. Cache Status</h2>";
echo "<p class='info'>WordPress Object Cache: " . (wp_using_ext_object_cache() ? 'ENABLED' : 'DISABLED') . "</p>";

// Test 9: Git status
echo "<h2>9. Git Repository Status</h2>";
$repo_path = dirname($plugin_path);
echo "<p class='info'>Checking: <code>$repo_path</code></p>";
$current_branch = shell_exec("cd $repo_path && git branch --show-current 2>&1");
echo "<p>Current branch: <code>" . trim($current_branch) . "</code></p>";
$last_commit = shell_exec("cd $repo_path && git log -1 --format='%h - %s (%cr)' 2>&1");
echo "<p>Last commit: <code>" . trim($last_commit) . "</code></p>";

// Summary
echo "<h2>📊 Summary</h2>";
echo "<p>If all tests show ✅, the changes ARE in your files.</p>";
echo "<p><strong>If changes still don't appear on the website:</strong></p>";
echo "<ul>";
echo "<li>Clear browser cache (Ctrl+Shift+R or Cmd+Shift+R)</li>";
echo "<li>Clear WordPress cache (if using caching plugin)</li>";
echo "<li>Check if files were uploaded to server correctly</li>";
echo "<li>Verify you're logged in as a sponsor role user</li>";
echo "</ul>";

echo "<hr><p><em>Delete this file after testing!</em></p>";
