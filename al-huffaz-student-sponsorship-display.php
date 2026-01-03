<?php
/*
Plugin Name: Al-Huffaz Student Sponsorship Display (Hide Sponsored)
Description: Displays students in card layout - hides already sponsored students
Version: 2.0
Author: RoohUl Hasnain
*/

defined('ABSPATH') || exit;

add_shortcode('student_sponsorship', 'alhuffaz_student_sponsorship_display');

function alhuffaz_student_sponsorship_display() {
    // ✅✅✅ ONLY CHANGE: Added filter to hide already_sponsored students ✅✅✅
    $args = array(
        'post_type' => 'student',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'orderby' => 'title',
        'order' => 'ASC',
        'meta_query' => array(
            'relation' => 'AND',
            array(
                'key' => 'donation_eligible',
                'value' => 'yes',
                'compare' => '='
            ),
            array(
                'relation' => 'OR',
                array(
                    'key' => 'already_sponsored',
                    'compare' => 'NOT EXISTS'
                ),
                array(
                    'key' => 'already_sponsored',
                    'value' => 'yes',
                    'compare' => '!='
                )
            )
        )
    );
    
    $students = new WP_Query($args);
    
    // Get unique grades and categories
    $all_grades = array();
    $all_categories = array();
    
    if ($students->have_posts()) {
        while ($students->have_posts()) {
            $students->the_post();
            $grade = get_post_meta(get_the_ID(), 'grade_level', true);
            $category = get_post_meta(get_the_ID(), 'islamic_studies_category', true);
            
            if ($grade && !in_array($grade, $all_grades)) {
                $all_grades[] = $grade;
            }
            if ($category && !in_array($category, $all_categories)) {
                $all_categories[] = $category;
            }
        }
        wp_reset_postdata();
    }
    
    $grade_map = array(
        'kg1' => 'KG 1', 'kg2' => 'KG 2',
        'class1' => 'CLASS 1', 'class2' => 'CLASS 2', 'class3' => 'CLASS 3',
        'level1' => 'LEVEL 1', 'level2' => 'LEVEL 2', 'level3' => 'LEVEL 3',
        'shb' => 'SHB', 'shg' => 'SHG'
    );
    
    $islamic_map = array('hifz' => 'Hifz', 'nazra' => 'Nazra', 'qaidah' => 'Qaidah');
    
    $output = '';
    
    // Add CSS
    $output .= '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">';
    $output .= '<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">';
    
    $output .= '<style>
    .sp-container {
        font-family: "Poppins", sans-serif;
        max-width: 1400px;
        margin: 30px auto;
        padding: 20px;
    }
    
    .sp-header {
        text-align: center;
        margin-bottom: 35px;
    }
    
    .sp-header h1 {
        font-size: 34px;
        font-weight: 700;
        color: #001a33;
        margin-bottom: 10px;
    }
    
    .sp-header h1 i {
        color: #0080ff;
    }
    
    .sp-header p {
        font-size: 15px;
        color: #64748b;
        max-width: 650px;
        margin: 0 auto;
        line-height: 1.5;
    }
    
    .sp-filters {
        background: #f8fafc;
        padding: 20px;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        margin-bottom: 30px;
        display: grid;
        grid-template-columns: 1fr 1fr 1fr auto;
        gap: 14px;
        align-items: end;
    }
    
    .sp-filter-group label {
        display: block;
        font-weight: 600;
        color: #334155;
        margin-bottom: 5px;
        font-size: 12px;
    }
    
    .sp-filter-group input,
    .sp-filter-group select {
        width: 100%;
        padding: 9px 12px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        font-size: 13px;
        font-family: "Poppins", sans-serif;
        background: white;
        transition: border 0.2s;
    }
    
    .sp-filter-group input:focus,
    .sp-filter-group select:focus {
        outline: none;
        border-color: #0080ff;
        box-shadow: 0 0 0 3px rgba(0, 128, 255, 0.1);
    }
    
    .sp-reset-btn {
        padding: 9px 20px;
        background: #64748b;
        color: white;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        font-size: 13px;
        font-family: "Poppins", sans-serif;
        height: 38px;
        transition: all 0.2s;
    }
    
    .sp-reset-btn:hover {
        background: #475569;
    }
    
    .sp-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 20px;
    }
    
    .sp-card {
        background: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        border: 1px solid #e2e8f0;
        transition: all 0.3s;
    }
    
    .sp-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 24px rgba(0, 128, 255, 0.15);
        border-color: #0080ff;
    }
    
    .sp-photo {
        position: relative;
        height: 160px;
        background: linear-gradient(135deg, #e6f2ff, #cce6ff);
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .sp-photo img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .sp-placeholder {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: linear-gradient(135deg, #0080ff, #004d99);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 40px;
        font-weight: 700;
    }
    
    .sp-badge {
        position: absolute;
        top: 10px;
        right: 10px;
        background: rgba(255, 255, 255, 0.95);
        padding: 4px 10px;
        border-radius: 14px;
        font-weight: 600;
        font-size: 10px;
        color: #0080ff;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }
    
    .sp-info {
        padding: 16px;
    }
    
    .sp-name {
        font-size: 16px;
        font-weight: 700;
        color: #001a33;
        margin: 0 0 8px 0;
        line-height: 1.3;
    }
    
    .sp-meta {
        display: flex;
        gap: 6px;
        margin-bottom: 12px;
        flex-wrap: wrap;
    }
    
    .sp-tag {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 3px 8px;
        background: #f0f8ff;
        border-radius: 12px;
        font-size: 10px;
        font-weight: 600;
        color: #004d99;
    }
    
    .sp-tag i {
        font-size: 9px;
        color: #0080ff;
    }
    
    .sp-financial {
        background: #f8fafc;
        padding: 10px;
        border-radius: 8px;
        margin-bottom: 12px;
        border: 1px solid #e2e8f0;
    }
    
    .sp-fee-row {
        display: flex;
        justify-content: space-between;
        padding: 4px 0;
    }
    
    .sp-fee-label {
        font-size: 11px;
        color: #64748b;
        font-weight: 500;
    }
    
    .sp-fee-value {
        font-size: 12px;
        font-weight: 700;
        color: #001a33;
        font-family: monospace;
    }
    
    .sp-sponsor-section {
        border-top: 1px solid #e2e8f0;
        padding-top: 12px;
    }
    
    .sp-sponsor-title {
        font-size: 12px;
        font-weight: 700;
        color: #001a33;
        margin: 0 0 10px 0;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    .sp-sponsor-title i {
        color: #10b981;
        font-size: 13px;
    }
    
    .sp-options {
        display: grid;
        gap: 6px;
    }
    
    .sp-option {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 12px;
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .sp-option:hover {
        border-color: #0080ff;
        background: #f0f8ff;
        transform: translateX(2px);
    }
    
    .sp-option-left {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .sp-icon {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        color: white;
        flex-shrink: 0;
    }
    
    .sp-monthly .sp-icon {
        background: linear-gradient(135deg, #3b82f6, #2563eb);
    }
    
    .sp-quarterly .sp-icon {
        background: linear-gradient(135deg, #10b981, #059669);
    }
    
    .sp-yearly .sp-icon {
        background: linear-gradient(135deg, #f59e0b, #d97706);
    }
    
    .sp-details h4 {
        margin: 0 0 2px 0;
        font-size: 12px;
        font-weight: 700;
        color: #001a33;
    }
    
    .sp-details p {
        margin: 0;
        font-size: 9px;
        color: #64748b;
    }
    
    .sp-amount {
        font-size: 13px;
        font-weight: 700;
        color: #0080ff;
        font-family: monospace;
    }
    
    .sp-no-results {
        grid-column: 1 / -1;
        text-align: center;
        padding: 50px 20px;
        color: #64748b;
    }
    
    .sp-no-results i {
        font-size: 48px;
        color: #cbd5e1;
        margin-bottom: 16px;
    }
    
    @media (max-width: 1200px) {
        .sp-grid {
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
        }
    }
    
    @media (max-width: 992px) {
        .sp-filters {
            grid-template-columns: 1fr;
        }
        
        .sp-grid {
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
        }
    }
    
    @media (max-width: 768px) {
        .sp-header h1 {
            font-size: 26px;
        }
        
        .sp-grid {
            grid-template-columns: 1fr;
        }
    }
    </style>';
    
    $output .= '<div class="sp-container">';
    
    // Header
    $output .= '<div class="sp-header">';
    $output .= '<h1><i class="fas fa-heart"></i> Sponsor a Student</h1>';
    $output .= '<p>Support a student\'s complete education journey. Your sponsorship covers tuition, books, uniform, and all annual expenses.</p>';
    $output .= '</div>';
    
    // Filters
    $output .= '<div class="sp-filters">';
    
    $output .= '<div class="sp-filter-group">';
    $output .= '<label><i class="fas fa-search"></i> Search by Name or GR</label>';
    $output .= '<input type="text" id="spSearchStudent" placeholder="Type name or GR...">';
    $output .= '</div>';
    
    $output .= '<div class="sp-filter-group">';
    $output .= '<label><i class="fas fa-layer-group"></i> Filter by Grade</label>';
    $output .= '<select id="spFilterGrade">';
    $output .= '<option value="">All Grades</option>';
    foreach ($all_grades as $grade) {
        $grade_label = isset($grade_map[$grade]) ? $grade_map[$grade] : ucfirst($grade);
        $output .= '<option value="' . esc_attr($grade) . '">' . esc_html($grade_label) . '</option>';
    }
    $output .= '</select>';
    $output .= '</div>';
    
    $output .= '<div class="sp-filter-group">';
    $output .= '<label><i class="fas fa-quran"></i> Category</label>';
    $output .= '<select id="spFilterCategory">';
    $output .= '<option value="">All Categories</option>';
    foreach ($all_categories as $cat) {
        $cat_label = isset($islamic_map[$cat]) ? $islamic_map[$cat] : ucfirst($cat);
        $output .= '<option value="' . esc_attr($cat) . '">' . esc_html($cat_label) . '</option>';
    }
    $output .= '</select>';
    $output .= '</div>';
    
    $output .= '<button class="sp-reset-btn" id="spResetFilters"><i class="fas fa-redo"></i> Reset</button>';
    
    $output .= '</div>'; // filters
    
    // Grid
    $output .= '<div class="sp-grid" id="spStudentsGrid">';
    
    if ($students->have_posts()) {
        while ($students->have_posts()) {
            $students->the_post();
            $student_id = get_the_ID();
            
            $gr_number = get_post_meta($student_id, 'gr_number', true);
            $grade_level = get_post_meta($student_id, 'grade_level', true);
            $islamic_category = get_post_meta($student_id, 'islamic_studies_category', true);
            $monthly_fee = floatval(get_post_meta($student_id, 'monthly_tuition_fee', true));
            $course_fee = floatval(get_post_meta($student_id, 'course_fee', true));
            $uniform_fee = floatval(get_post_meta($student_id, 'uniform_fee', true));
            $annual_fee = floatval(get_post_meta($student_id, 'annual_fee', true));
            $photo_id = get_post_meta($student_id, 'student_photo', true);
            
            $photo_url = $photo_id ? wp_get_attachment_image_url($photo_id, 'medium') : '';
            $one_time_fees = $course_fee + $uniform_fee + $annual_fee;
            
            $monthly_amount = $monthly_fee + ($one_time_fees / 12);
            $quarterly_amount = ($monthly_fee * 3) + ($one_time_fees / 4);
            $yearly_amount = ($monthly_fee * 12) + $one_time_fees;
            
            $grade_display = isset($grade_map[$grade_level]) ? $grade_map[$grade_level] : strtoupper($grade_level);
            $islamic_display = isset($islamic_map[$islamic_category]) ? $islamic_map[$islamic_category] : ucfirst($islamic_category);
            
            $output .= '<div class="sp-card" 
                data-name="' . esc_attr(strtolower(get_the_title())) . '"
                data-gr="' . esc_attr(strtolower($gr_number)) . '"
                data-grade="' . esc_attr($grade_level) . '"
                data-category="' . esc_attr($islamic_category) . '">';
            
            // Photo
            $output .= '<div class="sp-photo">';
            if ($photo_url) {
                $output .= '<img src="' . esc_url($photo_url) . '" alt="' . esc_attr(get_the_title()) . '">';
            } else {
                $initial = strtoupper(substr(get_the_title(), 0, 1));
                $output .= '<div class="sp-placeholder">' . esc_html($initial) . '</div>';
            }
            if ($islamic_display) {
                $output .= '<div class="sp-badge"><i class="fas fa-quran"></i> ' . esc_html($islamic_display) . '</div>';
            }
            $output .= '</div>';
            
            // Info
            $output .= '<div class="sp-info">';
            $output .= '<h3 class="sp-name">' . get_the_title() . '</h3>';
            
            $output .= '<div class="sp-meta">';
            if ($grade_display) {
                $output .= '<span class="sp-tag"><i class="fas fa-layer-group"></i> ' . esc_html($grade_display) . '</span>';
            }
            if ($gr_number) {
                $output .= '<span class="sp-tag"><i class="fas fa-id-card"></i> ' . esc_html($gr_number) . '</span>';
            }
            $output .= '</div>';
            
            // Financial
            $output .= '<div class="sp-financial">';
            $output .= '<div class="sp-fee-row">';
            $output .= '<span class="sp-fee-label">Monthly Tuition:</span>';
            $output .= '<span class="sp-fee-value">PKR ' . number_format($monthly_fee) . '</span>';
            $output .= '</div>';
            $output .= '<div class="sp-fee-row">';
            $output .= '<span class="sp-fee-label">One-Time Fees:</span>';
            $output .= '<span class="sp-fee-value">PKR ' . number_format($one_time_fees) . '</span>';
            $output .= '</div>';
            $output .= '</div>';
            
            // Sponsorship
            $output .= '<div class="sp-sponsor-section">';
            $output .= '<h4 class="sp-sponsor-title"><i class="fas fa-hand-holding-heart"></i> Support Options</h4>';
            $output .= '<div class="sp-options">';
            
            // Monthly
            $output .= '<div class="sp-option sp-monthly" onclick="sponsorStudent(' . $student_id . ', \'monthly\', ' . $monthly_amount . ')">';
            $output .= '<div class="sp-option-left">';
            $output .= '<div class="sp-icon"><i class="fas fa-calendar-day"></i></div>';
            $output .= '<div class="sp-details"><h4>Monthly</h4><p>1 month support</p></div>';
            $output .= '</div>';
            $output .= '<div class="sp-amount">PKR ' . number_format(round($monthly_amount)) . '</div>';
            $output .= '</div>';
            
            // Quarterly
            $output .= '<div class="sp-option sp-quarterly" onclick="sponsorStudent(' . $student_id . ', \'quarterly\', ' . $quarterly_amount . ')">';
            $output .= '<div class="sp-option-left">';
            $output .= '<div class="sp-icon"><i class="fas fa-calendar-week"></i></div>';
            $output .= '<div class="sp-details"><h4>Quarterly</h4><p>3 months support</p></div>';
            $output .= '</div>';
            $output .= '<div class="sp-amount">PKR ' . number_format(round($quarterly_amount)) . '</div>';
            $output .= '</div>';
            
            // Yearly
            $output .= '<div class="sp-option sp-yearly" onclick="sponsorStudent(' . $student_id . ', \'yearly\', ' . $yearly_amount . ')">';
            $output .= '<div class="sp-option-left">';
            $output .= '<div class="sp-icon"><i class="fas fa-calendar-alt"></i></div>';
            $output .= '<div class="sp-details"><h4>Yearly</h4><p>Full year support</p></div>';
            $output .= '</div>';
            $output .= '<div class="sp-amount">PKR ' . number_format(round($yearly_amount)) . '</div>';
            $output .= '</div>';
            
            $output .= '</div>'; // options
            $output .= '</div>'; // sponsor-section
            $output .= '</div>'; // info
            $output .= '</div>'; // card
        }
        wp_reset_postdata();
    } else {
        $output .= '<div class="sp-no-results">';
        $output .= '<i class="fas fa-info-circle"></i>';
        $output .= '<p>No students are currently available for sponsorship.</p>';
        $output .= '</div>';
    }
    
    $output .= '</div>'; // grid
    $output .= '</div>'; // container
    
    // ✅ BUILD URL IN PHP FIRST
    $payment_url = site_url('/sponsor-payment/');
    
    // ✅ THEN USE IT IN JAVASCRIPT
    $output .= '<script>
    function sponsorStudent(studentId, type, amount) {
        window.location.href = "' . $payment_url . '?student=" + studentId + "&type=" + type + "&amount=" + Math.round(amount);
    }
    
    (function() {
        function filterStudents() {
            var searchTerm = document.getElementById("spSearchStudent").value.toLowerCase();
            var selectedGrade = document.getElementById("spFilterGrade").value;
            var selectedCategory = document.getElementById("spFilterCategory").value;
            
            var cards = document.querySelectorAll(".sp-card");
            var visibleCount = 0;
            
            cards.forEach(function(card) {
                var name = card.getAttribute("data-name");
                var gr = card.getAttribute("data-gr");
                var grade = card.getAttribute("data-grade");
                var category = card.getAttribute("data-category");
                
                var matchesSearch = searchTerm === "" || name.includes(searchTerm) || gr.includes(searchTerm);
                var matchesGrade = selectedGrade === "" || grade === selectedGrade;
                var matchesCategory = selectedCategory === "" || category === selectedCategory;
                
                if (matchesSearch && matchesGrade && matchesCategory) {
                    card.style.display = "block";
                    visibleCount++;
                } else {
                    card.style.display = "none";
                }
            });
            
            var grid = document.getElementById("spStudentsGrid");
            var existingNoResults = grid.querySelector(".sp-no-results");
            
            if (visibleCount === 0 && !existingNoResults) {
                var noResults = document.createElement("div");
                noResults.className = "sp-no-results";
                noResults.innerHTML = \'<i class="fas fa-search"></i><p>No students match your search criteria.</p>\';
                grid.appendChild(noResults);
            } else if (visibleCount > 0 && existingNoResults) {
                existingNoResults.remove();
            }
        }
        
        document.getElementById("spSearchStudent").addEventListener("keyup", filterStudents);
        document.getElementById("spFilterGrade").addEventListener("change", filterStudents);
        document.getElementById("spFilterCategory").addEventListener("change", filterStudents);
        
        document.getElementById("spResetFilters").addEventListener("click", function() {
            document.getElementById("spSearchStudent").value = "";
            document.getElementById("spFilterGrade").value = "";
            document.getElementById("spFilterCategory").value = "";
            filterStudents();
        });
    })();
    </script>';
    
    return $output;
}
?>