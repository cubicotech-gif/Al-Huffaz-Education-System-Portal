<?php
/*
Plugin Name: Al-Huffaz Portal Hub (Sponsors Integrated)
Description: Central dashboard portal with sponsor management
Version: 2.0
Author: RoohUl Hasnain
*/

defined('ABSPATH') || exit;

// Register portal shortcode
add_shortcode('alhuffaz_portal', 'alhuffaz_portal_hub');

// Enqueue portal assets
add_action('wp_enqueue_scripts', 'portal_hub_assets');

function portal_hub_assets() {
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css');
    wp_enqueue_script('chartjs', 'https://cdn.jsdelivr.net/npm/chart.js', array('jquery'), '4.4.0', true);
    wp_enqueue_style('google-fonts', 'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
}

function alhuffaz_portal_hub() {
    ob_start();
    
    // Get active tab from URL
    $active_tab = isset($_GET['portal_tab']) ? sanitize_text_field($_GET['portal_tab']) : 'dashboard';
    
    // Calculate statistics
    $stats = calculate_portal_stats();
    
    ?>
    <div class="alhuffaz-portal-hub">
        
        <!-- Portal Header -->
        <div class="portal-header">
            <div class="portal-brand">
                <i class="fas fa-graduation-cap"></i>
                <div>
                    <h2>Al-Huffaz Education System</h2>
                    <p>Management Portal</p>
                </div>
            </div>
            <div class="portal-user">
                <i class="fas fa-user-circle"></i>
                <span><?php echo wp_get_current_user()->display_name; ?></span>
            </div>
        </div>
        
        <!-- Portal Navigation -->
        <nav class="portal-nav">
            <button class="nav-btn <?php echo $active_tab === 'dashboard' ? 'active' : ''; ?>" 
                    onclick="portalNavigate('dashboard')">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </button>
            
            <button class="nav-btn <?php echo $active_tab === 'students' ? 'active' : ''; ?>" 
                    onclick="portalNavigate('students')">
                <i class="fas fa-users"></i>
                <span>Students</span>
            </button>
            
            <button class="nav-btn <?php echo $active_tab === 'add' ? 'active' : ''; ?>" 
                    onclick="portalNavigate('add')">
                <i class="fas fa-plus-circle"></i>
                <span>Add Student</span>
            </button>
            
            <button class="nav-btn <?php echo $active_tab === 'import' ? 'active' : ''; ?>" 
                    onclick="portalNavigate('import')">
                <i class="fas fa-file-import"></i>
                <span>Bulk Import</span>
            </button>
            
            <!-- ✅ SPONSORS TAB (KEPT) -->
            <button class="nav-btn <?php echo $active_tab === 'sponsors' ? 'active' : ''; ?>" 
                    onclick="portalNavigate('sponsors')">
                <i class="fas fa-heart"></i>
                <span>Sponsors</span>
            </button>
            
            <button class="nav-btn <?php echo $active_tab === 'reports' ? 'active' : ''; ?>" 
                    onclick="portalNavigate('reports')">
                <i class="fas fa-chart-line"></i>
                <span>Reports</span>
            </button>
        </nav>
        
        <!-- Portal Content Area -->
        <div class="portal-content">
            
            <!-- DASHBOARD TAB -->
            <?php if ($active_tab === 'dashboard'): ?>
                <div class="portal-section active">
                    
                    <!-- Statistics Widgets -->
                    <div class="stats-widgets">
                        <div class="widget widget-blue">
                            <div class="widget-icon"><i class="fas fa-users"></i></div>
                            <div class="widget-data">
                                <div class="widget-number"><?php echo $stats['total_students']; ?></div>
                                <div class="widget-label">Total Students</div>
                            </div>
                        </div>
                        
                        <div class="widget widget-green">
                            <div class="widget-icon"><i class="fas fa-male"></i></div>
                            <div class="widget-data">
                                <div class="widget-number"><?php echo $stats['male_students']; ?></div>
                                <div class="widget-label">Male Students</div>
                            </div>
                        </div>
                        
                        <div class="widget widget-pink">
                            <div class="widget-icon"><i class="fas fa-female"></i></div>
                            <div class="widget-data">
                                <div class="widget-number"><?php echo $stats['female_students']; ?></div>
                                <div class="widget-label">Female Students</div>
                            </div>
                        </div>
                        
                        <div class="widget widget-purple">
                            <div class="widget-icon"><i class="fas fa-money-bill-wave"></i></div>
                            <div class="widget-data">
                                <div class="widget-number">PKR <?php echo number_format($stats['total_fees']); ?></div>
                                <div class="widget-label">Total Fees</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Charts Area -->
                    <div class="charts-area">
                        <div class="chart-widget">
                            <h3><i class="fas fa-chart-pie"></i> Students by Grade</h3>
                            <canvas id="portalGradeChart"></canvas>
                        </div>
                        
                        <div class="chart-widget">
                            <h3><i class="fas fa-chart-bar"></i> Gender Distribution</h3>
                            <canvas id="portalGenderChart"></canvas>
                        </div>
                    </div>
                    
                    <!-- Recent Students Section -->
                    <div class="portal-module">
                        <h3><i class="fas fa-clock"></i> Recent Students</h3>
                        <?php echo do_shortcode('[student_cards limit="6"]'); ?>
                    </div>
                    
                </div>
            <?php endif; ?>
            
            <!-- STUDENTS LIST TAB -->
            <?php if ($active_tab === 'students'): ?>
                <div class="portal-section active">
                    <div class="section-header">
                        <h2><i class="fas fa-users"></i> All Students</h2>
                    </div>
                    
                    <?php echo do_shortcode('[student_cards]'); ?>
                </div>
            <?php endif; ?>
            
            <!-- ADD/EDIT STUDENT TAB -->
            <?php if ($active_tab === 'add'): ?>
                <div class="portal-section active">
                    
                    <?php if (isset($_GET['edit']) && intval($_GET['edit']) > 0): ?>
                        <!-- EDIT MODE -->
                        <div class="section-header">
                            <h2><i class="fas fa-edit"></i> Edit Student</h2>
                        </div>
                        
                        <?php echo do_shortcode('[student_edit id="' . intval($_GET['edit']) . '"]'); ?>
                        
                    <?php else: ?>
                        <!-- ADD MODE -->
                        <div class="section-header">
                            <h2><i class="fas fa-plus-circle"></i> Add New Student</h2>
                        </div>
                        
                        <?php echo do_shortcode('[student_form]'); ?>
                    <?php endif; ?>
                    
                </div>
            <?php endif; ?>
            
            <!-- BULK IMPORT TAB -->
            <?php if ($active_tab === 'import'): ?>
                <div class="portal-section active">
                    <div class="section-header">
                        <h2><i class="fas fa-file-import"></i> Bulk Import Students</h2>
                    </div>
                    
                    <?php echo do_shortcode('[student_bulk_import]'); ?>
                </div>
            <?php endif; ?>
            
            <!-- ✅ SPONSORS TAB (FULLY INTEGRATED) -->
            <?php if ($active_tab === 'sponsors'): ?>
                <div class="portal-section active">
                    <div class="section-header">
                        <h2><i class="fas fa-heart"></i> Sponsor Management</h2>
                        <p style="margin: 10px 0 0 0; font-size: 14px; color: #64748b;">
                            Manage sponsor registrations, verify payments, and track active sponsorships
                        </p>
                    </div>
                    
                    <!-- ✅ SPONSOR ADMIN PANEL SHORTCODE -->
                    <?php echo do_shortcode('[sponsor-admin-panel]'); ?>
                </div>
            <?php endif; ?>
            
            <!-- REPORTS TAB -->
            <?php if ($active_tab === 'reports'): ?>
                <div class="portal-section active">
                    <div class="section-header">
                        <h2><i class="fas fa-chart-line"></i> Reports</h2>
                    </div>
                    
                    <?php echo do_shortcode('[student_reports]'); ?>
                </div>
            <?php endif; ?>
            
        </div>
    </div>
    
    <?php
    portal_add_scripts($stats);
    portal_add_styles();
    
    return ob_get_clean();
}

// Portal statistics calculation
function calculate_portal_stats() {
    $total = wp_count_posts('student')->publish ?? 0;
    
    $male_args = array(
        'post_type' => 'student',
        'post_status' => 'publish',
        'meta_query' => array(array('key' => 'gender', 'value' => 'male')),
        'fields' => 'ids'
    );
    $male = count(get_posts($male_args));
    
    $female_args = array(
        'post_type' => 'student',
        'post_status' => 'publish',
        'meta_query' => array(array('key' => 'gender', 'value' => 'female')),
        'fields' => 'ids'
    );
    $female = count(get_posts($female_args));
    
    $students = get_posts(array('post_type' => 'student', 'posts_per_page' => -1));
    $total_fees = 0;
    foreach ($students as $student) {
        $fee = get_post_meta($student->ID, 'monthly_tuition_fee', true);
        $total_fees += floatval($fee);
    }
    
    $grades_data = array();
    $grades = array('kg1', 'kg2', 'class1', 'class2', 'class3', 'level1', 'level2', 'level3', 'shb', 'shg');
    foreach ($grades as $grade) {
        $count = count(get_posts(array(
            'post_type' => 'student',
            'meta_query' => array(array('key' => 'grade_level', 'value' => $grade)),
            'fields' => 'ids'
        )));
        if ($count > 0) {
            $grades_data[$grade] = $count;
        }
    }
    
    return array(
        'total_students' => $total,
        'male_students' => $male,
        'female_students' => $female,
        'total_fees' => $total_fees,
        'grades_data' => $grades_data
    );
}

function portal_add_scripts($stats) {
    ?>
    <script>
    // Portal Navigation Function
    function portalNavigate(tab) {
        const url = new URL(window.location.href);
        url.searchParams.set('portal_tab', tab);
        // Remove edit parameter when navigating away from add tab
        if (tab !== 'add') {
            url.searchParams.delete('edit');
        }
        window.location.href = url.toString();
    }
    
    jQuery(document).ready(function($) {
        // Grade Chart
        const gradeCtx = document.getElementById('portalGradeChart');
        if (gradeCtx) {
            const gradesData = <?php echo json_encode($stats['grades_data']); ?>;
            new Chart(gradeCtx, {
                type: 'doughnut',
                data: {
                    labels: Object.keys(gradesData),
                    datasets: [{
                        data: Object.values(gradesData),
                        backgroundColor: ['#1e88e5', '#43a047', '#fb8c00', '#e53935', '#8e24aa', '#00acc1']
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { position: 'right' } }
                }
            });
        }
        
        // Gender Chart
        const genderCtx = document.getElementById('portalGenderChart');
        if (genderCtx) {
            new Chart(genderCtx, {
                type: 'bar',
                data: {
                    labels: ['Male', 'Female'],
                    datasets: [{
                        label: 'Students',
                        data: [<?php echo $stats['male_students']; ?>, <?php echo $stats['female_students']; ?>],
                        backgroundColor: ['#1e88e5', '#ec407a'],
                        borderRadius: 8
                    }]
                },
                options: {
                    responsive: true,
                    scales: { y: { beginAtZero: true } }
                }
            });
        }
    });
    </script>
    <?php
}

function portal_add_styles() {
    ?>
    <style>
    /* Portal Hub Styles */
    .alhuffaz-portal-hub {
        font-family: 'Inter', sans-serif;
        max-width: 1400px;
        margin: 0 auto;
        padding: 20px;
    }
    
    /* Header */
    .portal-header {
        background: linear-gradient(135deg, #1e88e5 0%, #0d47a1 100%);
        border-radius: 16px;
        padding: 30px;
        margin-bottom: 24px;
        color: white;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 8px 24px rgba(30, 136, 229, 0.3);
    }
    
    .portal-brand {
        display: flex;
        align-items: center;
        gap: 20px;
    }
    
    .portal-brand i {
        font-size: 48px;
    }
    
    .portal-brand h2 {
        margin: 0;
        font-size: 32px;
        font-weight: 700;
    }
    
    .portal-brand p {
        margin: 5px 0 0 0;
        opacity: 0.9;
    }
    
    .portal-user {
        display: flex;
        align-items: center;
        gap: 12px;
        background: rgba(255,255,255,0.2);
        padding: 12px 20px;
        border-radius: 10px;
    }
    
    .portal-user i {
        font-size: 24px;
    }
    
    /* Navigation */
    .portal-nav {
        background: white;
        border-radius: 12px;
        padding: 12px;
        margin-bottom: 24px;
        display: flex;
        gap: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        overflow-x: auto;
    }
    
    .nav-btn {
        flex: 1;
        min-width: 140px;
        padding: 14px 20px;
        border: none;
        border-radius: 8px;
        background: transparent;
        color: #666;
        font-weight: 600;
        font-size: 15px;
        cursor: pointer;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }
    
    .nav-btn:hover {
        background: #f5f5f5;
        color: #1e88e5;
    }
    
    .nav-btn.active {
        background: linear-gradient(135deg, #ec407a, #c2185b);
        color: white;
    }
    
    .nav-btn i {
        font-size: 18px;
    }
    
    /* Content Area */
    .portal-content {
        min-height: 500px;
    }
    
    .portal-section {
        display: none;
    }
    
    .portal-section.active {
        display: block;
        animation: fadeIn 0.3s;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    /* Widgets */
    .stats-widgets {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .widget {
        background: white;
        border-radius: 12px;
        padding: 24px;
        display: flex;
        align-items: center;
        gap: 20px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        transition: transform 0.3s;
    }
    
    .widget:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.12);
    }
    
    .widget-icon {
        width: 60px;
        height: 60px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 28px;
        color: white;
    }
    
    .widget-blue .widget-icon { background: linear-gradient(135deg, #1e88e5, #1565c0); }
    .widget-green .widget-icon { background: linear-gradient(135deg, #43a047, #2e7d32); }
    .widget-pink .widget-icon { background: linear-gradient(135deg, #ec407a, #c2185b); }
    .widget-purple .widget-icon { background: linear-gradient(135deg, #8e24aa, #6a1b9a); }
    
    .widget-number {
        font-size: 28px;
        font-weight: 700;
        color: #333;
        margin-bottom: 5px;
    }
    
    .widget-label {
        font-size: 13px;
        color: #666;
        font-weight: 500;
    }
    
    /* Charts */
    .charts-area {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .chart-widget {
        background: white;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }
    
    .chart-widget h3 {
        margin: 0 0 20px 0;
        font-size: 18px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 10px;
        color: #333;
    }
    
    .chart-widget canvas {
        max-height: 300px;
    }
    
    /* Portal Module */
    .portal-module {
        background: white;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }
    
    .portal-module h3 {
        margin: 0 0 20px 0;
        font-size: 20px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 12px;
        color: #333;
    }
    
    /* Section Header */
    .section-header {
        background: white;
        border-radius: 12px;
        padding: 20px 24px;
        margin-bottom: 24px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }
    
    .section-header h2 {
        margin: 0;
        font-size: 24px;
        font-weight: 700;
        color: #333;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .section-header p {
        margin: 10px 0 0 0;
        font-size: 14px;
        color: #64748b;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .portal-header {
            flex-direction: column;
            gap: 20px;
            text-align: center;
        }
        
        .portal-nav {
            flex-direction: column;
        }
        
        .nav-btn {
            min-width: 100%;
        }
        
        .stats-widgets,
        .charts-area {
            grid-template-columns: 1fr;
        }
    }
    </style>
    <?php
}
?>