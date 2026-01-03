<?php
/*
Plugin Name: Al-Huffaz Complete Student Form (Blue Theme) - FINAL
Description: Complete form with all modifications - Monthly Repeater, Health moved, Auto-calculations
Version: 8.1 FINAL
Author: RoohUl Hasnain
*/

defined('ABSPATH') || exit;

// Register shortcodes
add_shortcode('student_form', 'alhuffaz_complete_form');
add_shortcode('student_edit', 'alhuffaz_complete_form');

// Enqueue assets
add_action('wp_enqueue_scripts', 'student_form_assets');

function student_form_assets() {
    wp_enqueue_script('jquery');
    wp_enqueue_style('google-fonts', 'https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap');
    wp_enqueue_style('flatpickr-css', 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css');
    wp_enqueue_script('flatpickr-js', 'https://cdn.jsdelivr.net/npm/flatpickr', array('jquery'), null, true);
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css');
}

// Handle form submission
add_action('init', 'handle_complete_form_submission');

function handle_complete_form_submission() {
    if (isset($_POST['submit_student_form']) && isset($_POST['student_form_nonce']) && 
        wp_verify_nonce($_POST['student_form_nonce'], 'student_form_submit')) {
        $result = process_student_add();
        if (is_wp_error($result)) {
            set_transient('student_form_error', $result->get_error_message(), 30);
        } else {
            set_transient('student_form_success', $result, 30);
            wp_redirect(add_query_arg('portal_tab', 'students', remove_query_arg('action')));
            exit;
        }
    }
    
    if (isset($_POST['update_student_submit']) && isset($_POST['student_id']) &&
        isset($_POST['student_form_nonce']) && wp_verify_nonce($_POST['student_form_nonce'], 'student_form_submit')) {
        $result = process_student_update();
        if (is_wp_error($result)) {
            set_transient('student_form_error', $result->get_error_message(), 30);
        } else {
            set_transient('student_form_success', 'Student updated successfully!', 30);
            wp_redirect(add_query_arg('portal_tab', 'students', remove_query_arg(array('edit', 'action'))));
            exit;
        }
    }
}

function process_student_add() {
    try {
        if (empty($_POST['student_name']) || empty($_POST['gr_number'])) {
            return new WP_Error('missing_field', 'Student name and GR number are required.');
        }
        $post_data = array(
            'post_title' => sanitize_text_field($_POST['student_name']),
            'post_status' => 'publish',
            'post_type' => 'student'
        );
        $post_id = wp_insert_post($post_data);
        if (is_wp_error($post_id)) {
            return $post_id;
        }
        save_student_meta($post_id);
        return get_post_meta($post_id, 'gr_number', true);
    } catch (Exception $e) {
        return new WP_Error('processing_error', $e->getMessage());
    }
}

function process_student_update() {
    try {
        $student_id = intval($_POST['student_id']);
        if (get_post_type($student_id) !== 'student') {
            return new WP_Error('invalid_student', 'Invalid student ID.');
        }
        wp_update_post(array(
            'ID' => $student_id,
            'post_title' => sanitize_text_field($_POST['student_name'])
        ));
        save_student_meta($student_id);
        return true;
    } catch (Exception $e) {
        return new WP_Error('processing_error', $e->getMessage());
    }
}

function save_student_meta($post_id) {
    $fields = array(
        'gr_number', 'roll_number', 'gender', 'date_of_birth', 'admission_date',
        'grade_level', 'islamic_studies_category', 'permanent_address', 'current_address',
        'father_name', 'father_cnic', 'father_email',
        'guardian_name', 'guardian_cnic', 'guardian_email', 'guardian_phone',
        'guardian_whatsapp', 'relationship_to_student', 'emergency_contact', 'emergency_whatsapp',
        'monthly_tuition_fee', 'course_fee', 'uniform_fee', 'annual_fee', 'admission_fee',
        'zakat_eligible', 'donation_eligible', 'blood_group', 'allergies', 'medical_conditions',
        'total_school_days', 'present_days', 'academic_term', 'academic_year',
        'health_rating', 'cleanness_rating', 'completes_homework', 'participates_in_class',
        'works_well_in_groups', 'problem_solving_skills', 'organization_preparedness',
        'teacher_overall_comments', 'goal_1', 'goal_2', 'goal_3'
    );
    
    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
        }
    }
    
    // Process subjects with new structure
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
                
                // Process Monthly Exams (Repeater)
                if (isset($subject['monthly_exams']) && is_array($subject['monthly_exams'])) {
                    foreach ($subject['monthly_exams'] as $monthly) {
                        $oral_total = floatval($monthly['oral_total'] ?? 0);
                        $oral_obtained = floatval($monthly['oral_obtained'] ?? 0);
                        $written_total = floatval($monthly['written_total'] ?? 0);
                        $written_obtained = floatval($monthly['written_obtained'] ?? 0);
                        $overall_total = $oral_total + $written_total;
                        $overall_obtained = $oral_obtained + $written_obtained;
                        $percentage = ($overall_total > 0) ? round(($overall_obtained / $overall_total) * 100, 2) : 0;
                        
                        $subject_entry['monthly_exams'][] = array(
                            'month_name' => sanitize_text_field($monthly['month_name'] ?? ''),
                            'oral_total' => $oral_total,
                            'oral_obtained' => $oral_obtained,
                            'written_total' => $written_total,
                            'written_obtained' => $written_obtained,
                            'overall_total' => $overall_total,
                            'overall_obtained' => $overall_obtained,
                            'percentage' => $percentage,
                            'grade' => calculate_student_grade($percentage)
                        );
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
                        'grade' => calculate_student_grade($percentage)
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
                        'grade' => calculate_student_grade($percentage)
                    );
                }
                
                $subjects_data[] = $subject_entry;
            }
        }
        update_post_meta($post_id, 'subjects', $subjects_data);
    }
    
    if (isset($_FILES['student_photo']) && $_FILES['student_photo']['error'] == 0) {
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        
        $attachment_id = media_handle_upload('student_photo', $post_id);
        
        if (!is_wp_error($attachment_id)) {
            update_post_meta($post_id, 'student_photo', $attachment_id);
            set_post_thumbnail($post_id, $attachment_id);
        }
    }
}

function calculate_student_grade($percentage) {
    if ($percentage >= 90) return 'A+';
    if ($percentage >= 80) return 'A';
    if ($percentage >= 70) return 'B';
    if ($percentage >= 60) return 'C';
    if ($percentage >= 50) return 'D';
    return 'F';
}

function alhuffaz_complete_form($atts) {
    $atts = shortcode_atts(array(
        'edit' => isset($_GET['edit']) ? intval($_GET['edit']) : 0,
        'id' => 0
    ), $atts);
    
    $edit_id = max(intval($atts['edit']), intval($atts['id']));
    $is_edit = ($edit_id > 0 && get_post_type($edit_id) === 'student');
    
    $success = get_transient('student_form_success');
    $error = get_transient('student_form_error');
    
    if ($success) delete_transient('student_form_success');
    if ($error) delete_transient('student_form_error');
    
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
    
    ob_start();
    ?>
    
    <div class="complete-student-form">
        
        <?php if ($success): ?>
            <div class="form-alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span><?php echo esc_html($success); ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="form-alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo esc_html($error); ?></span>
            </div>
        <?php endif; ?>
        
        <div class="form-main-header">
            <div class="header-icon">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <div class="header-text">
                <h1><?php echo $is_edit ? 'Edit Student Record' : 'Student Enrollment Form'; ?></h1>
                <p><?php echo $is_edit ? 'Update student information' : 'Complete all required fields'; ?></p>
            </div>
            <?php if ($is_edit && $photo_url): ?>
                <div class="header-photo">
                    <img src="<?php echo esc_url($photo_url); ?>" alt="Student Photo">
                </div>
            <?php endif; ?>
        </div>
        
        <form method="post" enctype="multipart/form-data" id="completeStudentForm" class="student-main-form">
            <?php wp_nonce_field('student_form_submit', 'student_form_nonce'); ?>
            <?php if ($is_edit): ?>
                <input type="hidden" name="student_id" value="<?php echo $edit_id; ?>">
            <?php endif; ?>
            
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
                               placeholder="Enter full name"
                               value="<?php echo esc_attr($student_data['student_name'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-col-6">
                        <label class="form-label required">
                            <i class="fas fa-id-card"></i>
                            GR Number
                        </label>
                        <input type="text" name="gr_number" class="form-input" required 
                               placeholder="e.g., GR-2025-001"
                               value="<?php echo esc_attr($student_data['gr_number'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-col-4">
                        <label class="form-label">
                            <i class="fas fa-hashtag"></i>
                            Roll Number
                        </label>
                        <input type="text" name="roll_number" class="form-input" 
                               placeholder="Roll number"
                               value="<?php echo esc_attr($student_data['roll_number'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-col-4">
                        <label class="form-label required">
                            <i class="fas fa-venus-mars"></i>
                            Gender
                        </label>
                        <select name="gender" class="form-input" required>
                            <option value="">Select Gender</option>
                            <option value="male" <?php selected($student_data['gender'] ?? '', 'male'); ?>>Male</option>
                            <option value="female" <?php selected($student_data['gender'] ?? '', 'female'); ?>>Female</option>
                        </select>
                    </div>
                    
                    <div class="form-col-4">
                        <label class="form-label">
                            <i class="fas fa-calendar-alt"></i>
                            Date of Birth
                        </label>
                        <input type="text" name="date_of_birth" class="form-input datepicker" 
                               placeholder="Select date"
                               value="<?php echo esc_attr($student_data['date_of_birth'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-col-4">
                        <label class="form-label">
                            <i class="fas fa-calendar-check"></i>
                            Admission Date
                        </label>
                        <input type="text" name="admission_date" class="form-input datepicker" 
                               placeholder="Select date"
                               value="<?php echo esc_attr($student_data['admission_date'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-col-4">
                        <label class="form-label">
                            <i class="fas fa-layer-group"></i>
                            Grade Level
                        </label>
                        <select name="grade_level" class="form-input">
                            <option value="">Select Grade</option>
                            <option value="kg1" <?php selected($student_data['grade_level'] ?? '', 'kg1'); ?>>KG 1</option>
                            <option value="kg2" <?php selected($student_data['grade_level'] ?? '', 'kg2'); ?>>KG 2</option>
                            <option value="class1" <?php selected($student_data['grade_level'] ?? '', 'class1'); ?>>CLASS 1</option>
                            <option value="class2" <?php selected($student_data['grade_level'] ?? '', 'class2'); ?>>CLASS 2</option>
                            <option value="class3" <?php selected($student_data['grade_level'] ?? '', 'class3'); ?>>CLASS 3</option>
                            <option value="level1" <?php selected($student_data['grade_level'] ?? '', 'level1'); ?>>LEVEL 1</option>
                            <option value="level2" <?php selected($student_data['grade_level'] ?? '', 'level2'); ?>>LEVEL 2</option>
                            <option value="level3" <?php selected($student_data['grade_level'] ?? '', 'level3'); ?>>LEVEL 3</option>
                            <option value="shb" <?php selected($student_data['grade_level'] ?? '', 'shb'); ?>>SHB</option>
                            <option value="shg" <?php selected($student_data['grade_level'] ?? '', 'shg'); ?>>SHG</option>
                        </select>
                    </div>
                    
                    <div class="form-col-4">
                        <label class="form-label">
                            <i class="fas fa-quran"></i>
                            Islamic Studies
                        </label>
                        <select name="islamic_studies_category" class="form-input">
                            <option value="">Select Category</option>
                            <option value="hifz" <?php selected($student_data['islamic_studies_category'] ?? '', 'hifz'); ?>>Hifz</option>
                            <option value="nazra" <?php selected($student_data['islamic_studies_category'] ?? '', 'nazra'); ?>>Nazra</option>
                            <option value="qaidah" <?php selected($student_data['islamic_studies_category'] ?? '', 'qaidah'); ?>>Qaidah</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-col-12">
                        <label class="form-label">
                            <i class="fas fa-map-marker-alt"></i>
                            Permanent Address
                        </label>
                        <textarea name="permanent_address" class="form-input" rows="3" 
                                  placeholder="Enter permanent address"><?php echo esc_textarea($student_data['permanent_address'] ?? ''); ?></textarea>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-col-12">
                        <label class="form-label">
                            <i class="fas fa-home"></i>
                            Current Address
                        </label>
                        <textarea name="current_address" class="form-input" rows="3" 
                                  placeholder="Leave blank if same as permanent"><?php echo esc_textarea($student_data['current_address'] ?? ''); ?></textarea>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-col-12">
                        <label class="form-label">
                            <i class="fas fa-camera"></i>
                            Student Photo <?php echo $is_edit ? '(Upload new to replace)' : ''; ?>
                        </label>
                        <input type="file" name="student_photo" class="form-input" accept="image/*">
                    </div>
                </div>
            </div>
            
            <!-- STEP 2: FAMILY INFORMATION -->
            <div class="form-step-container" data-step="2">
                <div class="step-header">
                    <i class="fas fa-users"></i>
                    <h2>Family Information</h2>
                </div>
                
                <div class="info-section">
                    <h3><i class="fas fa-user-tie"></i> Father Information</h3>
                    
                    <div class="form-row">
                        <div class="form-col-6">
                            <label class="form-label">Father's Name</label>
                            <input type="text" name="father_name" class="form-input" 
                                   value="<?php echo esc_attr($student_data['father_name'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-col-6">
                            <label class="form-label">CNIC Number</label>
                            <input type="text" name="father_cnic" class="form-input" 
                                   placeholder="42101-1234567-8"
                                   value="<?php echo esc_attr($student_data['father_cnic'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col-12">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="father_email" class="form-input" 
                                   placeholder="father@example.com"
                                   value="<?php echo esc_attr($student_data['father_email'] ?? ''); ?>">
                        </div>
                    </div>
                </div>
                
                <div class="info-section">
                    <h3><i class="fas fa-user-shield"></i> Guardian Information</h3>
                    
                    <div class="form-row">
                        <div class="form-col-6">
                            <label class="form-label">Guardian's Name</label>
                            <input type="text" name="guardian_name" class="form-input" 
                                   value="<?php echo esc_attr($student_data['guardian_name'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-col-6">
                            <label class="form-label">CNIC Number</label>
                            <input type="text" name="guardian_cnic" class="form-input" 
                                   placeholder="42101-1234567-8"
                                   value="<?php echo esc_attr($student_data['guardian_cnic'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col-4">
                            <label class="form-label">Relationship</label>
                            <input type="text" name="relationship_to_student" class="form-input" 
                                   placeholder="e.g., Uncle"
                                   value="<?php echo esc_attr($student_data['relationship_to_student'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-col-4">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" name="guardian_phone" class="form-input" 
                                   placeholder="+92 300 1234567"
                                   value="<?php echo esc_attr($student_data['guardian_phone'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-col-4">
                            <label class="form-label">WhatsApp</label>
                            <input type="tel" name="guardian_whatsapp" class="form-input" 
                                   placeholder="+92 300 1234567"
                                   value="<?php echo esc_attr($student_data['guardian_whatsapp'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col-12">
                            <label class="form-label">Email</label>
                            <input type="email" name="guardian_email" class="form-input" 
                                   placeholder="guardian@example.com"
                                   value="<?php echo esc_attr($student_data['guardian_email'] ?? ''); ?>">
                        </div>
                    </div>
                </div>
                
                <div class="info-section">
                    <h3><i class="fas fa-phone-alt"></i> Emergency Contact</h3>
                    
                    <div class="form-row">
                        <div class="form-col-6">
                            <label class="form-label">Emergency Phone</label>
                            <input type="tel" name="emergency_contact" class="form-input" 
                                   placeholder="+92 300 1234567"
                                   value="<?php echo esc_attr($student_data['emergency_contact'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-col-6">
                            <label class="form-label">Emergency WhatsApp</label>
                            <input type="tel" name="emergency_whatsapp" class="form-input" 
                                   placeholder="+92 300 1234567"
                                   value="<?php echo esc_attr($student_data['emergency_whatsapp'] ?? ''); ?>">
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- STEP 3: ACADEMIC -->
            <div class="form-step-container" data-step="3">
                <div class="step-header">
                    <i class="fas fa-book"></i>
                    <h2>Academic Information</h2>
                </div>
                
                <div class="info-section">
                    <h3><i class="fas fa-calendar-week"></i> Academic Period</h3>
                    
                    <div class="form-row">
                        <div class="form-col-6">
                            <label class="form-label">Term/Semester</label>
                            <select name="academic_term" class="form-input">
                                <option value="">Select</option>
                                <option value="mid_term" <?php selected($student_data['academic_term'] ?? '', 'mid_term'); ?>>Mid Term</option>
                                <option value="annual" <?php selected($student_data['academic_term'] ?? '', 'annual'); ?>>Annual</option>
                            </select>
                        </div>
                        
                        <div class="form-col-6">
                            <label class="form-label">Academic Year</label>
                            <input type="text" name="academic_year" class="form-input" 
                                   placeholder="e.g., 2025"
                                   value="<?php echo esc_attr($student_data['academic_year'] ?? ''); ?>">
                        </div>
                    </div>
                </div>
                
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
                                <?php echo render_subject_fields($index, $subject); ?>
                            <?php endforeach;
                        endif; ?>
                    </div>
                </div>
                
                <div class="info-section">
                    <h3><i class="fas fa-tasks"></i> Behavioral Skills</h3>
                    
                    <div class="form-row">
                        <div class="form-col-6">
                            <label class="form-label">Homework Completion</label>
                            <select name="completes_homework" class="form-input">
                                <option value="">Select</option>
                                <option value="always" <?php selected($student_data['completes_homework'] ?? '', 'always'); ?>>Always</option>
                                <option value="usually" <?php selected($student_data['completes_homework'] ?? '', 'usually'); ?>>Usually</option>
                                <option value="sometimes" <?php selected($student_data['completes_homework'] ?? '', 'sometimes'); ?>>Sometimes</option>
                                <option value="rarely" <?php selected($student_data['completes_homework'] ?? '', 'rarely'); ?>>Rarely</option>
                            </select>
                        </div>
                        
                        <div class="form-col-6">
                            <label class="form-label">Class Participation</label>
                            <select name="participates_in_class" class="form-input">
                                <option value="">Select</option>
                                <option value="always" <?php selected($student_data['participates_in_class'] ?? '', 'always'); ?>>Always</option>
                                <option value="usually" <?php selected($student_data['participates_in_class'] ?? '', 'usually'); ?>>Usually</option>
                                <option value="sometimes" <?php selected($student_data['participates_in_class'] ?? '', 'sometimes'); ?>>Sometimes</option>
                                <option value="rarely" <?php selected($student_data['participates_in_class'] ?? '', 'rarely'); ?>>Rarely</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col-6">
                            <label class="form-label">Teamwork</label>
                            <select name="works_well_in_groups" class="form-input">
                                <option value="">Select</option>
                                <option value="excellent" <?php selected($student_data['works_well_in_groups'] ?? '', 'excellent'); ?>>Excellent</option>
                                <option value="good" <?php selected($student_data['works_well_in_groups'] ?? '', 'good'); ?>>Good</option>
                                <option value="satisfactory" <?php selected($student_data['works_well_in_groups'] ?? '', 'satisfactory'); ?>>Satisfactory</option>
                                <option value="needs_improvement" <?php selected($student_data['works_well_in_groups'] ?? '', 'needs_improvement'); ?>>Needs Improvement</option>
                            </select>
                        </div>
                        
                        <div class="form-col-6">
                            <label class="form-label">Problem Solving</label>
                            <select name="problem_solving_skills" class="form-input">
                                <option value="">Select</option>
                                <option value="excellent" <?php selected($student_data['problem_solving_skills'] ?? '', 'excellent'); ?>>Excellent</option>
                                <option value="good" <?php selected($student_data['problem_solving_skills'] ?? '', 'good'); ?>>Good</option>
                                <option value="satisfactory" <?php selected($student_data['problem_solving_skills'] ?? '', 'satisfactory'); ?>>Satisfactory</option>
                                <option value="needs_improvement" <?php selected($student_data['problem_solving_skills'] ?? '', 'needs_improvement'); ?>>Needs Improvement</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col-12">
                            <label class="form-label">Organization & Preparedness</label>
                            <select name="organization_preparedness" class="form-input">
                                <option value="">Select</option>
                                <option value="excellent" <?php selected($student_data['organization_preparedness'] ?? '', 'excellent'); ?>>Excellent</option>
                                <option value="good" <?php selected($student_data['organization_preparedness'] ?? '', 'good'); ?>>Good</option>
                                <option value="satisfactory" <?php selected($student_data['organization_preparedness'] ?? '', 'satisfactory'); ?>>Satisfactory</option>
                                <option value="needs_improvement" <?php selected($student_data['organization_preparedness'] ?? '', 'needs_improvement'); ?>>Needs Improvement</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="info-section">
                    <h3><i class="fas fa-comment-alt"></i> Teacher Comments</h3>
                    
                    <div class="form-row">
                        <div class="form-col-12">
                            <textarea name="teacher_overall_comments" class="form-input" rows="4" 
                                      placeholder="Overall observations"><?php echo esc_textarea($student_data['teacher_overall_comments'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>
                
                <div class="info-section">
                    <h3><i class="fas fa-bullseye"></i> Goals for Next Term</h3>
                    
                    <div class="form-row">
                        <div class="form-col-12">
                            <input type="text" name="goal_1" class="form-input" 
                                   placeholder="Goal 1"
                                   value="<?php echo esc_attr($student_data['goal_1'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col-12">
                            <input type="text" name="goal_2" class="form-input" 
                                   placeholder="Goal 2"
                                   value="<?php echo esc_attr($student_data['goal_2'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col-12">
                            <input type="text" name="goal_3" class="form-input" 
                                   placeholder="Goal 3"
                                   value="<?php echo esc_attr($student_data['goal_3'] ?? ''); ?>">
                        </div>
                    </div>
                </div>
                
                <div class="info-section">
                    <h3><i class="fas fa-clipboard-check"></i> Attendance</h3>
                    
                    <div class="form-row">
                        <div class="form-col-6">
                            <label class="form-label">Total School Days</label>
                            <input type="number" name="total_school_days" class="form-input" 
                                   placeholder="e.g., 200" id="totalSchoolDays"
                                   value="<?php echo esc_attr($student_data['total_school_days'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-col-6">
                            <label class="form-label">Present Days</label>
                            <input type="number" name="present_days" class="form-input" 
                                   placeholder="e.g., 180" id="presentDays"
                                   value="<?php echo esc_attr($student_data['present_days'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div id="attendanceDisplay" class="attendance-display"></div>
                </div>
            </div>
            
            <!-- STEP 4: FEES -->
            <div class="form-step-container" data-step="4">
                <div class="step-header">
                    <i class="fas fa-money-bill-wave"></i>
                    <h2>Fee Structure</h2>
                </div>
                
                <div class="fee-cards-grid">
                    <div class="fee-card">
                        <div class="fee-card-icon">
                            <i class="fas fa-sync-alt"></i>
                        </div>
                        <h4>Monthly Tuition</h4>
                        <div class="fee-input-wrapper">
                            <span class="currency-symbol">PKR</span>
                            <input type="number" name="monthly_tuition_fee" class="form-input fee-amount" 
                                   placeholder="0"
                                   value="<?php echo esc_attr($student_data['monthly_tuition_fee'] ?? ''); ?>">
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
                                   placeholder="0"
                                   value="<?php echo esc_attr($student_data['course_fee'] ?? ''); ?>">
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
                                   placeholder="0"
                                   value="<?php echo esc_attr($student_data['uniform_fee'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="fee-card">
                        <div class="fee-card-icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <h4>Annual Fee</h4>
                        <div class="fee-input-wrapper">
                            <span class="currency-symbol">PKR</span>
                            <input type="number" name="annual_fee" class="form-input fee-amount" 
                                   placeholder="0"
                                   value="<?php echo esc_attr($student_data['annual_fee'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="fee-card">
                        <div class="fee-card-icon">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <h4>Admission Fee</h4>
                        <div class="fee-input-wrapper">
                            <span class="currency-symbol">PKR</span>
                            <input type="number" name="admission_fee" class="form-input fee-amount" 
                                   placeholder="0"
                                   value="<?php echo esc_attr($student_data['admission_fee'] ?? ''); ?>">
                        </div>
                    </div>
                </div>
                
                <div class="fee-summary-box">
                    <h3>Fee Summary</h3>
                    <div class="summary-row">
                        <span>Monthly Fee:</span>
                        <strong id="monthlyFeeTotal">PKR 0</strong>
                    </div>
                    <div class="summary-row">
                        <span>One-Time Fees:</span>
                        <strong id="oneTimeFeeTotal">PKR 0</strong>
                    </div>
                    <div class="summary-row total-row">
                        <span>Total Initial:</span>
                        <strong id="grandFeeTotal">PKR 0</strong>
                    </div>
                </div>
                
                <div class="info-section">
                    <h3><i class="fas fa-hand-holding-heart"></i> Financial Aid</h3>
                    
                    <div class="checkbox-container">
                        <label class="checkbox-label">
                            <input type="checkbox" name="zakat_eligible" value="yes" 
                                   <?php checked($student_data['zakat_eligible'] ?? '', 'yes'); ?>>
                            <span class="checkbox-text">
                                <i class="fas fa-hand-holding-usd"></i>
                                Eligible for Zakat
                            </span>
                        </label>
                        
                        <label class="checkbox-label">
                            <input type="checkbox" name="donation_eligible" value="yes" 
                                   <?php checked($student_data['donation_eligible'] ?? '', 'yes'); ?>>
                            <span class="checkbox-text">
                                <i class="fas fa-donate"></i>
                                Eligible for Donation
                            </span>
                        </label>
                    </div>
                </div>
            </div>
            
            <!-- STEP 5: HEALTH -->
            <div class="form-step-container" data-step="5">
                <div class="step-header">
                    <i class="fas fa-heartbeat"></i>
                    <h2>Health Information</h2>
                </div>
                
                <div class="form-row">
                    <div class="form-col-6">
                        <label class="form-label">Overall Health Rating</label>
                        <select name="health_rating" class="form-input">
                            <option value="">Select</option>
                            <option value="poor" <?php selected($student_data['health_rating'] ?? '', 'poor'); ?>>Poor</option>
                            <option value="satisfactory" <?php selected($student_data['health_rating'] ?? '', 'satisfactory'); ?>>Satisfactory</option>
                            <option value="perfect" <?php selected($student_data['health_rating'] ?? '', 'perfect'); ?>>Perfect</option>
                        </select>
                    </div>
                    
                    <div class="form-col-6">
                        <label class="form-label">Cleanliness Rating</label>
                        <select name="cleanness_rating" class="form-input">
                            <option value="">Select</option>
                            <option value="poor" <?php selected($student_data['cleanness_rating'] ?? '', 'poor'); ?>>Poor</option>
                            <option value="satisfactory" <?php selected($student_data['cleanness_rating'] ?? '', 'satisfactory'); ?>>Satisfactory</option>
                            <option value="perfect" <?php selected($student_data['cleanness_rating'] ?? '', 'perfect'); ?>>Perfect</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-col-12">
                        <label class="form-label">Blood Group</label>
                        <select name="blood_group" class="form-input">
                            <option value="">Select</option>
                            <option value="A+" <?php selected($student_data['blood_group'] ?? '', 'A+'); ?>>A+</option>
                            <option value="A-" <?php selected($student_data['blood_group'] ?? '', 'A-'); ?>>A-</option>
                            <option value="B+" <?php selected($student_data['blood_group'] ?? '', 'B+'); ?>>B+</option>
                            <option value="B-" <?php selected($student_data['blood_group'] ?? '', 'B-'); ?>>B-</option>
                            <option value="AB+" <?php selected($student_data['blood_group'] ?? '', 'AB+'); ?>>AB+</option>
                            <option value="AB-" <?php selected($student_data['blood_group'] ?? '', 'AB-'); ?>>AB-</option>
                            <option value="O+" <?php selected($student_data['blood_group'] ?? '', 'O+'); ?>>O+</option>
                            <option value="O-" <?php selected($student_data['blood_group'] ?? '', 'O-'); ?>>O-</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-col-12">
                        <label class="form-label">Allergies</label>
                        <textarea name="allergies" class="form-input" rows="3" 
                                  placeholder="List any known allergies"><?php echo esc_textarea($student_data['allergies'] ?? ''); ?></textarea>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-col-12">
                        <label class="form-label">Medical Conditions</label>
                        <textarea name="medical_conditions" class="form-input" rows="3" 
                                  placeholder="List any chronic conditions"><?php echo esc_textarea($student_data['medical_conditions'] ?? ''); ?></textarea>
                    </div>
                </div>
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
                
                <button type="submit" class="nav-button btn-submit" 
                        name="<?php echo $is_edit ? 'update_student_submit' : 'submit_student_form'; ?>" 
                        id="submitFormBtn" style="display:none;">
                    <i class="fas fa-<?php echo $is_edit ? 'save' : 'check'; ?>"></i>
                    <?php echo $is_edit ? 'Update Student' : 'Submit Form'; ?>
                </button>
            </div>
            
        </form>
    </div>
    
    <?php
    add_form_scripts($subjects, $is_edit);
    add_form_styles();
    
    return ob_get_clean();
}

function render_subject_fields($index, $subject = array()) {
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
        
        <!-- MONTHLY EXAMS (REPEATER) -->
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
                        <?php echo render_monthly_exam_fields($index, $month_index, $monthly); ?>
                    <?php endforeach;
                endif;
                ?>
            </div>
        </div>
        
        <!-- MID SEMESTER -->
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
            <div class="marks-result-display" id="mid-result-<?php echo $index; ?>"></div>
        </div>
        
        <!-- FINAL SEMESTER -->
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
            <div class="marks-result-display" id="final-result-<?php echo $index; ?>"></div>
        </div>
        
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

function render_monthly_exam_fields($subject_index, $month_index, $monthly = array()) {
    ob_start();
    ?>
    <div class="monthly-exam-entry" data-month-index="<?php echo $month_index; ?>">
        <div class="monthly-exam-header">
            <input type="text" name="subjects[<?php echo $subject_index; ?>][monthly_exams][<?php echo $month_index; ?>][month_name]" 
                   placeholder="e.g., January or Month 1" class="month-name-input"
                   value="<?php echo esc_attr($monthly['month_name'] ?? ''); ?>">
            <button type="button" class="btn-remove-monthly-exam">
                <i class="fas fa-times-circle"></i>
            </button>
        </div>
        
        <div class="marks-grid-new">
            <div class="marks-column">
                <label>Oral Total</label>
                <input type="number" name="subjects[<?php echo $subject_index; ?>][monthly_exams][<?php echo $month_index; ?>][oral_total]" 
                       class="marks-input oral-total" data-subject="<?php echo $subject_index; ?>" data-month="<?php echo $month_index; ?>"
                       placeholder="0" value="<?php echo esc_attr($monthly['oral_total'] ?? ''); ?>">
            </div>
            <div class="marks-column">
                <label>Oral Obtained</label>
                <input type="number" name="subjects[<?php echo $subject_index; ?>][monthly_exams][<?php echo $month_index; ?>][oral_obtained]" 
                       class="marks-input oral-obtained" data-subject="<?php echo $subject_index; ?>" data-month="<?php echo $month_index; ?>"
                       placeholder="0" value="<?php echo esc_attr($monthly['oral_obtained'] ?? ''); ?>">
            </div>
            <div class="marks-column">
                <label>Written Total</label>
                <input type="number" name="subjects[<?php echo $subject_index; ?>][monthly_exams][<?php echo $month_index; ?>][written_total]" 
                       class="marks-input written-total" data-subject="<?php echo $subject_index; ?>" data-month="<?php echo $month_index; ?>"
                       placeholder="0" value="<?php echo esc_attr($monthly['written_total'] ?? ''); ?>">
            </div>
            <div class="marks-column">
                <label>Written Obtained</label>
                <input type="number" name="subjects[<?php echo $subject_index; ?>][monthly_exams][<?php echo $month_index; ?>][written_obtained]" 
                       class="marks-input written-obtained" data-subject="<?php echo $subject_index; ?>" data-month="<?php echo $month_index; ?>"
                       placeholder="0" value="<?php echo esc_attr($monthly['written_obtained'] ?? ''); ?>">
            </div>
        </div>
        
        <div class="marks-result-display" id="monthly-result-<?php echo $subject_index; ?>-<?php echo $month_index; ?>"></div>
    </div>
    <?php
    return ob_get_clean();
}

function add_form_scripts($subjects, $is_edit) {
    $subject_count = count($subjects);
    ?>
    <script>
    jQuery(document).ready(function($) {
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
        
        // Add Subject
        $('#addSubjectBtn').on('click', function() {
            const subjectHTML = `<?php echo addslashes(render_subject_fields('SUBJECT_INDEX')); ?>`.replace(/SUBJECT_INDEX/g, subjectIndex);
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
        
        // Add Monthly Exam
        $(document).on('click', '.btn-add-monthly-exam', function() {
            const subjIndex = $(this).data('subject-index');
            if (!monthlyExamCounters[subjIndex]) {
                monthlyExamCounters[subjIndex] = 0;
            }
            const monthIndex = monthlyExamCounters[subjIndex];
            
            const monthlyHTML = `<?php echo addslashes(render_monthly_exam_fields('SUBJ_INDEX', 'MONTH_INDEX')); ?>`
                .replace(/SUBJ_INDEX/g, subjIndex)
                .replace(/MONTH_INDEX/g, monthIndex);
            
            $('.monthly-exams-container[data-subject-index="' + subjIndex + '"]').append(monthlyHTML);
            monthlyExamCounters[subjIndex]++;
        });
        
        // Remove Monthly Exam
        $(document).on('click', '.btn-remove-monthly-exam', function() {
            if (confirm('Remove this monthly exam?')) {
                $(this).closest('.monthly-exam-entry').remove();
            }
        });
        
        // Real-time marks calculation
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
        
        // Fee calculation
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
        
        // Attendance calculation
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
        
        <?php if ($is_edit): ?>
        $('.fee-amount').trigger('input');
        calculateAttendance();
        
        // Initialize monthly exam counters for existing subjects
        <?php if (!empty($subjects)): ?>
            <?php foreach ($subjects as $idx => $subj): ?>
                monthlyExamCounters[<?php echo $idx; ?>] = <?php echo count($subj['monthly_exams'] ?? array()); ?>;
            <?php endforeach; ?>
        <?php endif; ?>
        
        // Trigger calculation on page load for edit mode
        setTimeout(function() {
            $('.marks-input').trigger('input');
        }, 500);
        <?php endif; ?>
        
        <?php if (empty($subjects)): ?>
        $('#addSubjectBtn').click();
        <?php endif; ?>
    });
    </script>
    <?php
}

function add_form_styles() {
    ?>
    <style>
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
    
    .form-alert {
        padding: 18px 24px;
        border-radius: 12px;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        gap: 14px;
        font-weight: 500;
        font-size: 15px;
        animation: slideDown 0.3s ease;
    }
    
    @keyframes slideDown {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .alert-success {
        background: #d1fae5;
        color: #065f46;
        border: 2px solid #6ee7b7;
    }
    
    .alert-error {
        background: #fee2e2;
        color: #991b1b;
        border: 2px solid #fca5a5;
    }
    
    .form-alert i {
        font-size: 22px;
    }
    
    .form-main-header {
        background: linear-gradient(135deg, #0080ff 0%, #004d99 100%);
        color: white;
        padding: 40px;
        border-radius: 16px 16px 0 0;
        display: flex;
        align-items: center;
        gap: 24px;
        box-shadow: 0 8px 24px rgba(0, 128, 255, 0.3);
    }
    
    .header-icon {
        font-size: 64px;
    }
    
    .header-text h1 {
        font-size: 32px;
        font-weight: 700;
        margin-bottom: 8px;
    }
    
    .header-text p {
        font-size: 16px;
        opacity: 0.9;
    }
    
    .header-photo {
        margin-left: auto;
    }
    
    .header-photo img {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        border: 4px solid white;
        object-fit: cover;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    }
    
    .student-main-form {
        background: white;
        padding: 0;
        border-radius: 0 0 16px 16px;
        box-shadow: 0 4px 24px rgba(0,0,0,0.1);
    }
    
    .form-progress-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 36px;
        background: #e6f2ff;
        border-bottom: 2px solid #b3d9ff;
    }
    
    .progress-step {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        position: relative;
    }
    
    .step-circle {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: white;
        border: 3px solid #cce6ff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 20px;
        color: #80bfff;
        transition: all 0.3s;
        position: relative;
        z-index: 2;
    }
    
    .progress-step.active .step-circle {
        background: linear-gradient(135deg, #0080ff, #004d99);
        border-color: #0080ff;
        color: white;
        transform: scale(1.1);
        box-shadow: 0 4px 12px rgba(0, 128, 255, 0.3);
    }
    
    .progress-step.completed .step-circle {
        background: #10b981;
        border-color: #10b981;
        color: white;
    }
    
    .progress-step.completed .step-circle::after {
        content: '\f00c';
        font-family: 'Font Awesome 6 Free';
        font-weight: 900;
        position: absolute;
    }
    
    .step-text {
        font-size: 14px;
        font-weight: 600;
        color: #66b3ff;
    }
    
    .progress-step.active .step-text {
        color: #0080ff;
    }
    
    .progress-line {
        flex: 1;
        height: 3px;
        background: #b3d9ff;
        position: relative;
        top: -24px;
    }
    
    .form-step-container {
        display: none;
        padding: 40px;
        animation: fadeInUp 0.4s ease;
    }
    
    .form-step-container.active {
        display: block;
    }
    
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .step-header {
        display: flex;
        align-items: center;
        gap: 16px;
        margin-bottom: 32px;
        padding-bottom: 16px;
        border-bottom: 3px solid #0080ff;
    }
    
    .step-header i {
        font-size: 40px;
        color: #0080ff;
    }
    
    .step-header h2 {
        font-size: 28px;
        font-weight: 700;
        color: #001a33;
        margin: 0;
    }
    
    .form-row {
        display: grid;
        grid-template-columns: repeat(12, 1fr);
        gap: 20px;
        margin-bottom: 20px;
    }
    
    .form-col-4 { grid-column: span 4; }
    .form-col-6 { grid-column: span 6; }
    .form-col-12 { grid-column: span 12; }
    
    .form-label {
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 600;
        color: #00264d;
        margin-bottom: 8px;
        font-size: 14px;
    }
    
    .form-label i {
        color: #0080ff;
        font-size: 14px;
    }
    
    .form-label.required::after {
        content: '*';
        color: #ef4444;
        margin-left: 4px;
    }
    
    .form-input {
        width: 100%;
        padding: 13px 18px;
        border: 2px solid #cce6ff;
        border-radius: 10px;
        font-size: 15px;
        font-family: 'Poppins', sans-serif;
        transition: all 0.3s;
        background: white;
        color: #001a33;
    }
    
    .form-input:focus {
        outline: none;
        border-color: #0080ff;
        box-shadow: 0 0 0 4px rgba(0, 128, 255, 0.1);
    }
    
    .form-input.error-input {
        border-color: #ef4444;
        box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.1);
    }
    
    textarea.form-input {
        resize: vertical;
        min-height: 90px;
    }
    
    .info-section {
        background: #f0f8ff;
        border-radius: 12px;
        padding: 28px;
        margin-bottom: 24px;
        border: 1px solid #cce6ff;
    }
    
    .info-section h3 {
        font-size: 18px;
        font-weight: 600;
        color: #00264d;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .info-section h3 i {
        color: #0080ff;
    }
    
    .section-header-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    
    .section-header-row h3 {
        margin: 0;
    }
    
    .btn-add-subject {
        padding: 11px 20px;
        background: #10b981;
        color: white;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s;
    }
    
    .btn-add-subject:hover {
        background: #059669;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    }
    
    .subject-box {
        background: white;
        border: 2px solid #cce6ff;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 20px;
        transition: all 0.3s;
    }
    
    .subject-box:hover {
        border-color: #0080ff;
        box-shadow: 0 4px 12px rgba(0, 128, 255, 0.1);
    }
    
    .subject-header-bar {
        display: flex;
        gap: 12px;
        margin-bottom: 16px;
        padding-bottom: 16px;
        border-bottom: 2px solid #e6f2ff;
    }
    
    .subject-name-input {
        flex: 1;
        padding: 13px 18px;
        border: 2px solid #cce6ff;
        border-radius: 8px;
        font-weight: 600;
        font-size: 16px;
    }
    
    .btn-remove-subject {
        padding: 10px 16px;
        background: #ef4444;
        color: white;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .btn-remove-subject:hover {
        background: #dc2626;
    }
    
    .exam-type-section {
        background: #f8fbff;
        border: 2px solid #e0f0ff;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 16px;
    }
    
    .exam-type-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 16px;
    }
    
    .exam-type-header h5 {
        font-size: 16px;
        font-weight: 600;
        color: #00264d;
        display: flex;
        align-items: center;
        gap: 8px;
        margin: 0;
    }
    
    .exam-type-header h5 i {
        color: #0080ff;
    }
    
    .btn-add-monthly-exam {
        padding: 8px 16px;
        background: #0080ff;
        color: white;
        border: none;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 6px;
        transition: all 0.3s;
    }
    
    .btn-add-monthly-exam:hover {
        background: #0066cc;
        transform: translateY(-1px);
    }
    
    .monthly-exam-entry {
        background: white;
        border: 2px solid #cce6ff;
        border-radius: 8px;
        padding: 16px;
        margin-bottom: 12px;
    }
    
    .monthly-exam-header {
        display: flex;
        gap: 12px;
        margin-bottom: 12px;
        align-items: center;
    }
    
    .month-name-input {
        flex: 1;
        padding: 10px 14px;
        border: 2px solid #b3d9ff;
        border-radius: 6px;
        font-weight: 500;
        font-size: 14px;
    }
    
    .btn-remove-monthly-exam {
        padding: 8px 12px;
        background: #ef4444;
        color: white;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.3s;
        font-size: 14px;
    }
    
    .btn-remove-monthly-exam:hover {
        background: #dc2626;
    }
    
    .marks-grid-new {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 12px;
    }
    
    .marks-column {
        display: flex;
        flex-direction: column;
    }
    
    .marks-column label {
        font-size: 12px;
        font-weight: 600;
        color: #00264d;
        margin-bottom: 6px;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    
    .marks-column input {
        padding: 10px 12px;
        border: 2px solid #cce6ff;
        border-radius: 6px;
        font-size: 14px;
        text-align: center;
    }
    
    .marks-column input:focus {
        outline: none;
        border-color: #0080ff;
        box-shadow: 0 0 0 3px rgba(0, 128, 255, 0.1);
    }
    
    .marks-result-display {
        margin-top: 16px;
    }
    
    .marks-calculation-result {
        background: linear-gradient(135deg, #0080ff, #004d99);
        color: white;
        padding: 20px;
        border-radius: 10px;
        animation: slideIn 0.3s ease;
    }
    
    @keyframes slideIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .marks-summary {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
    }
    
    .marks-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
    }
    
    .marks-label {
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        opacity: 0.9;
        margin-bottom: 6px;
    }
    
    .marks-value {
        font-size: 20px;
        font-weight: 700;
    }
    
    .grade-badge {
        background: rgba(255, 255, 255, 0.2);
        padding: 6px 16px;
        border-radius: 20px;
        font-size: 18px;
    }
    
    .marks-excellent {
        background: linear-gradient(135deg, #10b981, #059669) !important;
    }
    
    .marks-good {
        background: linear-gradient(135deg, #f59e0b, #d97706) !important;
    }
    
    .marks-poor {
        background: linear-gradient(135deg, #ef4444, #dc2626) !important;
    }
    
    .teacher-assessment-fields {
        display: grid;
        gap: 12px;
        margin-top: 16px;
    }
    
    .teacher-assessment-fields textarea {
        padding: 12px 16px;
        border: 2px solid #cce6ff;
        border-radius: 8px;
        resize: vertical;
        min-height: 65px;
        font-size: 14px;
    }
    
    .attendance-display {
        margin-top: 16px;
    }
    
    .attendance-result {
        background: linear-gradient(135deg, #0080ff, #004d99);
        color: white;
        padding: 24px;
        border-radius: 12px;
        text-align: center;
    }
    
    .att-percentage {
        font-size: 48px;
        font-weight: 700;
        margin-bottom: 8px;
    }
    
    .att-status {
        font-size: 20px;
        font-weight: 600;
        margin-bottom: 4px;
    }
    
    .att-fraction {
        font-size: 14px;
        opacity: 0.9;
    }
    
    .status-excellent {
        background: linear-gradient(135deg, #10b981, #059669);
    }
    
    .status-good {
        background: linear-gradient(135deg, #f59e0b, #d97706);
    }
    
    .status-poor {
        background: linear-gradient(135deg, #ef4444, #dc2626);
    }
    
    .fee-cards-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .fee-card {
        background: white;
        border: 2px solid #cce6ff;
        border-radius: 12px;
        padding: 24px;
        text-align: center;
        transition: all 0.3s;
    }
    
    .fee-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 20px rgba(0, 128, 255, 0.15);
        border-color: #0080ff;
    }
    
    .fee-card-icon {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: linear-gradient(135deg, #0080ff, #004d99);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        margin: 0 auto 16px;
    }
    
    .fee-card h4 {
        font-size: 16px;
        font-weight: 600;
        color: #00264d;
        margin-bottom: 12px;
    }
    
    .fee-input-wrapper {
        position: relative;
        margin-bottom: 8px;
    }
    
    .currency-symbol {
        position: absolute;
        left: 18px;
        top: 50%;
        transform: translateY(-50%);
        font-weight: 600;
        color: #0080ff;
        font-size: 16px;
    }
    
    .fee-amount {
        padding-left: 55px !important;
        text-align: center;
        font-size: 18px !important;
        font-weight: 600;
    }
    
    .fee-summary-box {
        background: #f0f8ff;
        border: 2px solid #b3d9ff;
        border-radius: 12px;
        padding: 24px;
        margin-bottom: 24px;
    }
    
    .fee-summary-box h3 {
        font-size: 20px;
        font-weight: 600;
        margin-bottom: 20px;
        text-align: center;
        color: #00264d;
    }
    
    .summary-row {
        display: flex;
        justify-content: space-between;
        padding: 12px 0;
        border-bottom: 1px solid #cce6ff;
        font-size: 16px;
    }
    
    .summary-row.total-row {
        border-bottom: none;
        margin-top: 12px;
        padding-top: 16px;
        border-top: 3px solid #0080ff;
        font-size: 20px;
        font-weight: 700;
    }
    
    .summary-row.total-row strong {
        color: #0080ff;
    }
    
    .checkbox-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 16px;
    }
    
    .checkbox-label {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 16px;
        background: white;
        border: 2px solid #cce6ff;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .checkbox-label:hover {
        border-color: #0080ff;
        background: #f0f8ff;
    }
    
    .checkbox-label input[type="checkbox"] {
        width: 20px;
        height: 20px;
        cursor: pointer;
        accent-color: #0080ff;
    }
    
    .checkbox-text {
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 500;
        color: #00264d;
    }
    
    .checkbox-text i {
        color: #0080ff;
    }
    
    .form-navigation-bar {
        display: flex;
        justify-content: space-between;
        gap: 16px;
        padding: 32px 40px;
        background: #e6f2ff;
        border-top: 2px solid #b3d9ff;
        border-radius: 0 0 16px 16px;
    }
    
    .nav-button {
        padding: 15px 34px;
        border: none;
        border-radius: 10px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 10px;
        transition: all 0.3s;
        font-family: 'Poppins', sans-serif;
    }
    
    .btn-prev {
        background: #64748b;
        color: white;
    }
    
    .btn-prev:hover {
        background: #475569;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(100, 116, 139, 0.3);
    }
    
    .btn-next {
        background: linear-gradient(135deg, #0080ff, #004d99);
        color: white;
        margin-left: auto;
    }
    
    .btn-next:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 128, 255, 0.3);
    }
    
    .btn-submit {
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
        margin-left: auto;
    }
    
    .btn-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    }
    
    @media (max-width: 768px) {
        .form-main-header {
            flex-direction: column;
            text-align: center;
            padding: 30px 20px;
        }
        
        .header-text h1 {
            font-size: 26px;
        }
        
        .header-photo {
            margin: 0;
        }
        
        .form-progress-bar {
            flex-wrap: wrap;
            gap: 16px;
            padding: 20px;
        }
        
        .progress-line {
            display: none;
        }
        
        .form-step-container {
            padding: 20px;
        }
        
        .form-col-4,
        .form-col-6,
        .form-col-12 {
            grid-column: span 12;
        }
        
        .marks-grid-new {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .marks-summary {
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }
        
        .marks-value {
            font-size: 16px;
        }
        
        .marks-label {
            font-size: 10px;
        }
        
        .fee-cards-grid {
            grid-template-columns: 1fr;
        }
        
        .checkbox-container {
            grid-template-columns: 1fr;
        }
        
        .form-navigation-bar {
            flex-direction: column;
            padding: 20px;
        }
        
        .btn-next,
        .btn-submit {
            margin-left: 0;
        }
    }
    </style>
    <?php
}
?>