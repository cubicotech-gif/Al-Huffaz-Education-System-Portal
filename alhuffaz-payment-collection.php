<?php
/*
Plugin Name: Al-Huffaz Payment Collection (Professional Redirect)
Description: Manual payment system with dashboard redirect after submission
Version: 2.0
Author: RoohUl Hasnain
*/

defined('ABSPATH') || exit;

// Create payment page endpoint
add_action('init', 'create_payment_page_rewrite');
function create_payment_page_rewrite() {
    add_rewrite_rule('^sponsor-payment/?$', 'index.php?sponsor_payment=1', 'top');
}

add_filter('query_vars', 'payment_query_vars');
function payment_query_vars($vars) {
    $vars[] = 'sponsor_payment';
    return $vars;
}

add_action('template_redirect', 'payment_page_template');
function payment_page_template() {
    if (get_query_var('sponsor_payment')) {
        include plugin_dir_path(__FILE__) . 'payment-template.php';
        exit;
    }
}

// Handle payment proof submission
add_action('wp_ajax_submit_payment_proof', 'handle_payment_proof_submission');
add_action('wp_ajax_nopriv_submit_payment_proof', 'handle_payment_proof_submission');

function handle_payment_proof_submission() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'payment_proof_nonce')) {
        wp_send_json_error('Invalid security token');
    }
    
    // Get form data
    $student_id = intval($_POST['student_id']);
    $sponsorship_type = sanitize_text_field($_POST['sponsorship_type']);
    $amount = floatval($_POST['amount']);
    $sponsor_name = sanitize_text_field($_POST['sponsor_name']);
    $sponsor_email = sanitize_email($_POST['sponsor_email']);
    $sponsor_phone = sanitize_text_field($_POST['sponsor_phone']);
    $sponsor_country = sanitize_text_field($_POST['sponsor_country']);
    $transaction_id = sanitize_text_field($_POST['transaction_id']);
    $payment_method = sanitize_text_field($_POST['payment_method']);
    $payment_date = sanitize_text_field($_POST['payment_date']);
    $notes = sanitize_textarea_field($_POST['notes']);
    
    // Handle file upload
    $screenshot_id = 0;
    if (isset($_FILES['payment_screenshot'])) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        
        $screenshot_id = media_handle_upload('payment_screenshot', 0);
        
        if (is_wp_error($screenshot_id)) {
            wp_send_json_error('Failed to upload screenshot: ' . $screenshot_id->get_error_message());
        }
    }
    
    // Check if user exists or create new
    $user = get_user_by('email', $sponsor_email);
    
    if (!$user) {
        // Create new user account
        $username = sanitize_user(strtolower(str_replace(' ', '_', $sponsor_name)));
        
        // Make username unique if exists
        $base_username = $username;
        $counter = 1;
        while (username_exists($username)) {
            $username = $base_username . '_' . $counter;
            $counter++;
        }
        
        $password = wp_generate_password(12, true);
        
        $user_id = wp_create_user($username, $password, $sponsor_email);
        
        if (is_wp_error($user_id)) {
            wp_send_json_error('Failed to create user account: ' . $user_id->get_error_message());
        }
        
        // Set user role as sponsor
        $user = new WP_User($user_id);
        $user->set_role('sponsor');
        
        // Update user meta
        update_user_meta($user_id, 'first_name', $sponsor_name);
        update_user_meta($user_id, 'sponsor_phone', $sponsor_phone);
        update_user_meta($user_id, 'sponsor_country', $sponsor_country);
        update_user_meta($user_id, 'account_status', 'approved'); // ✅ Auto-approve since they're submitting payment
        
        // Send welcome email with login details
        $to = $sponsor_email;
        $subject = 'Al-Huffaz Sponsorship - Account Created';
        $message = "Dear $sponsor_name,\n\n";
        $message .= "Thank you for your sponsorship! Your account has been created.\n\n";
        $message .= "Login Details:\n";
        $message .= "Username: $username\n";
        $message .= "Password: $password\n";
        $message .= "Login URL: " . wp_login_url() . "\n\n";
        $message .= "Your sponsorship payment is being verified and you will receive confirmation within 24-48 hours.\n\n";
        $message .= "You can track your payment status in your dashboard:\n";
        
        // Get dashboard URL
        $dashboard_page = get_page_by_path('sponsor-dashboard');
        if ($dashboard_page) {
            $message .= get_permalink($dashboard_page->ID) . "\n\n";
        }
        
        $message .= "Best regards,\nAl-Huffaz Education System";
        
        wp_mail($to, $subject, $message);
        
    } else {
        $user_id = $user->ID;
    }
    
    // Create sponsorship request post
    $student_name = get_the_title($student_id);
    
    $sponsorship_post = array(
        'post_title' => $sponsor_name . ' → ' . $student_name . ' (' . ucfirst($sponsorship_type) . ')',
        'post_type' => 'sponsorship',
        'post_status' => 'pending',
        'post_author' => $user_id
    );
    
    $sponsorship_id = wp_insert_post($sponsorship_post);
    
    if (!$sponsorship_id) {
        wp_send_json_error('Failed to create sponsorship request');
    }
    
    // Save all sponsorship data
    update_post_meta($sponsorship_id, 'student_id', $student_id);
    update_post_meta($sponsorship_id, 'sponsor_user_id', $user_id);
    update_post_meta($sponsorship_id, 'sponsorship_type', $sponsorship_type);
    update_post_meta($sponsorship_id, 'amount', $amount);
    update_post_meta($sponsorship_id, 'sponsor_name', $sponsor_name);
    update_post_meta($sponsorship_id, 'sponsor_email', $sponsor_email);
    update_post_meta($sponsorship_id, 'sponsor_phone', $sponsor_phone);
    update_post_meta($sponsorship_id, 'sponsor_country', $sponsor_country);
    update_post_meta($sponsorship_id, 'transaction_id', $transaction_id);
    update_post_meta($sponsorship_id, 'payment_method', $payment_method);
    update_post_meta($sponsorship_id, 'payment_date', $payment_date);
    update_post_meta($sponsorship_id, 'payment_screenshot', $screenshot_id);
    update_post_meta($sponsorship_id, 'notes', $notes);
    update_post_meta($sponsorship_id, 'submission_date', current_time('mysql'));
    update_post_meta($sponsorship_id, 'verification_status', 'pending');
    update_post_meta($sponsorship_id, 'linked', 'no'); // ✅ Not linked until verified
    
    // Send notification to admin
    $admin_email = get_option('admin_email');
    $admin_subject = 'New Sponsorship Payment Received';
    $admin_message = "A new sponsorship payment has been submitted:\n\n";
    $admin_message .= "Sponsor: $sponsor_name\n";
    $admin_message .= "Email: $sponsor_email\n";
    $admin_message .= "Student: $student_name\n";
    $admin_message .= "Type: " . ucfirst($sponsorship_type) . "\n";
    $admin_message .= "Amount: PKR " . number_format($amount) . "\n";
    $admin_message .= "Transaction ID: $transaction_id\n\n";
    $admin_message .= "Please verify in admin dashboard:\n";
    $admin_message .= admin_url('edit.php?post_type=sponsorship');
    
    wp_mail($admin_email, $admin_subject, $admin_message);
    
    // ✅ NEW: Build dashboard redirect URL
    $dashboard_page = get_page_by_path('sponsor-dashboard');
    $redirect_url = home_url('/sponsor-dashboard/'); // Fallback
    
    if ($dashboard_page) {
        $redirect_url = add_query_arg(array(
            'payment_submitted' => 'success',
            'open_tab' => 'payments'
        ), get_permalink($dashboard_page->ID));
    }
    
    // ✅ FIXED: Return redirect URL instead of just message
    wp_send_json_success(array(
        'message' => 'Payment proof submitted successfully! Redirecting to your dashboard...',
        'sponsorship_id' => $sponsorship_id,
        'redirect_url' => $redirect_url,
        'user_created' => !$user // Tell frontend if new user was created
    ));
}

// Register Sponsorship CPT
add_action('init', 'register_sponsorship_cpt');
function register_sponsorship_cpt() {
    register_post_type('sponsorship', array(
        'labels' => array(
            'name' => 'Sponsorships',
            'singular_name' => 'Sponsorship',
            'add_new' => 'Add New',
            'add_new_item' => 'Add New Sponsorship',
            'edit_item' => 'Edit Sponsorship',
            'view_item' => 'View Sponsorship',
            'all_items' => 'All Sponsorships',
        ),
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_icon' => 'dashicons-heart',
        'supports' => array('title'),
        'capability_type' => 'post',
        'capabilities' => array(
            'create_posts' => 'do_not_allow',
        ),
        'map_meta_cap' => true,
    ));
}

// Add custom role for sponsors
add_action('init', 'add_sponsor_role');
function add_sponsor_role() {
    if (!get_role('sponsor')) {
        add_role('sponsor', 'Sponsor', array(
            'read' => true,
            'edit_posts' => false,
            'delete_posts' => false,
        ));
    }
}

// Flush rewrite rules on activation
register_activation_hook(__FILE__, 'payment_plugin_activate');
function payment_plugin_activate() {
    add_sponsor_role();
    register_sponsorship_cpt();
    create_payment_page_rewrite();
    flush_rewrite_rules();
}

register_deactivation_hook(__FILE__, 'payment_plugin_deactivate');
function payment_plugin_deactivate() {
    flush_rewrite_rules();
}
?>