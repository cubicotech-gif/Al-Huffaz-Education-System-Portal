<?php
/**
 * Students Grid Template
 * Al-Huffaz Education System Portal
 *
 * Modern Card-Based Student Display
 * Fixed alignment and no overflow
 */

use AlHuffaz\Frontend\Student_Display;
use AlHuffaz\Core\Helpers;
use AlHuffaz\Admin\Settings;
if (!defined('ABSPATH')) exit;

$result = Student_Display::get_students(array('per_page' => $atts['limit'], 'sponsored' => 'available'));
$grades = Settings::get('grade_levels', Settings::get_default_grades());
$categories = Settings::get('islamic_categories', Settings::get_default_categories());
?>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<div class="alhuffaz-container">
    <div class="alhuffaz-section-header">
        <h2 class="alhuffaz-section-title"><?php _e('Sponsor a Student', 'al-huffaz-portal'); ?></h2>
        <p class="alhuffaz-section-subtitle"><?php _e('Choose a student to support their education and make a difference in their life.', 'al-huffaz-portal'); ?></p>
    </div>

    <?php if ($atts['show_filters'] === 'yes'): ?>
    <div class="alhuffaz-filters-bar">
        <div class="alhuffaz-search-box">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
            <input type="text" id="studentSearch" placeholder="<?php _e('Search students...', 'al-huffaz-portal'); ?>">
        </div>
        <select name="grade" id="gradeFilter" class="alhuffaz-filter-select">
            <option value=""><?php _e('All Grades', 'al-huffaz-portal'); ?></option>
            <?php foreach ($grades as $key => $label): ?><option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option><?php endforeach; ?>
        </select>
        <select name="category" id="categoryFilter" class="alhuffaz-filter-select">
            <option value=""><?php _e('All Categories', 'al-huffaz-portal'); ?></option>
            <?php foreach ($categories as $key => $label): ?><option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option><?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>

    <div class="alhuffaz-students-grid" id="studentsGrid">
        <?php if (empty($result['students'])): ?>
            <div class="alhuffaz-empty" style="grid-column: 1 / -1;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width:64px;height:64px;opacity:0.4;margin-bottom:16px;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
                <h3><?php _e('No students available for sponsorship at this time.', 'al-huffaz-portal'); ?></h3>
                <p style="color:#6b7280;margin-top:8px;"><?php _e('Please check back later for new students.', 'al-huffaz-portal'); ?></p>
            </div>
        <?php else: ?>
            <?php foreach ($result['students'] as $student):
                $description = !empty($student['description']) ? $student['description'] : __('This student is seeking sponsorship for their Islamic education journey.', 'al-huffaz-portal');
            ?>
            <div class="alhuffaz-student-card" data-name="<?php echo esc_attr(strtolower($student['name'])); ?>" data-grade="<?php echo esc_attr(strtolower($student['grade_key'] ?? '')); ?>" data-category="<?php echo esc_attr(strtolower($student['category_key'] ?? '')); ?>">
                <div class="alhuffaz-student-card-image">
                    <?php if (!empty($student['photo'])): ?>
                        <img src="<?php echo esc_url($student['photo']); ?>" alt="<?php echo esc_attr($student['name']); ?>" loading="lazy">
                    <?php else: ?>
                        <div style="position:absolute;top:0;left:0;width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#10b981,#059669);color:white;font-size:48px;font-weight:700;">
                            <?php echo strtoupper(substr($student['name'], 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                    <span class="alhuffaz-student-card-badge <?php echo $student['is_sponsored'] ? 'sponsored' : 'available'; ?>">
                        <?php echo $student['is_sponsored'] ? __('Sponsored', 'al-huffaz-portal') : __('Available', 'al-huffaz-portal'); ?>
                    </span>
                </div>
                <div class="alhuffaz-student-card-body">
                    <h3 class="alhuffaz-student-card-name" title="<?php echo esc_attr($student['name']); ?>"><?php echo esc_html($student['name']); ?></h3>
                    <div class="alhuffaz-student-card-meta">
                        <?php if (!empty($student['grade'])): ?>
                        <span>
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
                            <?php echo esc_html($student['grade']); ?>
                        </span>
                        <?php endif; ?>
                        <?php if (!empty($student['category'])): ?>
                        <span>
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" /></svg>
                            <?php echo esc_html($student['category']); ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    <p class="alhuffaz-student-card-description"><?php echo esc_html($description); ?></p>
                    <div class="alhuffaz-student-card-footer">
                        <div class="alhuffaz-student-card-amount">
                            <?php echo esc_html($student['monthly_fee']); ?><span>/month</span>
                        </div>
                        <?php if (!$student['is_sponsored']): ?>
                            <a href="<?php echo esc_url(add_query_arg('sponsor', $student['id'], get_permalink())); ?>" class="alhuffaz-btn alhuffaz-btn-primary">
                                <?php _e('Sponsor', 'al-huffaz-portal'); ?>
                            </a>
                        <?php else: ?>
                            <span class="alhuffaz-badge badge-info"><?php _e('Fully Sponsored', 'al-huffaz-portal'); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="alhuffaz-pagination" id="pagination"></div>
</div>

<?php if ($atts['show_filters'] === 'yes'): ?>
<script>
(function() {
    'use strict';

    const grid = document.getElementById('studentsGrid');
    const search = document.getElementById('studentSearch');
    const gradeFilter = document.getElementById('gradeFilter');
    const categoryFilter = document.getElementById('categoryFilter');
    const cards = grid.querySelectorAll('.alhuffaz-student-card');

    function filterCards() {
        const searchTerm = search.value.toLowerCase().trim();
        const grade = gradeFilter.value.toLowerCase();
        const category = categoryFilter.value.toLowerCase();

        let visibleCount = 0;

        cards.forEach(card => {
            const name = card.dataset.name || '';
            const cardGrade = card.dataset.grade || '';
            const cardCategory = card.dataset.category || '';

            const matchesSearch = !searchTerm || name.includes(searchTerm);
            const matchesGrade = !grade || cardGrade === grade;
            const matchesCategory = !category || cardCategory === category;

            if (matchesSearch && matchesGrade && matchesCategory) {
                card.style.display = '';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });

        // Show empty message if no results
        let emptyMsg = grid.querySelector('.alhuffaz-empty-filter');
        if (visibleCount === 0 && cards.length > 0) {
            if (!emptyMsg) {
                emptyMsg = document.createElement('div');
                emptyMsg.className = 'alhuffaz-empty alhuffaz-empty-filter';
                emptyMsg.style.gridColumn = '1 / -1';
                emptyMsg.innerHTML = '<h3><?php _e('No students match your filters.', 'al-huffaz-portal'); ?></h3><p style="color:#6b7280;margin-top:8px;"><?php _e('Try adjusting your search criteria.', 'al-huffaz-portal'); ?></p>';
                grid.appendChild(emptyMsg);
            }
            emptyMsg.style.display = '';
        } else if (emptyMsg) {
            emptyMsg.style.display = 'none';
        }
    }

    if (search) search.addEventListener('input', filterCards);
    if (gradeFilter) gradeFilter.addEventListener('change', filterCards);
    if (categoryFilter) categoryFilter.addEventListener('change', filterCards);
})();
</script>
<?php endif; ?>
