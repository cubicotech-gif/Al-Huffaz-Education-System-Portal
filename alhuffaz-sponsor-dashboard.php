<?php
/*
Plugin Name: Al-Huffaz Sponsor Dashboard (Professional)
Description: Beautiful sponsor dashboard with payment tracking and student management - Professional redirects
Version: 2.0
Author: RoohUl Hasnain
*/

defined('ABSPATH') || exit;

// Register shortcode
add_shortcode('sponsor_dashboard', 'alhuffaz_sponsor_dashboard_display');

function alhuffaz_sponsor_dashboard_display() {
    
    // Check if user is logged in
    if (!is_user_logged_in()) {
        return '<div style="text-align: center; padding: 50px;">
                    <h3>Please login to access your dashboard</h3>
                    <a href="' . wp_login_url() . '" style="background: #0080ff; color: white; padding: 12px 30px; border-radius: 8px; text-decoration: none; display: inline-block; margin-top: 20px;">Login Now</a>
                </div>';
    }
    
    // Get current user
    $current_user = wp_get_current_user();
    $user_id = $current_user->ID;
    $user_name = $current_user->display_name;
    $user_email = $current_user->user_email;
    
    // Check if user is sponsor
    if (!in_array('sponsor', $current_user->roles)) {
        return '<div style="text-align: center; padding: 50px;">
                    <h3>Access Denied</h3>
                    <p>This dashboard is only available for sponsors.</p>
                </div>';
    }
    
    // ✅ NEW: Check for success parameter
    $show_success = isset($_GET['payment_submitted']) && $_GET['payment_submitted'] === 'success';
    $auto_open_tab = isset($_GET['open_tab']) ? sanitize_text_field($_GET['open_tab']) : '';
    
    // Get all sponsorships for this user
    $all_sponsorships = get_posts(array(
        'post_type' => 'sponsorship',
        'author' => $user_id,
        'posts_per_page' => -1,
        'post_status' => array('pending', 'publish')
    ));
    
    // Get approved/linked sponsorships
    $approved_sponsorships = array();
    $pending_sponsorships = array();
    $total_donations = 0;
    $monthly_total = 0;
    $quarterly_total = 0;
    $yearly_total = 0;
    
    foreach ($all_sponsorships as $sponsorship) {
        $verification_status = get_post_meta($sponsorship->ID, 'verification_status', true);
        $linked = get_post_meta($sponsorship->ID, 'linked', true);
        $amount = floatval(get_post_meta($sponsorship->ID, 'amount', true));
        $type = get_post_meta($sponsorship->ID, 'sponsorship_type', true);
        
        if ($verification_status === 'approved' && $linked === 'yes') {
            $approved_sponsorships[] = $sponsorship;
            $total_donations += $amount;
            
            if ($type === 'monthly') {
                $monthly_total += $amount;
            } elseif ($type === 'quarterly') {
                $quarterly_total += $amount;
            } elseif ($type === 'yearly') {
                $yearly_total += $amount;
            }
        } else {
            $pending_sponsorships[] = $sponsorship;
        }
    }
    
    $sponsored_students_count = count($approved_sponsorships);
    $pending_count = count($pending_sponsorships);
    
    // Get unique sponsored students
    $unique_students = array();
    foreach ($approved_sponsorships as $sponsorship) {
        $student_id = get_post_meta($sponsorship->ID, 'student_id', true);
        if (!isset($unique_students[$student_id])) {
            $unique_students[$student_id] = array(
                'student_id' => $student_id,
                'total_paid' => 0,
                'sponsorships' => array()
            );
        }
        $amount = floatval(get_post_meta($sponsorship->ID, 'amount', true));
        $unique_students[$student_id]['total_paid'] += $amount;
        $unique_students[$student_id]['sponsorships'][] = $sponsorship;
    }
    
    ob_start();
    ?>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
    .sd-container {
        font-family: 'Poppins', sans-serif;
        max-width: 1400px;
        margin: 30px auto;
        padding: 20px;
        background: #f8fafc;
        min-height: 80vh;
    }
    
    /* ✅ NEW: Success message styles */
    .sd-success-message {
        background: linear-gradient(135deg, #d1fae5, #a7f3d0);
        border-left: 4px solid #10b981;
        padding: 20px 25px;
        border-radius: 12px;
        margin-bottom: 25px;
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
        animation: slideIn 0.5s ease-out;
    }
    
    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .sd-success-message h3 {
        margin: 0 0 10px 0;
        font-size: 18px;
        font-weight: 700;
        color: #065f46;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .sd-success-message p {
        margin: 0;
        font-size: 14px;
        color: #047857;
        line-height: 1.6;
    }
    
    .sd-success-icon {
        width: 40px;
        height: 40px;
        background: #10b981;
        color: white;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
    }
    
    .sd-header {
        background: linear-gradient(135deg, #0080ff, #004d99);
        color: white;
        padding: 40px;
        border-radius: 16px;
        margin-bottom: 30px;
        box-shadow: 0 8px 24px rgba(0, 128, 255, 0.2);
    }
    
    .sd-header-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    
    .sd-welcome h1 {
        margin: 0;
        font-size: 32px;
        font-weight: 700;
    }
    
    .sd-welcome p {
        margin: 5px 0 0 0;
        opacity: 0.9;
        font-size: 14px;
    }
    
    .sd-user-info {
        text-align: right;
    }
    
    .sd-user-avatar {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        font-weight: 700;
        margin-left: auto;
        margin-bottom: 5px;
    }
    
    .sd-stats {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
        margin-top: 20px;
    }
    
    .sd-stat-card {
        background: rgba(255, 255, 255, 0.15);
        backdrop-filter: blur(10px);
        padding: 20px;
        border-radius: 12px;
        border: 1px solid rgba(255, 255, 255, 0.2);
    }
    
    .sd-stat-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        background: rgba(255, 255, 255, 0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        margin-bottom: 12px;
    }
    
    .sd-stat-value {
        font-size: 28px;
        font-weight: 800;
        margin-bottom: 4px;
    }
    
    .sd-stat-label {
        font-size: 13px;
        opacity: 0.9;
    }
    
    .sd-tabs {
        display: flex;
        gap: 10px;
        margin-bottom: 30px;
        background: white;
        padding: 10px;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    }
    
    .sd-tab {
        flex: 1;
        padding: 15px 30px;
        background: transparent;
        border: none;
        border-radius: 8px;
        font-size: 15px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        color: #64748b;
        font-family: 'Poppins', sans-serif;
        position: relative;
    }
    
    .sd-tab i {
        margin-right: 8px;
    }
    
    .sd-tab.active {
        background: linear-gradient(135deg, #0080ff, #004d99);
        color: white;
        box-shadow: 0 4px 12px rgba(0, 128, 255, 0.3);
    }
    
    .sd-tab-badge {
        position: absolute;
        top: 8px;
        right: 8px;
        background: #ef4444;
        color: white;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        font-size: 11px;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .sd-tab-content {
        display: none;
    }
    
    .sd-tab-content.active {
        display: block;
    }
    
    .sd-students-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 25px;
    }
    
    .sd-student-card {
        background: white;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
        border: 2px solid transparent;
        transition: all 0.3s;
    }
    
    .sd-student-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 24px rgba(0, 128, 255, 0.15);
        border-color: #0080ff;
    }
    
    .sd-student-header {
        background: linear-gradient(135deg, #e6f2ff, #cce6ff);
        padding: 25px;
        text-align: center;
        position: relative;
    }
    
    .sd-student-photo {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        margin: 0 auto 15px;
        overflow: hidden;
        border: 4px solid white;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }
    
    .sd-student-photo img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .sd-student-photo-placeholder {
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, #0080ff, #004d99);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 40px;
        font-weight: 700;
        color: white;
    }
    
    .sd-student-name {
        font-size: 20px;
        font-weight: 700;
        color: #001a33;
        margin: 0 0 5px 0;
    }
    
    .sd-student-gr {
        font-size: 13px;
        color: #64748b;
        font-weight: 600;
    }
    
    .sd-student-body {
        padding: 25px;
    }
    
    .sd-student-info-row {
        display: flex;
        justify-content: space-between;
        padding: 12px 0;
        border-bottom: 1px solid #f1f5f9;
    }
    
    .sd-student-info-row:last-child {
        border-bottom: none;
    }
    
    .sd-info-label {
        font-size: 13px;
        color: #64748b;
        font-weight: 500;
    }
    
    .sd-info-value {
        font-size: 14px;
        color: #001a33;
        font-weight: 700;
    }
    
    .sd-donation-summary {
        background: #f0f8ff;
        padding: 20px;
        border-radius: 12px;
        margin-top: 20px;
        border-left: 4px solid #0080ff;
    }
    
    .sd-donation-summary h4 {
        margin: 0 0 15px 0;
        font-size: 14px;
        font-weight: 700;
        color: #001a33;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .sd-donation-item {
        display: flex;
        justify-content: space-between;
        padding: 8px 0;
    }
    
    .sd-donation-type {
        font-size: 13px;
        color: #64748b;
    }
    
    .sd-donation-amount {
        font-size: 14px;
        font-weight: 700;
        color: #0080ff;
        font-family: monospace;
    }
    
    .sd-total-row {
        border-top: 2px solid #cce6ff;
        margin-top: 10px;
        padding-top: 10px;
        font-size: 16px;
        font-weight: 800;
    }
    
    /* ✅ NEW: View Profile Button */
    .sd-view-profile-btn {
        width: 100%;
        padding: 12px;
        background: linear-gradient(135deg, #0080ff, #004d99);
        color: white;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.3s;
        margin-top: 15px;
        font-family: 'Poppins', sans-serif;
        text-decoration: none;
        display: block;
        text-align: center;
    }
    
    .sd-view-profile-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(0, 128, 255, 0.3);
    }
    
    .sd-payment-history {
        background: white;
        border-radius: 16px;
        padding: 30px;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
        margin-bottom: 30px;
    }
    
    .sd-payment-history h3 {
        margin: 0 0 20px 0;
        font-size: 20px;
        font-weight: 700;
        color: #001a33;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .sd-payment-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .sd-payment-table th {
        text-align: left;
        padding: 12px;
        background: #f8fafc;
        font-size: 13px;
        font-weight: 700;
        color: #334155;
        border-bottom: 2px solid #e2e8f0;
    }
    
    .sd-payment-table td {
        padding: 15px 12px;
        border-bottom: 1px solid #f1f5f9;
        font-size: 14px;
        color: #334155;
    }
    
    .sd-payment-table tr:hover {
        background: #f8fafc;
    }
    
    .sd-status-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
    }
    
    .sd-status-approved {
        background: #d1fae5;
        color: #065f46;
    }
    
    .sd-status-pending {
        background: #fef3c7;
        color: #92400e;
    }
    
    .sd-type-badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 8px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
    }
    
    .sd-type-monthly {
        background: #dbeafe;
        color: #1e40af;
    }
    
    .sd-type-quarterly {
        background: #d1fae5;
        color: #065f46;
    }
    
    .sd-type-yearly {
        background: #fed7aa;
        color: #92400e;
    }
    
    .sd-empty-state {
        text-align: center;
        padding: 60px 20px;
        background: white;
        border-radius: 16px;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
    }
    
    .sd-empty-state i {
        font-size: 64px;
        color: #cbd5e1;
        margin-bottom: 20px;
    }
    
    .sd-empty-state h3 {
        font-size: 22px;
        font-weight: 700;
        color: #334155;
        margin: 0 0 10px 0;
    }
    
    .sd-empty-state p {
        font-size: 15px;
        color: #64748b;
        margin: 0 0 25px 0;
    }
    
    .sd-btn {
        display: inline-block;
        padding: 12px 30px;
        background: linear-gradient(135deg, #0080ff, #004d99);
        color: white;
        border-radius: 10px;
        text-decoration: none;
        font-weight: 600;
        font-size: 15px;
        transition: all 0.3s;
        border: none;
        cursor: pointer;
    }
    
    .sd-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0, 128, 255, 0.3);
    }
    
    .sd-pending-notice {
        background: #fffbeb;
        border-left: 4px solid #f59e0b;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 20px;
    }
    
    .sd-pending-notice h4 {
        margin: 0 0 10px 0;
        font-size: 16px;
        font-weight: 700;
        color: #92400e;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .sd-pending-notice p {
        margin: 0;
        font-size: 14px;
        color: #78350f;
        line-height: 1.6;
    }
    
    /* ✅ NEW: Pending payments list */
    .sd-pending-list {
        background: white;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    }
    
    .sd-pending-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px;
        background: #fef3c7;
        border-radius: 8px;
        margin-bottom: 10px;
        border-left: 4px solid #f59e0b;
    }
    
    .sd-pending-item:last-child {
        margin-bottom: 0;
    }
    
    .sd-pending-info h5 {
        margin: 0 0 5px 0;
        font-size: 15px;
        font-weight: 700;
        color: #78350f;
    }
    
    .sd-pending-info p {
        margin: 0;
        font-size: 13px;
        color: #92400e;
    }
    
    @media (max-width: 1024px) {
        .sd-stats {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .sd-students-grid {
            grid-template-columns: 1fr;
        }
    }
    
    @media (max-width: 768px) {
        .sd-header-top {
            flex-direction: column;
            text-align: center;
        }
        
        .sd-user-info {
            text-align: center;
            margin-top: 20px;
        }
        
        .sd-user-avatar {
            margin: 0 auto 10px;
        }
        
        .sd-stats {
            grid-template-columns: 1fr;
        }
        
        .sd-tabs {
            flex-direction: column;
        }
        
        .sd-payment-table {
            font-size: 12px;
        }
        
        .sd-payment-table th,
        .sd-payment-table td {
            padding: 8px 6px;
        }
    }
    </style>
    
    <div class="sd-container">
        
        <!-- ✅ NEW: Success Message -->
        <?php if ($show_success): ?>
        <div class="sd-success-message">
            <h3>
                <span class="sd-success-icon"><i class="fas fa-check"></i></span>
                Payment Submitted Successfully!
            </h3>
            <p>
                <strong>Your sponsorship payment has been received!</strong> Our team will verify your payment within 24-48 hours. 
                You can track your payment status in the "Payment History" tab below. Once verified, the student will appear in your "My Students" section.
            </p>
        </div>
        <?php endif; ?>
        
        <!-- Header with Stats -->
        <div class="sd-header">
            <div class="sd-header-top">
                <div class="sd-welcome">
                    <h1><i class="fas fa-hand-sparkles"></i> Welcome back, <?php echo esc_html($user_name); ?>!</h1>
                    <p><?php echo esc_html($user_email); ?></p>
                </div>
                <div class="sd-user-info">
                    <div class="sd-user-avatar">
                        <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                    </div>
                    <span style="font-size: 13px; opacity: 0.9;">Sponsor ID: #<?php echo $user_id; ?></span>
                </div>
            </div>
            
            <div class="sd-stats">
                <div class="sd-stat-card">
                    <div class="sd-stat-icon"><i class="fas fa-users"></i></div>
                    <div class="sd-stat-value"><?php echo $sponsored_students_count; ?></div>
                    <div class="sd-stat-label">Active Sponsorships</div>
                </div>
                
                <div class="sd-stat-card">
                    <div class="sd-stat-icon"><i class="fas fa-hand-holding-heart"></i></div>
                    <div class="sd-stat-value">PKR <?php echo number_format($total_donations); ?></div>
                    <div class="sd-stat-label">Total Donated</div>
                </div>
                
                <div class="sd-stat-card">
                    <div class="sd-stat-icon"><i class="fas fa-calendar-check"></i></div>
                    <div class="sd-stat-value"><?php echo count($all_sponsorships); ?></div>
                    <div class="sd-stat-label">Total Payments</div>
                </div>
                
                <div class="sd-stat-card">
                    <div class="sd-stat-icon"><i class="fas fa-clock"></i></div>
                    <div class="sd-stat-value"><?php echo $pending_count; ?></div>
                    <div class="sd-stat-label">Pending Verification</div>
                </div>
            </div>
        </div>
        
        <!-- Tabs -->
        <div class="sd-tabs">
            <button class="sd-tab <?php echo ($auto_open_tab !== 'payments' && $auto_open_tab !== 'my-students') ? 'active' : ''; ?>" onclick="switchTab('browse')">
                <i class="fas fa-search"></i> Browse Students
            </button>
            <button class="sd-tab <?php echo $auto_open_tab === 'my-students' ? 'active' : ''; ?>" onclick="switchTab('my-students')">
                <i class="fas fa-heart"></i> My Students
                <?php if ($sponsored_students_count > 0): ?>
                    <span class="sd-tab-badge"><?php echo $sponsored_students_count; ?></span>
                <?php endif; ?>
            </button>
            <button class="sd-tab <?php echo $auto_open_tab === 'payments' ? 'active' : ''; ?>" onclick="switchTab('payments')">
                <i class="fas fa-receipt"></i> Payment History
                <?php if ($pending_count > 0): ?>
                    <span class="sd-tab-badge"><?php echo $pending_count; ?></span>
                <?php endif; ?>
            </button>
        </div>
        
        <!-- Tab: Browse Students -->
        <div id="tab-browse" class="sd-tab-content <?php echo ($auto_open_tab !== 'payments' && $auto_open_tab !== 'my-students') ? 'active' : ''; ?>">
            <?php echo do_shortcode('[student_sponsorship]'); ?>
        </div>
        
        <!-- Tab: My Students -->
        <div id="tab-my-students" class="sd-tab-content <?php echo $auto_open_tab === 'my-students' ? 'active' : ''; ?>">
            <?php if ($pending_count > 0): ?>
                <div class="sd-pending-notice">
                    <h4><i class="fas fa-info-circle"></i> Pending Verifications (<?php echo $pending_count; ?>)</h4>
                    <p>The following payments are awaiting verification. Students will appear in your "My Students" section once payments are verified by our team (usually within 24-48 hours).</p>
                </div>
                
                <!-- ✅ NEW: Show pending payments list -->
                <div class="sd-pending-list">
                    <?php foreach ($pending_sponsorships as $pending): 
                        $student_id = get_post_meta($pending->ID, 'student_id', true);
                        $student = get_post($student_id);
                        $amount = get_post_meta($pending->ID, 'amount', true);
                        $type = get_post_meta($pending->ID, 'sponsorship_type', true);
                        $payment_date = get_post_meta($pending->ID, 'payment_date', true);
                    ?>
                        <div class="sd-pending-item">
                            <div class="sd-pending-info">
                                <h5><?php echo esc_html($student ? $student->post_title : 'Student'); ?></h5>
                                <p><i class="fas fa-money-bill-wave"></i> PKR <?php echo number_format($amount); ?> (<?php echo ucfirst($type); ?>) • Submitted: <?php echo date('M d, Y', strtotime($payment_date)); ?></p>
                            </div>
                            <span class="sd-status-badge sd-status-pending"><i class="fas fa-clock"></i> Pending</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php if (count($unique_students) > 0): ?>
                <div class="sd-students-grid" style="margin-top: 30px;">
                    <?php foreach ($unique_students as $student_data): 
                        $student_id = $student_data['student_id'];
                        $student = get_post($student_id);
                        if (!$student) continue;
                        
                        $gr_number = get_post_meta($student_id, 'gr_number', true);
                        $grade = get_post_meta($student_id, 'grade_level', true);
                        $photo_id = get_post_meta($student_id, 'student_photo', true);
                        $photo_url = $photo_id ? wp_get_attachment_image_url($photo_id, 'medium') : '';
                        
                        // Calculate breakdown
                        $student_monthly = 0;
                        $student_quarterly = 0;
                        $student_yearly = 0;
                        
                        foreach ($student_data['sponsorships'] as $sp) {
                            $type = get_post_meta($sp->ID, 'sponsorship_type', true);
                            $amount = floatval(get_post_meta($sp->ID, 'amount', true));
                            
                            if ($type === 'monthly') {
                                $student_monthly += $amount;
                            } elseif ($type === 'quarterly') {
                                $student_quarterly += $amount;
                            } elseif ($type === 'yearly') {
                                $student_yearly += $amount;
                            }
                        }
                    ?>
                        <div class="sd-student-card">
                            <div class="sd-student-header">
                                <div class="sd-student-photo">
                                    <?php if ($photo_url): ?>
                                        <img src="<?php echo esc_url($photo_url); ?>" alt="<?php echo esc_attr($student->post_title); ?>">
                                    <?php else: ?>
                                        <div class="sd-student-photo-placeholder">
                                            <?php echo strtoupper(substr($student->post_title, 0, 1)); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <h3 class="sd-student-name"><?php echo esc_html($student->post_title); ?></h3>
                                <div class="sd-student-gr">GR: <?php echo esc_html($gr_number); ?></div>
                            </div>
                            
                            <div class="sd-student-body">
                                <div class="sd-student-info-row">
                                    <span class="sd-info-label">Grade Level</span>
                                    <span class="sd-info-value"><?php echo esc_html(strtoupper($grade)); ?></span>
                                </div>
                                
                                <div class="sd-student-info-row">
                                    <span class="sd-info-label">Total Payments</span>
                                    <span class="sd-info-value"><?php echo count($student_data['sponsorships']); ?></span>
                                </div>
                                
                                <div class="sd-donation-summary">
                                    <h4><i class="fas fa-chart-pie"></i> Your Contribution</h4>
                                    
                                    <?php if ($student_monthly > 0): ?>
                                        <div class="sd-donation-item">
                                            <span class="sd-donation-type"><i class="fas fa-calendar-day"></i> Monthly</span>
                                            <span class="sd-donation-amount">PKR <?php echo number_format($student_monthly); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($student_quarterly > 0): ?>
                                        <div class="sd-donation-item">
                                            <span class="sd-donation-type"><i class="fas fa-calendar-week"></i> Quarterly</span>
                                            <span class="sd-donation-amount">PKR <?php echo number_format($student_quarterly); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($student_yearly > 0): ?>
                                        <div class="sd-donation-item">
                                            <span class="sd-donation-type"><i class="fas fa-calendar-alt"></i> Yearly</span>
                                            <span class="sd-donation-amount">PKR <?php echo number_format($student_yearly); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="sd-donation-item sd-total-row">
                                        <span class="sd-donation-type">Total Donated</span>
                                        <span class="sd-donation-amount">PKR <?php echo number_format($student_data['total_paid']); ?></span>
                                    </div>
                                </div>
                                
                                <!-- ✅ NEW: View Profile Button -->
                                <a href="<?php echo get_permalink($student_id); ?>" class="sd-view-profile-btn">
                                    <i class="fas fa-user-graduate"></i> View Complete Profile
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="sd-empty-state">
                    <i class="fas fa-users-slash"></i>
                    <h3>No Sponsored Students Yet</h3>
                    <p>Start making a difference by sponsoring a student today!</p>
                    <button class="sd-btn" onclick="switchTab('browse')">
                        <i class="fas fa-search"></i> Browse Students
                    </button>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Tab: Payment History -->
        <div id="tab-payments" class="sd-tab-content <?php echo $auto_open_tab === 'payments' ? 'active' : ''; ?>">
            <?php if (count($all_sponsorships) > 0): ?>
                <div class="sd-payment-history">
                    <h3><i class="fas fa-history"></i> Complete Payment History</h3>
                    
                    <table class="sd-payment-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Student</th>
                                <th>Type</th>
                                <th>Amount</th>
                                <th>Transaction ID</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_sponsorships as $sponsorship): 
                                $student_id = get_post_meta($sponsorship->ID, 'student_id', true);
                                $student = get_post($student_id);
                                $amount = get_post_meta($sponsorship->ID, 'amount', true);
                                $type = get_post_meta($sponsorship->ID, 'sponsorship_type', true);
                                $transaction_id = get_post_meta($sponsorship->ID, 'transaction_id', true);
                                $payment_date = get_post_meta($sponsorship->ID, 'payment_date', true);
                                $verification_status = get_post_meta($sponsorship->ID, 'verification_status', true);
                                $linked = get_post_meta($sponsorship->ID, 'linked', true);
                                
                                $status_class = ($verification_status === 'approved' && $linked === 'yes') ? 'sd-status-approved' : 'sd-status-pending';
                                $status_text = ($verification_status === 'approved' && $linked === 'yes') ? 'Approved' : 'Pending';
                                
                                $type_class = 'sd-type-' . $type;
                            ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($payment_date)); ?></td>
                                    <td><strong><?php echo esc_html($student ? $student->post_title : 'N/A'); ?></strong></td>
                                    <td><span class="sd-type-badge <?php echo $type_class; ?>"><?php echo ucfirst($type); ?></span></td>
                                    <td><strong>PKR <?php echo number_format($amount); ?></strong></td>
                                    <td><?php echo esc_html($transaction_id); ?></td>
                                    <td><span class="sd-status-badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Financial Summary -->
                <div class="sd-payment-history">
                    <h3><i class="fas fa-calculator"></i> Financial Summary</h3>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                        <div style="background: #dbeafe; padding: 20px; border-radius: 12px; border-left: 4px solid #3b82f6;">
                            <div style="font-size: 14px; color: #1e40af; margin-bottom: 5px;">Monthly Donations</div>
                            <div style="font-size: 28px; font-weight: 800; color: #1e3a8a;">PKR <?php echo number_format($monthly_total); ?></div>
                        </div>
                        
                        <div style="background: #d1fae5; padding: 20px; border-radius: 12px; border-left: 4px solid #10b981;">
                            <div style="font-size: 14px; color: #065f46; margin-bottom: 5px;">Quarterly Donations</div>
                            <div style="font-size: 28px; font-weight: 800; color: #064e3b;">PKR <?php echo number_format($quarterly_total); ?></div>
                        </div>
                        
                        <div style="background: #fed7aa; padding: 20px; border-radius: 12px; border-left: 4px solid #f59e0b;">
                            <div style="font-size: 14px; color: #92400e; margin-bottom: 5px;">Yearly Donations</div>
                            <div style="font-size: 28px; font-weight: 800; color: #78350f;">PKR <?php echo number_format($yearly_total); ?></div>
                        </div>
                        
                        <div style="background: #e0e7ff; padding: 20px; border-radius: 12px; border-left: 4px solid #6366f1;">
                            <div style="font-size: 14px; color: #4338ca; margin-bottom: 5px;">Total Contributions</div>
                            <div style="font-size: 28px; font-weight: 800; color: #3730a3;">PKR <?php echo number_format($total_donations); ?></div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="sd-empty-state">
                    <i class="fas fa-file-invoice-dollar"></i>
                    <h3>No Payment History</h3>
                    <p>You haven't made any sponsorship payments yet.</p>
                    <button class="sd-btn" onclick="switchTab('browse')">
                        <i class="fas fa-heart"></i> Start Sponsoring
                    </button>
                </div>
            <?php endif; ?>
        </div>
        
    </div>
    
    <script>
    function switchTab(tabName) {
        // Hide all tabs
        document.querySelectorAll('.sd-tab-content').forEach(function(tab) {
            tab.classList.remove('active');
        });
        
        document.querySelectorAll('.sd-tab').forEach(function(btn) {
            btn.classList.remove('active');
        });
        
        // Show selected tab
        document.getElementById('tab-' + tabName).classList.add('active');
        event.target.classList.add('active');
        
        // Remove URL parameters
        const url = new URL(window.location.href);
        url.searchParams.delete('payment_submitted');
        url.searchParams.delete('open_tab');
        window.history.replaceState({}, '', url);
        
        // Scroll to top
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
    
    // ✅ NEW: Auto-dismiss success message
    setTimeout(function() {
        const successMsg = document.querySelector('.sd-success-message');
        if (successMsg) {
            successMsg.style.transition = 'opacity 0.5s';
            successMsg.style.opacity = '0';
            setTimeout(() => successMsg.remove(), 500);
        }
    }, 8000); // Auto-hide after 8 seconds
    </script>
    
    <?php
    return ob_get_clean();
}

// Redirect sponsors to dashboard after login
add_filter('login_redirect', 'sponsor_login_redirect', 10, 3);
function sponsor_login_redirect($redirect_to, $request, $user) {
    if (isset($user->roles) && is_array($user->roles)) {
        if (in_array('sponsor', $user->roles)) {
            $dashboard_page = get_page_by_path('sponsor-dashboard');
            if ($dashboard_page) {
                return get_permalink($dashboard_page->ID);
            }
            return home_url('/sponsor-dashboard/');
        }
    }
    return $redirect_to;
}
?>