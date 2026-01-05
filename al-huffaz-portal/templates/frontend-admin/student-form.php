<?php
/**
 * Front-end Admin Student Form Template
 * Al-Huffaz Education System Portal
 *
 * Standalone student add/edit form for front-end
 */

defined('ABSPATH') || exit;

use AlHuffaz\Admin\Student_Manager;

$current_user = wp_get_current_user();

// Check for edit mode
$edit_id = isset($atts['id']) ? intval($atts['id']) : (isset($_GET['id']) ? intval($_GET['id']) : 0);
$student_data = array();
$is_edit = false;

if ($edit_id) {
    $student_post = get_post($edit_id);
    if ($student_post && $student_post->post_type === 'student') {
        $is_edit = true;
        $student_data = array(
            'name' => $student_post->post_title,
            'gr_number' => get_post_meta($edit_id, 'gr_number', true),
            'roll_number' => get_post_meta($edit_id, 'roll_number', true),
            'gender' => get_post_meta($edit_id, 'gender', true),
            'date_of_birth' => get_post_meta($edit_id, 'date_of_birth', true),
            'admission_date' => get_post_meta($edit_id, 'admission_date', true),
            'grade_level' => get_post_meta($edit_id, 'grade_level', true),
            'islamic_studies_category' => get_post_meta($edit_id, 'islamic_studies_category', true),
            'academic_year' => get_post_meta($edit_id, 'academic_year', true),
            'academic_term' => get_post_meta($edit_id, 'academic_term', true),
            'father_name' => get_post_meta($edit_id, 'father_name', true),
            'father_cnic' => get_post_meta($edit_id, 'father_cnic', true),
            'guardian_name' => get_post_meta($edit_id, 'guardian_name', true),
            'guardian_phone' => get_post_meta($edit_id, 'guardian_phone', true),
            'guardian_whatsapp' => get_post_meta($edit_id, 'guardian_whatsapp', true),
            'relationship_to_student' => get_post_meta($edit_id, 'relationship_to_student', true),
            'permanent_address' => get_post_meta($edit_id, 'permanent_address', true),
            'current_address' => get_post_meta($edit_id, 'current_address', true),
            'monthly_tuition_fee' => get_post_meta($edit_id, 'monthly_tuition_fee', true),
            'admission_fee' => get_post_meta($edit_id, 'admission_fee', true),
            'zakat_eligible' => get_post_meta($edit_id, 'zakat_eligible', true),
            'donation_eligible' => get_post_meta($edit_id, 'donation_eligible', true),
            'blood_group' => get_post_meta($edit_id, 'blood_group', true),
            'allergies' => get_post_meta($edit_id, 'allergies', true),
            'medical_conditions' => get_post_meta($edit_id, 'medical_conditions', true),
        );
    }
}
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

<style>
.ahp-student-form-wrapper {
    font-family: 'Poppins', sans-serif;
    padding: 24px;
    background: #f8fafc;
    border-radius: 16px;
}

.ahp-form-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}

.ahp-form-title {
    margin: 0;
    font-size: 24px;
    font-weight: 700;
    color: #1e293b;
}

.ahp-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    overflow: hidden;
}

.ahp-card-body {
    padding: 24px;
}

.ahp-form-section {
    background: #f8fafc;
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 20px;
}

.ahp-form-section-title {
    margin: 0 0 16px;
    font-size: 15px;
    font-weight: 700;
    color: #1e293b;
    display: flex;
    align-items: center;
    gap: 8px;
}

.ahp-form-section-title i {
    color: #0080ff;
}

.ahp-form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
}

.ahp-form-group {
    margin-bottom: 0;
}

.ahp-form-group.full-width {
    grid-column: 1 / -1;
}

.ahp-form-label {
    display: block;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 6px;
    font-size: 13px;
}

.ahp-form-input,
.ahp-form-select,
.ahp-form-textarea {
    width: 100%;
    padding: 10px 14px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-size: 14px;
    font-family: 'Poppins', sans-serif;
    transition: border-color 0.3s;
}

.ahp-form-input:focus,
.ahp-form-select:focus,
.ahp-form-textarea:focus {
    outline: none;
    border-color: #0080ff;
}

.ahp-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 12px 20px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 14px;
    text-decoration: none;
    cursor: pointer;
    border: none;
    font-family: 'Poppins', sans-serif;
    transition: all 0.3s;
}

.ahp-btn-primary {
    background: linear-gradient(135deg, #0080ff, #004d99);
    color: white;
}

.ahp-btn-secondary {
    background: #f8fafc;
    color: #1e293b;
    border: 1px solid #e2e8f0;
}

.ahp-btn-success {
    background: #10b981;
    color: white;
}

.ahp-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.ahp-form-actions {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
    padding-top: 20px;
    border-top: 1px solid #e2e8f0;
    margin-top: 20px;
}

.ahp-toast {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 14px 20px;
    border-radius: 8px;
    color: white;
    font-weight: 600;
    z-index: 9999;
    display: none;
}

.ahp-toast.success { background: #10b981; }
.ahp-toast.error { background: #ef4444; }

@media (max-width: 768px) {
    .ahp-form-grid {
        grid-template-columns: 1fr;
    }
    .ahp-form-header {
        flex-direction: column;
        gap: 16px;
        align-items: flex-start;
    }
}
</style>

<div class="ahp-student-form-wrapper">
    <div class="ahp-form-header">
        <h2 class="ahp-form-title">
            <i class="fas fa-<?php echo $is_edit ? 'edit' : 'user-plus'; ?>"></i>
            <?php echo $is_edit ? __('Edit Student', 'al-huffaz-portal') : __('Add New Student', 'al-huffaz-portal'); ?>
        </h2>
    </div>

    <form id="studentForm" class="ahp-card">
        <input type="hidden" name="student_id" id="studentId" value="<?php echo $edit_id; ?>">
        <input type="hidden" name="action" value="alhuffaz_save_student">
        <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('alhuffaz_student_nonce'); ?>">

        <div class="ahp-card-body">
            <!-- Basic Information -->
            <div class="ahp-form-section">
                <h4 class="ahp-form-section-title"><i class="fas fa-user"></i> <?php _e('Basic Information', 'al-huffaz-portal'); ?></h4>
                <div class="ahp-form-grid">
                    <div class="ahp-form-group">
                        <label class="ahp-form-label"><?php _e('Full Name', 'al-huffaz-portal'); ?> *</label>
                        <input type="text" name="student_name" class="ahp-form-input" required value="<?php echo esc_attr($student_data['name'] ?? ''); ?>">
                    </div>
                    <div class="ahp-form-group">
                        <label class="ahp-form-label"><?php _e('GR Number', 'al-huffaz-portal'); ?></label>
                        <input type="text" name="gr_number" class="ahp-form-input" value="<?php echo esc_attr($student_data['gr_number'] ?? ''); ?>">
                    </div>
                    <div class="ahp-form-group">
                        <label class="ahp-form-label"><?php _e('Roll Number', 'al-huffaz-portal'); ?></label>
                        <input type="text" name="roll_number" class="ahp-form-input" value="<?php echo esc_attr($student_data['roll_number'] ?? ''); ?>">
                    </div>
                    <div class="ahp-form-group">
                        <label class="ahp-form-label"><?php _e('Gender', 'al-huffaz-portal'); ?></label>
                        <select name="gender" class="ahp-form-select">
                            <option value="">Select Gender</option>
                            <option value="male" <?php selected($student_data['gender'] ?? '', 'male'); ?>>Male</option>
                            <option value="female" <?php selected($student_data['gender'] ?? '', 'female'); ?>>Female</option>
                        </select>
                    </div>
                    <div class="ahp-form-group">
                        <label class="ahp-form-label"><?php _e('Date of Birth', 'al-huffaz-portal'); ?></label>
                        <input type="date" name="date_of_birth" class="ahp-form-input" value="<?php echo esc_attr($student_data['date_of_birth'] ?? ''); ?>">
                    </div>
                    <div class="ahp-form-group">
                        <label class="ahp-form-label"><?php _e('Admission Date', 'al-huffaz-portal'); ?></label>
                        <input type="date" name="admission_date" class="ahp-form-input" value="<?php echo esc_attr($student_data['admission_date'] ?? ''); ?>">
                    </div>
                </div>
            </div>

            <!-- Academic Information -->
            <div class="ahp-form-section">
                <h4 class="ahp-form-section-title"><i class="fas fa-graduation-cap"></i> <?php _e('Academic Information', 'al-huffaz-portal'); ?></h4>
                <div class="ahp-form-grid">
                    <div class="ahp-form-group">
                        <label class="ahp-form-label"><?php _e('Grade Level', 'al-huffaz-portal'); ?></label>
                        <select name="grade_level" class="ahp-form-select">
                            <option value="">Select Grade</option>
                            <?php
                            $grades = array('kg1' => 'KG 1', 'kg2' => 'KG 2', 'class1' => 'Class 1', 'class2' => 'Class 2', 'class3' => 'Class 3', 'level1' => 'Level 1', 'level2' => 'Level 2', 'level3' => 'Level 3', 'shb' => 'SHB', 'shg' => 'SHG');
                            foreach ($grades as $value => $label):
                            ?>
                            <option value="<?php echo $value; ?>" <?php selected($student_data['grade_level'] ?? '', $value); ?>><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="ahp-form-group">
                        <label class="ahp-form-label"><?php _e('Islamic Studies Category', 'al-huffaz-portal'); ?></label>
                        <select name="islamic_studies_category" class="ahp-form-select">
                            <option value="">Select Category</option>
                            <option value="hifz" <?php selected($student_data['islamic_studies_category'] ?? '', 'hifz'); ?>>Hifz</option>
                            <option value="nazra" <?php selected($student_data['islamic_studies_category'] ?? '', 'nazra'); ?>>Nazra</option>
                            <option value="qaidah" <?php selected($student_data['islamic_studies_category'] ?? '', 'qaidah'); ?>>Qaidah</option>
                        </select>
                    </div>
                    <div class="ahp-form-group">
                        <label class="ahp-form-label"><?php _e('Academic Year', 'al-huffaz-portal'); ?></label>
                        <select name="academic_year" class="ahp-form-select">
                            <option value="">Select Year</option>
                            <?php for ($y = date('Y') + 1; $y >= 2020; $y--): ?>
                            <option value="<?php echo $y . '-' . ($y + 1); ?>" <?php selected($student_data['academic_year'] ?? '', $y . '-' . ($y + 1)); ?>><?php echo $y . '-' . ($y + 1); ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="ahp-form-group">
                        <label class="ahp-form-label"><?php _e('Academic Term', 'al-huffaz-portal'); ?></label>
                        <select name="academic_term" class="ahp-form-select">
                            <option value="">Select Term</option>
                            <option value="mid" <?php selected($student_data['academic_term'] ?? '', 'mid'); ?>>Mid Term</option>
                            <option value="annual" <?php selected($student_data['academic_term'] ?? '', 'annual'); ?>>Annual</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Family Information -->
            <div class="ahp-form-section">
                <h4 class="ahp-form-section-title"><i class="fas fa-users"></i> <?php _e('Family Information', 'al-huffaz-portal'); ?></h4>
                <div class="ahp-form-grid">
                    <div class="ahp-form-group">
                        <label class="ahp-form-label"><?php _e('Father Name', 'al-huffaz-portal'); ?></label>
                        <input type="text" name="father_name" class="ahp-form-input" value="<?php echo esc_attr($student_data['father_name'] ?? ''); ?>">
                    </div>
                    <div class="ahp-form-group">
                        <label class="ahp-form-label"><?php _e('Father CNIC', 'al-huffaz-portal'); ?></label>
                        <input type="text" name="father_cnic" class="ahp-form-input" placeholder="12345-1234567-1" value="<?php echo esc_attr($student_data['father_cnic'] ?? ''); ?>">
                    </div>
                    <div class="ahp-form-group">
                        <label class="ahp-form-label"><?php _e('Guardian Name', 'al-huffaz-portal'); ?></label>
                        <input type="text" name="guardian_name" class="ahp-form-input" value="<?php echo esc_attr($student_data['guardian_name'] ?? ''); ?>">
                    </div>
                    <div class="ahp-form-group">
                        <label class="ahp-form-label"><?php _e('Guardian Phone', 'al-huffaz-portal'); ?></label>
                        <input type="text" name="guardian_phone" class="ahp-form-input" value="<?php echo esc_attr($student_data['guardian_phone'] ?? ''); ?>">
                    </div>
                    <div class="ahp-form-group">
                        <label class="ahp-form-label"><?php _e('Guardian WhatsApp', 'al-huffaz-portal'); ?></label>
                        <input type="text" name="guardian_whatsapp" class="ahp-form-input" value="<?php echo esc_attr($student_data['guardian_whatsapp'] ?? ''); ?>">
                    </div>
                    <div class="ahp-form-group">
                        <label class="ahp-form-label"><?php _e('Relationship to Student', 'al-huffaz-portal'); ?></label>
                        <select name="relationship_to_student" class="ahp-form-select">
                            <option value="">Select Relationship</option>
                            <?php
                            $relationships = array('father' => 'Father', 'mother' => 'Mother', 'uncle' => 'Uncle', 'aunt' => 'Aunt', 'grandfather' => 'Grandfather', 'other' => 'Other');
                            foreach ($relationships as $value => $label):
                            ?>
                            <option value="<?php echo $value; ?>" <?php selected($student_data['relationship_to_student'] ?? '', $value); ?>><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Address -->
            <div class="ahp-form-section">
                <h4 class="ahp-form-section-title"><i class="fas fa-map-marker-alt"></i> <?php _e('Address', 'al-huffaz-portal'); ?></h4>
                <div class="ahp-form-grid">
                    <div class="ahp-form-group full-width">
                        <label class="ahp-form-label"><?php _e('Permanent Address', 'al-huffaz-portal'); ?></label>
                        <textarea name="permanent_address" class="ahp-form-textarea" rows="2"><?php echo esc_textarea($student_data['permanent_address'] ?? ''); ?></textarea>
                    </div>
                    <div class="ahp-form-group full-width">
                        <label class="ahp-form-label"><?php _e('Current Address', 'al-huffaz-portal'); ?></label>
                        <textarea name="current_address" class="ahp-form-textarea" rows="2"><?php echo esc_textarea($student_data['current_address'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Financial Information -->
            <div class="ahp-form-section">
                <h4 class="ahp-form-section-title"><i class="fas fa-dollar-sign"></i> <?php _e('Financial Information', 'al-huffaz-portal'); ?></h4>
                <div class="ahp-form-grid">
                    <div class="ahp-form-group">
                        <label class="ahp-form-label"><?php _e('Monthly Tuition Fee (PKR)', 'al-huffaz-portal'); ?></label>
                        <input type="number" name="monthly_tuition_fee" class="ahp-form-input" min="0" value="<?php echo esc_attr($student_data['monthly_tuition_fee'] ?? ''); ?>">
                    </div>
                    <div class="ahp-form-group">
                        <label class="ahp-form-label"><?php _e('Admission Fee (PKR)', 'al-huffaz-portal'); ?></label>
                        <input type="number" name="admission_fee" class="ahp-form-input" min="0" value="<?php echo esc_attr($student_data['admission_fee'] ?? ''); ?>">
                    </div>
                    <div class="ahp-form-group">
                        <label class="ahp-form-label"><?php _e('Zakat Eligible', 'al-huffaz-portal'); ?></label>
                        <select name="zakat_eligible" class="ahp-form-select">
                            <option value="">Select</option>
                            <option value="yes" <?php selected($student_data['zakat_eligible'] ?? '', 'yes'); ?>>Yes</option>
                            <option value="no" <?php selected($student_data['zakat_eligible'] ?? '', 'no'); ?>>No</option>
                        </select>
                    </div>
                    <div class="ahp-form-group">
                        <label class="ahp-form-label"><?php _e('Donation Eligible', 'al-huffaz-portal'); ?></label>
                        <select name="donation_eligible" class="ahp-form-select">
                            <option value="">Select</option>
                            <option value="yes" <?php selected($student_data['donation_eligible'] ?? '', 'yes'); ?>>Yes</option>
                            <option value="no" <?php selected($student_data['donation_eligible'] ?? '', 'no'); ?>>No</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Health Information -->
            <div class="ahp-form-section">
                <h4 class="ahp-form-section-title"><i class="fas fa-heartbeat"></i> <?php _e('Health Information', 'al-huffaz-portal'); ?></h4>
                <div class="ahp-form-grid">
                    <div class="ahp-form-group">
                        <label class="ahp-form-label"><?php _e('Blood Group', 'al-huffaz-portal'); ?></label>
                        <select name="blood_group" class="ahp-form-select">
                            <option value="">Select</option>
                            <?php
                            $blood_groups = array('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-');
                            foreach ($blood_groups as $bg):
                            ?>
                            <option value="<?php echo $bg; ?>" <?php selected($student_data['blood_group'] ?? '', $bg); ?>><?php echo $bg; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="ahp-form-group">
                        <label class="ahp-form-label"><?php _e('Allergies', 'al-huffaz-portal'); ?></label>
                        <input type="text" name="allergies" class="ahp-form-input" placeholder="None" value="<?php echo esc_attr($student_data['allergies'] ?? ''); ?>">
                    </div>
                    <div class="ahp-form-group full-width">
                        <label class="ahp-form-label"><?php _e('Medical Conditions', 'al-huffaz-portal'); ?></label>
                        <textarea name="medical_conditions" class="ahp-form-textarea" rows="2" placeholder="None"><?php echo esc_textarea($student_data['medical_conditions'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Submit -->
            <div class="ahp-form-actions">
                <button type="button" class="ahp-btn ahp-btn-secondary" onclick="resetForm()">
                    <i class="fas fa-undo"></i> <?php _e('Reset', 'al-huffaz-portal'); ?>
                </button>
                <button type="submit" class="ahp-btn ahp-btn-success" id="submitBtn">
                    <i class="fas fa-save"></i> <?php _e('Save Student', 'al-huffaz-portal'); ?>
                </button>
            </div>
        </div>
    </form>
</div>

<div class="ahp-toast" id="toast"></div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    function resetForm() {
        document.getElementById('studentForm').reset();
        <?php if (!$is_edit): ?>
        document.getElementById('studentId').value = 0;
        <?php endif; ?>
    }
    window.resetForm = resetForm;

    function showToast(message, type) {
        const toast = document.getElementById('toast');
        toast.textContent = message;
        toast.className = 'ahp-toast ' + type;
        toast.style.display = 'block';
        setTimeout(() => { toast.style.display = 'none'; }, 3000);
    }

    // Save Student
    document.getElementById('studentForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const btn = document.getElementById('submitBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <?php _e('Saving...', 'al-huffaz-portal'); ?>';

        const formData = new FormData(this);

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save"></i> <?php _e('Save Student', 'al-huffaz-portal'); ?>';

            if (data.success) {
                showToast(data.data?.message || '<?php _e('Student saved successfully', 'al-huffaz-portal'); ?>', 'success');
                <?php if (!$is_edit): ?>
                resetForm();
                <?php endif; ?>
            } else {
                showToast(data.data?.message || '<?php _e('Error saving student', 'al-huffaz-portal'); ?>', 'error');
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save"></i> <?php _e('Save Student', 'al-huffaz-portal'); ?>';
            showToast('<?php _e('Error saving student', 'al-huffaz-portal'); ?>', 'error');
        });
    });
});
</script>
