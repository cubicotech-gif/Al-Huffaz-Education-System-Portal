<?php
/**
 * Single Student Profile Template
 * Al-Huffaz Education System Portal
 *
 * This template displays the full student profile with all data
 */

defined('ABSPATH') || exit;

get_header();

if (have_posts()) {
    while (have_posts()) {
        the_post();

        $student_id = get_the_ID();

        // Get ALL meta data (without underscore prefix)
        $student_data = array(
            'gr_number' => get_post_meta($student_id, 'gr_number', true),
            'roll_number' => get_post_meta($student_id, 'roll_number', true),
            'gender' => get_post_meta($student_id, 'gender', true),
            'date_of_birth' => get_post_meta($student_id, 'date_of_birth', true),
            'admission_date' => get_post_meta($student_id, 'admission_date', true),
            'grade_level' => get_post_meta($student_id, 'grade_level', true),
            'islamic_studies_category' => get_post_meta($student_id, 'islamic_studies_category', true),
            'permanent_address' => get_post_meta($student_id, 'permanent_address', true),
            'current_address' => get_post_meta($student_id, 'current_address', true),
            'father_name' => get_post_meta($student_id, 'father_name', true),
            'father_cnic' => get_post_meta($student_id, 'father_cnic', true),
            'father_email' => get_post_meta($student_id, 'father_email', true),
            'guardian_name' => get_post_meta($student_id, 'guardian_name', true),
            'guardian_cnic' => get_post_meta($student_id, 'guardian_cnic', true),
            'guardian_email' => get_post_meta($student_id, 'guardian_email', true),
            'guardian_phone' => get_post_meta($student_id, 'guardian_phone', true),
            'guardian_whatsapp' => get_post_meta($student_id, 'guardian_whatsapp', true),
            'relationship_to_student' => get_post_meta($student_id, 'relationship_to_student', true),
            'emergency_contact' => get_post_meta($student_id, 'emergency_contact', true),
            'emergency_whatsapp' => get_post_meta($student_id, 'emergency_whatsapp', true),
            'monthly_tuition_fee' => get_post_meta($student_id, 'monthly_tuition_fee', true),
            'course_fee' => get_post_meta($student_id, 'course_fee', true),
            'uniform_fee' => get_post_meta($student_id, 'uniform_fee', true),
            'annual_fee' => get_post_meta($student_id, 'annual_fee', true),
            'zakat_eligible' => get_post_meta($student_id, 'zakat_eligible', true),
            'donation_eligible' => get_post_meta($student_id, 'donation_eligible', true),
            'blood_group' => get_post_meta($student_id, 'blood_group', true),
            'allergies' => get_post_meta($student_id, 'allergies', true),
            'medical_conditions' => get_post_meta($student_id, 'medical_conditions', true),
            'total_school_days' => get_post_meta($student_id, 'total_school_days', true),
            'present_days' => get_post_meta($student_id, 'present_days', true),
            'academic_term' => get_post_meta($student_id, 'academic_term', true),
            'academic_year' => get_post_meta($student_id, 'academic_year', true),
            'health_rating' => get_post_meta($student_id, 'health_rating', true),
            'cleanness_rating' => get_post_meta($student_id, 'cleanness_rating', true),
            'completes_homework' => get_post_meta($student_id, 'completes_homework', true),
            'participates_in_class' => get_post_meta($student_id, 'participates_in_class', true),
            'works_well_in_groups' => get_post_meta($student_id, 'works_well_in_groups', true),
            'problem_solving_skills' => get_post_meta($student_id, 'problem_solving_skills', true),
            'organization_preparedness' => get_post_meta($student_id, 'organization_preparedness', true),
            'teacher_overall_comments' => get_post_meta($student_id, 'teacher_overall_comments', true),
            'goal_1' => get_post_meta($student_id, 'goal_1', true),
            'goal_2' => get_post_meta($student_id, 'goal_2', true),
            'goal_3' => get_post_meta($student_id, 'goal_3', true),
            'student_photo' => get_post_meta($student_id, 'student_photo', true),
        );

        $subjects = get_post_meta($student_id, 'subjects', true);
        if (!is_array($subjects)) {
            $subjects = array();
        }

        $photo_url = '';
        if (!empty($student_data['student_photo'])) {
            $photo_url = wp_get_attachment_image_url($student_data['student_photo'], 'medium');
        }

        $grade_map = array(
            'kg1' => 'KG 1', 'kg2' => 'KG 2',
            'class1' => 'CLASS 1', 'class2' => 'CLASS 2', 'class3' => 'CLASS 3',
            'level1' => 'LEVEL 1', 'level2' => 'LEVEL 2', 'level3' => 'LEVEL 3',
            'shb' => 'SHB', 'shg' => 'SHG'
        );

        $grade_display = isset($grade_map[$student_data['grade_level']])
            ? $grade_map[$student_data['grade_level']]
            : ucfirst($student_data['grade_level']);

        $islamic_map = array('hifz' => 'Hifz', 'nazra' => 'Nazra', 'qaidah' => 'Qaidah');
        $islamic_display = isset($islamic_map[$student_data['islamic_studies_category']])
            ? $islamic_map[$student_data['islamic_studies_category']]
            : ucfirst($student_data['islamic_studies_category']);

        $term_map = array(
            'term1' => 'Term 1', 'term2' => 'Term 2', 'term3' => 'Term 3',
            'semester1' => 'Semester 1', 'semester2' => 'Semester 2', 'annual' => 'Annual'
        );
        $term_display = isset($term_map[$student_data['academic_term']])
            ? $term_map[$student_data['academic_term']]
            : ucfirst($student_data['academic_term']);

        // Edit URL for admin users
        $edit_url = current_user_can('edit_posts') ? admin_url('admin.php?page=alhuffaz-add-student&id=' . $student_id) : '';
        ?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

<style>
/* BASE STYLES */
:root {
    --primary: #0080ff;
    --primary-dark: #004d99;
    --secondary: #0066cc;
    --light-blue: #e6f2ff;
    --blue-100: #b3d9ff;
    --success: #10b981;
    --warning: #f59e0b;
    --danger: #ef4444;
    --text-dark: #001a33;
    --text-body: #00264d;
    --text-muted: #64748b;
    --border: #cce6ff;
    --bg-light: #f0f8ff;
}

.sp-container {
    max-width: 1400px;
    margin: 40px auto;
    padding: 0 24px;
    font-family: 'Poppins', sans-serif;
}

.sp-wrapper {
    background: white;
    border-radius: 16px;
    box-shadow: 0 8px 32px rgba(0, 128, 255, 0.12);
    overflow: hidden;
}

/* HEADER */
.sp-header {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    color: white;
    padding: 36px 40px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 24px;
}

.sp-header-info h1 {
    margin: 0 0 8px 0;
    font-size: 36px;
    font-weight: 800;
}

.sp-header-info p {
    margin: 0;
    opacity: 0.95;
    font-size: 16px;
}

.sp-buttons {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.sp-btn {
    padding: 12px 24px;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    text-decoration: none;
    transition: all 0.3s;
    font-family: 'Poppins', sans-serif;
    font-size: 15px;
}

.sp-btn-print {
    background: rgba(255,255,255,0.2);
    color: white;
    border: 2px solid rgba(255,255,255,0.5);
}

.sp-btn-edit {
    background: white;
    color: var(--primary);
}

.sp-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}

/* TABS */
.sp-tabs {
    display: flex;
    background: white;
    border-bottom: 3px solid var(--border);
    padding: 0 40px;
    gap: 8px;
    overflow-x: auto;
}

.sp-tab {
    padding: 18px 28px;
    border: none;
    background: transparent;
    color: var(--text-muted);
    font-weight: 600;
    cursor: pointer;
    border-bottom: 4px solid transparent;
    transition: all 0.3s;
    white-space: nowrap;
    font-size: 15px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.sp-tab:hover,
.sp-tab.active {
    color: var(--primary);
    border-bottom-color: var(--primary);
    background: var(--bg-light);
}

/* CONTENT */
.sp-content {
    padding: 40px;
}

.sp-panel {
    display: none;
}

.sp-panel.active {
    display: block;
    animation: fadeIn 0.4s;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.sp-section-title {
    margin: 0 0 32px 0;
    font-size: 28px;
    font-weight: 700;
    color: var(--text-dark);
    display: flex;
    align-items: center;
    gap: 12px;
}

.sp-section-title i {
    color: var(--primary);
}

.sp-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 28px;
    margin-bottom: 28px;
}

.sp-card {
    background: white;
    border: 2px solid var(--border);
    border-radius: 14px;
    padding: 28px;
    box-shadow: 0 4px 16px rgba(0, 128, 255, 0.08);
}

.sp-card-title {
    margin: 0 0 24px 0;
    font-size: 20px;
    font-weight: 700;
    color: var(--text-dark);
    display: flex;
    align-items: center;
    gap: 12px;
    padding-bottom: 16px;
    border-bottom: 3px solid var(--light-blue);
}

.sp-card-title i {
    font-size: 22px;
    color: var(--primary);
}

/* PROFILE SECTION */
.sp-profile-card {
    background: linear-gradient(135deg, var(--bg-light) 0%, white 100%);
    border: 2px solid var(--blue-100);
}

.sp-profile-header {
    display: flex;
    gap: 28px;
    align-items: flex-start;
    margin-bottom: 28px;
    padding-bottom: 24px;
    border-bottom: 2px solid var(--border);
}

.sp-photo img {
    width: 160px;
    height: 160px;
    border-radius: 50%;
    object-fit: cover;
    border: 6px solid white;
    box-shadow: 0 8px 24px rgba(0, 128, 255, 0.2);
}

.sp-photo-placeholder {
    width: 160px;
    height: 160px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 64px;
    font-weight: 800;
    border: 6px solid white;
    box-shadow: 0 8px 24px rgba(0, 128, 255, 0.2);
}

.sp-profile-info h2 {
    margin: 0 0 12px 0;
    font-size: 32px;
    font-weight: 800;
    color: var(--text-dark);
}

.sp-badges {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 16px;
}

.sp-badge {
    padding: 8px 20px;
    border-radius: 24px;
    font-size: 14px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.sp-badge-primary {
    background: var(--primary);
    color: white;
}

.sp-badge-light {
    background: var(--blue-100);
    color: var(--primary-dark);
}

.sp-badge-success {
    background: #d1fae5;
    color: #065f46;
}

/* INFO GRID */
.sp-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 16px;
}

.sp-info-item {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 16px;
    background: white;
    border: 2px solid var(--border);
    border-radius: 12px;
}

.sp-info-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: var(--light-blue);
    color: var(--primary);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    flex-shrink: 0;
}

.sp-info-content label {
    display: block;
    font-size: 12px;
    color: var(--text-muted);
    margin-bottom: 4px;
    font-weight: 500;
    text-transform: uppercase;
}

.sp-info-content strong {
    font-size: 16px;
    color: var(--text-dark);
    font-weight: 600;
}

/* DATA LIST */
.sp-data-list {
    display: grid;
    gap: 12px;
}

.sp-data-row {
    display: flex;
    justify-content: space-between;
    padding: 14px 18px;
    background: var(--bg-light);
    border-radius: 10px;
    border-left: 4px solid var(--primary);
    gap: 20px;
    align-items: center;
}

.sp-data-label {
    font-weight: 600;
    color: var(--text-muted);
    font-size: 14px;
}

.sp-data-value {
    font-weight: 600;
    color: var(--text-dark);
    text-align: right;
    font-size: 15px;
}

/* FEES */
.sp-fee-grid {
    display: grid;
    gap: 14px;
}

.sp-fee-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 20px;
    background: var(--bg-light);
    border-radius: 10px;
    border-left: 4px solid var(--primary);
}

.sp-fee-item.total {
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    color: white;
    font-weight: 700;
    margin-top: 12px;
    padding: 20px;
}

/* ATTENDANCE */
.sp-attendance-wrapper {
    display: flex;
    gap: 36px;
    align-items: center;
}

.sp-att-circle {
    width: 160px;
    height: 160px;
    flex-shrink: 0;
}

.sp-att-stats {
    display: grid;
    gap: 14px;
    flex: 1;
}

.sp-att-stat {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 14px 18px;
    background: var(--bg-light);
    border-radius: 10px;
    border-left: 4px solid var(--primary);
}

/* BLOOD GROUP */
.sp-blood-box {
    display: flex;
    align-items: center;
    gap: 24px;
    padding: 32px;
    background: linear-gradient(135deg, #fee2e2, #fecaca);
    border-radius: 14px;
    border: 2px solid #fca5a5;
    margin-bottom: 28px;
}

.sp-blood-icon {
    font-size: 48px;
    color: var(--danger);
}

.sp-blood-value {
    font-size: 32px;
    font-weight: 800;
    color: var(--danger);
}

/* NO DATA */
.sp-no-data {
    text-align: center;
    padding: 60px 20px;
    color: var(--text-muted);
}

.sp-no-data i {
    font-size: 64px;
    margin-bottom: 20px;
    color: var(--blue-100);
}

/* RESPONSIVE */
@media (max-width: 768px) {
    .sp-container { padding: 12px; margin: 20px auto; }
    .sp-header { flex-direction: column; padding: 24px 20px; }
    .sp-header-info h1 { font-size: 26px; }
    .sp-tabs { padding: 0 16px; }
    .sp-tab { padding: 14px 16px; font-size: 13px; }
    .sp-content { padding: 24px 16px; }
    .sp-profile-header { flex-direction: column; text-align: center; }
    .sp-attendance-wrapper { flex-direction: column; }
    .sp-grid { grid-template-columns: 1fr; }
}

@media print {
    .no-print { display: none !important; }
    .sp-wrapper { box-shadow: none; }
    .sp-panel { display: block !important; page-break-after: always; }
}
</style>

<div class="sp-container">
    <div class="sp-wrapper">

        <!-- HEADER -->
        <div class="sp-header no-print">
            <div class="sp-header-info">
                <h1><?php the_title(); ?></h1>
                <p><i class="fas fa-id-card"></i> GR: <strong><?php echo esc_html($student_data['gr_number'] ?: 'N/A'); ?></strong> | Roll: <strong><?php echo esc_html($student_data['roll_number'] ?: 'N/A'); ?></strong></p>
            </div>
            <div class="sp-buttons">
                <button onclick="window.print()" class="sp-btn sp-btn-print">
                    <i class="fas fa-print"></i> Print
                </button>
                <?php if ($edit_url): ?>
                <a href="<?php echo esc_url($edit_url); ?>" class="sp-btn sp-btn-edit">
                    <i class="fas fa-edit"></i> Edit
                </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- TABS -->
        <div class="sp-tabs no-print">
            <button class="sp-tab active" data-tab="overview"><i class="fas fa-home"></i> Overview</button>
            <button class="sp-tab" data-tab="personal"><i class="fas fa-user"></i> Personal</button>
            <button class="sp-tab" data-tab="family"><i class="fas fa-users"></i> Family</button>
            <button class="sp-tab" data-tab="academic"><i class="fas fa-graduation-cap"></i> Academic</button>
            <button class="sp-tab" data-tab="financial"><i class="fas fa-dollar-sign"></i> Financial</button>
            <button class="sp-tab" data-tab="health"><i class="fas fa-heartbeat"></i> Health</button>
        </div>

        <!-- CONTENT -->
        <div class="sp-content">

            <!-- OVERVIEW TAB -->
            <div class="sp-panel active" id="overview">
                <h2 class="sp-section-title"><i class="fas fa-dashboard"></i> Student Overview</h2>

                <div class="sp-card sp-profile-card">
                    <div class="sp-profile-header">
                        <div class="sp-photo-wrapper">
                            <?php if ($photo_url): ?>
                                <div class="sp-photo"><img src="<?php echo esc_url($photo_url); ?>" alt="<?php the_title(); ?>"></div>
                            <?php else: ?>
                                <div class="sp-photo-placeholder"><?php echo esc_html(strtoupper(substr(get_the_title(), 0, 1))); ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="sp-profile-info">
                            <h2><?php the_title(); ?></h2>
                            <div class="sp-badges">
                                <?php if ($grade_display): ?>
                                    <span class="sp-badge sp-badge-primary"><i class="fas fa-layer-group"></i> <?php echo esc_html($grade_display); ?></span>
                                <?php endif; ?>
                                <?php if ($student_data['gender']): ?>
                                    <span class="sp-badge sp-badge-light"><i class="fas fa-venus-mars"></i> <?php echo esc_html(ucfirst($student_data['gender'])); ?></span>
                                <?php endif; ?>
                                <?php if ($islamic_display): ?>
                                    <span class="sp-badge sp-badge-success"><i class="fas fa-quran"></i> <?php echo esc_html($islamic_display); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="sp-info-grid">
                        <div class="sp-info-item">
                            <div class="sp-info-icon"><i class="fas fa-id-card"></i></div>
                            <div class="sp-info-content">
                                <label>GR Number</label>
                                <strong><?php echo esc_html($student_data['gr_number'] ?: 'N/A'); ?></strong>
                            </div>
                        </div>
                        <div class="sp-info-item">
                            <div class="sp-info-icon"><i class="fas fa-hashtag"></i></div>
                            <div class="sp-info-content">
                                <label>Roll Number</label>
                                <strong><?php echo esc_html($student_data['roll_number'] ?: 'N/A'); ?></strong>
                            </div>
                        </div>
                        <div class="sp-info-item">
                            <div class="sp-info-icon"><i class="fas fa-birthday-cake"></i></div>
                            <div class="sp-info-content">
                                <label>Date of Birth</label>
                                <strong><?php echo $student_data['date_of_birth'] ? date('F j, Y', strtotime($student_data['date_of_birth'])) : 'N/A'; ?></strong>
                            </div>
                        </div>
                        <div class="sp-info-item">
                            <div class="sp-info-icon"><i class="fas fa-calendar-check"></i></div>
                            <div class="sp-info-content">
                                <label>Admission Date</label>
                                <strong><?php echo $student_data['admission_date'] ? date('F j, Y', strtotime($student_data['admission_date'])) : 'N/A'; ?></strong>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if ($student_data['total_school_days'] && $student_data['present_days']):
                    $att_pct = round(($student_data['present_days'] / $student_data['total_school_days']) * 100);
                ?>
                <div class="sp-card" style="margin-top: 28px;">
                    <h3 class="sp-card-title"><i class="fas fa-clipboard-check"></i> Attendance Record</h3>
                    <div class="sp-attendance-wrapper">
                        <div class="sp-att-circle">
                            <svg viewBox="0 0 100 100">
                                <circle cx="50" cy="50" r="40" fill="none" stroke="#e6f2ff" stroke-width="10"></circle>
                                <circle cx="50" cy="50" r="40" fill="none" stroke="#0080ff" stroke-width="10"
                                        stroke-dasharray="<?php echo $att_pct * 2.513; ?> 251.3"
                                        transform="rotate(-90 50 50)" stroke-linecap="round"></circle>
                                <text x="50" y="55" text-anchor="middle" font-size="20" font-weight="bold" fill="#0080ff"><?php echo $att_pct; ?>%</text>
                            </svg>
                        </div>
                        <div class="sp-att-stats">
                            <div class="sp-att-stat"><span>Total School Days:</span><strong><?php echo esc_html($student_data['total_school_days']); ?></strong></div>
                            <div class="sp-att-stat"><span>Present Days:</span><strong><?php echo esc_html($student_data['present_days']); ?></strong></div>
                            <div class="sp-att-stat"><span>Attendance Rate:</span><strong style="color: var(--primary);"><?php echo $att_pct; ?>%</strong></div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- PERSONAL TAB -->
            <div class="sp-panel" id="personal">
                <h2 class="sp-section-title"><i class="fas fa-user"></i> Personal Information</h2>
                <div class="sp-grid">
                    <div class="sp-card">
                        <h3 class="sp-card-title"><i class="fas fa-info-circle"></i> Basic Details</h3>
                        <div class="sp-data-list">
                            <div class="sp-data-row"><span class="sp-data-label">Full Name:</span><span class="sp-data-value"><?php the_title(); ?></span></div>
                            <div class="sp-data-row"><span class="sp-data-label">GR Number:</span><span class="sp-data-value"><?php echo esc_html($student_data['gr_number'] ?: 'N/A'); ?></span></div>
                            <div class="sp-data-row"><span class="sp-data-label">Roll Number:</span><span class="sp-data-value"><?php echo esc_html($student_data['roll_number'] ?: 'N/A'); ?></span></div>
                            <div class="sp-data-row"><span class="sp-data-label">Gender:</span><span class="sp-data-value"><?php echo esc_html(ucfirst($student_data['gender']) ?: 'N/A'); ?></span></div>
                            <div class="sp-data-row"><span class="sp-data-label">Date of Birth:</span><span class="sp-data-value"><?php echo $student_data['date_of_birth'] ? date('F j, Y', strtotime($student_data['date_of_birth'])) : 'N/A'; ?></span></div>
                        </div>
                    </div>
                    <div class="sp-card">
                        <h3 class="sp-card-title"><i class="fas fa-school"></i> Academic Details</h3>
                        <div class="sp-data-list">
                            <div class="sp-data-row"><span class="sp-data-label">Admission Date:</span><span class="sp-data-value"><?php echo $student_data['admission_date'] ? date('F j, Y', strtotime($student_data['admission_date'])) : 'N/A'; ?></span></div>
                            <div class="sp-data-row"><span class="sp-data-label">Grade Level:</span><span class="sp-data-value"><?php echo esc_html($grade_display ?: 'N/A'); ?></span></div>
                            <div class="sp-data-row"><span class="sp-data-label">Islamic Studies:</span><span class="sp-data-value"><?php echo esc_html($islamic_display ?: 'N/A'); ?></span></div>
                            <div class="sp-data-row"><span class="sp-data-label">Academic Term:</span><span class="sp-data-value"><?php echo esc_html($term_display ?: 'N/A'); ?></span></div>
                            <div class="sp-data-row"><span class="sp-data-label">Academic Year:</span><span class="sp-data-value"><?php echo esc_html($student_data['academic_year'] ?: 'N/A'); ?></span></div>
                        </div>
                    </div>
                </div>

                <div class="sp-card" style="margin-top: 28px;">
                    <h3 class="sp-card-title"><i class="fas fa-map-marker-alt"></i> Address</h3>
                    <div class="sp-data-list">
                        <div class="sp-data-row"><span class="sp-data-label">Permanent Address:</span><span class="sp-data-value"><?php echo esc_html($student_data['permanent_address'] ?: 'Not provided'); ?></span></div>
                        <div class="sp-data-row"><span class="sp-data-label">Current Address:</span><span class="sp-data-value"><?php echo esc_html($student_data['current_address'] ?: 'Same as permanent'); ?></span></div>
                    </div>
                </div>
            </div>

            <!-- FAMILY TAB -->
            <div class="sp-panel" id="family">
                <h2 class="sp-section-title"><i class="fas fa-users"></i> Family Information</h2>
                <div class="sp-grid">
                    <div class="sp-card">
                        <h3 class="sp-card-title"><i class="fas fa-user-tie"></i> Father Information</h3>
                        <div class="sp-data-list">
                            <div class="sp-data-row"><span class="sp-data-label">Name:</span><span class="sp-data-value"><?php echo esc_html($student_data['father_name'] ?: 'N/A'); ?></span></div>
                            <div class="sp-data-row"><span class="sp-data-label">CNIC:</span><span class="sp-data-value"><?php echo esc_html($student_data['father_cnic'] ?: 'N/A'); ?></span></div>
                            <div class="sp-data-row"><span class="sp-data-label">Email:</span><span class="sp-data-value"><?php echo esc_html($student_data['father_email'] ?: 'N/A'); ?></span></div>
                        </div>
                    </div>
                    <div class="sp-card">
                        <h3 class="sp-card-title"><i class="fas fa-user-shield"></i> Guardian Information</h3>
                        <div class="sp-data-list">
                            <div class="sp-data-row"><span class="sp-data-label">Name:</span><span class="sp-data-value"><?php echo esc_html($student_data['guardian_name'] ?: 'N/A'); ?></span></div>
                            <div class="sp-data-row"><span class="sp-data-label">Relationship:</span><span class="sp-data-value"><?php echo esc_html($student_data['relationship_to_student'] ?: 'N/A'); ?></span></div>
                            <div class="sp-data-row"><span class="sp-data-label">Phone:</span><span class="sp-data-value"><?php echo esc_html($student_data['guardian_phone'] ?: 'N/A'); ?></span></div>
                            <div class="sp-data-row"><span class="sp-data-label">WhatsApp:</span><span class="sp-data-value"><?php echo esc_html($student_data['guardian_whatsapp'] ?: 'N/A'); ?></span></div>
                            <div class="sp-data-row"><span class="sp-data-label">Email:</span><span class="sp-data-value"><?php echo esc_html($student_data['guardian_email'] ?: 'N/A'); ?></span></div>
                        </div>
                    </div>
                </div>

                <div class="sp-card" style="margin-top: 28px;">
                    <h3 class="sp-card-title"><i class="fas fa-phone-alt"></i> Emergency Contact</h3>
                    <div class="sp-data-list">
                        <div class="sp-data-row"><span class="sp-data-label">Emergency Phone:</span><span class="sp-data-value"><?php echo esc_html($student_data['emergency_contact'] ?: 'N/A'); ?></span></div>
                        <div class="sp-data-row"><span class="sp-data-label">Emergency WhatsApp:</span><span class="sp-data-value"><?php echo esc_html($student_data['emergency_whatsapp'] ?: 'N/A'); ?></span></div>
                    </div>
                </div>
            </div>

            <!-- ACADEMIC TAB -->
            <div class="sp-panel" id="academic">
                <h2 class="sp-section-title"><i class="fas fa-graduation-cap"></i> Academic Performance</h2>

                <?php if (!empty($subjects)): ?>
                    <?php foreach ($subjects as $subject): ?>
                    <div class="sp-card" style="margin-bottom: 28px;">
                        <h3 class="sp-card-title"><i class="fas fa-book-open"></i> <?php echo esc_html($subject['name']); ?></h3>

                        <?php if (!empty($subject['monthly_exams'])): ?>
                        <h4 style="margin: 16px 0 12px; color: var(--text-dark);"><i class="fas fa-calendar"></i> Monthly Exams</h4>
                        <?php foreach ($subject['monthly_exams'] as $exam): ?>
                        <div class="sp-data-list" style="margin-bottom: 16px;">
                            <div class="sp-data-row"><span class="sp-data-label"><?php echo esc_html($exam['month_name'] ?: 'Monthly'); ?>:</span><span class="sp-data-value"><?php echo esc_html($exam['overall_obtained']); ?>/<?php echo esc_html($exam['overall_total']); ?> (<?php echo esc_html($exam['percentage']); ?>% - <?php echo esc_html($exam['grade']); ?>)</span></div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>

                        <?php if (!empty($subject['mid_semester']['overall_total'])): ?>
                        <div class="sp-data-list" style="margin-bottom: 16px;">
                            <div class="sp-data-row"><span class="sp-data-label">Mid Semester:</span><span class="sp-data-value"><?php echo esc_html($subject['mid_semester']['overall_obtained']); ?>/<?php echo esc_html($subject['mid_semester']['overall_total']); ?> (<?php echo esc_html($subject['mid_semester']['percentage']); ?>% - <?php echo esc_html($subject['mid_semester']['grade']); ?>)</span></div>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($subject['final_semester']['overall_total'])): ?>
                        <div class="sp-data-list">
                            <div class="sp-data-row"><span class="sp-data-label">Final Semester:</span><span class="sp-data-value"><?php echo esc_html($subject['final_semester']['overall_obtained']); ?>/<?php echo esc_html($subject['final_semester']['overall_total']); ?> (<?php echo esc_html($subject['final_semester']['percentage']); ?>% - <?php echo esc_html($subject['final_semester']['grade']); ?>)</span></div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="sp-no-data">
                        <i class="fas fa-book"></i>
                        <p>No academic records available yet</p>
                    </div>
                <?php endif; ?>

                <?php if ($student_data['teacher_overall_comments']): ?>
                <div class="sp-card">
                    <h3 class="sp-card-title"><i class="fas fa-comment-alt"></i> Teacher's Comments</h3>
                    <p style="padding: 16px; background: var(--bg-light); border-radius: 10px; line-height: 1.8;"><?php echo nl2br(esc_html($student_data['teacher_overall_comments'])); ?></p>
                </div>
                <?php endif; ?>
            </div>

            <!-- FINANCIAL TAB -->
            <div class="sp-panel" id="financial">
                <h2 class="sp-section-title"><i class="fas fa-dollar-sign"></i> Financial Information</h2>

                <div class="sp-grid">
                    <div class="sp-card">
                        <h3 class="sp-card-title"><i class="fas fa-money-bill-wave"></i> Fee Structure</h3>
                        <?php
                        $fees = array();
                        if ($student_data['monthly_tuition_fee']) $fees['Monthly Tuition'] = $student_data['monthly_tuition_fee'];
                        if ($student_data['course_fee']) $fees['Course Fee'] = $student_data['course_fee'];
                        if ($student_data['uniform_fee']) $fees['Uniform Fee'] = $student_data['uniform_fee'];
                        if ($student_data['annual_fee']) $fees['Annual Fee'] = $student_data['annual_fee'];
                        $total_fees = array_sum($fees);
                        ?>

                        <?php if (!empty($fees)): ?>
                        <div class="sp-fee-grid">
                            <?php foreach ($fees as $type => $amount): ?>
                            <div class="sp-fee-item"><span><?php echo esc_html($type); ?></span><span>PKR <?php echo number_format($amount); ?></span></div>
                            <?php endforeach; ?>
                            <div class="sp-fee-item total"><span>TOTAL</span><span>PKR <?php echo number_format($total_fees); ?></span></div>
                        </div>
                        <?php else: ?>
                        <div class="sp-no-data"><i class="fas fa-info-circle"></i><p>No fee information</p></div>
                        <?php endif; ?>
                    </div>

                    <div class="sp-card">
                        <h3 class="sp-card-title"><i class="fas fa-hand-holding-heart"></i> Aid Eligibility</h3>
                        <div class="sp-data-list">
                            <div class="sp-data-row">
                                <span class="sp-data-label">Zakat Eligible:</span>
                                <span class="sp-data-value" style="color: <?php echo $student_data['zakat_eligible'] === 'yes' ? 'var(--success)' : 'var(--danger)'; ?>">
                                    <i class="fas fa-<?php echo $student_data['zakat_eligible'] === 'yes' ? 'check-circle' : 'times-circle'; ?>"></i>
                                    <?php echo $student_data['zakat_eligible'] === 'yes' ? 'Yes' : 'No'; ?>
                                </span>
                            </div>
                            <div class="sp-data-row">
                                <span class="sp-data-label">Donation Eligible:</span>
                                <span class="sp-data-value" style="color: <?php echo $student_data['donation_eligible'] === 'yes' ? 'var(--success)' : 'var(--danger)'; ?>">
                                    <i class="fas fa-<?php echo $student_data['donation_eligible'] === 'yes' ? 'check-circle' : 'times-circle'; ?>"></i>
                                    <?php echo $student_data['donation_eligible'] === 'yes' ? 'Yes' : 'No'; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- HEALTH TAB -->
            <div class="sp-panel" id="health">
                <h2 class="sp-section-title"><i class="fas fa-heartbeat"></i> Health Information</h2>

                <div class="sp-card">
                    <h3 class="sp-card-title"><i class="fas fa-notes-medical"></i> Health Records</h3>

                    <div class="sp-blood-box">
                        <div class="sp-blood-icon"><i class="fas fa-tint"></i></div>
                        <div><label style="display: block; font-size: 13px; color: #991b1b; margin-bottom: 8px; font-weight: 600;">Blood Group</label><div class="sp-blood-value"><?php echo esc_html($student_data['blood_group'] ?: 'Not Specified'); ?></div></div>
                    </div>

                    <?php if ($student_data['health_rating'] || $student_data['cleanness_rating']): ?>
                    <div class="sp-data-list" style="margin-bottom: 24px;">
                        <?php if ($student_data['health_rating']): ?>
                        <div class="sp-data-row"><span class="sp-data-label">Health Rating:</span><span class="sp-data-value"><?php echo esc_html(ucfirst($student_data['health_rating'])); ?></span></div>
                        <?php endif; ?>
                        <?php if ($student_data['cleanness_rating']): ?>
                        <div class="sp-data-row"><span class="sp-data-label">Cleanliness Rating:</span><span class="sp-data-value"><?php echo esc_html(ucfirst($student_data['cleanness_rating'])); ?></span></div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <?php if ($student_data['allergies']): ?>
                    <div style="margin-bottom: 16px;">
                        <h4 style="margin-bottom: 8px; color: var(--text-dark);"><i class="fas fa-allergies"></i> Allergies</h4>
                        <p style="padding: 16px; background: var(--bg-light); border-radius: 10px;"><?php echo nl2br(esc_html($student_data['allergies'])); ?></p>
                    </div>
                    <?php endif; ?>

                    <?php if ($student_data['medical_conditions']): ?>
                    <div>
                        <h4 style="margin-bottom: 8px; color: var(--text-dark);"><i class="fas fa-file-medical"></i> Medical Conditions</h4>
                        <p style="padding: 16px; background: var(--bg-light); border-radius: 10px;"><?php echo nl2br(esc_html($student_data['medical_conditions'])); ?></p>
                    </div>
                    <?php endif; ?>

                    <?php if (!$student_data['allergies'] && !$student_data['medical_conditions']): ?>
                    <div class="sp-no-data"><i class="fas fa-check-circle"></i><p>No health concerns reported</p></div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tabs = document.querySelectorAll('.sp-tab');
    const panels = document.querySelectorAll('.sp-panel');

    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const target = this.dataset.tab;
            tabs.forEach(t => t.classList.remove('active'));
            panels.forEach(p => p.classList.remove('active'));
            this.classList.add('active');
            document.getElementById(target).classList.add('active');
        });
    });
});
</script>

        <?php
    }
}

get_footer();
