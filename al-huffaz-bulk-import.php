<?php
/*
Plugin Name: Al-Huffaz Bulk Import (Complete)
Description: Import multiple students via CSV with ALL fields
Version: 2.0
Author: RoohUl Hasnain
*/

defined('ABSPATH') || exit;

add_shortcode('student_bulk_import', 'alhuffaz_bulk_import');
add_action('init', 'handle_bulk_import_submission');

// Enqueue styles
add_action('wp_enqueue_scripts', 'bulk_import_assets');
function bulk_import_assets() {
    wp_enqueue_style('google-fonts', 'https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css');
}

function handle_bulk_import_submission() {
    if (!isset($_POST['bulk_import_submit'])) {
        return;
    }
    
    if (!isset($_POST['bulk_import_nonce']) || !wp_verify_nonce($_POST['bulk_import_nonce'], 'bulk_import_submit')) {
        return;
    }
    
    if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== 0) {
        set_transient('bulk_import_error', 'Please upload a valid CSV file.', 30);
        return;
    }
    
    $file = $_FILES['import_file']['tmp_name'];
    $file_extension = pathinfo($_FILES['import_file']['name'], PATHINFO_EXTENSION);
    
    if ($file_extension !== 'csv') {
        set_transient('bulk_import_error', 'Only CSV files are supported.', 30);
        return;
    }
    
    $results = process_csv_import($file);
    
    set_transient('bulk_import_results', $results, 300);
    
    wp_redirect(add_query_arg('portal_tab', 'import', remove_query_arg('action')));
    exit;
}

function process_csv_import($file) {
    $handle = fopen($file, 'r');
    
    if (!$handle) {
        return array('success' => 0, 'failed' => 0, 'errors' => array('Could not open file'));
    }
    
    $headers = fgetcsv($handle);
    
    $success_count = 0;
    $failed_count = 0;
    $errors = array();
    $row_num = 1;
    
    while (($data = fgetcsv($handle)) !== false) {
        $row_num++;
        
        if (count($data) < count($headers)) {
            $errors[] = "Row $row_num: Incomplete data";
            $failed_count++;
            continue;
        }
        
        $student_data = array_combine($headers, $data);
        
        if (empty($student_data['student_name']) || empty($student_data['gr_number'])) {
            $errors[] = "Row $row_num: Missing required fields (student_name or gr_number)";
            $failed_count++;
            continue;
        }
        
        $post_id = wp_insert_post(array(
            'post_title' => sanitize_text_field($student_data['student_name']),
            'post_type' => 'student',
            'post_status' => 'publish'
        ));
        
        if (is_wp_error($post_id)) {
            $errors[] = "Row $row_num: Failed to create student - " . $post_id->get_error_message();
            $failed_count++;
            continue;
        }
        
        foreach ($student_data as $key => $value) {
            if ($key !== 'student_name' && !empty($value)) {
                update_post_meta($post_id, $key, sanitize_text_field($value));
            }
        }
        
        $success_count++;
    }
    
    fclose($handle);
    
    return array(
        'success' => $success_count,
        'failed' => $failed_count,
        'errors' => $errors
    );
}

function alhuffaz_bulk_import() {
    ob_start();
    
    $import_results = get_transient('bulk_import_results');
    $import_error = get_transient('bulk_import_error');
    
    if ($import_results) {
        delete_transient('bulk_import_results');
    }
    
    if ($import_error) {
        delete_transient('bulk_import_error');
    }
    
    ?>
    <div class="bulk-import-module">
        
        <!-- Header -->
        <div class="import-header">
            <div class="header-content">
                <i class="fas fa-file-import"></i>
                <div>
                    <h2>Bulk Import Students</h2>
                    <p>Import multiple students with complete information via CSV</p>
                </div>
            </div>
        </div>
        
        <!-- Results -->
        <?php if ($import_results): ?>
            <div class="import-results">
                <div class="result-box success-box">
                    <i class="fas fa-check-circle"></i>
                    <div>
                        <h3>Import Complete</h3>
                        <p><strong><?php echo $import_results['success']; ?></strong> students imported successfully</p>
                        <?php if ($import_results['failed'] > 0): ?>
                            <p><strong><?php echo $import_results['failed']; ?></strong> students failed</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if (!empty($import_results['errors'])): ?>
                    <div class="errors-list">
                        <h4><i class="fas fa-exclamation-triangle"></i> Errors:</h4>
                        <ul>
                            <?php foreach (array_slice($import_results['errors'], 0, 10) as $error): ?>
                                <li><?php echo esc_html($error); ?></li>
                            <?php endforeach; ?>
                            <?php if (count($import_results['errors']) > 10): ?>
                                <li>... and <?php echo count($import_results['errors']) - 10; ?> more</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($import_error): ?>
            <div class="import-error">
                <i class="fas fa-times-circle"></i>
                <p><?php echo esc_html($import_error); ?></p>
            </div>
        <?php endif; ?>
        
        <!-- Instructions -->
        <div class="import-instructions">
            <h3><i class="fas fa-info-circle"></i> How to Import Students</h3>
            
            <div class="steps-grid">
                <div class="step-item">
                    <div class="step-num">1</div>
                    <h4>Download Template</h4>
                    <p>Get the complete CSV template with all 40+ fields</p>
                    <button onclick="downloadTemplate()" class="btn-template">
                        <i class="fas fa-download"></i> Download Complete Template
                    </button>
                </div>
                
                <div class="step-item">
                    <div class="step-num">2</div>
                    <h4>Fill Student Data</h4>
                    <p>Open in Excel/Sheets and enter student information</p>
                </div>
                
                <div class="step-item">
                    <div class="step-num">3</div>
                    <h4>Upload CSV</h4>
                    <p>Save as CSV and upload using the form below</p>
                </div>
            </div>
            
            <!-- Field Categories -->
            <div class="field-categories">
                <div class="category-box">
                    <h4><i class="fas fa-user"></i> Basic Info (10 fields)</h4>
                    <ul>
                        <li><strong>student_name</strong> <span class="required">*</span> - Full name</li>
                        <li><strong>gr_number</strong> <span class="required">*</span> - GR number (e.g., GR-2025-001)</li>
                        <li>roll_number, gender (male/female), date_of_birth (YYYY-MM-DD)</li>
                        <li>admission_date, grade_level (kg1/kg2/class1/class2/etc)</li>
                        <li>islamic_studies_category (hifz/nazra/qaidah)</li>
                        <li>permanent_address, current_address</li>
                    </ul>
                </div>
                
                <div class="category-box">
                    <h4><i class="fas fa-users"></i> Family Info (11 fields)</h4>
                    <ul>
                        <li>parent_name, parent_cnic, parent_email</li>
                        <li>guardian_name, guardian_cnic, guardian_email</li>
                        <li>guardian_phone, guardian_whatsapp</li>
                        <li>relationship_to_student (e.g., Uncle, Aunt)</li>
                        <li>emergency_contact, emergency_whatsapp</li>
                    </ul>
                </div>
                
                <div class="category-box">
                    <h4><i class="fas fa-book"></i> Academic Info (13 fields)</h4>
                    <ul>
                        <li>academic_term (term1/term2/semester1/annual)</li>
                        <li>academic_year (e.g., 2025)</li>
                        <li>health_rating, cleanness_rating (poor/satisfactory/perfect)</li>
                        <li>completes_homework, participates_in_class (always/usually/sometimes/rarely)</li>
                        <li>works_well_in_groups, problem_solving_skills (excellent/good/satisfactory/needs_improvement)</li>
                        <li>organization_preparedness</li>
                        <li>total_school_days, present_days</li>
                        <li>teacher_overall_comments, goal_1, goal_2, goal_3</li>
                    </ul>
                </div>
                
                <div class="category-box">
                    <h4><i class="fas fa-money-bill-wave"></i> Fees (6 fields)</h4>
                    <ul>
                        <li>monthly_tuition_fee (numbers only, e.g., 5000)</li>
                        <li>course_fee, uniform_fee, lab_it_fee</li>
                        <li>zakat_eligible, donation_eligible (yes/no)</li>
                    </ul>
                </div>
                
                <div class="category-box">
                    <h4><i class="fas fa-heartbeat"></i> Health Info (3 fields)</h4>
                    <ul>
                        <li>blood_group (A+/A-/B+/B-/AB+/AB-/O+/O-)</li>
                        <li>allergies (list any allergies)</li>
                        <li>medical_conditions (any chronic conditions)</li>
                    </ul>
                </div>
            </div>
            
            <!-- Important Notes -->
            <div class="important-notes">
                <h4><i class="fas fa-exclamation-circle"></i> Important Notes:</h4>
                <ul>
                    <li><strong>Required fields:</strong> Only student_name and gr_number are mandatory</li>
                    <li><strong>Date format:</strong> Use YYYY-MM-DD (e.g., 2015-06-15)</li>
                    <li><strong>Empty fields:</strong> Leave blank if no data available</li>
                    <li><strong>Photos:</strong> Cannot be uploaded via CSV. Add manually later</li>
                    <li><strong>Subjects:</strong> Cannot be imported via CSV. Add manually later</li>
                    <li><strong>File format:</strong> Save as CSV (Comma delimited) *.csv</li>
                </ul>
            </div>
        </div>
        
        <!-- Upload Form -->
        <div class="upload-section">
            <h3><i class="fas fa-upload"></i> Upload CSV File</h3>
            
            <form method="post" enctype="multipart/form-data" class="upload-form">
                <?php wp_nonce_field('bulk_import_submit', 'bulk_import_nonce'); ?>
                
                <div class="file-upload-area" id="fileUploadArea">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <h4>Drag & Drop CSV File</h4>
                    <p>or click to browse</p>
                    <input type="file" name="import_file" id="importFile" accept=".csv" required>
                </div>
                
                <div class="selected-file" id="selectedFile" style="display:none;">
                    <i class="fas fa-file-csv"></i>
                    <span id="fileName"></span>
                    <button type="button" onclick="clearFile()" class="btn-clear">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <button type="submit" name="bulk_import_submit" class="btn-import">
                    <i class="fas fa-file-import"></i> Import Students
                </button>
            </form>
        </div>
        
        <!-- Sample Preview -->
        <div class="sample-preview">
            <h3><i class="fas fa-table"></i> CSV Format Preview (First 8 Columns)</h3>
            <p style="color: #666; margin-bottom: 16px;">
                <i class="fas fa-info-circle"></i> 
                The actual template has 43 columns. This shows a simplified preview.
            </p>
            <div class="table-wrapper">
                <table class="sample-table">
                    <thead>
                        <tr>
                            <th>student_name</th>
                            <th>gr_number</th>
                            <th>gender</th>
                            <th>grade_level</th>
                            <th>guardian_phone</th>
                            <th>monthly_tuition_fee</th>
                            <th>blood_group</th>
                            <th>...</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Muhammad Ahmed</td>
                            <td>GR-2025-001</td>
                            <td>male</td>
                            <td>class1</td>
                            <td>+92 300 1234567</td>
                            <td>5000</td>
                            <td>O+</td>
                            <td>+ 35 more</td>
                        </tr>
                        <tr>
                            <td>Fatima Khan</td>
                            <td>GR-2025-002</td>
                            <td>female</td>
                            <td>kg2</td>
                            <td>+92 321 7654321</td>
                            <td>4500</td>
                            <td>A+</td>
                            <td>+ 35 more</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        
    </div>
    
    <?php
    bulk_import_scripts();
    bulk_import_styles();
    
    return ob_get_clean();
}

function bulk_import_scripts() {
    ?>
    <script>
    function downloadTemplate() {
        // COMPLETE LIST OF ALL FIELDS (43 total)
        const headers = [
            // Basic Info (10)
            'student_name',
            'gr_number',
            'roll_number',
            'gender',
            'date_of_birth',
            'admission_date',
            'grade_level',
            'islamic_studies_category',
            'permanent_address',
            'current_address',
            
            // Family Info (11)
            'parent_name',
            'parent_cnic',
            'parent_email',
            'guardian_name',
            'guardian_cnic',
            'guardian_email',
            'guardian_phone',
            'guardian_whatsapp',
            'relationship_to_student',
            'emergency_contact',
            'emergency_whatsapp',
            
            // Academic Info (13)
            'academic_term',
            'academic_year',
            'health_rating',
            'cleanness_rating',
            'completes_homework',
            'participates_in_class',
            'works_well_in_groups',
            'problem_solving_skills',
            'organization_preparedness',
            'total_school_days',
            'present_days',
            'teacher_overall_comments',
            'goal_1',
            'goal_2',
            'goal_3',
            
            // Fees (6)
            'monthly_tuition_fee',
            'course_fee',
            'uniform_fee',
            'lab_it_fee',
            'zakat_eligible',
            'donation_eligible',
            
            // Health (3)
            'blood_group',
            'allergies',
            'medical_conditions'
        ];
        
        // Sample data rows with complete information
        const sampleData = [
            [
                // Basic Info
                'Muhammad Ahmed Khan',
                'GR-2025-001',
                '1',
                'male',
                '2015-06-15',
                '2025-01-10',
                'class1',
                'nazra',
                '123 Main Street, Block A, Karachi',
                '123 Main Street, Block A, Karachi',
                
                // Family Info
                'Ahmed Khan',
                '42101-1234567-8',
                'ahmed.khan@email.com',
                'Uncle Bilal',
                '42201-9876543-2',
                'bilal@email.com',
                '+92 300 1234567',
                '+92 300 1234567',
                'Uncle',
                '+92 311 9876543',
                '+92 311 9876543',
                
                // Academic Info
                'term1',
                '2025',
                'perfect',
                'satisfactory',
                'always',
                'usually',
                'excellent',
                'good',
                'good',
                '200',
                '185',
                'Excellent student with good behavior',
                'Improve handwriting',
                'Focus on mathematics',
                'Participate more in sports',
                
                // Fees
                '5000',
                '2000',
                '1500',
                '500',
                'no',
                'no',
                
                // Health
                'O+',
                'None',
                'None'
            ],
            [
                // Basic Info
                'Fatima Zahra',
                'GR-2025-002',
                '2',
                'female',
                '2016-03-20',
                '2025-01-10',
                'kg2',
                'qaidah',
                '456 Park Avenue, Gulshan, Karachi',
                '456 Park Avenue, Gulshan, Karachi',
                
                // Family Info
                'Ali Hassan',
                '42301-5555555-5',
                'ali.hassan@email.com',
                'Aunt Sara',
                '42401-6666666-6',
                'sara@email.com',
                '+92 321 7654321',
                '+92 321 7654321',
                'Aunt',
                '+92 333 1111111',
                '+92 333 1111111',
                
                // Academic Info
                'term1',
                '2025',
                'satisfactory',
                'perfect',
                'usually',
                'always',
                'good',
                'excellent',
                'excellent',
                '200',
                '195',
                'Very active and enthusiastic learner',
                'Continue with Quran studies',
                'Develop reading skills',
                'Maintain good attendance',
                
                // Fees
                '4500',
                '2000',
                '1500',
                '500',
                'yes',
                'no',
                
                // Health
                'A+',
                'Peanut allergy',
                'Asthma (mild)'
            ],
            [
                // Basic Info
                'Abdullah Mahmood',
                'GR-2025-003',
                '3',
                'male',
                '2014-08-10',
                '2025-01-10',
                'class2',
                'hifz',
                '789 Garden Road, Defence, Karachi',
                '',
                
                // Family Info
                'Mahmood Ahmed',
                '42501-7777777-7',
                'mahmood@email.com',
                'Father',
                '42501-7777777-7',
                'mahmood@email.com',
                '+92 345 9999999',
                '+92 345 9999999',
                'Father',
                '+92 345 9999999',
                '+92 345 9999999',
                
                // Academic Info
                'term1',
                '2025',
                'perfect',
                'perfect',
                'always',
                'always',
                'excellent',
                'excellent',
                'excellent',
                '200',
                '200',
                'Outstanding student and role model',
                'Complete Hifz memorization',
                'Excel in mathematics',
                'Lead class activities',
                
                // Fees
                '6000',
                '3000',
                '2000',
                '1000',
                'no',
                'no',
                
                // Health
                'B+',
                'None',
                'None'
            ]
        ];
        
        // Create CSV content
        let csv = headers.join(',') + '\n';
        
        sampleData.forEach(row => {
            csv += row.map(cell => {
                // Escape quotes and wrap in quotes if contains comma
                const cellStr = String(cell);
                if (cellStr.includes(',') || cellStr.includes('"') || cellStr.includes('\n')) {
                    return '"' + cellStr.replace(/"/g, '""') + '"';
                }
                return cellStr;
            }).join(',') + '\n';
        });
        
        // Download file
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = 'al_huffaz_students_import_template.csv';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(url);
        
        // Show success message
        alert('✓ Template downloaded!\n\nThe CSV file contains:\n• 43 columns (all fields)\n• 3 sample students with complete data\n\nOpen in Excel/Google Sheets to fill in your students.');
    }
    
    jQuery(document).ready(function($) {
        const fileInput = $('#importFile');
        const uploadArea = $('#fileUploadArea');
        const selectedFile = $('#selectedFile');
        const fileName = $('#fileName');
        
        uploadArea.on('click', function(e) {
            if (e.target.tagName !== 'INPUT') {
                fileInput.click();
            }
        });
        
        fileInput.on('change', function() {
            if (this.files && this.files[0]) {
                fileName.text(this.files[0].name);
                uploadArea.hide();
                selectedFile.show();
            }
        });
        
        uploadArea.on('dragover', function(e) {
            e.preventDefault();
            $(this).addClass('dragging');
        });
        
        uploadArea.on('dragleave', function(e) {
            e.preventDefault();
            $(this).removeClass('dragging');
        });
        
        uploadArea.on('drop', function(e) {
            e.preventDefault();
            $(this).removeClass('dragging');
            
            const files = e.originalEvent.dataTransfer.files;
            if (files.length > 0) {
                fileInput[0].files = files;
                fileName.text(files[0].name);
                uploadArea.hide();
                selectedFile.show();
            }
        });
    });
    
    function clearFile() {
        jQuery('#importFile').val('');
        jQuery('#selectedFile').hide();
        jQuery('#fileUploadArea').show();
    }
    </script>
    <?php
}

function bulk_import_styles() {
    ?>
    <style>
    .bulk-import-module {
        max-width: 1200px;
        margin: 0 auto;
        font-family: 'Poppins', sans-serif;
    }
    
    .import-header {
        background: linear-gradient(135deg, #0080ff 0%, #004d99 100%);
        color: white;
        border-radius: 16px;
        padding: 32px;
        margin-bottom: 24px;
        box-shadow: 0 8px 24px rgba(0, 128, 255, 0.3);
    }
    
    .header-content {
        display: flex;
        align-items: center;
        gap: 20px;
    }
    
    .header-content i {
        font-size: 56px;
    }
    
    .header-content h2 {
        margin: 0;
        font-size: 28px;
        font-weight: 700;
    }
    
    .header-content p {
        margin: 8px 0 0 0;
        opacity: 0.9;
        font-size: 16px;
    }
    
    .import-results {
        background: white;
        border-radius: 12px;
        padding: 24px;
        margin-bottom: 24px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }
    
    .result-box {
        display: flex;
        align-items: flex-start;
        gap: 16px;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 16px;
    }
    
    .success-box {
        background: #d1fae5;
        border: 2px solid #10b981;
    }
    
    .success-box i {
        font-size: 32px;
        color: #10b981;
    }
    
    .result-box h3 {
        margin: 0 0 8px 0;
        font-size: 18px;
        color: #065f46;
    }
    
    .result-box p {
        margin: 4px 0;
        color: #047857;
    }
    
    .errors-list {
        background: #fff3e0;
        border: 2px solid #fb8c00;
        border-radius: 10px;
        padding: 20px;
    }
    
    .errors-list h4 {
        margin: 0 0 12px 0;
        color: #f57c00;
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 16px;
    }
    
    .errors-list ul {
        margin: 0;
        padding-left: 24px;
    }
    
    .errors-list li {
        margin: 6px 0;
        color: #e65100;
        font-size: 14px;
    }
    
    .import-error {
        background: #fee2e2;
        border: 2px solid #ef4444;
        border-radius: 10px;
        padding: 18px 24px;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        gap: 14px;
    }
    
    .import-error i {
        font-size: 28px;
        color: #ef4444;
    }
    
    .import-instructions {
        background: white;
        border-radius: 12px;
        padding: 32px;
        margin-bottom: 24px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }
    
    .import-instructions h3 {
        margin: 0 0 28px 0;
        font-size: 24px;
        color: #001a33;
        display: flex;
        align-items: center;
        gap: 12px;
        font-weight: 700;
    }
    
    .steps-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 24px;
        margin-bottom: 32px;
    }
    
    .step-item {
        background: #f0f8ff;
        border: 2px solid #b3d9ff;
        border-radius: 12px;
        padding: 28px;
        text-align: center;
        transition: all 0.3s;
    }
    
    .step-item:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 20px rgba(0, 128, 255, 0.15);
        border-color: #0080ff;
    }
    
    .step-num {
        width: 56px;
        height: 56px;
        border-radius: 50%;
        background: linear-gradient(135deg, #0080ff, #004d99);
        color: white;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 28px;
        font-weight: 700;
        margin-bottom: 16px;
        box-shadow: 0 4px 12px rgba(0, 128, 255, 0.3);
    }
    
    .step-item h4 {
        margin: 0 0 10px 0;
        color: #001a33;
        font-size: 18px;
        font-weight: 600;
    }
    
    .step-item p {
        margin: 0 0 20px 0;
        color: #334155;
        font-size: 14px;
        line-height: 1.6;
    }
    
    .btn-template {
        padding: 12px 20px;
        background: linear-gradient(135deg, #0080ff, #004d99);
        color: white;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        transition: all 0.3s;
        font-family: 'Poppins', sans-serif;
    }
    
    .btn-template:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(0, 128, 255, 0.4);
    }
    
    .field-categories {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        gap: 20px;
        margin-bottom: 28px;
    }
    
    .category-box {
        background: #f0f8ff;
        border: 2px solid #b3d9ff;
        border-radius: 10px;
        padding: 20px;
    }
    
    .category-box h4 {
        margin: 0 0 14px 0;
        color: #0080ff;
        font-size: 16px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .category-box ul {
        margin: 0;
        padding-left: 20px;
    }
    
    .category-box li {
        margin: 8px 0;
        color: #334155;
        font-size: 13px;
        line-height: 1.6;
    }
    
    .required {
        color: #ef4444;
        font-weight: 700;
    }
    
    .important-notes {
        background: #fff3e0;
        border: 2px solid #fb8c00;
        border-radius: 10px;
        padding: 20px;
    }
    
    .important-notes h4 {
        margin: 0 0 14px 0;
        color: #f57c00;
        font-size: 16px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .important-notes ul {
        margin: 0;
        padding-left: 24px;
    }
    
    .important-notes li {
        margin: 10px 0;
        color: #78350f;
        font-size: 14px;
        line-height: 1.6;
    }
    
    .upload-section {
        background: white;
        border-radius: 12px;
        padding: 32px;
        margin-bottom: 24px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }
    
    .upload-section h3 {
        margin: 0 0 24px 0;
        font-size: 24px;
        color: #001a33;
        display: flex;
        align-items: center;
        gap: 12px;
        font-weight: 700;
    }
    
    .upload-form {
        max-width: 600px;
        margin: 0 auto;
    }
    
    .file-upload-area {
        border: 3px dashed #b3d9ff;
        border-radius: 16px;
        padding: 60px 40px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s;
        position: relative;
        background: #f0f8ff;
    }
    
    .file-upload-area:hover,
    .file-upload-area.dragging {
        border-color: #0080ff;
        background: #e6f2ff;
        transform: scale(1.02);
    }
    
    .file-upload-area i {
        font-size: 72px;
        color: #0080ff;
        margin-bottom: 20px;
    }
    
    .file-upload-area h4 {
        margin: 0 0 10px 0;
        color: #001a33;
        font-size: 20px;
        font-weight: 600;
    }
    
    .file-upload-area p {
        margin: 0;
        color: #64748b;
        font-size: 15px;
    }
    
    .file-upload-area input[type="file"] {
        position: absolute;
        width: 0;
        height: 0;
        opacity: 0;
    }
    
    .selected-file {
        background: #e6f2ff;
        border: 2px solid #0080ff;
        border-radius: 12px;
        padding: 20px 24px;
        display: flex;
        align-items: center;
        gap: 16px;
        margin-bottom: 20px;
    }
    
    .selected-file i {
        font-size: 36px;
        color: #0080ff;
    }
    
    .selected-file span {
        flex: 1;
        font-weight: 500;
        color: #001a33;
        font-size: 15px;
    }
    
    .btn-clear {
        padding: 10px 14px;
        background: #ef4444;
        color: white;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .btn-clear:hover {
        background: #dc2626;
    }
    
    .btn-import {
        width: 100%;
        padding: 16px 28px;
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
        border: none;
        border-radius: 10px;
        font-size: 17px;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        transition: all 0.3s;
        font-family: 'Poppins', sans-serif;
    }
    
    .btn-import:hover {
        background: linear-gradient(135deg, #059669, #047857);
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
    }
    
    .sample-preview {
        background: white;
        border-radius: 12px;
        padding: 32px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }
    
    .sample-preview h3 {
        margin: 0 0 8px 0;
        font-size: 24px;
        color: #001a33;
        display: flex;
        align-items: center;
        gap: 12px;
        font-weight: 700;
    }
    
    .table-wrapper {
        overflow-x: auto;
        margin-top: 16px;
        border-radius: 8px;
        border: 2px solid #e6f2ff;
    }
    
    .sample-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .sample-table th,
    .sample-table td {
        padding: 14px 16px;
        text-align: left;
        border: 1px solid #cce6ff;
        font-size: 14px;
    }
    
    .sample-table th {
        background: #0080ff;
        color: white;
        font-weight: 600;
        white-space: nowrap;
    }
    
    .sample-table td {
        color: #334155;
        background: white;
    }
    
    .sample-table tbody tr:hover {
        background: #f0f8ff;
    }
    
    @media (max-width: 768px) {
        .import-header {
            padding: 24px;
        }
        
        .header-content {
            flex-direction: column;
            text-align: center;
        }
        
        .steps-grid,
        .field-categories {
            grid-template-columns: 1fr;
        }
        
        .file-upload-area {
            padding: 40px 20px;
        }
    }
    </style>
    <?php
}
?>