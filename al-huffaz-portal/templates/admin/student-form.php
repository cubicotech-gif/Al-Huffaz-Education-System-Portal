<?php
/**
 * Enhanced Student Form Template - 5 Step Wizard
 * Al-Huffaz Education System Portal v2.0
 *
 * Complete student enrollment/edit form with all 46+ fields
 * Includes: Basic Info, Family, Academic (with subjects repeater), Fees, Health
 */

defined('ABSPATH') || exit;

$edit_id = isset($_GET['id']) ? intval($_GET['id']) : (isset($_GET['edit']) ? intval($_GET['edit']) : 0);
$is_edit = ($edit_id > 0 && get_post_type($edit_id) === 'student');

// Load student data if editing
$student_data = array();
$subjects = array();
$photo_url = '';

if ($is_edit) {
    $student_data = array(
        'student_name' => get_the_title($edit_id),
        'gr_number' => get_post_meta($edit_id, 'gr_number', true),
        'roll_number' => get_post_meta($edit_id, 'roll_number', true),
        'gender' => get_post_meta($edit_id, 'gender', true),
        'date_of_birth' => get_post_meta($edit_id, 'date_of_birth', true),
        'admission_date' => get_post_meta($edit_id, 'admission_date', true),
        'grade_level' => get_post_meta($edit_id, 'grade_level', true),
        'islamic_studies_category' => get_post_meta($edit_id, 'islamic_studies_category', true),
        'permanent_address' => get_post_meta($edit_id, 'permanent_address', true),
        'current_address' => get_post_meta($edit_id, 'current_address', true),
        'father_name' => get_post_meta($edit_id, 'father_name', true),
        'father_cnic' => get_post_meta($edit_id, 'father_cnic', true),
        'father_email' => get_post_meta($edit_id, 'father_email', true),
        'guardian_name' => get_post_meta($edit_id, 'guardian_name', true),
        'guardian_cnic' => get_post_meta($edit_id, 'guardian_cnic', true),
        'guardian_email' => get_post_meta($edit_id, 'guardian_email', true),
        'guardian_phone' => get_post_meta($edit_id, 'guardian_phone', true),
        'guardian_whatsapp' => get_post_meta($edit_id, 'guardian_whatsapp', true),
        'relationship_to_student' => get_post_meta($edit_id, 'relationship_to_student', true),
        'emergency_contact' => get_post_meta($edit_id, 'emergency_contact', true),
        'emergency_whatsapp' => get_post_meta($edit_id, 'emergency_whatsapp', true),
        'monthly_tuition_fee' => get_post_meta($edit_id, 'monthly_tuition_fee', true),
        'course_fee' => get_post_meta($edit_id, 'course_fee', true),
        'uniform_fee' => get_post_meta($edit_id, 'uniform_fee', true),
        'annual_fee' => get_post_meta($edit_id, 'annual_fee', true),
        'admission_fee' => get_post_meta($edit_id, 'admission_fee', true),
        'zakat_eligible' => get_post_meta($edit_id, 'zakat_eligible', true),
        'donation_eligible' => get_post_meta($edit_id, 'donation_eligible', true),
        'blood_group' => get_post_meta($edit_id, 'blood_group', true),
        'allergies' => get_post_meta($edit_id, 'allergies', true),
        'medical_conditions' => get_post_meta($edit_id, 'medical_conditions', true),
        'total_school_days' => get_post_meta($edit_id, 'total_school_days', true),
        'present_days' => get_post_meta($edit_id, 'present_days', true),
        'academic_term' => get_post_meta($edit_id, 'academic_term', true),
        'academic_year' => get_post_meta($edit_id, 'academic_year', true),
        'health_rating' => get_post_meta($edit_id, 'health_rating', true),
        'cleanness_rating' => get_post_meta($edit_id, 'cleanness_rating', true),
        'completes_homework' => get_post_meta($edit_id, 'completes_homework', true),
        'participates_in_class' => get_post_meta($edit_id, 'participates_in_class', true),
        'works_well_in_groups' => get_post_meta($edit_id, 'works_well_in_groups', true),
        'problem_solving_skills' => get_post_meta($edit_id, 'problem_solving_skills', true),
        'organization_preparedness' => get_post_meta($edit_id, 'organization_preparedness', true),
        'teacher_overall_comments' => get_post_meta($edit_id, 'teacher_overall_comments', true),
        'goal_1' => get_post_meta($edit_id, 'goal_1', true),
        'goal_2' => get_post_meta($edit_id, 'goal_2', true),
        'goal_3' => get_post_meta($edit_id, 'goal_3', true),
    );

    $subjects = get_post_meta($edit_id, 'subjects', true);
    if (!is_array($subjects)) {
        $subjects = array();
    }

    $photo_id = get_post_meta($edit_id, 'student_photo', true);
    $photo_url = $photo_id ? wp_get_attachment_image_url($photo_id, 'medium') : '';
}

// Helper functions
function ahp_selected($field, $value, $data) {
    return (isset($data[$field]) && $data[$field] === $value) ? 'selected' : '';
}

function ahp_checked($field, $data) {
    return (!empty($data[$field]) && $data[$field] === 'yes') ? 'checked' : '';
}
?>

<div class="ahp-student-form-wrapper">

    <!-- Form Header -->
    <div class="ahp-form-header">
        <div class="ahp-header-icon">
            <i class="fas fa-<?php echo $is_edit ? 'edit' : 'user-plus'; ?>"></i>
        </div>
        <div class="ahp-header-content">
            <h1><?php echo $is_edit ? 'Edit Student Record' : 'Student Enrollment'; ?></h1>
            <p><?php echo $is_edit ? 'Update student information below' : 'Complete all required fields to enroll a new student'; ?></p>
        </div>
        <?php if ($is_edit && $photo_url): ?>
        <div class="ahp-header-photo">
            <img src="<?php echo esc_url($photo_url); ?>" alt="Student Photo">
        </div>
        <?php endif; ?>
        <a href="<?php echo admin_url('admin.php?page=alhuffaz-students'); ?>" class="ahp-btn ahp-btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Students
        </a>
    </div>

    <!-- Progress Bar -->
    <div class="ahp-progress-bar">
        <div class="ahp-progress-step active" data-step="1">
            <div class="ahp-step-number">1</div>
            <div class="ahp-step-label">Basic Info</div>
        </div>
        <div class="ahp-progress-line"></div>
        <div class="ahp-progress-step" data-step="2">
            <div class="ahp-step-number">2</div>
            <div class="ahp-step-label">Family</div>
        </div>
        <div class="ahp-progress-line"></div>
        <div class="ahp-progress-step" data-step="3">
            <div class="ahp-step-number">3</div>
            <div class="ahp-step-label">Academic</div>
        </div>
        <div class="ahp-progress-line"></div>
        <div class="ahp-progress-step" data-step="4">
            <div class="ahp-step-number">4</div>
            <div class="ahp-step-label">Fees</div>
        </div>
        <div class="ahp-progress-line"></div>
        <div class="ahp-progress-step" data-step="5">
            <div class="ahp-step-number">5</div>
            <div class="ahp-step-label">Health</div>
        </div>
    </div>

    <!-- Form -->
    <form id="ahpStudentForm" class="ahp-student-form" enctype="multipart/form-data">
        <?php wp_nonce_field('ahp_student_form', 'ahp_nonce'); ?>
        <?php if ($is_edit): ?>
        <input type="hidden" name="student_id" value="<?php echo $edit_id; ?>">
        <?php endif; ?>
        <input type="hidden" name="action" value="<?php echo $is_edit ? 'ahp_update_student' : 'ahp_add_student'; ?>">

        <!-- STEP 1: Basic Information -->
        <div class="ahp-form-step active" data-step="1">
            <div class="ahp-step-header">
                <i class="fas fa-user"></i>
                <h2>Basic Information</h2>
            </div>

            <div class="ahp-form-grid">
                <div class="ahp-form-group ahp-col-6">
                    <label class="ahp-label required"><i class="fas fa-user-graduate"></i> Student Full Name</label>
                    <input type="text" name="student_name" class="ahp-input" required
                           placeholder="Enter full name" value="<?php echo esc_attr($student_data['student_name'] ?? ''); ?>">
                </div>

                <div class="ahp-form-group ahp-col-3">
                    <label class="ahp-label required"><i class="fas fa-id-card"></i> GR Number</label>
                    <input type="text" name="gr_number" class="ahp-input" required
                           placeholder="GR-2025-001" value="<?php echo esc_attr($student_data['gr_number'] ?? ''); ?>">
                </div>

                <div class="ahp-form-group ahp-col-3">
                    <label class="ahp-label"><i class="fas fa-hashtag"></i> Roll Number</label>
                    <input type="text" name="roll_number" class="ahp-input"
                           placeholder="Roll #" value="<?php echo esc_attr($student_data['roll_number'] ?? ''); ?>">
                </div>

                <div class="ahp-form-group ahp-col-4">
                    <label class="ahp-label required"><i class="fas fa-venus-mars"></i> Gender</label>
                    <select name="gender" class="ahp-input" required>
                        <option value="">Select Gender</option>
                        <option value="male" <?php echo ahp_selected('gender', 'male', $student_data); ?>>Male</option>
                        <option value="female" <?php echo ahp_selected('gender', 'female', $student_data); ?>>Female</option>
                    </select>
                </div>

                <div class="ahp-form-group ahp-col-4">
                    <label class="ahp-label"><i class="fas fa-calendar-alt"></i> Date of Birth</label>
                    <input type="date" name="date_of_birth" class="ahp-input"
                           value="<?php echo esc_attr($student_data['date_of_birth'] ?? ''); ?>">
                </div>

                <div class="ahp-form-group ahp-col-4">
                    <label class="ahp-label"><i class="fas fa-calendar-check"></i> Admission Date</label>
                    <input type="date" name="admission_date" class="ahp-input"
                           value="<?php echo esc_attr($student_data['admission_date'] ?? ''); ?>">
                </div>

                <div class="ahp-form-group ahp-col-4">
                    <label class="ahp-label"><i class="fas fa-layer-group"></i> Grade Level</label>
                    <select name="grade_level" class="ahp-input">
                        <option value="">Select Grade</option>
                        <option value="kg1" <?php echo ahp_selected('grade_level', 'kg1', $student_data); ?>>KG 1</option>
                        <option value="kg2" <?php echo ahp_selected('grade_level', 'kg2', $student_data); ?>>KG 2</option>
                        <option value="class1" <?php echo ahp_selected('grade_level', 'class1', $student_data); ?>>Class 1</option>
                        <option value="class2" <?php echo ahp_selected('grade_level', 'class2', $student_data); ?>>Class 2</option>
                        <option value="class3" <?php echo ahp_selected('grade_level', 'class3', $student_data); ?>>Class 3</option>
                        <option value="level1" <?php echo ahp_selected('grade_level', 'level1', $student_data); ?>>Level 1</option>
                        <option value="level2" <?php echo ahp_selected('grade_level', 'level2', $student_data); ?>>Level 2</option>
                        <option value="level3" <?php echo ahp_selected('grade_level', 'level3', $student_data); ?>>Level 3</option>
                        <option value="shb" <?php echo ahp_selected('grade_level', 'shb', $student_data); ?>>SHB</option>
                        <option value="shg" <?php echo ahp_selected('grade_level', 'shg', $student_data); ?>>SHG</option>
                    </select>
                </div>

                <div class="ahp-form-group ahp-col-4">
                    <label class="ahp-label"><i class="fas fa-quran"></i> Islamic Studies</label>
                    <select name="islamic_studies_category" class="ahp-input">
                        <option value="">Select Category</option>
                        <option value="hifz" <?php echo ahp_selected('islamic_studies_category', 'hifz', $student_data); ?>>Hifz</option>
                        <option value="nazra" <?php echo ahp_selected('islamic_studies_category', 'nazra', $student_data); ?>>Nazra</option>
                        <option value="qaidah" <?php echo ahp_selected('islamic_studies_category', 'qaidah', $student_data); ?>>Qaidah</option>
                    </select>
                </div>

                <div class="ahp-form-group ahp-col-4">
                    <label class="ahp-label"><i class="fas fa-camera"></i> Student Photo</label>
                    <input type="file" name="student_photo" class="ahp-input-file" accept="image/*">
                    <?php if ($photo_url): ?>
                    <small class="ahp-help-text">Current photo will be kept if no new photo uploaded</small>
                    <?php endif; ?>
                </div>

                <div class="ahp-form-group ahp-col-6">
                    <label class="ahp-label"><i class="fas fa-map-marker-alt"></i> Permanent Address</label>
                    <textarea name="permanent_address" class="ahp-input" rows="3"
                              placeholder="Enter permanent address"><?php echo esc_textarea($student_data['permanent_address'] ?? ''); ?></textarea>
                </div>

                <div class="ahp-form-group ahp-col-6">
                    <label class="ahp-label"><i class="fas fa-home"></i> Current Address</label>
                    <textarea name="current_address" class="ahp-input" rows="3"
                              placeholder="Leave blank if same as permanent"><?php echo esc_textarea($student_data['current_address'] ?? ''); ?></textarea>
                </div>
            </div>
        </div>

        <!-- STEP 2: Family Information -->
        <div class="ahp-form-step" data-step="2">
            <div class="ahp-step-header">
                <i class="fas fa-users"></i>
                <h2>Family Information</h2>
            </div>

            <!-- Father Section -->
            <div class="ahp-info-section">
                <h3 class="ahp-section-title"><i class="fas fa-user-tie"></i> Father's Information</h3>
                <div class="ahp-form-grid">
                    <div class="ahp-form-group ahp-col-4">
                        <label class="ahp-label"><i class="fas fa-user"></i> Father's Name</label>
                        <input type="text" name="father_name" class="ahp-input"
                               placeholder="Father's full name" value="<?php echo esc_attr($student_data['father_name'] ?? ''); ?>">
                    </div>
                    <div class="ahp-form-group ahp-col-4">
                        <label class="ahp-label"><i class="fas fa-id-card"></i> Father's CNIC</label>
                        <input type="text" name="father_cnic" class="ahp-input"
                               placeholder="XXXXX-XXXXXXX-X" value="<?php echo esc_attr($student_data['father_cnic'] ?? ''); ?>">
                    </div>
                    <div class="ahp-form-group ahp-col-4">
                        <label class="ahp-label"><i class="fas fa-envelope"></i> Father's Email</label>
                        <input type="email" name="father_email" class="ahp-input"
                               placeholder="email@example.com" value="<?php echo esc_attr($student_data['father_email'] ?? ''); ?>">
                    </div>
                </div>
            </div>

            <!-- Guardian Section -->
            <div class="ahp-info-section">
                <h3 class="ahp-section-title"><i class="fas fa-user-shield"></i> Guardian Information</h3>
                <div class="ahp-form-grid">
                    <div class="ahp-form-group ahp-col-4">
                        <label class="ahp-label"><i class="fas fa-user"></i> Guardian Name</label>
                        <input type="text" name="guardian_name" class="ahp-input"
                               placeholder="Guardian's name" value="<?php echo esc_attr($student_data['guardian_name'] ?? ''); ?>">
                    </div>
                    <div class="ahp-form-group ahp-col-4">
                        <label class="ahp-label"><i class="fas fa-id-card"></i> Guardian CNIC</label>
                        <input type="text" name="guardian_cnic" class="ahp-input"
                               placeholder="XXXXX-XXXXXXX-X" value="<?php echo esc_attr($student_data['guardian_cnic'] ?? ''); ?>">
                    </div>
                    <div class="ahp-form-group ahp-col-4">
                        <label class="ahp-label"><i class="fas fa-link"></i> Relationship</label>
                        <select name="relationship_to_student" class="ahp-input">
                            <option value="">Select Relationship</option>
                            <option value="father" <?php echo ahp_selected('relationship_to_student', 'father', $student_data); ?>>Father</option>
                            <option value="mother" <?php echo ahp_selected('relationship_to_student', 'mother', $student_data); ?>>Mother</option>
                            <option value="uncle" <?php echo ahp_selected('relationship_to_student', 'uncle', $student_data); ?>>Uncle</option>
                            <option value="aunt" <?php echo ahp_selected('relationship_to_student', 'aunt', $student_data); ?>>Aunt</option>
                            <option value="grandparent" <?php echo ahp_selected('relationship_to_student', 'grandparent', $student_data); ?>>Grandparent</option>
                            <option value="sibling" <?php echo ahp_selected('relationship_to_student', 'sibling', $student_data); ?>>Sibling</option>
                            <option value="other" <?php echo ahp_selected('relationship_to_student', 'other', $student_data); ?>>Other</option>
                        </select>
                    </div>
                    <div class="ahp-form-group ahp-col-4">
                        <label class="ahp-label"><i class="fas fa-envelope"></i> Guardian Email</label>
                        <input type="email" name="guardian_email" class="ahp-input"
                               placeholder="email@example.com" value="<?php echo esc_attr($student_data['guardian_email'] ?? ''); ?>">
                    </div>
                    <div class="ahp-form-group ahp-col-4">
                        <label class="ahp-label"><i class="fas fa-phone"></i> Guardian Phone</label>
                        <input type="tel" name="guardian_phone" class="ahp-input"
                               placeholder="03XX-XXXXXXX" value="<?php echo esc_attr($student_data['guardian_phone'] ?? ''); ?>">
                    </div>
                    <div class="ahp-form-group ahp-col-4">
                        <label class="ahp-label"><i class="fab fa-whatsapp"></i> Guardian WhatsApp</label>
                        <input type="tel" name="guardian_whatsapp" class="ahp-input"
                               placeholder="03XX-XXXXXXX" value="<?php echo esc_attr($student_data['guardian_whatsapp'] ?? ''); ?>">
                    </div>
                </div>
            </div>

            <!-- Emergency Contact -->
            <div class="ahp-info-section">
                <h3 class="ahp-section-title"><i class="fas fa-ambulance"></i> Emergency Contact</h3>
                <div class="ahp-form-grid">
                    <div class="ahp-form-group ahp-col-6">
                        <label class="ahp-label"><i class="fas fa-phone-alt"></i> Emergency Phone</label>
                        <input type="tel" name="emergency_contact" class="ahp-input"
                               placeholder="Emergency contact number" value="<?php echo esc_attr($student_data['emergency_contact'] ?? ''); ?>">
                    </div>
                    <div class="ahp-form-group ahp-col-6">
                        <label class="ahp-label"><i class="fab fa-whatsapp"></i> Emergency WhatsApp</label>
                        <input type="tel" name="emergency_whatsapp" class="ahp-input"
                               placeholder="Emergency WhatsApp" value="<?php echo esc_attr($student_data['emergency_whatsapp'] ?? ''); ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- STEP 3: Academic Information -->
        <div class="ahp-form-step" data-step="3">
            <div class="ahp-step-header">
                <i class="fas fa-graduation-cap"></i>
                <h2>Academic Information</h2>
            </div>

            <!-- Academic Period -->
            <div class="ahp-info-section">
                <h3 class="ahp-section-title"><i class="fas fa-calendar"></i> Academic Period</h3>
                <div class="ahp-form-grid">
                    <div class="ahp-form-group ahp-col-6">
                        <label class="ahp-label"><i class="fas fa-calendar-alt"></i> Academic Year</label>
                        <select name="academic_year" class="ahp-input">
                            <option value="">Select Year</option>
                            <?php
                            $current_year = date('Y');
                            for ($y = $current_year; $y >= $current_year - 5; $y--):
                                $year_val = $y . '-' . ($y + 1);
                            ?>
                            <option value="<?php echo $year_val; ?>" <?php echo ahp_selected('academic_year', $year_val, $student_data); ?>><?php echo $year_val; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="ahp-form-group ahp-col-6">
                        <label class="ahp-label"><i class="fas fa-clock"></i> Academic Term</label>
                        <select name="academic_term" class="ahp-input">
                            <option value="">Select Term</option>
                            <option value="mid" <?php echo ahp_selected('academic_term', 'mid', $student_data); ?>>Mid Term</option>
                            <option value="annual" <?php echo ahp_selected('academic_term', 'annual', $student_data); ?>>Annual</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Attendance -->
            <div class="ahp-info-section">
                <h3 class="ahp-section-title"><i class="fas fa-clipboard-check"></i> Attendance Record</h3>
                <div class="ahp-form-grid">
                    <div class="ahp-form-group ahp-col-4">
                        <label class="ahp-label"><i class="fas fa-calendar-day"></i> Total School Days</label>
                        <input type="number" name="total_school_days" id="totalSchoolDays" class="ahp-input"
                               placeholder="0" min="0" value="<?php echo esc_attr($student_data['total_school_days'] ?? ''); ?>">
                    </div>
                    <div class="ahp-form-group ahp-col-4">
                        <label class="ahp-label"><i class="fas fa-check-circle"></i> Present Days</label>
                        <input type="number" name="present_days" id="presentDays" class="ahp-input"
                               placeholder="0" min="0" value="<?php echo esc_attr($student_data['present_days'] ?? ''); ?>">
                    </div>
                    <div class="ahp-form-group ahp-col-4">
                        <label class="ahp-label"><i class="fas fa-percentage"></i> Attendance</label>
                        <div id="attendanceDisplay" class="ahp-attendance-display">
                            <span class="ahp-attendance-value">--</span>
                            <span class="ahp-attendance-label">%</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Subjects Section -->
            <div class="ahp-info-section">
                <div class="ahp-section-header-row">
                    <h3 class="ahp-section-title"><i class="fas fa-book"></i> Subject Performance</h3>
                    <button type="button" id="addSubjectBtn" class="ahp-btn ahp-btn-success">
                        <i class="fas fa-plus"></i> Add Subject
                    </button>
                </div>

                <div id="subjectsContainer">
                    <?php if (!empty($subjects)): ?>
                        <?php foreach ($subjects as $index => $subject): ?>
                            <?php include(ALHUFFAZ_PLUGIN_PATH . 'templates/partials/subject-fields.php'); ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div id="noSubjectsMessage" class="ahp-empty-subjects" <?php echo !empty($subjects) ? 'style="display:none;"' : ''; ?>>
                    <i class="fas fa-book-open"></i>
                    <p>No subjects added yet. Click "Add Subject" to start tracking academic performance.</p>
                </div>
            </div>
        </div>

        <!-- STEP 4: Fees Information -->
        <div class="ahp-form-step" data-step="4">
            <div class="ahp-step-header">
                <i class="fas fa-money-bill-wave"></i>
                <h2>Fee Structure</h2>
            </div>

            <div class="ahp-fee-cards">
                <div class="ahp-fee-card">
                    <div class="ahp-fee-icon"><i class="fas fa-sync-alt"></i></div>
                    <h4>Monthly Tuition</h4>
                    <div class="ahp-fee-input">
                        <span class="ahp-currency">PKR</span>
                        <input type="number" name="monthly_tuition_fee" class="ahp-input fee-input"
                               placeholder="0" min="0" value="<?php echo esc_attr($student_data['monthly_tuition_fee'] ?? ''); ?>">
                    </div>
                </div>

                <div class="ahp-fee-card">
                    <div class="ahp-fee-icon"><i class="fas fa-book-open"></i></div>
                    <h4>Course Fee</h4>
                    <div class="ahp-fee-input">
                        <span class="ahp-currency">PKR</span>
                        <input type="number" name="course_fee" class="ahp-input fee-input"
                               placeholder="0" min="0" value="<?php echo esc_attr($student_data['course_fee'] ?? ''); ?>">
                    </div>
                </div>

                <div class="ahp-fee-card">
                    <div class="ahp-fee-icon"><i class="fas fa-tshirt"></i></div>
                    <h4>Uniform Fee</h4>
                    <div class="ahp-fee-input">
                        <span class="ahp-currency">PKR</span>
                        <input type="number" name="uniform_fee" class="ahp-input fee-input"
                               placeholder="0" min="0" value="<?php echo esc_attr($student_data['uniform_fee'] ?? ''); ?>">
                    </div>
                </div>

                <div class="ahp-fee-card">
                    <div class="ahp-fee-icon"><i class="fas fa-calendar-alt"></i></div>
                    <h4>Annual Fee</h4>
                    <div class="ahp-fee-input">
                        <span class="ahp-currency">PKR</span>
                        <input type="number" name="annual_fee" class="ahp-input fee-input"
                               placeholder="0" min="0" value="<?php echo esc_attr($student_data['annual_fee'] ?? ''); ?>">
                    </div>
                </div>

                <div class="ahp-fee-card">
                    <div class="ahp-fee-icon"><i class="fas fa-user-plus"></i></div>
                    <h4>Admission Fee</h4>
                    <div class="ahp-fee-input">
                        <span class="ahp-currency">PKR</span>
                        <input type="number" name="admission_fee" class="ahp-input fee-input"
                               placeholder="0" min="0" value="<?php echo esc_attr($student_data['admission_fee'] ?? ''); ?>">
                    </div>
                </div>
            </div>

            <!-- Fee Summary -->
            <div class="ahp-fee-summary">
                <div class="ahp-fee-summary-row">
                    <span>Monthly Fee:</span>
                    <strong id="monthlyTotal">PKR 0</strong>
                </div>
                <div class="ahp-fee-summary-row">
                    <span>One-time Fees:</span>
                    <strong id="oneTimeTotal">PKR 0</strong>
                </div>
                <div class="ahp-fee-summary-row ahp-fee-grand-total">
                    <span>Total (First Month):</span>
                    <strong id="grandTotal">PKR 0</strong>
                </div>
            </div>

            <!-- Eligibility -->
            <div class="ahp-info-section">
                <h3 class="ahp-section-title"><i class="fas fa-hand-holding-heart"></i> Financial Aid Eligibility</h3>
                <div class="ahp-checkbox-group">
                    <label class="ahp-checkbox-label">
                        <input type="checkbox" name="zakat_eligible" value="yes" <?php echo ahp_checked('zakat_eligible', $student_data); ?>>
                        <span class="ahp-checkbox-text"><i class="fas fa-donate"></i> Eligible for Zakat</span>
                    </label>
                    <label class="ahp-checkbox-label">
                        <input type="checkbox" name="donation_eligible" value="yes" <?php echo ahp_checked('donation_eligible', $student_data); ?>>
                        <span class="ahp-checkbox-text"><i class="fas fa-gift"></i> Eligible for Donations/Sponsorship</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- STEP 5: Health & Behavior -->
        <div class="ahp-form-step" data-step="5">
            <div class="ahp-step-header">
                <i class="fas fa-heartbeat"></i>
                <h2>Health & Behavior Assessment</h2>
            </div>

            <!-- Medical Information -->
            <div class="ahp-info-section">
                <h3 class="ahp-section-title"><i class="fas fa-notes-medical"></i> Medical Information</h3>
                <div class="ahp-form-grid">
                    <div class="ahp-form-group ahp-col-4">
                        <label class="ahp-label"><i class="fas fa-tint"></i> Blood Group</label>
                        <select name="blood_group" class="ahp-input">
                            <option value="">Select Blood Group</option>
                            <option value="A+" <?php echo ahp_selected('blood_group', 'A+', $student_data); ?>>A+</option>
                            <option value="A-" <?php echo ahp_selected('blood_group', 'A-', $student_data); ?>>A-</option>
                            <option value="B+" <?php echo ahp_selected('blood_group', 'B+', $student_data); ?>>B+</option>
                            <option value="B-" <?php echo ahp_selected('blood_group', 'B-', $student_data); ?>>B-</option>
                            <option value="AB+" <?php echo ahp_selected('blood_group', 'AB+', $student_data); ?>>AB+</option>
                            <option value="AB-" <?php echo ahp_selected('blood_group', 'AB-', $student_data); ?>>AB-</option>
                            <option value="O+" <?php echo ahp_selected('blood_group', 'O+', $student_data); ?>>O+</option>
                            <option value="O-" <?php echo ahp_selected('blood_group', 'O-', $student_data); ?>>O-</option>
                        </select>
                    </div>
                    <div class="ahp-form-group ahp-col-4">
                        <label class="ahp-label"><i class="fas fa-allergies"></i> Allergies</label>
                        <input type="text" name="allergies" class="ahp-input"
                               placeholder="Any known allergies" value="<?php echo esc_attr($student_data['allergies'] ?? ''); ?>">
                    </div>
                    <div class="ahp-form-group ahp-col-4">
                        <label class="ahp-label"><i class="fas fa-file-medical"></i> Medical Conditions</label>
                        <input type="text" name="medical_conditions" class="ahp-input"
                               placeholder="Any medical conditions" value="<?php echo esc_attr($student_data['medical_conditions'] ?? ''); ?>">
                    </div>
                </div>
            </div>

            <!-- Behavior Assessment -->
            <div class="ahp-info-section">
                <h3 class="ahp-section-title"><i class="fas fa-star"></i> Behavior Assessment</h3>
                <div class="ahp-rating-grid">
                    <?php
                    $ratings = array(
                        'health_rating' => array('icon' => 'fa-heartbeat', 'label' => 'Health & Wellness'),
                        'cleanness_rating' => array('icon' => 'fa-broom', 'label' => 'Cleanliness & Hygiene'),
                        'completes_homework' => array('icon' => 'fa-tasks', 'label' => 'Completes Homework'),
                        'participates_in_class' => array('icon' => 'fa-hand-paper', 'label' => 'Class Participation'),
                        'works_well_in_groups' => array('icon' => 'fa-users', 'label' => 'Group Work'),
                        'problem_solving_skills' => array('icon' => 'fa-lightbulb', 'label' => 'Problem Solving'),
                        'organization_preparedness' => array('icon' => 'fa-folder-open', 'label' => 'Organization'),
                    );
                    foreach ($ratings as $field => $info):
                        $current_val = $student_data[$field] ?? '';
                    ?>
                    <div class="ahp-rating-item">
                        <label class="ahp-rating-label"><i class="fas <?php echo $info['icon']; ?>"></i> <?php echo $info['label']; ?></label>
                        <div class="ahp-rating-stars" data-field="<?php echo $field; ?>">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="fas fa-star ahp-star <?php echo ($current_val >= $i) ? 'active' : ''; ?>" data-value="<?php echo $i; ?>"></i>
                            <?php endfor; ?>
                            <input type="hidden" name="<?php echo $field; ?>" value="<?php echo esc_attr($current_val); ?>">
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Goals & Comments -->
            <div class="ahp-info-section">
                <h3 class="ahp-section-title"><i class="fas fa-bullseye"></i> Goals & Teacher Comments</h3>
                <div class="ahp-form-grid">
                    <div class="ahp-form-group ahp-col-4">
                        <label class="ahp-label"><i class="fas fa-flag"></i> Goal 1</label>
                        <input type="text" name="goal_1" class="ahp-input"
                               placeholder="First learning goal" value="<?php echo esc_attr($student_data['goal_1'] ?? ''); ?>">
                    </div>
                    <div class="ahp-form-group ahp-col-4">
                        <label class="ahp-label"><i class="fas fa-flag"></i> Goal 2</label>
                        <input type="text" name="goal_2" class="ahp-input"
                               placeholder="Second learning goal" value="<?php echo esc_attr($student_data['goal_2'] ?? ''); ?>">
                    </div>
                    <div class="ahp-form-group ahp-col-4">
                        <label class="ahp-label"><i class="fas fa-flag"></i> Goal 3</label>
                        <input type="text" name="goal_3" class="ahp-input"
                               placeholder="Third learning goal" value="<?php echo esc_attr($student_data['goal_3'] ?? ''); ?>">
                    </div>
                    <div class="ahp-form-group ahp-col-12">
                        <label class="ahp-label"><i class="fas fa-comment-alt"></i> Teacher's Overall Comments</label>
                        <textarea name="teacher_overall_comments" class="ahp-input" rows="4"
                                  placeholder="Overall assessment, observations, and recommendations..."><?php echo esc_textarea($student_data['teacher_overall_comments'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Navigation -->
        <div class="ahp-form-navigation">
            <button type="button" id="prevStepBtn" class="ahp-btn ahp-btn-secondary" style="display: none;">
                <i class="fas fa-arrow-left"></i> Previous
            </button>
            <button type="button" id="nextStepBtn" class="ahp-btn ahp-btn-primary">
                Next <i class="fas fa-arrow-right"></i>
            </button>
            <button type="submit" id="submitFormBtn" class="ahp-btn ahp-btn-success" style="display: none;">
                <i class="fas fa-<?php echo $is_edit ? 'save' : 'user-plus'; ?>"></i>
                <?php echo $is_edit ? 'Update Student' : 'Enroll Student'; ?>
            </button>
        </div>

    </form>
</div>

<!-- Subject Template (Hidden) -->
<template id="subjectTemplate">
    <?php
    $index = 'SUBJECT_INDEX';
    $subject = array();
    include(ALHUFFAZ_PLUGIN_PATH . 'templates/partials/subject-fields.php');
    ?>
</template>

<!-- Monthly Exam Template (Hidden) -->
<template id="monthlyExamTemplate">
    <?php include(ALHUFFAZ_PLUGIN_PATH . 'templates/partials/monthly-exam-fields.php'); ?>
</template>
