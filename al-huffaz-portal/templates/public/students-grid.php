<?php
use AlHuffaz\Frontend\Student_Display;
use AlHuffaz\Core\Helpers;
use AlHuffaz\Admin\Settings;
if (!defined('ABSPATH')) exit;

$result = Student_Display::get_students(array('per_page' => $atts['limit'], 'sponsored' => 'available'));
$grades = Settings::get('grade_levels', Settings::get_default_grades());
$categories = Settings::get('islamic_categories', Settings::get_default_categories());
?>
<div class="alhuffaz-container">
    <div class="alhuffaz-section-header">
        <h2 class="alhuffaz-section-title"><?php _e('Sponsor a Student', 'al-huffaz-portal'); ?></h2>
        <p class="alhuffaz-section-subtitle"><?php _e('Choose a student to support their education and make a difference in their life.', 'al-huffaz-portal'); ?></p>
    </div>

    <?php if ($atts['show_filters'] === 'yes'): ?>
    <div class="alhuffaz-filters-bar">
        <div class="alhuffaz-search-box">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
            <input type="text" placeholder="<?php _e('Search students...', 'al-huffaz-portal'); ?>">
        </div>
        <select name="grade" class="alhuffaz-filter-select">
            <option value=""><?php _e('All Grades', 'al-huffaz-portal'); ?></option>
            <?php foreach ($grades as $key => $label): ?><option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option><?php endforeach; ?>
        </select>
        <select name="category" class="alhuffaz-filter-select">
            <option value=""><?php _e('All Categories', 'al-huffaz-portal'); ?></option>
            <?php foreach ($categories as $key => $label): ?><option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option><?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>

    <div class="alhuffaz-students-grid">
        <?php if (empty($result['students'])): ?>
            <div class="alhuffaz-empty"><h3><?php _e('No students available for sponsorship at this time.', 'al-huffaz-portal'); ?></h3></div>
        <?php else: ?>
            <?php foreach ($result['students'] as $student): ?>
            <div class="alhuffaz-student-card">
                <div class="alhuffaz-student-card-image">
                    <img src="<?php echo esc_url($student['photo']); ?>" alt="<?php echo esc_attr($student['name']); ?>">
                    <span class="alhuffaz-student-card-badge <?php echo $student['is_sponsored'] ? 'sponsored' : 'available'; ?>"><?php echo $student['is_sponsored'] ? __('Sponsored', 'al-huffaz-portal') : __('Available', 'al-huffaz-portal'); ?></span>
                </div>
                <div class="alhuffaz-student-card-body">
                    <h3 class="alhuffaz-student-card-name"><?php echo esc_html($student['name']); ?></h3>
                    <div class="alhuffaz-student-card-meta">
                        <span><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg> <?php echo esc_html($student['grade']); ?></span>
                        <span><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" /></svg> <?php echo esc_html($student['category']); ?></span>
                    </div>
                    <p class="alhuffaz-student-card-description"><?php echo esc_html($student['description']); ?></p>
                    <div class="alhuffaz-student-card-footer">
                        <div class="alhuffaz-student-card-amount"><?php echo esc_html($student['monthly_fee']); ?><span>/month</span></div>
                        <?php if (!$student['is_sponsored']): ?><a href="?sponsor=<?php echo $student['id']; ?>" class="alhuffaz-btn alhuffaz-btn-primary"><?php _e('Sponsor', 'al-huffaz-portal'); ?></a><?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="alhuffaz-pagination"></div>
</div>
