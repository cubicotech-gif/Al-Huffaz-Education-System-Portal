<?php
/**
 * Single Student Profile Template
 * Al-Huffaz Education System Portal
 *
 * Displays the complete student profile with result card format
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
            'admission_fee' => get_post_meta($student_id, 'admission_fee', true),
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

        // Grade mapping
        $grade_map = array(
            'kg1' => 'KG 1', 'kg2' => 'KG 2',
            'class1' => 'Class 1', 'class2' => 'Class 2', 'class3' => 'Class 3',
            'level1' => 'Level 1', 'level2' => 'Level 2', 'level3' => 'Level 3',
            'shb' => 'SHB', 'shg' => 'SHG'
        );
        $grade_display = isset($grade_map[$student_data['grade_level']])
            ? $grade_map[$student_data['grade_level']]
            : ucfirst($student_data['grade_level']);

        // Islamic category mapping
        $islamic_map = array('hifz' => 'Hifz', 'nazra' => 'Nazra', 'qaidah' => 'Qaidah');
        $islamic_display = isset($islamic_map[$student_data['islamic_studies_category']])
            ? $islamic_map[$student_data['islamic_studies_category']]
            : ucfirst($student_data['islamic_studies_category']);

        // Term mapping (only Mid and Annual)
        $term_map = array('mid' => 'Mid Term', 'annual' => 'Annual');
        $term_display = isset($term_map[$student_data['academic_term']])
            ? $term_map[$student_data['academic_term']]
            : ucfirst($student_data['academic_term']);

        // Calculate grade from percentage
        function sp_get_grade($pct) {
            if ($pct >= 90) return 'A+';
            if ($pct >= 80) return 'A';
            if ($pct >= 70) return 'B';
            if ($pct >= 60) return 'C';
            if ($pct >= 50) return 'D';
            return 'F';
        }

        function sp_get_grade_class($pct) {
            if ($pct >= 80) return 'excellent';
            if ($pct >= 60) return 'good';
            if ($pct >= 50) return 'average';
            return 'poor';
        }

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

.sp-header-info h1 { margin: 0 0 8px 0; font-size: 36px; font-weight: 800; }
.sp-header-info p { margin: 0; opacity: 0.95; font-size: 16px; }

.sp-buttons { display: flex; gap: 12px; flex-wrap: wrap; }

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

.sp-btn-print { background: rgba(255,255,255,0.2); color: white; border: 2px solid rgba(255,255,255,0.5); }
.sp-btn-edit { background: white; color: var(--primary); }
.sp-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.2); }

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

.sp-tab:hover, .sp-tab.active {
    color: var(--primary);
    border-bottom-color: var(--primary);
    background: var(--bg-light);
}

/* CONTENT */
.sp-content { padding: 40px; }
.sp-panel { display: none; }
.sp-panel.active { display: block; animation: fadeIn 0.4s; }

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

.sp-section-title i { color: var(--primary); }

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

.sp-card-title i { font-size: 22px; color: var(--primary); }

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

.sp-profile-info h2 { margin: 0 0 12px 0; font-size: 32px; font-weight: 800; color: var(--text-dark); }

.sp-badges { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 16px; }

.sp-badge {
    padding: 8px 20px;
    border-radius: 24px;
    font-size: 14px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.sp-badge-primary { background: var(--primary); color: white; }
.sp-badge-light { background: var(--blue-100); color: var(--primary-dark); }
.sp-badge-success { background: #d1fae5; color: #065f46; }

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

.sp-info-content strong { font-size: 16px; color: var(--text-dark); font-weight: 600; }

/* DATA LIST */
.sp-data-list { display: grid; gap: 12px; }

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

.sp-data-label { font-weight: 600; color: var(--text-muted); font-size: 14px; }
.sp-data-value { font-weight: 600; color: var(--text-dark); text-align: right; font-size: 15px; }

/* RESULT CARD TABLE STYLES */
.sp-result-card {
    background: white;
    border: 2px solid var(--border);
    border-radius: 16px;
    overflow: hidden;
    margin-bottom: 32px;
    box-shadow: 0 4px 20px rgba(0, 128, 255, 0.1);
}

.sp-result-header {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    color: white;
    padding: 20px 28px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.sp-result-header h3 {
    margin: 0;
    font-size: 22px;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 12px;
}

.sp-result-summary {
    display: flex;
    gap: 20px;
    align-items: center;
}

.sp-result-avg {
    background: rgba(255,255,255,0.2);
    padding: 8px 20px;
    border-radius: 24px;
    font-weight: 700;
}

.sp-result-grade {
    background: white;
    color: var(--primary);
    padding: 8px 20px;
    border-radius: 24px;
    font-weight: 800;
    font-size: 18px;
}

.sp-result-body { padding: 0; }

.sp-result-table {
    width: 100%;
    border-collapse: collapse;
}

.sp-result-table th,
.sp-result-table td {
    padding: 14px 16px;
    text-align: center;
    border-bottom: 1px solid var(--border);
}

.sp-result-table thead th {
    background: var(--bg-light);
    color: var(--text-dark);
    font-weight: 700;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.sp-result-table tbody tr:hover { background: var(--bg-light); }

.sp-result-table tbody td { font-size: 14px; color: var(--text-body); }

.sp-result-table .exam-name {
    text-align: left;
    font-weight: 600;
    color: var(--text-dark);
    background: var(--bg-light);
}

.sp-result-table .exam-month {
    text-align: left;
    padding-left: 32px;
    font-weight: 500;
    color: var(--text-muted);
}

.sp-grade-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 6px;
    font-weight: 700;
    font-size: 13px;
}

.sp-grade-excellent { background: #d1fae5; color: #065f46; }
.sp-grade-good { background: #dbeafe; color: #1e40af; }
.sp-grade-average { background: #fef3c7; color: #92400e; }
.sp-grade-poor { background: #fee2e2; color: #991b1b; }

.sp-result-footer {
    background: linear-gradient(135deg, var(--bg-light), white);
    padding: 20px 28px;
    border-top: 2px solid var(--border);
}

.sp-result-footer-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 16px;
}

.sp-result-footer-item {
    text-align: center;
    padding: 12px;
    background: white;
    border-radius: 10px;
    border: 2px solid var(--border);
}

.sp-result-footer-item label {
    display: block;
    font-size: 11px;
    color: var(--text-muted);
    text-transform: uppercase;
    margin-bottom: 4px;
}

.sp-result-footer-item strong {
    font-size: 18px;
    color: var(--primary);
}

/* Teacher Assessment in Result Card */
.sp-teacher-assessment {
    padding: 20px 28px;
    background: var(--bg-light);
    border-top: 2px solid var(--border);
}

.sp-assessment-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 16px;
}

.sp-assessment-item h5 {
    margin: 0 0 8px 0;
    font-size: 13px;
    color: var(--text-muted);
    text-transform: uppercase;
}

.sp-assessment-item p {
    margin: 0;
    padding: 12px;
    background: white;
    border-radius: 8px;
    font-size: 14px;
    color: var(--text-body);
    border-left: 3px solid var(--primary);
}

/* FEES */
.sp-fee-grid { display: grid; gap: 14px; }

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
.sp-attendance-wrapper { display: flex; gap: 36px; align-items: center; }
.sp-att-circle { width: 160px; height: 160px; flex-shrink: 0; }
.sp-att-stats { display: grid; gap: 14px; flex: 1; }

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

.sp-blood-icon { font-size: 48px; color: var(--danger); }
.sp-blood-value { font-size: 32px; font-weight: 800; color: var(--danger); }

/* BEHAVIOR RATING */
.sp-rating-display {
    display: flex;
    gap: 4px;
}

.sp-rating-star { color: #e2e8f0; font-size: 16px; }
.sp-rating-star.filled { color: #f59e0b; }

/* NO DATA */
.sp-no-data { text-align: center; padding: 60px 20px; color: var(--text-muted); }
.sp-no-data i { font-size: 64px; margin-bottom: 20px; color: var(--blue-100); }

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
    .sp-result-table { font-size: 12px; }
    .sp-result-table th, .sp-result-table td { padding: 10px 8px; }
}

@media print {
    .no-print { display: none !important; }
    .sp-wrapper { box-shadow: none; }
    .sp-panel { display: block !important; page-break-after: always; }
    .sp-result-card { page-break-inside: avoid; }
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
            <button class="sp-tab" data-tab="academic"><i class="fas fa-graduation-cap"></i> Result Card</button>
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

            <!-- ACADEMIC / RESULT CARD TAB -->
            <div class="sp-panel" id="academic">
                <h2 class="sp-section-title"><i class="fas fa-graduation-cap"></i> Academic Result Card</h2>

                <?php if ($student_data['academic_year']): ?>
                <div style="margin-bottom: 24px; padding: 16px 24px; background: var(--bg-light); border-radius: 10px; display: flex; gap: 32px; flex-wrap: wrap;">
                    <div><strong>Academic Year:</strong> <?php echo esc_html($student_data['academic_year']); ?></div>
                    <div><strong>Grade:</strong> <?php echo esc_html($grade_display); ?></div>
                    <div><strong>Term:</strong> <?php echo esc_html($term_display); ?></div>
                </div>
                <?php endif; ?>

                <?php if (!empty($subjects)):
                    $grand_total_obtained = 0;
                    $grand_total_marks = 0;

                    foreach ($subjects as $subject):
                        if (empty($subject['name'])) continue;

                        // Calculate subject totals
                        $subject_obtained = 0;
                        $subject_total = 0;
                        $exam_count = 0;

                        // Monthly exams
                        $monthly_exams = isset($subject['monthly_exams']) ? $subject['monthly_exams'] : array();

                        // Mid semester
                        $mid = isset($subject['mid_semester']) ? $subject['mid_semester'] : array();
                        $mid_total = (intval($mid['oral_total'] ?? 0) + intval($mid['written_total'] ?? 0));
                        $mid_obtained = (intval($mid['oral_obtained'] ?? 0) + intval($mid['written_obtained'] ?? 0));
                        if ($mid_total > 0) {
                            $mid_pct = round(($mid_obtained / $mid_total) * 100);
                            $subject_obtained += $mid_obtained;
                            $subject_total += $mid_total;
                            $exam_count++;
                        }

                        // Final/Annual semester
                        $final = isset($subject['final_semester']) ? $subject['final_semester'] : array();
                        $final_total = (intval($final['oral_total'] ?? 0) + intval($final['written_total'] ?? 0));
                        $final_obtained = (intval($final['oral_obtained'] ?? 0) + intval($final['written_obtained'] ?? 0));
                        if ($final_total > 0) {
                            $final_pct = round(($final_obtained / $final_total) * 100);
                            $subject_obtained += $final_obtained;
                            $subject_total += $final_total;
                            $exam_count++;
                        }

                        // Add monthly exams to total
                        foreach ($monthly_exams as $monthly) {
                            $m_total = (intval($monthly['oral_total'] ?? 0) + intval($monthly['written_total'] ?? 0));
                            $m_obtained = (intval($monthly['oral_obtained'] ?? 0) + intval($monthly['written_obtained'] ?? 0));
                            if ($m_total > 0) {
                                $subject_obtained += $m_obtained;
                                $subject_total += $m_total;
                            }
                        }

                        $subject_pct = $subject_total > 0 ? round(($subject_obtained / $subject_total) * 100) : 0;
                        $subject_grade = sp_get_grade($subject_pct);
                        $subject_grade_class = sp_get_grade_class($subject_pct);

                        $grand_total_obtained += $subject_obtained;
                        $grand_total_marks += $subject_total;
                ?>

                <div class="sp-result-card">
                    <div class="sp-result-header">
                        <h3><i class="fas fa-book"></i> <?php echo esc_html($subject['name']); ?></h3>
                        <div class="sp-result-summary">
                            <span class="sp-result-avg"><?php echo $subject_obtained; ?>/<?php echo $subject_total; ?> (<?php echo $subject_pct; ?>%)</span>
                            <span class="sp-result-grade"><?php echo $subject_grade; ?></span>
                        </div>
                    </div>

                    <div class="sp-result-body">
                        <table class="sp-result-table">
                            <thead>
                                <tr>
                                    <th style="text-align: left; width: 25%;">Examination</th>
                                    <th>Oral Total</th>
                                    <th>Oral Obt.</th>
                                    <th>Written Total</th>
                                    <th>Written Obt.</th>
                                    <th>Total</th>
                                    <th>Obtained</th>
                                    <th>%</th>
                                    <th>Grade</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($monthly_exams)): ?>
                                <tr>
                                    <td class="exam-name" colspan="9"><i class="fas fa-calendar-alt"></i> Monthly Tests</td>
                                </tr>
                                <?php foreach ($monthly_exams as $monthly):
                                    if (empty($monthly['oral_total']) && empty($monthly['written_total'])) continue;
                                    $m_oral_total = intval($monthly['oral_total'] ?? 0);
                                    $m_oral_obt = intval($monthly['oral_obtained'] ?? 0);
                                    $m_written_total = intval($monthly['written_total'] ?? 0);
                                    $m_written_obt = intval($monthly['written_obtained'] ?? 0);
                                    $m_total = $m_oral_total + $m_written_total;
                                    $m_obtained = $m_oral_obt + $m_written_obt;
                                    $m_pct = $m_total > 0 ? round(($m_obtained / $m_total) * 100) : 0;
                                    $m_grade = sp_get_grade($m_pct);
                                    $m_grade_class = sp_get_grade_class($m_pct);
                                ?>
                                <tr>
                                    <td class="exam-month"><?php echo esc_html($monthly['month_name'] ?: 'Monthly'); ?></td>
                                    <td><?php echo $m_oral_total; ?></td>
                                    <td><?php echo $m_oral_obt; ?></td>
                                    <td><?php echo $m_written_total; ?></td>
                                    <td><?php echo $m_written_obt; ?></td>
                                    <td><strong><?php echo $m_total; ?></strong></td>
                                    <td><strong><?php echo $m_obtained; ?></strong></td>
                                    <td><strong><?php echo $m_pct; ?>%</strong></td>
                                    <td><span class="sp-grade-badge sp-grade-<?php echo $m_grade_class; ?>"><?php echo $m_grade; ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>

                                <?php if ($mid_total > 0): ?>
                                <tr>
                                    <td class="exam-name"><i class="fas fa-book-open"></i> Mid Term Exam</td>
                                    <td><?php echo intval($mid['oral_total'] ?? 0); ?></td>
                                    <td><?php echo intval($mid['oral_obtained'] ?? 0); ?></td>
                                    <td><?php echo intval($mid['written_total'] ?? 0); ?></td>
                                    <td><?php echo intval($mid['written_obtained'] ?? 0); ?></td>
                                    <td><strong><?php echo $mid_total; ?></strong></td>
                                    <td><strong><?php echo $mid_obtained; ?></strong></td>
                                    <td><strong><?php echo $mid_pct; ?>%</strong></td>
                                    <td><span class="sp-grade-badge sp-grade-<?php echo sp_get_grade_class($mid_pct); ?>"><?php echo sp_get_grade($mid_pct); ?></span></td>
                                </tr>
                                <?php endif; ?>

                                <?php if ($final_total > 0): ?>
                                <tr>
                                    <td class="exam-name"><i class="fas fa-graduation-cap"></i> Annual Exam</td>
                                    <td><?php echo intval($final['oral_total'] ?? 0); ?></td>
                                    <td><?php echo intval($final['oral_obtained'] ?? 0); ?></td>
                                    <td><?php echo intval($final['written_total'] ?? 0); ?></td>
                                    <td><?php echo intval($final['written_obtained'] ?? 0); ?></td>
                                    <td><strong><?php echo $final_total; ?></strong></td>
                                    <td><strong><?php echo $final_obtained; ?></strong></td>
                                    <td><strong><?php echo $final_pct; ?>%</strong></td>
                                    <td><span class="sp-grade-badge sp-grade-<?php echo sp_get_grade_class($final_pct); ?>"><?php echo sp_get_grade($final_pct); ?></span></td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if (!empty($subject['strengths']) || !empty($subject['areas_for_improvement']) || !empty($subject['teacher_comments'])): ?>
                    <div class="sp-teacher-assessment">
                        <div class="sp-assessment-grid">
                            <?php if (!empty($subject['strengths'])): ?>
                            <div class="sp-assessment-item">
                                <h5><i class="fas fa-star"></i> Strengths</h5>
                                <p><?php echo esc_html($subject['strengths']); ?></p>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($subject['areas_for_improvement'])): ?>
                            <div class="sp-assessment-item">
                                <h5><i class="fas fa-chart-line"></i> Areas for Improvement</h5>
                                <p><?php echo esc_html($subject['areas_for_improvement']); ?></p>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($subject['teacher_comments'])): ?>
                            <div class="sp-assessment-item">
                                <h5><i class="fas fa-comment"></i> Teacher Comments</h5>
                                <p><?php echo esc_html($subject['teacher_comments']); ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <?php endforeach; ?>

                <!-- Grand Total Summary -->
                <?php
                $grand_pct = $grand_total_marks > 0 ? round(($grand_total_obtained / $grand_total_marks) * 100) : 0;
                $grand_grade = sp_get_grade($grand_pct);
                $grand_grade_class = sp_get_grade_class($grand_pct);
                ?>
                <div class="sp-card" style="background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%); color: white;">
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
                        <div>
                            <h3 style="margin: 0; font-size: 24px;"><i class="fas fa-trophy"></i> Overall Result</h3>
                        </div>
                        <div style="display: flex; gap: 24px; align-items: center;">
                            <div style="text-align: center;">
                                <div style="font-size: 12px; opacity: 0.8;">Total Marks</div>
                                <div style="font-size: 28px; font-weight: 800;"><?php echo $grand_total_obtained; ?>/<?php echo $grand_total_marks; ?></div>
                            </div>
                            <div style="text-align: center;">
                                <div style="font-size: 12px; opacity: 0.8;">Percentage</div>
                                <div style="font-size: 28px; font-weight: 800;"><?php echo $grand_pct; ?>%</div>
                            </div>
                            <div style="background: white; color: var(--primary); padding: 12px 28px; border-radius: 12px; text-align: center;">
                                <div style="font-size: 12px; color: var(--text-muted);">Grade</div>
                                <div style="font-size: 32px; font-weight: 800;"><?php echo $grand_grade; ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <?php else: ?>
                    <div class="sp-no-data">
                        <i class="fas fa-book"></i>
                        <p>No academic records available yet</p>
                    </div>
                <?php endif; ?>

                <?php if ($student_data['teacher_overall_comments']): ?>
                <div class="sp-card" style="margin-top: 28px;">
                    <h3 class="sp-card-title"><i class="fas fa-comment-alt"></i> Teacher's Overall Comments</h3>
                    <p style="padding: 16px; background: var(--bg-light); border-radius: 10px; line-height: 1.8;"><?php echo nl2br(esc_html($student_data['teacher_overall_comments'])); ?></p>
                </div>
                <?php endif; ?>

                <?php if ($student_data['goal_1'] || $student_data['goal_2'] || $student_data['goal_3']): ?>
                <div class="sp-card" style="margin-top: 28px;">
                    <h3 class="sp-card-title"><i class="fas fa-bullseye"></i> Learning Goals</h3>
                    <div class="sp-data-list">
                        <?php if ($student_data['goal_1']): ?>
                        <div class="sp-data-row"><span class="sp-data-label">Goal 1:</span><span class="sp-data-value"><?php echo esc_html($student_data['goal_1']); ?></span></div>
                        <?php endif; ?>
                        <?php if ($student_data['goal_2']): ?>
                        <div class="sp-data-row"><span class="sp-data-label">Goal 2:</span><span class="sp-data-value"><?php echo esc_html($student_data['goal_2']); ?></span></div>
                        <?php endif; ?>
                        <?php if ($student_data['goal_3']): ?>
                        <div class="sp-data-row"><span class="sp-data-label">Goal 3:</span><span class="sp-data-value"><?php echo esc_html($student_data['goal_3']); ?></span></div>
                        <?php endif; ?>
                    </div>
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
                        if ($student_data['admission_fee']) $fees['Admission Fee'] = $student_data['admission_fee'];
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
                <h2 class="sp-section-title"><i class="fas fa-heartbeat"></i> Health & Behavior</h2>

                <div class="sp-grid">
                    <div class="sp-card">
                        <h3 class="sp-card-title"><i class="fas fa-notes-medical"></i> Health Information</h3>

                        <div class="sp-blood-box">
                            <div class="sp-blood-icon"><i class="fas fa-tint"></i></div>
                            <div>
                                <label style="display: block; font-size: 13px; color: #991b1b; margin-bottom: 8px; font-weight: 600;">Blood Group</label>
                                <div class="sp-blood-value"><?php echo esc_html($student_data['blood_group'] ?: 'Not Specified'); ?></div>
                            </div>
                        </div>

                        <div class="sp-data-list">
                            <?php if ($student_data['allergies']): ?>
                            <div class="sp-data-row">
                                <span class="sp-data-label"><i class="fas fa-allergies"></i> Allergies:</span>
                                <span class="sp-data-value"><?php echo esc_html($student_data['allergies']); ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if ($student_data['medical_conditions']): ?>
                            <div class="sp-data-row">
                                <span class="sp-data-label"><i class="fas fa-file-medical"></i> Medical Conditions:</span>
                                <span class="sp-data-value"><?php echo esc_html($student_data['medical_conditions']); ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if (!$student_data['allergies'] && !$student_data['medical_conditions']): ?>
                            <div class="sp-data-row">
                                <span class="sp-data-label"><i class="fas fa-check-circle" style="color: var(--success);"></i></span>
                                <span class="sp-data-value">No health concerns reported</span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="sp-card">
                        <h3 class="sp-card-title"><i class="fas fa-star"></i> Behavior Assessment</h3>
                        <div class="sp-data-list">
                            <?php
                            $ratings = array(
                                'health_rating' => 'Health & Wellness',
                                'cleanness_rating' => 'Cleanliness & Hygiene',
                                'completes_homework' => 'Homework Completion',
                                'participates_in_class' => 'Class Participation',
                                'works_well_in_groups' => 'Group Work',
                                'problem_solving_skills' => 'Problem Solving',
                                'organization_preparedness' => 'Organization'
                            );
                            foreach ($ratings as $field => $label):
                                $val = intval($student_data[$field] ?? 0);
                                if ($val > 0):
                            ?>
                            <div class="sp-data-row">
                                <span class="sp-data-label"><?php echo $label; ?>:</span>
                                <span class="sp-data-value">
                                    <div class="sp-rating-display">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star sp-rating-star <?php echo $i <= $val ? 'filled' : ''; ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                </span>
                            </div>
                            <?php endif; endforeach; ?>
                        </div>
                    </div>
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
