<?php
/*
Plugin Name: Al-Huffaz Student Display
Description: Displays students in card format with search/filter
Version: 1.2
Author: RoohUl Hasnain
*/

defined('ABSPATH') || exit;

// Register shortcode
add_shortcode('student_cards', 'alhuffaz_student_cards');

function alhuffaz_student_cards($atts) {
    $atts = shortcode_atts(array(
        'limit' => 12,
        'grade' => '',
        'gender' => ''
    ), $atts);
    
    ob_start();
    
    // Get filters from URL
    $search = isset($_GET['student_search']) ? sanitize_text_field($_GET['student_search']) : '';
    $grade_filter = isset($_GET['student_grade']) ? sanitize_text_field($_GET['student_grade']) : $atts['grade'];
    $gender_filter = isset($_GET['student_gender']) ? sanitize_text_field($_GET['student_gender']) : $atts['gender'];
    $paged = max(1, get_query_var('paged') ?: (isset($_GET['student_page']) ? intval($_GET['student_page']) : 1));
    
    // Get all available grades dynamically
    $available_grades = get_all_student_grades();
    
    // Build query
    $args = array(
        'post_type' => 'student',
        'post_status' => 'publish',
        'posts_per_page' => intval($atts['limit']),
        'orderby' => 'date',
        'order' => 'DESC',
        'paged' => $paged
    );
    
    if ($search) {
        $args['s'] = $search;
    }
    
    $meta_query = array('relation' => 'AND');
    
    if ($grade_filter) {
        $meta_query[] = array(
            'key' => 'grade_level',
            'value' => $grade_filter,
            'compare' => '='
        );
    }
    
    if ($gender_filter) {
        $meta_query[] = array(
            'key' => 'gender',
            'value' => $gender_filter,
            'compare' => '='
        );
    }
    
    if (count($meta_query) > 1) {
        $args['meta_query'] = $meta_query;
    }
    
    $students = new WP_Query($args);
    
    // Calculate pagination
    $total_pages = $students->max_num_pages;
    $current_page = $paged;
    
    ?>
    <div class="student-display-module">
        
        <!-- Search & Filters -->
        <?php if ($atts['limit'] == -1 || $atts['limit'] >= 12): ?>
        <div class="display-filters">
            <form method="get" class="filter-form">
                <?php 
                // Preserve existing query parameters
                foreach($_GET as $key => $value) {
                    if ($key !== 'student_search' && $key !== 'student_grade' && $key !== 'student_gender' && $key !== 'student_page') {
                        echo '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '">';
                    }
                }
                ?>
                
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" name="student_search" placeholder="Search students..." 
                           value="<?php echo esc_attr($search); ?>">
                </div>
                
                <select name="student_grade" class="filter-select">
                    <option value="">All Grades</option>
                    <?php
                    // Dynamic grade options
                    foreach ($available_grades as $grade_value):
                        $grade_label = format_grade_label($grade_value);
                    ?>
                        <option value="<?php echo esc_attr($grade_value); ?>" <?php selected($grade_filter, $grade_value); ?>>
                            <?php echo esc_html($grade_label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <select name="student_gender" class="filter-select">
                    <option value="">All Genders</option>
                    <option value="male" <?php selected($gender_filter, 'male'); ?>>Male</option>
                    <option value="female" <?php selected($gender_filter, 'female'); ?>>Female</option>
                </select>
                
                <button type="submit" class="btn-filter">
                    <i class="fas fa-filter"></i> Filter
                </button>
                
                <?php if ($search || $grade_filter || $gender_filter): ?>
                <a href="<?php echo esc_url(remove_query_arg(array('student_search', 'student_grade', 'student_gender', 'student_page'))); ?>" 
                   class="btn-clear">
                    <i class="fas fa-times"></i> Clear Filters
                </a>
                <?php endif; ?>
            </form>
        </div>
        <?php endif; ?>
        
        <!-- Student Cards -->
        <?php if ($students->have_posts()): ?>
            <div class="student-cards-grid">
                <?php while ($students->have_posts()): $students->the_post();
                    $id = get_the_ID();
                    $photo_id = get_post_meta($id, 'student_photo', true);
                    $photo = $photo_id ? wp_get_attachment_image_url($photo_id, 'medium') : '';
                    $gr = get_post_meta($id, 'gr_number', true);
                    $grade = get_post_meta($id, 'grade_level', true);
                    $gender = get_post_meta($id, 'gender', true);
                    $phone = get_post_meta($id, 'guardian_phone', true);
                    $percentage = get_post_meta($id, 'overall_percentage', true);
                ?>
                    <div class="student-card">
                        <div class="card-photo">
                            <?php if ($photo): ?>
                                <img src="<?php echo esc_url($photo); ?>" alt="<?php the_title_attribute(); ?>">
                            <?php else: ?>
                                <div class="photo-placeholder">
                                    <?php echo strtoupper(substr(get_the_title(), 0, 1)); ?>
                                </div>
                            <?php endif; ?>
                            <span class="card-badge"><?php echo esc_html(format_grade_label($grade)); ?></span>
                        </div>
                        
                        <div class="card-content">
                            <h4><?php the_title(); ?></h4>
                            <div class="card-details">
                                <span><i class="fas fa-id-card"></i> <?php echo esc_html($gr); ?></span>
                                <span><i class="fas fa-venus-mars"></i> <?php echo ucfirst($gender); ?></span>
                                <?php if ($phone): ?>
                                <span><i class="fas fa-phone"></i> <?php echo esc_html($phone); ?></span>
                                <?php endif; ?>
                                <?php if ($percentage): ?>
                                <span><i class="fas fa-chart-line"></i> <?php echo esc_html($percentage); ?>%</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="card-actions">
                            <a href="<?php echo esc_url(get_permalink()); ?>" class="btn-card btn-view">
                                <i class="fas fa-eye"></i> View
                            </a>
                            <a href="?portal_tab=add&edit=<?php echo esc_attr($id); ?>" class="btn-card btn-edit">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                        </div>
                    </div>
                <?php endwhile; wp_reset_postdata(); ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1 && $atts['limit'] != -1): ?>
            <div class="student-pagination">
                <div class="pagination-info">
                    Showing page <?php echo esc_html($current_page); ?> of <?php echo esc_html($total_pages); ?>
                    | Total students: <?php echo esc_html($students->found_posts); ?>
                </div>
                
                <div class="pagination-links">
                    <?php if ($current_page > 1): ?>
                    <a href="<?php echo esc_url(add_query_arg('student_page', $current_page - 1)); ?>" 
                       class="pagination-link prev">
                        <i class="fas fa-chevron-left"></i> Previous
                    </a>
                    <?php endif; ?>
                    
                    <div class="page-numbers">
                        <?php
                        $range = 2;
                        $show_items = ($range * 2) + 1;
                        
                        for ($i = 1; $i <= $total_pages; $i++):
                            if (1 != $total_pages && (!($i >= $current_page + $range + 1 || $i <= $current_page - $range - 1) || $total_pages <= $show_items)):
                                if ($current_page == $i): ?>
                                    <span class="current-page"><?php echo esc_html($i); ?></span>
                                <?php else: ?>
                                    <a href="<?php echo esc_url(add_query_arg('student_page', $i)); ?>" 
                                       class="page-number"><?php echo esc_html($i); ?></a>
                                <?php endif;
                            endif;
                        endfor;
                        ?>
                    </div>
                    
                    <?php if ($current_page < $total_pages): ?>
                    <a href="<?php echo esc_url(add_query_arg('student_page', $current_page + 1)); ?>" 
                       class="pagination-link next">
                        Next <i class="fas fa-chevron-right"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            
        <?php else: ?>
            <div class="no-students">
                <i class="fas fa-inbox"></i>
                <h3>No Students Found</h3>
                <p>Try adjusting your search criteria</p>
                <?php if ($search || $grade_filter || $gender_filter): ?>
                <a href="<?php echo esc_url(remove_query_arg(array('student_search', 'student_grade', 'student_gender', 'student_page'))); ?>" 
                   class="btn-clear">
                    Clear All Filters
                </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
    </div>
    
    <?php
    student_display_styles();
    return ob_get_clean();
}

/**
 * Get all unique grade levels from students
 * Uses efficient database query with caching
 */
function get_all_student_grades() {
    global $wpdb;
    
    $cache_key = 'alhuffaz_student_grades';
    $grades = get_transient($cache_key);
    
    // Return cached results if available
    if ($grades !== false) {
        return $grades;
    }
    
    // Get unique grade values from database
    $grade_values = $wpdb->get_col(
        $wpdb->prepare(
            "SELECT DISTINCT meta_value 
             FROM {$wpdb->postmeta} 
             WHERE meta_key = %s 
             AND meta_value != '' 
             AND meta_value IS NOT NULL
             ORDER BY 
                CASE 
                    WHEN meta_value LIKE 'kg%' THEN 0
                    WHEN meta_value LIKE 'class%' THEN 1
                    WHEN meta_value LIKE 'grade%' THEN 2
                    ELSE 3
                END,
                meta_value",
            'grade_level'
        )
    );
    
    $grades = array();
    if (!empty($grade_values)) {
        foreach ($grade_values as $value) {
            if ($value && !in_array($value, $grades)) {
                $grades[] = $value;
            }
        }
    }
    
    // Fallback to default grades if none found
    if (empty($grades)) {
        $grades = array('kg1', 'kg2', 'class1', 'class2', 'class3');
    }
    
    // Cache results for 12 hours
    set_transient($cache_key, $grades, 12 * HOUR_IN_SECONDS);
    
    return $grades;
}

/**
 * Format grade label for display
 * Converts values like 'kg1' to 'KG 1', 'class1' to 'CLASS 1'
 */
function format_grade_label($grade_value) {
    if (empty($grade_value)) {
        return 'N/A';
    }
    
    // Convert to uppercase and add space
    if (preg_match('/^(kg|class|grade)(\d+)$/i', $grade_value, $matches)) {
        $type = strtoupper($matches[1]);
        $num = $matches[2];
        return $type . ' ' . $num;
    }
    
    // Return as-is with first letters uppercase
    return ucwords(str_replace('_', ' ', $grade_value));
}

function student_display_styles() {
    ?>
    <style>
    .student-display-module {
        width: 100%;
    }
    
    .display-filters {
        background: white;
        padding: 20px;
        border-radius: 12px;
        margin-bottom: 24px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }
    
    .filter-form {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
        align-items: center;
    }
    
    .search-box {
        flex: 1;
        min-width: 250px;
        position: relative;
    }
    
    .search-box i {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #999;
    }
    
    .search-box input {
        width: 100%;
        padding: 12px 12px 12px 40px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        font-size: 15px;
    }
    
    .search-box input:focus {
        outline: none;
        border-color: #1e88e5;
    }
    
    .filter-select {
        padding: 12px 16px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        font-size: 15px;
        cursor: pointer;
        min-width: 150px;
    }
    
    .btn-filter {
        padding: 12px 24px;
        background: #1e88e5;
        color: white;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        white-space: nowrap;
    }
    
    .btn-filter:hover {
        background: #1565c0;
    }
    
    .btn-clear {
        padding: 12px 16px;
        background: #f5f5f5;
        color: #666;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s;
        white-space: nowrap;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    
    .btn-clear:hover {
        background: #e0e0e0;
        color: #333;
    }
    
    .student-cards-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .student-card {
        background: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        transition: transform 0.3s;
    }
    
    .student-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.12);
    }
    
    .card-photo {
        background: linear-gradient(135deg, #e3f2fd, #bbdefb);
        padding: 24px;
        text-align: center;
        position: relative;
    }
    
    .card-photo img {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        object-fit: cover;
        border: 4px solid white;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    
    .photo-placeholder {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        background: linear-gradient(135deg, #1e88e5, #0d47a1);
        color: white;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 40px;
        font-weight: 700;
        border: 4px solid white;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    
    .card-badge {
        position: absolute;
        top: 12px;
        right: 12px;
        background: #1e88e5;
        color: white;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }
    
    .card-content {
        padding: 20px;
    }
    
    .card-content h4 {
        margin: 0 0 12px 0;
        font-size: 18px;
        color: #333;
    }
    
    .card-details {
        display: flex;
        flex-direction: column;
        gap: 8px;
        font-size: 14px;
        color: #666;
    }
    
    .card-details i {
        color: #1e88e5;
        width: 18px;
    }
    
    .card-actions {
        padding: 16px 20px;
        background: #f8f9fa;
        display: flex;
        gap: 10px;
    }
    
    .btn-card {
        flex: 1;
        padding: 10px;
        border-radius: 6px;
        text-align: center;
        text-decoration: none;
        font-weight: 600;
        font-size: 14px;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
    }
    
    .btn-view {
        background: #1e88e5;
        color: white;
    }
    
    .btn-view:hover {
        background: #1565c0;
    }
    
    .btn-edit {
        background: #fb8c00;
        color: white;
    }
    
    .btn-edit:hover {
        background: #f57c00;
    }
    
    .no-students {
        background: white;
        border-radius: 12px;
        padding: 60px 40px;
        text-align: center;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }
    
    .no-students i {
        font-size: 64px;
        color: #e0e0e0;
        margin-bottom: 16px;
    }
    
    .no-students h3 {
        margin: 0 0 8px 0;
        color: #333;
    }
    
    .no-students p {
        margin: 0 0 20px 0;
        color: #666;
    }
    
    /* Pagination Styles */
    .student-pagination {
        background: white;
        padding: 20px;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        margin-top: 20px;
    }
    
    .pagination-info {
        text-align: center;
        color: #666;
        margin-bottom: 20px;
        font-size: 14px;
    }
    
    .pagination-links {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }
    
    .pagination-link {
        padding: 10px 20px;
        background: #1e88e5;
        color: white;
        text-decoration: none;
        border-radius: 6px;
        font-weight: 600;
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    
    .pagination-link:hover {
        background: #1565c0;
    }
    
    .pagination-link.prev {
        background: #f5f5f5;
        color: #666;
        border: 1px solid #e0e0e0;
    }
    
    .pagination-link.prev:hover {
        background: #e0e0e0;
    }
    
    .pagination-link.next {
        background: #1e88e5;
        color: white;
    }
    
    .page-numbers {
        display: flex;
        gap: 5px;
    }
    
    .page-number, .current-page {
        padding: 10px 15px;
        border-radius: 6px;
        font-weight: 600;
        text-decoration: none;
        min-width: 40px;
        text-align: center;
    }
    
    .page-number {
        background: #f5f5f5;
        color: #666;
        border: 1px solid #e0e0e0;
    }
    
    .page-number:hover {
        background: #e0e0e0;
        color: #333;
    }
    
    .current-page {
        background: #1e88e5;
        color: white;
    }
    
    @media (max-width: 768px) {
        .filter-form {
            flex-direction: column;
            align-items: stretch;
        }
        
        .student-cards-grid {
            grid-template-columns: 1fr;
        }
        
        .pagination-links {
            flex-direction: column;
        }
        
        .page-numbers {
            order: -1;
            margin-bottom: 10px;
        }
    }
    </style>
    <?php
}

/**
 * Clear grade cache when students are added/updated/deleted
 */
add_action('save_post_student', 'clear_student_grades_cache');
add_action('delete_post', 'clear_student_grades_cache');

function clear_student_grades_cache($post_id) {
    if (get_post_type($post_id) === 'student') {
        delete_transient('alhuffaz_student_grades');
    }
}
?>