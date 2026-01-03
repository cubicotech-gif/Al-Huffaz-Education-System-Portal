<?php
/*
Plugin Name: Al-Huffaz Student Edit Only - FIXED
Description: Edit existing student records - Compatible with main form data
Version: 4.0 FIXED
Author: RoohUl Hasnain
*/

defined('ABSPATH') || exit;

add_shortcode('student_edit_only', 'alhuffaz_student_edit_only');
add_action('init', 'handle_student_edit_submission_final');

// Enqueue assets
add_action('wp_enqueue_scripts', 'student_edit_assets_final');
function student_edit_assets_final() {
    wp_enqueue_script('jquery');
    wp_enqueue_style('google-fonts', 'https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap');
    wp_enqueue_style('flatpickr-css', 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css');
    wp_enqueue_script('flatpickr-js', 'https://cdn.jsdelivr.net/npm/flatpickr', array('jquery'), null, true);
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css');
}

function handle_student_edit_submission_final() {
    if (!isset($_POST['update_student_submit']) || !isset($_POST['student_id'])) {
        return;
    }
    
    if (!isset($_POST['student_form_nonce']) || !wp_verify_nonce($_POST['student_form_nonce'], 'student_form_submit')) {
        wp_die('Security check failed');
    }
    
    $student_id = intval($_POST['student_id']);
    
    if (get_post_type($student_id) !== 'student') {
        wp_die('Invalid student ID');
    }
    
    // Update post
    wp_update_post(array(
        'ID' => $student_id,
        'post_title' => sanitize_text_field($_POST['student_name'])
    ));
    
    // FIXED: Use EXACT SAME field processing as main form
    process_student_update_final($student_id);
    
    set_transient('student_form_success', 'Student updated successfully!', 30);
    wp_redirect(add_query_arg('portal_tab', 'students', remove_query_arg(array('edit', 'action'))));
    exit;
}

function process_student_update_final($student_id) {
    try {
        // Update student meta - MUST MATCH MAIN FORM EXACTLY
        $fields = array(
            'gr_number', 'roll_number', 'gender', 'date_of_birth', 'admission_date',
            'grade_level', 'islamic_studies_category', 'permanent_address', 'current_address',
            'father_name', 'father_cnic', 'father_email', // CORRECT: father_ NOT parent_
            'guardian_name', 'guardian_cnic', 'guardian_email', 'guardian_phone',
            'guardian_whatsapp', 'relationship_to_student', 'emergency_contact', 'emergency_whatsapp',
            'monthly_tuition_fee', 'course_fee', 'uniform_fee', 'annual_fee', 'admission_fee', // ADDED: annual_fee, admission_fee
            'zakat_eligible', 'donation_eligible', 'blood_group', 'allergies', 'medical_conditions',
            'total_school_days', 'present_days', 'academic_term', 'academic_year',
            'health_rating', 'cleanness_rating', 'completes_homework', 'participates_in_class',
            'works_well_in_groups', 'problem_solving_skills', 'organization_preparedness',
            'teacher_overall_comments', 'goal_1', 'goal_2', 'goal_3'
        );
        
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($student_id, $field, sanitize_text_field($_POST[$field]));
            } else {
                // Clear checkboxes if not checked
                if (in_array($field, ['zakat_eligible', 'donation_eligible'])) {
                    update_post_meta($student_id, $field, '');
                }
            }
        }
        
        // CRITICAL FIX: Process subjects EXACTLY like main form
        if (isset($_POST['subjects']) && is_array($_POST['subjects'])) {
            $subjects_data = array();
            
            foreach ($_POST['subjects'] as $subject) {
                if (!empty($subject['name'])) {
                    $subject_entry = array(
                        'name' => sanitize_text_field($subject['name']),
                        'monthly_exams' => array(),
                        'mid_semester' => array(),
                        'final_semester' => array(),
                        'strengths' => sanitize_textarea_field($subject['strengths'] ?? ''),
                        'areas_for_improvement' => sanitize_textarea_field($subject['areas_for_improvement'] ?? ''),
                        'teacher_comments' => sanitize_textarea_field($subject['teacher_comments'] ?? '')
                    );
                    
                    // Process Monthly Exams - SAME STRUCTURE AS MAIN FORM
                    if (isset($subject['monthly_exams']) && is_array($subject['monthly_exams'])) {
                        foreach ($subject['monthly_exams'] as $monthly) {
                            if (!empty($monthly['month_name'])) {
                                $oral_total = floatval($monthly['oral_total'] ?? 0);
                                $oral_obtained = floatval($monthly['oral_obtained'] ?? 0);
                                $written_total = floatval($monthly['written_total'] ?? 0);
                                $written_obtained = floatval($monthly['written_obtained'] ?? 0);
                                
                                $overall_total = $oral_total + $written_total;
                                $overall_obtained = $oral_obtained + $written_obtained;
                                $percentage = ($overall_total > 0) ? round(($overall_obtained / $overall_total) * 100, 2) : 0;
                                
                                $subject_entry['monthly_exams'][] = array(
                                    'month_name' => sanitize_text_field($monthly['month_name']),
                                    'oral_total' => $oral_total,
                                    'oral_obtained' => $oral_obtained,
                                    'written_total' => $written_total,
                                    'written_obtained' => $written_obtained,
                                    'overall_total' => $overall_total,
                                    'overall_obtained' => $overall_obtained,
                                    'percentage' => $percentage,
                                    'grade' => calculate_grade_final($percentage)
                                );
                            }
                        }
                    }
                    
                    // Process Mid Semester
                    if (isset($subject['mid_semester'])) {
                        $mid = $subject['mid_semester'];
                        $oral_total = floatval($mid['oral_total'] ?? 0);
                        $oral_obtained = floatval($mid['oral_obtained'] ?? 0);
                        $written_total = floatval($mid['written_total'] ?? 0);
                        $written_obtained = floatval($mid['written_obtained'] ?? 0);
                        
                        $overall_total = $oral_total + $written_total;
                        $overall_obtained = $oral_obtained + $written_obtained;
                        $percentage = ($overall_total > 0) ? round(($overall_obtained / $overall_total) * 100, 2) : 0;
                        
                        $subject_entry['mid_semester'] = array(
                            'oral_total' => $oral_total,
                            'oral_obtained' => $oral_obtained,
                            'written_total' => $written_total,
                            'written_obtained' => $written_obtained,
                            'overall_total' => $overall_total,
                            'overall_obtained' => $overall_obtained,
                            'percentage' => $percentage,
                            'grade' => calculate_grade_final($percentage)
                        );
                    }
                    
                    // Process Final Semester
                    if (isset($subject['final_semester'])) {
                        $final = $subject['final_semester'];
                        $oral_total = floatval($final['oral_total'] ?? 0);
                        $oral_obtained = floatval($final['oral_obtained'] ?? 0);
                        $written_total = floatval($final['written_total'] ?? 0);
                        $written_obtained = floatval($final['written_obtained'] ?? 0);
                        
                        $overall_total = $oral_total + $written_total;
                        $overall_obtained = $oral_obtained + $written_obtained;
                        $percentage = ($overall_total > 0) ? round(($overall_obtained / $overall_total) * 100, 2) : 0;
                        
                        $subject_entry['final_semester'] = array(
                            'oral_total' => $oral_total,
                            'oral_obtained' => $oral_obtained,
                            'written_total' => $written_total,
                            'written_obtained' => $written_obtained,
                            'overall_total' => $overall_total,
                            'overall_obtained' => $overall_obtained,
                            'percentage' => $percentage,
                            'grade' => calculate_grade_final($percentage)
                        );
                    }
                    
                    $subjects_data[] = $subject_entry;
                }
            }
            
            update_post_meta($student_id, 'subjects', $subjects_data);
        } else {
            // If no subjects submitted, keep existing subjects
            // Or clear if you want: delete_post_meta($student_id, 'subjects');
        }
        
        // Handle photo upload
        if (isset($_FILES['student_photo']) && $_FILES['student_photo']['error'] == 0) {
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            
            $attachment_id = media_handle_upload('student_photo', $student_id);
            
            if (!is_wp_error($attachment_id)) {
                update_post_meta($student_id, 'student_photo', $attachment_id);
                set_post_thumbnail($student_id, $attachment_id);
            }
        }
        
        return true;
        
    } catch (Exception $e) {
        error_log('Student update error: ' . $e->getMessage());
        return false;
    }
}

function calculate_grade_final($percentage) {
    if ($percentage >= 90) return 'A+';
    if ($percentage >= 80) return 'A';
    if ($percentage >= 70) return 'B';
    if ($percentage >= 60) return 'C';
    if ($percentage >= 50) return 'D';
    return 'F';
}

function alhuffaz_student_edit_only($atts) {
    $atts = shortcode_atts(array(
        'id' => isset($_GET['edit']) ? intval($_GET['edit']) : 0
    ), $atts);
    
    $student_id = intval($atts['id']);
    
    if (!$student_id || get_post_type($student_id) !== 'student') {
        return '<div class="edit-error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h3>Student Not Found</h3>
                    <p>Invalid student ID or student does not exist.</p>
                </div>';
    }
    
    // Load student data - MUST USE SAME FIELD NAMES AS MAIN FORM
    $student_data = array(
        'student_name' => get_the_title($student_id),
        'gr_number' => get_post_meta($student_id, 'gr_number', true),
        'roll_number' => get_post_meta($student_id, 'roll_number', true),
        'gender' => get_post_meta($student_id, 'gender', true),
        'date_of_birth' => get_post_meta($student_id, 'date_of_birth', true),
        'admission_date' => get_post_meta($student_id, 'admission_date', true),
        'grade_level' => get_post_meta($student_id, 'grade_level', true),
        'islamic_studies_category' => get_post_meta($student_id, 'islamic_studies_category', true),
        'permanent_address' => get_post_meta($student_id, 'permanent_address', true),
        'current_address' => get_post_meta($student_id, 'current_address', true),
        'father_name' => get_post_meta($student_id, 'father_name', true), // FIXED: father_name NOT parent_name
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
        'annual_fee' => get_post_meta($student_id, 'annual_fee', true), // ADDED
        'admission_fee' => get_post_meta($student_id, 'admission_fee', true), // ADDED
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
    );
    
    $subjects = get_post_meta($student_id, 'subjects', true);
    if (!is_array($subjects)) {
        $subjects = array();
    }
    
    $photo_id = get_post_meta($student_id, 'student_photo', true);
    $photo_url = $photo_id ? wp_get_attachment_image_url($photo_id, 'medium') : '';
    
    $success = get_transient('student_form_success');
    if ($success) delete_transient('student_form_success');
    
    ob_start();
    ?>
    
    <div class="complete-student-form">
        
        <?php if ($success): ?>
            <div class="form-alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span><?php echo esc_html($success); ?></span>
            </div>
        <?php endif; ?>
        
        <div class="form-main-header">
            <div class="header-icon">
                <i class="fas fa-edit"></i>
            </div>
            <div class="header-text">
                <h1>Edit Student Record</h1>
                <p>Update student information</p>
            </div>
            <?php if ($photo_url): ?>
                <div class="header-photo">
                    <img src="<?php echo esc_url($photo_url); ?>" alt="Student Photo">
                </div>
            <?php endif; ?>
        </div>
        
        <form method="post" enctype="multipart/form-data" id="studentEditForm" class="student-main-form">
            <?php wp_nonce_field('student_form_submit', 'student_form_nonce'); ?>
            <input type="hidden" name="student_id" value="<?php echo $student_id; ?>">
            
            <!-- Progress bar (same as main form) -->
            <div class="form-progress-bar">
                <div class="progress-step active" data-step="1">
                    <div class="step-circle">1</div>
                    <div class="step-text">Basic</div>
                </div>
                <div class="progress-line"></div>
                <div class="progress-step" data-step="2">
                    <div class="step-circle">2</div>
                    <div class="step-text">Family</div>
                </div>
                <div class="progress-line"></div>
                <div class="progress-step" data-step="3">
                    <div class="step-circle">3</div>
                    <div class="step-text">Academic</div>
                </div>
                <div class="progress-line"></div>
                <div class="progress-step" data-step="4">
                    <div class="step-circle">4</div>
                    <div class="step-text">Fees</div>
                </div>
                <div class="progress-line"></div>
                <div class="progress-step" data-step="5">
                    <div class="step-circle">5</div>
                    <div class="step-text">Health</div>
                </div>
            </div>
            
            <!-- STEP 1: BASIC INFORMATION -->
            <div class="form-step-container active" data-step="1">
                <!-- COPY EXACT HTML FROM MAIN FORM - I'll show key differences -->
                <div class="step-header">
                    <i class="fas fa-user"></i>
                    <h2>Basic Information</h2>
                </div>
                
                <div class="form-row">
                    <div class="form-col-6">
                        <label class="form-label required">
                            <i class="fas fa-user-graduate"></i>
                            Student Full Name
                        </label>
                        <input type="text" name="student_name" class="form-input" required 
                               value="<?php echo esc_attr($student_data['student_name']); ?>">
                    </div>
                    
                    <div class="form-col-6">
                        <label class="form-label required">
                            <i class="fas fa-id-card"></i>
                            GR Number
                        </label>
                        <input type="text" name="gr_number" class="form-input" required 
                               value="<?php echo esc_attr($student_data['gr_number']); ?>">
                    </div>
                </div>
                
                <!-- ... Rest of STEP 1 fields (copy from main form exactly) ... -->
                
            </div>
            
            <!-- STEP 2: FAMILY -->
            <div class="form-step-container" data-step="2">
                <div class="step-header">
                    <i class="fas fa-users"></i>
                    <h2>Family Information</h2>
                </div>
                
                <div class="info-section">
                    <h3><i class="fas fa-user-tie"></i> Father Information</h3> <!-- FIXED: Father NOT Parent -->
                    
                    <div class="form-row">
                        <div class="form-col-6">
                            <label class="form-label">Father's Name</label> <!-- FIXED -->
                            <input type="text" name="father_name" class="form-input" <!-- FIXED: father_name -->
                                   value="<?php echo esc_attr($student_data['father_name']); ?>">
                        </div>
                        
                        <div class="form-col-6">
                            <label class="form-label">CNIC Number</label>
                            <input type="text" name="father_cnic" class="form-input" <!-- FIXED: father_cnic -->
                                   value="<?php echo esc_attr($student_data['father_cnic']); ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col-12">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="father_email" class="form-input" <!-- FIXED: father_email -->
                                   value="<?php echo esc_attr($student_data['father_email']); ?>">
                        </div>
                    </div>
                </div>
                
                <!-- ... Rest of family fields (same as main form) ... -->
                
            </div>
            
            <!-- STEP 3: ACADEMIC -->
            <div class="form-step-container" data-step="3">
                <div class="step-header">
                    <i class="fas fa-book"></i>
                    <h2>Academic Information</h2>
                </div>
                
                <!-- CRITICAL: Subjects section with monthly repeater -->
                <div class="info-section">
                    <div class="section-header-row">
                        <h3><i class="fas fa-graduation-cap"></i> Subject Performance</h3>
                        <button type="button" class="btn-add-subject" id="addSubjectBtn">
                            <i class="fas fa-plus"></i> Add Subject
                        </button>
                    </div>
                    
                    <div id="subjectsContainer">
                        <?php if (!empty($subjects)): 
                            foreach ($subjects as $index => $subject): ?>
                                <?php echo render_subject_fields_fixed($index, $subject); ?>
                            <?php endforeach;
                        endif; ?>
                    </div>
                </div>
                
                <!-- ... Rest of academic fields ... -->
                
            </div>
            
            <!-- STEP 4: FEES -->
            <div class="form-step-container" data-step="4">
                <div class="step-header">
                    <i class="fas fa-money-bill-wave"></i>
                    <h2>Fee Structure</h2>
                </div>
                
                <div class="fee-cards-grid">
                    <!-- MUST MATCH MAIN FORM FEE FIELDS -->
                    <div class="fee-card">
                        <div class="fee-card-icon">
                            <i class="fas fa-sync-alt"></i>
                        </div>
                        <h4>Monthly Tuition</h4>
                        <div class="fee-input-wrapper">
                            <span class="currency-symbol">PKR</span>
                            <input type="number" name="monthly_tuition_fee" class="form-input fee-amount" 
                                   value="<?php echo esc_attr($student_data['monthly_tuition_fee']); ?>">
                        </div>
                    </div>
                    
                    <div class="fee-card">
                        <div class="fee-card-icon">
                            <i class="fas fa-book-open"></i>
                        </div>
                        <h4>Course Fee</h4>
                        <div class="fee-input-wrapper">
                            <span class="currency-symbol">PKR</span>
                            <input type="number" name="course_fee" class="form-input fee-amount" 
                                   value="<?php echo esc_attr($student_data['course_fee']); ?>">
                        </div>
                    </div>
                    
                    <div class="fee-card">
                        <div class="fee-card-icon">
                            <i class="fas fa-tshirt"></i>
                        </div>
                        <h4>Uniform Fee</h4>
                        <div class="fee-input-wrapper">
                            <span class="currency-symbol">PKR</span>
                            <input type="number" name="uniform_fee" class="form-input fee-amount" 
                                   value="<?php echo esc_attr($student_data['uniform_fee']); ?>">
                        </div>
                    </div>
                    
                    <!-- ADDED: Annual Fee (missing in your edit form) -->
                    <div class="fee-card">
                        <div class="fee-card-icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <h4>Annual Fee</h4>
                        <div class="fee-input-wrapper">
                            <span class="currency-symbol">PKR</span>
                            <input type="number" name="annual_fee" class="form-input fee-amount" 
                                   value="<?php echo esc_attr($student_data['annual_fee']); ?>">
                        </div>
                    </div>
                    
                    <!-- ADDED: Admission Fee (missing in your edit form) -->
                    <div class="fee-card">
                        <div class="fee-card-icon">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <h4>Admission Fee</h4>
                        <div class="fee-input-wrapper">
                            <span class="currency-symbol">PKR</span>
                            <input type="number" name="admission_fee" class="form-input fee-amount" 
                                   value="<?php echo esc_attr($student_data['admission_fee']); ?>">
                        </div>
                    </div>
                </div>
                
                <!-- ... Rest of fee fields ... -->
                
            </div>
            
            <!-- STEP 5: HEALTH -->
            <div class="form-step-container" data-step="5">
                <div class="step-header">
                    <i class="fas fa-heartbeat"></i>
                    <h2>Health Information</h2>
                </div>
                
                <!-- Health fields here (move from step 3 if needed) -->
                
            </div>
            
            <!-- Navigation -->
            <div class="form-navigation-bar">
                <button type="button" class="nav-button btn-prev" id="prevStepBtn" style="display:none;">
                    <i class="fas fa-arrow-left"></i>
                    Previous
                </button>
                
                <button type="button" class="nav-button btn-next" id="nextStepBtn">
                    Next
                    <i class="fas fa-arrow-right"></i>
                </button>
                
                <button type="submit" class="nav-button btn-submit" name="update_student_submit" id="submitFormBtn" style="display:none;">
                    <i class="fas fa-save"></i>
                    Update Student
                </button>
            </div>
            
        </form>
    </div>
    
    <?php
    // Add the SAME JavaScript as main form
    add_edit_scripts_fixed($subjects);
    // Add the SAME CSS as main form
    add_edit_styles_fixed();
    
    return ob_get_clean();
}

// CRITICAL: This must be EXACTLY the same as main form's render_subject_fields
function render_subject_fields_fixed($index, $subject = array()) {
    ob_start();
    ?>
    <div class="subject-box" data-index="<?php echo $index; ?>">
        <div class="subject-header-bar">
            <input type="text" name="subjects[<?php echo $index; ?>][name]" 
                   class="subject-name-input" placeholder="Subject Name" 
                   value="<?php echo esc_attr($subject['name'] ?? ''); ?>">
            <button type="button" class="btn-remove-subject">
                <i class="fas fa-trash"></i>
            </button>
        </div>
        
        <!-- MONTHLY EXAMS (REPEATER) - SAME AS MAIN FORM -->
        <div class="exam-type-section">
            <div class="exam-type-header">
                <h5><i class="fas fa-calendar"></i> Monthly Exams</h5>
                <button type="button" class="btn-add-monthly-exam" data-subject-index="<?php echo $index; ?>">
                    <i class="fas fa-plus-circle"></i> Add Month
                </button>
            </div>
            
            <div class="monthly-exams-container" data-subject-index="<?php echo $index; ?>">
                <?php 
                $monthly_exams = $subject['monthly_exams'] ?? array();
                if (!empty($monthly_exams)):
                    foreach ($monthly_exams as $month_index => $monthly): ?>
                        <div class="monthly-exam-entry" data-month-index="<?php echo $month_index; ?>">
                            <div class="monthly-exam-header">
                                <input type="text" name="subjects[<?php echo $index; ?>][monthly_exams][<?php echo $month_index; ?>][month_name]" 
                                       placeholder="e.g., January or Month 1" class="month-name-input"
                                       value="<?php echo esc_attr($monthly['month_name'] ?? ''); ?>">
                                <button type="button" class="btn-remove-monthly-exam">
                                    <i class="fas fa-times-circle"></i>
                                </button>
                            </div>
                            
                            <div class="marks-grid-new">
                                <div class="marks-column">
                                    <label>Oral Total</label>
                                    <input type="number" name="subjects[<?php echo $index; ?>][monthly_exams][<?php echo $month_index; ?>][oral_total]" 
                                           class="marks-input oral-total" data-subject="<?php echo $index; ?>" data-month="<?php echo $month_index; ?>"
                                           placeholder="0" value="<?php echo esc_attr($monthly['oral_total'] ?? ''); ?>">
                                </div>
                                <div class="marks-column">
                                    <label>Oral Obtained</label>
                                    <input type="number" name="subjects[<?php echo $index; ?>][monthly_exams][<?php echo $month_index; ?>][oral_obtained]" 
                                           class="marks-input oral-obtained" data-subject="<?php echo $index; ?>" data-month="<?php echo $month_index; ?>"
                                           placeholder="0" value="<?php echo esc_attr($monthly['oral_obtained'] ?? ''); ?>">
                                </div>
                                <div class="marks-column">
                                    <label>Written Total</label>
                                    <input type="number" name="subjects[<?php echo $index; ?>][monthly_exams][<?php echo $month_index; ?>][written_total]" 
                                           class="marks-input written-total" data-subject="<?php echo $index; ?>" data-month="<?php echo $month_index; ?>"
                                           placeholder="0" value="<?php echo esc_attr($monthly['written_total'] ?? ''); ?>">
                                </div>
                                <div class="marks-column">
                                    <label>Written Obtained</label>
                                    <input type="number" name="subjects[<?php echo $index; ?>][monthly_exams][<?php echo $month_index; ?>][written_obtained]" 
                                           class="marks-input written-obtained" data-subject="<?php echo $index; ?>" data-month="<?php echo $month_index; ?>"
                                           placeholder="0" value="<?php echo esc_attr($monthly['written_obtained'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="marks-result-display" id="monthly-result-<?php echo $index; ?>-<?php echo $month_index; ?>">
                                <?php if (isset($monthly['percentage'])): ?>
                                    <div class="marks-calculation-result marks-<?php echo ($monthly['percentage'] >= 80) ? 'excellent' : (($monthly['percentage'] >= 60) ? 'good' : 'poor'); ?>">
                                        <div class="marks-summary">
                                            <div class="marks-item">
                                                <span class="marks-label">Overall Total:</span>
                                                <span class="marks-value"><?php echo $monthly['overall_total'] ?? 0; ?></span>
                                            </div>
                                            <div class="marks-item">
                                                <span class="marks-label">Overall Obtained:</span>
                                                <span class="marks-value"><?php echo $monthly['overall_obtained'] ?? 0; ?></span>
                                            </div>
                                            <div class="marks-item">
                                                <span class="marks-label">Percentage:</span>
                                                <span class="marks-value"><?php echo $monthly['percentage'] ?? 0; ?>%</span>
                                            </div>
                                            <div class="marks-item">
                                                <span class="marks-label">Grade:</span>
                                                <span class="marks-value grade-badge"><?php echo $monthly['grade'] ?? 'F'; ?></span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach;
                endif; ?>
            </div>
        </div>
        
        <!-- MID SEMESTER - SAME AS MAIN FORM -->
        <div class="exam-type-section">
            <div class="exam-type-header">
                <h5><i class="fas fa-book"></i> Mid Semester</h5>
            </div>
            
            <div class="marks-grid-new">
                <?php $mid = $subject['mid_semester'] ?? array(); ?>
                <div class="marks-column">
                    <label>Oral Total</label>
                    <input type="number" name="subjects[<?php echo $index; ?>][mid_semester][oral_total]" 
                           class="marks-input oral-total" data-subject="<?php echo $index; ?>" data-exam="mid"
                           placeholder="0" value="<?php echo esc_attr($mid['oral_total'] ?? ''); ?>">
                </div>
                <div class="marks-column">
                    <label>Oral Obtained</label>
                    <input type="number" name="subjects[<?php echo $index; ?>][mid_semester][oral_obtained]" 
                           class="marks-input oral-obtained" data-subject="<?php echo $index; ?>" data-exam="mid"
                           placeholder="0" value="<?php echo esc_attr($mid['oral_obtained'] ?? ''); ?>">
                </div>
                <div class="marks-column">
                    <label>Written Total</label>
                    <input type="number" name="subjects[<?php echo $index; ?>][mid_semester][written_total]" 
                           class="marks-input written-total" data-subject="<?php echo $index; ?>" data-exam="mid"
                           placeholder="0" value="<?php echo esc_attr($mid['written_total'] ?? ''); ?>">
                </div>
                <div class="marks-column">
                    <label>Written Obtained</label>
                    <input type="number" name="subjects[<?php echo $index; ?>][mid_semester][written_obtained]" 
                           class="marks-input written-obtained" data-subject="<?php echo $index; ?>" data-exam="mid"
                           placeholder="0" value="<?php echo esc_attr($mid['written_obtained'] ?? ''); ?>">
                </div>
            </div>
            <div class="marks-result-display" id="mid-result-<?php echo $index; ?>">
                <?php if (isset($mid['percentage'])): ?>
                    <div class="marks-calculation-result marks-<?php echo ($mid['percentage'] >= 80) ? 'excellent' : (($mid['percentage'] >= 60) ? 'good' : 'poor'); ?>">
                        <div class="marks-summary">
                            <div class="marks-item">
                                <span class="marks-label">Overall Total:</span>
                                <span class="marks-value"><?php echo $mid['overall_total'] ?? 0; ?></span>
                            </div>
                            <div class="marks-item">
                                <span class="marks-label">Overall Obtained:</span>
                                <span class="marks-value"><?php echo $mid['overall_obtained'] ?? 0; ?></span>
                            </div>
                            <div class="marks-item">
                                <span class="marks-label">Percentage:</span>
                                <span class="marks-value"><?php echo $mid['percentage'] ?? 0; ?>%</span>
                            </div>
                            <div class="marks-item">
                                <span class="marks-label">Grade:</span>
                                <span class="marks-value grade-badge"><?php echo $mid['grade'] ?? 'F'; ?></span>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- FINAL SEMESTER - SAME AS MAIN FORM -->
        <div class="exam-type-section">
            <div class="exam-type-header">
                <h5><i class="fas fa-graduation-cap"></i> Final Semester</h5>
            </div>
            
            <div class="marks-grid-new">
                <?php $final = $subject['final_semester'] ?? array(); ?>
                <div class="marks-column">
                    <label>Oral Total</label>
                    <input type="number" name="subjects[<?php echo $index; ?>][final_semester][oral_total]" 
                           class="marks-input oral-total" data-subject="<?php echo $index; ?>" data-exam="final"
                           placeholder="0" value="<?php echo esc_attr($final['oral_total'] ?? ''); ?>">
                </div>
                <div class="marks-column">
                    <label>Oral Obtained</label>
                    <input type="number" name="subjects[<?php echo $index; ?>][final_semester][oral_obtained]" 
                           class="marks-input oral-obtained" data-subject="<?php echo $index; ?>" data-exam="final"
                           placeholder="0" value="<?php echo esc_attr($final['oral_obtained'] ?? ''); ?>">
                </div>
                <div class="marks-column">
                    <label>Written Total</label>
                    <input type="number" name="subjects[<?php echo $index; ?>][final_semester][written_total]" 
                           class="marks-input written-total" data-subject="<?php echo $index; ?>" data-exam="final"
                           placeholder="0" value="<?php echo esc_attr($final['written_total'] ?? ''); ?>">
                </div>
                <div class="marks-column">
                    <label>Written Obtained</label>
                    <input type="number" name="subjects[<?php echo $index; ?>][final_semester][written_obtained]" 
                           class="marks-input written-obtained" data-subject="<?php echo $index; ?>" data-exam="final"
                           placeholder="0" value="<?php echo esc_attr($final['written_obtained'] ?? ''); ?>">
                </div>
            </div>
            <div class="marks-result-display" id="final-result-<?php echo $index; ?>">
                <?php if (isset($final['percentage'])): ?>
                    <div class="marks-calculation-result marks-<?php echo ($final['percentage'] >= 80) ? 'excellent' : (($final['percentage'] >= 60) ? 'good' : 'poor'); ?>">
                        <div class="marks-summary">
                            <div class="marks-item">
                                <span class="marks-label">Overall Total:</span>
                                <span class="marks-value"><?php echo $final['overall_total'] ?? 0; ?></span>
                            </div>
                            <div class="marks-item">
                                <span class="marks-label">Overall Obtained:</span>
                                <span class="marks-value"><?php echo $final['overall_obtained'] ?? 0; ?></span>
                            </div>
                            <div class="marks-item">
                                <span class="marks-label">Percentage:</span>
                                <span class="marks-value"><?php echo $final['percentage'] ?? 0; ?>%</span>
                            </div>
                            <div class="marks-item">
                                <span class="marks-label">Grade:</span>
                                <span class="marks-value grade-badge"><?php echo $final['grade'] ?? 'F'; ?></span>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Teacher comments -->
        <div class="teacher-assessment-fields">
            <textarea name="subjects[<?php echo $index; ?>][strengths]" 
                      placeholder="Strengths" rows="2"><?php echo esc_textarea($subject['strengths'] ?? ''); ?></textarea>
            <textarea name="subjects[<?php echo $index; ?>][areas_for_improvement]" 
                      placeholder="Areas for Improvement" rows="2"><?php echo esc_textarea($subject['areas_for_improvement'] ?? ''); ?></textarea>
            <textarea name="subjects[<?php echo $index; ?>][teacher_comments]" 
                      placeholder="Teacher Comments" rows="2"><?php echo esc_textarea($subject['teacher_comments'] ?? ''); ?></textarea>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

function add_edit_scripts_fixed($subjects) {
    $subject_count = count($subjects);
    ?>
    <script>
    jQuery(document).ready(function($) {
        // COPY EXACT JAVASCRIPT FROM MAIN FORM
        let currentStep = 1;
        const totalSteps = 5;
        let subjectIndex = <?php echo $subject_count; ?>;
        let monthlyExamCounters = {};
        
        if (typeof flatpickr !== 'undefined') {
            flatpickr('.datepicker', {
                dateFormat: 'Y-m-d',
                altInput: true,
                altFormat: 'F j, Y'
            });
        }
        
        function showStep(step) {
            $('.form-step-container').removeClass('active');
            $('.form-step-container[data-step="' + step + '"]').addClass('active');
            
            $('.progress-step').removeClass('active').each(function() {
                const stepNum = $(this).data('step');
                if (stepNum < step) {
                    $(this).addClass('completed');
                } else {
                    $(this).removeClass('completed');
                }
                if (stepNum === step) {
                    $(this).addClass('active');
                }
            });
            
            $('#prevStepBtn').toggle(step > 1);
            $('#nextStepBtn').toggle(step < totalSteps);
            $('#submitFormBtn').toggle(step === totalSteps);
            
            $('html, body').animate({ scrollTop: $('.complete-student-form').offset().top - 20 }, 400);
        }
        
        $('#nextStepBtn').on('click', function() {
            if (validateCurrentStep() && currentStep < totalSteps) {
                currentStep++;
                showStep(currentStep);
            }
        });
        
        $('#prevStepBtn').on('click', function() {
            if (currentStep > 1) {
                currentStep--;
                showStep(currentStep);
            }
        });
        
        function validateCurrentStep() {
            let isValid = true;
            const currentStepEl = $('.form-step-container[data-step="' + currentStep + '"]');
            
            currentStepEl.find('input[required], select[required]').each(function() {
                if (!$(this).val()) {
                    $(this).focus();
                    $(this).addClass('error-input');
                    alert('Please fill in all required fields marked with *');
                    isValid = false;
                    return false;
                }
            });
            
            return isValid;
        }
        
        $('input, select').on('input change', function() {
            $(this).removeClass('error-input');
        });
        
        // Add Subject - SAME AS MAIN FORM
        $('#addSubjectBtn').on('click', function() {
            const subjectHTML = `<?php 
                // We need the template for new subjects
                $template = render_subject_fields_fixed('SUBJECT_INDEX');
                echo addslashes($template);
            ?>`.replace(/SUBJECT_INDEX/g, subjectIndex);
            
            $('#subjectsContainer').append(subjectHTML);
            monthlyExamCounters[subjectIndex] = 0;
            subjectIndex++;
        });
        
        // Remove Subject
        $(document).on('click', '.btn-remove-subject', function() {
            if (confirm('Remove this subject?')) {
                $(this).closest('.subject-box').remove();
            }
        });
        
        // Add Monthly Exam - SAME AS MAIN FORM
        $(document).on('click', '.btn-add-monthly-exam', function() {
            const subjIndex = $(this).data('subject-index');
            if (!monthlyExamCounters[subjIndex]) {
                monthlyExamCounters[subjIndex] = 0;
            }
            const monthIndex = monthlyExamCounters[subjIndex];
            
            const monthlyHTML = `
                <div class="monthly-exam-entry" data-month-index="${monthIndex}">
                    <div class="monthly-exam-header">
                        <input type="text" name="subjects[${subjIndex}][monthly_exams][${monthIndex}][month_name]" 
                               placeholder="e.g., January or Month 1" class="month-name-input">
                        <button type="button" class="btn-remove-monthly-exam">
                            <i class="fas fa-times-circle"></i>
                        </button>
                    </div>
                    
                    <div class="marks-grid-new">
                        <div class="marks-column">
                            <label>Oral Total</label>
                            <input type="number" name="subjects[${subjIndex}][monthly_exams][${monthIndex}][oral_total]" 
                                   class="marks-input oral-total" data-subject="${subjIndex}" data-month="${monthIndex}" placeholder="0">
                        </div>
                        <div class="marks-column">
                            <label>Oral Obtained</label>
                            <input type="number" name="subjects[${subjIndex}][monthly_exams][${monthIndex}][oral_obtained]" 
                                   class="marks-input oral-obtained" data-subject="${subjIndex}" data-month="${monthIndex}" placeholder="0">
                        </div>
                        <div class="marks-column">
                            <label>Written Total</label>
                            <input type="number" name="subjects[${subjIndex}][monthly_exams][${monthIndex}][written_total]" 
                                   class="marks-input written-total" data-subject="${subjIndex}" data-month="${monthIndex}" placeholder="0">
                        </div>
                        <div class="marks-column">
                            <label>Written Obtained</label>
                            <input type="number" name="subjects[${subjIndex}][monthly_exams][${monthIndex}][written_obtained]" 
                                   class="marks-input written-obtained" data-subject="${subjIndex}" data-month="${monthIndex}" placeholder="0">
                        </div>
                    </div>
                    
                    <div class="marks-result-display" id="monthly-result-${subjIndex}-${monthIndex}"></div>
                </div>
            `;
            
            $('.monthly-exams-container[data-subject-index="' + subjIndex + '"]').append(monthlyHTML);
            monthlyExamCounters[subjIndex]++;
        });
        
        // Remove Monthly Exam
        $(document).on('click', '.btn-remove-monthly-exam', function() {
            if (confirm('Remove this monthly exam?')) {
                $(this).closest('.monthly-exam-entry').remove();
            }
        });
        
        // CRITICAL: Real-time marks calculation - SAME AS MAIN FORM
        $(document).on('input', '.marks-input', function() {
            const container = $(this).closest('.monthly-exam-entry, .exam-type-section');
            const subjectIndex = $(this).data('subject');
            const monthIndex = $(this).data('month');
            const examType = $(this).data('exam');
            
            // Get all values
            const oralTotal = parseFloat(container.find('.oral-total').val()) || 0;
            const oralObtained = parseFloat(container.find('.oral-obtained').val()) || 0;
            const writtenTotal = parseFloat(container.find('.written-total').val()) || 0;
            const writtenObtained = parseFloat(container.find('.written-obtained').val()) || 0;
            
            // Calculate
            const overallTotal = oralTotal + writtenTotal;
            const overallObtained = oralObtained + writtenObtained;
            const percentage = (overallTotal > 0) ? ((overallObtained / overallTotal) * 100).toFixed(2) : 0;
            
            // Determine grade
            let grade = 'F';
            if (percentage >= 90) grade = 'A+';
            else if (percentage >= 80) grade = 'A';
            else if (percentage >= 70) grade = 'B';
            else if (percentage >= 60) grade = 'C';
            else if (percentage >= 50) grade = 'D';
            
            // Determine status class
            let statusClass = 'marks-poor';
            if (percentage >= 80) statusClass = 'marks-excellent';
            else if (percentage >= 60) statusClass = 'marks-good';
            
            // Display result
            let resultHTML = '';
            if (overallTotal > 0) {
                resultHTML = `
                    <div class="marks-calculation-result ${statusClass}">
                        <div class="marks-summary">
                            <div class="marks-item">
                                <span class="marks-label">Overall Total:</span>
                                <span class="marks-value">${overallTotal}</span>
                            </div>
                            <div class="marks-item">
                                <span class="marks-label">Overall Obtained:</span>
                                <span class="marks-value">${overallObtained}</span>
                            </div>
                            <div class="marks-item">
                                <span class="marks-label">Percentage:</span>
                                <span class="marks-value">${percentage}%</span>
                            </div>
                            <div class="marks-item">
                                <span class="marks-label">Grade:</span>
                                <span class="marks-value grade-badge">${grade}</span>
                            </div>
                        </div>
                    </div>
                `;
            }
            
            // Find result display div
            let resultDiv;
            if (monthIndex !== undefined) {
                resultDiv = $(`#monthly-result-${subjectIndex}-${monthIndex}`);
            } else if (examType === 'mid') {
                resultDiv = $(`#mid-result-${subjectIndex}`);
            } else if (examType === 'final') {
                resultDiv = $(`#final-result-${subjectIndex}`);
            }
            
            if (resultDiv && resultDiv.length) {
                resultDiv.html(resultHTML);
            }
        });
        
        // Fee calculation - SAME AS MAIN FORM
        $('.fee-amount').on('input', function() {
            const monthly = parseFloat($('input[name="monthly_tuition_fee"]').val()) || 0;
            const course = parseFloat($('input[name="course_fee"]').val()) || 0;
            const uniform = parseFloat($('input[name="uniform_fee"]').val()) || 0;
            const annual = parseFloat($('input[name="annual_fee"]').val()) || 0;
            const admission = parseFloat($('input[name="admission_fee"]').val()) || 0;
            
            const oneTime = course + uniform + annual + admission;
            const total = oneTime + monthly;
            
            $('#monthlyFeeTotal').text('PKR ' + monthly.toLocaleString());
            $('#oneTimeFeeTotal').text('PKR ' + oneTime.toLocaleString());
            $('#grandFeeTotal').text('PKR ' + total.toLocaleString());
        });
        
        // Attendance calculation - SAME AS MAIN FORM
        function calculateAttendance() {
            const total = parseFloat($('#totalSchoolDays').val()) || 0;
            const present = parseFloat($('#presentDays').val()) || 0;
            
            if (total > 0 && present > 0) {
                const percentage = ((present / total) * 100).toFixed(2);
                let status = 'Excellent';
                let statusClass = 'status-excellent';
                
                if (percentage < 75) {
                    status = 'Poor';
                    statusClass = 'status-poor';
                } else if (percentage < 85) {
                    status = 'Good';
                    statusClass = 'status-good';
                }
                
                $('#attendanceDisplay').html(`
                    <div class="attendance-result ${statusClass}">
                        <div class="att-percentage">${percentage}%</div>
                        <div class="att-status">${status} Attendance</div>
                        <div class="att-fraction">${present} / ${total} days</div>
                    </div>
                `).show();
            } else {
                $('#attendanceDisplay').hide();
            }
        }
        
        $('#totalSchoolDays, #presentDays').on('input', calculateAttendance);
        
        // Initialize on page load
        $('.fee-amount').trigger('input');
        calculateAttendance();
        
        // Initialize monthly exam counters
        <?php if (!empty($subjects)): ?>
            <?php foreach ($subjects as $idx => $subj): ?>
                monthlyExamCounters[<?php echo $idx; ?>] = <?php echo count($subj['monthly_exams'] ?? array()); ?>;
            <?php endforeach; ?>
        <?php endif; ?>
        
        // Trigger calculation on page load
        setTimeout(function() {
            $('.marks-input').trigger('input');
        }, 500);
    });
    </script>
    <?php
}

function add_edit_styles_fixed() {
    ?>
    <style>
    /* COPY EXACT CSS FROM MAIN FORM - All 800+ lines */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    
    .complete-student-form {
        font-family: 'Poppins', sans-serif;
        max-width: 1200px;
        margin: 0 auto;
        padding: 0;
        font-size: 16px;
        line-height: 1.6;
    }
    
    /* ... COPY ALL CSS FROM MAIN FORM EXACTLY ... */
    /* You need to copy the ENTIRE CSS from your main form plugin */
    /* This ensures identical styling */
    
    </style>
    <?php
}