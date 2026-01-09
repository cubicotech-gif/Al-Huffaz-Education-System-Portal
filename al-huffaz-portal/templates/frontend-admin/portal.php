<?php
/**
 * Unified Front-end Admin Portal Template
 * Al-Huffaz Education System Portal
 *
 * ✅ NOW UNIFIED WITH BACKEND ADMIN
 * Uses same templates as WP Admin - any change reflects in BOTH places!
 */

use AlHuffaz\Admin\Dashboard;
use AlHuffaz\Admin\Student_Manager;
use AlHuffaz\Admin\Sponsor_Manager;
use AlHuffaz\Admin\Payment_Manager;
use AlHuffaz\Admin\Reports;
use AlHuffaz\Admin\Settings;
use AlHuffaz\Admin\Bulk_Import;
use AlHuffaz\Core\Helpers;

defined('ABSPATH') || exit;

// Check access
$current_user = wp_get_current_user();
if (!current_user_can('manage_options') && !current_user_can('edit_posts')) {
    echo '<div class="alhuffaz-notice notice-error">' . __('Access denied. You don\'t have permission to access this portal.', 'al-huffaz-portal') . '</div>';
    return;
}

// Get current page/tab
$current_page = isset($_GET['admin_page']) ? sanitize_key($_GET['admin_page']) : 'dashboard';

// Get current URL for building navigation links
$current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
$base_url = remove_query_arg(['admin_page', 'id', 'paged', 'view', 'status'], $current_url);

?>

<style>
/* Frontend Admin Portal Wrapper */
.alhuffaz-frontend-admin {
    background: #f0f0f1;
    padding: 20px 0;
    min-height: 100vh;
}

.alhuffaz-frontend-admin .alhuffaz-wrap {
    max-width: none !important;
    margin: 0 !important;
    padding: 0 20px !important;
}

/* Top Navigation Bar */
.alhuffaz-frontend-nav {
    background: #fff;
    border-radius: 8px;
    padding: 15px 20px;
    margin-bottom: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.alhuffaz-frontend-nav h2 {
    margin: 0 0 15px 0;
    font-size: 24px;
    color: #1d1d1f;
}

.alhuffaz-frontend-nav-links {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.alhuffaz-frontend-nav-link {
    padding: 10px 20px;
    background: #f6f6f7;
    border-radius: 6px;
    text-decoration: none;
    color: #333;
    font-weight: 500;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.alhuffaz-frontend-nav-link:hover {
    background: #e8e8e9;
    color: #000;
}

.alhuffaz-frontend-nav-link.active {
    background: var(--alhuffaz-primary, #2563eb);
    color: #fff;
}

.alhuffaz-frontend-nav-link .dashicons {
    font-size: 18px;
    width: 18px;
    height: 18px;
}

/* Content Area */
.alhuffaz-frontend-content {
    background: transparent;
}

/* Override some admin styles for frontend */
.alhuffaz-frontend-admin .alhuffaz-header {
    display: none; /* Hide duplicate header */
}

/* Make cards fit better in frontend */
.alhuffaz-frontend-admin .alhuffaz-card {
    background: #fff;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

/* Responsive */
@media (max-width: 768px) {
    .alhuffaz-frontend-nav-links {
        flex-direction: column;
    }

    .alhuffaz-frontend-nav-link {
        width: 100%;
        justify-content: center;
    }
}
</style>

<div class="alhuffaz-frontend-admin">
    <!-- Top Navigation -->
    <div class="alhuffaz-frontend-nav">
        <h2><?php _e('Al-Huffaz School Admin Portal', 'al-huffaz-portal'); ?></h2>

        <div class="alhuffaz-frontend-nav-links">
            <a href="<?php echo add_query_arg('admin_page', 'dashboard', $base_url); ?>"
               class="alhuffaz-frontend-nav-link <?php echo $current_page === 'dashboard' ? 'active' : ''; ?>">
                <span class="dashicons dashicons-dashboard"></span>
                <?php _e('Dashboard', 'al-huffaz-portal'); ?>
            </a>

            <a href="<?php echo add_query_arg('admin_page', 'students', $base_url); ?>"
               class="alhuffaz-frontend-nav-link <?php echo $current_page === 'students' ? 'active' : ''; ?>">
                <span class="dashicons dashicons-groups"></span>
                <?php _e('Students', 'al-huffaz-portal'); ?>
            </a>

            <a href="<?php echo add_query_arg('admin_page', 'add-student', $base_url); ?>"
               class="alhuffaz-frontend-nav-link <?php echo $current_page === 'add-student' ? 'active' : ''; ?>">
                <span class="dashicons dashicons-plus-alt"></span>
                <?php _e('Add Student', 'al-huffaz-portal'); ?>
            </a>

            <a href="<?php echo add_query_arg('admin_page', 'sponsors', $base_url); ?>"
               class="alhuffaz-frontend-nav-link <?php echo $current_page === 'sponsors' ? 'active' : ''; ?>">
                <span class="dashicons dashicons-heart"></span>
                <?php _e('Sponsors', 'al-huffaz-portal'); ?>
            </a>

            <a href="<?php echo add_query_arg('admin_page', 'payments', $base_url); ?>"
               class="alhuffaz-frontend-nav-link <?php echo $current_page === 'payments' ? 'active' : ''; ?>">
                <span class="dashicons dashicons-money-alt"></span>
                <?php _e('Payments', 'al-huffaz-portal'); ?>
            </a>

            <a href="<?php echo add_query_arg('admin_page', 'reports', $base_url); ?>"
               class="alhuffaz-frontend-nav-link <?php echo $current_page === 'reports' ? 'active' : ''; ?>">
                <span class="dashicons dashicons-chart-bar"></span>
                <?php _e('Reports', 'al-huffaz-portal'); ?>
            </a>

            <a href="<?php echo add_query_arg('admin_page', 'import', $base_url); ?>"
               class="alhuffaz-frontend-nav-link <?php echo $current_page === 'import' ? 'active' : ''; ?>">
                <span class="dashicons dashicons-upload"></span>
                <?php _e('Import', 'al-huffaz-portal'); ?>
            </a>

            <a href="<?php echo add_query_arg('admin_page', 'settings', $base_url); ?>"
               class="alhuffaz-frontend-nav-link <?php echo $current_page === 'settings' ? 'active' : ''; ?>">
                <span class="dashicons dashicons-admin-settings"></span>
                <?php _e('Settings', 'al-huffaz-portal'); ?>
            </a>
        </div>
    </div>

    <!-- Content Area -->
    <div class="alhuffaz-frontend-content">
        <?php
        // ✅ UNIFIED: Use the SAME templates as backend admin!
        // Any change you make to admin templates automatically appears here!
        switch ($current_page) {
            case 'dashboard':
                include ALHUFFAZ_TEMPLATES_DIR . 'admin/dashboard.php';
                break;

            case 'students':
                include ALHUFFAZ_TEMPLATES_DIR . 'admin/students.php';
                break;

            case 'add-student':
                include ALHUFFAZ_TEMPLATES_DIR . 'admin/student-form.php';
                break;

            case 'sponsors':
                include ALHUFFAZ_TEMPLATES_DIR . 'admin/sponsors.php';
                break;

            case 'payments':
                include ALHUFFAZ_TEMPLATES_DIR . 'admin/payments.php';
                break;

            case 'reports':
                include ALHUFFAZ_TEMPLATES_DIR . 'admin/reports.php';
                break;

            case 'import':
                include ALHUFFAZ_TEMPLATES_DIR . 'admin/import.php';
                break;

            case 'settings':
                include ALHUFFAZ_TEMPLATES_DIR . 'admin/settings.php';
                break;

            default:
                echo '<div class="alhuffaz-card">';
                echo '<p>' . __('Page not found.', 'al-huffaz-portal') . '</p>';
                echo '</div>';
        }
        ?>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Fix admin URLs in frontend context
    // Replace admin.php?page= links with frontend shortcode links
    $('.alhuffaz-frontend-admin a').each(function() {
        var href = $(this).attr('href');
        if (href && href.indexOf('admin.php?page=alhuffaz-') !== -1) {
            // Extract page name
            var match = href.match(/page=alhuffaz-([^&]+)/);
            if (match && match[1]) {
                var pageName = match[1];
                var baseUrl = '<?php echo esc_js($base_url); ?>';
                var newHref = baseUrl + (baseUrl.indexOf('?') !== -1 ? '&' : '?') + 'admin_page=' + pageName;

                // Preserve other query params
                if (href.indexOf('&id=') !== -1) {
                    var idMatch = href.match(/&id=(\d+)/);
                    if (idMatch) newHref += '&id=' + idMatch[1];
                }
                if (href.indexOf('&view=') !== -1) {
                    var viewMatch = href.match(/&view=([^&]+)/);
                    if (viewMatch) newHref += '&view=' + viewMatch[1];
                }
                if (href.indexOf('&status=') !== -1) {
                    var statusMatch = href.match(/&status=([^&]+)/);
                    if (statusMatch) newHref += '&status=' + statusMatch[1];
                }
                if (href.indexOf('&paged=') !== -1) {
                    var pagedMatch = href.match(/&paged=(\d+)/);
                    if (pagedMatch) newHref += '&paged=' + pagedMatch[1];
                }

                $(this).attr('href', newHref);
            }
        }
    });

    // Make forms work in frontend context
    $('.alhuffaz-frontend-admin form').each(function() {
        var action = $(this).attr('action');
        if (action && action.indexOf('admin.php') !== -1) {
            // Forms will submit to same page
            $(this).attr('action', '<?php echo esc_js($current_url); ?>');
        }
    });
});
</script>
