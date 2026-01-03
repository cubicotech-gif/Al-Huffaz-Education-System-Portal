<?php
/**
 * Subject Fields Partial Template
 * Used for subjects repeater in student form
 */

defined('ABSPATH') || exit;

// Variables: $index, $subject (array with name, monthly_exams, mid_semester, final_semester, etc.)
$index = $index ?? 'SUBJECT_INDEX';
$subject = $subject ?? array();
$monthly_exams = $subject['monthly_exams'] ?? array();
$mid_semester = $subject['mid_semester'] ?? array();
$final_semester = $subject['final_semester'] ?? array();
?>

<div class="ahp-subject-box" data-index="<?php echo esc_attr($index); ?>">
    <div class="ahp-subject-header">
        <div class="ahp-subject-title">
            <i class="fas fa-book"></i>
            <input type="text" name="subjects[<?php echo esc_attr($index); ?>][name]"
                   class="ahp-input ahp-subject-name" placeholder="Subject Name (e.g., Mathematics, Urdu, Quran)"
                   value="<?php echo esc_attr($subject['name'] ?? ''); ?>">
        </div>
        <button type="button" class="ahp-btn ahp-btn-danger ahp-btn-sm ahp-remove-subject">
            <i class="fas fa-trash"></i>
        </button>
    </div>

    <div class="ahp-subject-content">
        <!-- Monthly Exams Repeater -->
        <div class="ahp-exam-section">
            <div class="ahp-exam-header">
                <h4><i class="fas fa-calendar-alt"></i> Monthly Exams</h4>
                <button type="button" class="ahp-btn ahp-btn-primary ahp-btn-sm ahp-add-monthly" data-subject-index="<?php echo esc_attr($index); ?>">
                    <i class="fas fa-plus"></i> Add Month
                </button>
            </div>
            <div class="ahp-monthly-container" data-subject="<?php echo esc_attr($index); ?>">
                <?php if (!empty($monthly_exams)): ?>
                    <?php foreach ($monthly_exams as $month_index => $monthly): ?>
                        <div class="ahp-monthly-exam" data-month="<?php echo esc_attr($month_index); ?>">
                            <div class="ahp-monthly-header">
                                <input type="text" name="subjects[<?php echo esc_attr($index); ?>][monthly_exams][<?php echo esc_attr($month_index); ?>][month_name]"
                                       class="ahp-input ahp-month-name" placeholder="Month (e.g., January, February)"
                                       value="<?php echo esc_attr($monthly['month_name'] ?? ''); ?>">
                                <button type="button" class="ahp-btn ahp-btn-danger ahp-btn-xs ahp-remove-monthly">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <div class="ahp-marks-row">
                                <div class="ahp-marks-group">
                                    <label>Oral Total</label>
                                    <input type="number" name="subjects[<?php echo esc_attr($index); ?>][monthly_exams][<?php echo esc_attr($month_index); ?>][oral_total]"
                                           class="ahp-input marks-input oral-total" placeholder="0" min="0"
                                           value="<?php echo esc_attr($monthly['oral_total'] ?? ''); ?>">
                                </div>
                                <div class="ahp-marks-group">
                                    <label>Oral Obtained</label>
                                    <input type="number" name="subjects[<?php echo esc_attr($index); ?>][monthly_exams][<?php echo esc_attr($month_index); ?>][oral_obtained]"
                                           class="ahp-input marks-input oral-obtained" placeholder="0" min="0"
                                           value="<?php echo esc_attr($monthly['oral_obtained'] ?? ''); ?>">
                                </div>
                                <div class="ahp-marks-group">
                                    <label>Written Total</label>
                                    <input type="number" name="subjects[<?php echo esc_attr($index); ?>][monthly_exams][<?php echo esc_attr($month_index); ?>][written_total]"
                                           class="ahp-input marks-input written-total" placeholder="0" min="0"
                                           value="<?php echo esc_attr($monthly['written_total'] ?? ''); ?>">
                                </div>
                                <div class="ahp-marks-group">
                                    <label>Written Obtained</label>
                                    <input type="number" name="subjects[<?php echo esc_attr($index); ?>][monthly_exams][<?php echo esc_attr($month_index); ?>][written_obtained]"
                                           class="ahp-input marks-input written-obtained" placeholder="0" min="0"
                                           value="<?php echo esc_attr($monthly['written_obtained'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="ahp-marks-result" <?php if (empty($monthly['percentage'])) echo 'style="display:none;"'; ?>>
                                <?php if (!empty($monthly['percentage'])):
                                    $pct = $monthly['percentage'];
                                    $statusClass = $pct >= 80 ? 'excellent' : ($pct >= 60 ? 'good' : 'poor');
                                ?>
                                <div class="ahp-result-box ahp-result-<?php echo $statusClass; ?>">
                                    <span><strong><?php echo $monthly['overall_obtained']; ?></strong>/<?php echo $monthly['overall_total']; ?></span>
                                    <span><strong><?php echo $pct; ?>%</strong></span>
                                    <span class="ahp-grade-badge"><?php echo $monthly['grade']; ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Mid Semester -->
        <div class="ahp-exam-section">
            <h4><i class="fas fa-book-open"></i> Mid Semester Exam</h4>
            <div class="ahp-marks-row">
                <div class="ahp-marks-group">
                    <label>Oral Total</label>
                    <input type="number" name="subjects[<?php echo esc_attr($index); ?>][mid_semester][oral_total]"
                           class="ahp-input marks-input oral-total" placeholder="0" min="0"
                           value="<?php echo esc_attr($mid_semester['oral_total'] ?? ''); ?>">
                </div>
                <div class="ahp-marks-group">
                    <label>Oral Obtained</label>
                    <input type="number" name="subjects[<?php echo esc_attr($index); ?>][mid_semester][oral_obtained]"
                           class="ahp-input marks-input oral-obtained" placeholder="0" min="0"
                           value="<?php echo esc_attr($mid_semester['oral_obtained'] ?? ''); ?>">
                </div>
                <div class="ahp-marks-group">
                    <label>Written Total</label>
                    <input type="number" name="subjects[<?php echo esc_attr($index); ?>][mid_semester][written_total]"
                           class="ahp-input marks-input written-total" placeholder="0" min="0"
                           value="<?php echo esc_attr($mid_semester['written_total'] ?? ''); ?>">
                </div>
                <div class="ahp-marks-group">
                    <label>Written Obtained</label>
                    <input type="number" name="subjects[<?php echo esc_attr($index); ?>][mid_semester][written_obtained]"
                           class="ahp-input marks-input written-obtained" placeholder="0" min="0"
                           value="<?php echo esc_attr($mid_semester['written_obtained'] ?? ''); ?>">
                </div>
            </div>
            <div class="ahp-marks-result" <?php if (empty($mid_semester['percentage'])) echo 'style="display:none;"'; ?>>
                <?php if (!empty($mid_semester['percentage'])):
                    $pct = $mid_semester['percentage'];
                    $statusClass = $pct >= 80 ? 'excellent' : ($pct >= 60 ? 'good' : 'poor');
                ?>
                <div class="ahp-result-box ahp-result-<?php echo $statusClass; ?>">
                    <span><strong><?php echo $mid_semester['overall_obtained']; ?></strong>/<?php echo $mid_semester['overall_total']; ?></span>
                    <span><strong><?php echo $pct; ?>%</strong></span>
                    <span class="ahp-grade-badge"><?php echo $mid_semester['grade']; ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Final Semester -->
        <div class="ahp-exam-section">
            <h4><i class="fas fa-graduation-cap"></i> Final Semester Exam</h4>
            <div class="ahp-marks-row">
                <div class="ahp-marks-group">
                    <label>Oral Total</label>
                    <input type="number" name="subjects[<?php echo esc_attr($index); ?>][final_semester][oral_total]"
                           class="ahp-input marks-input oral-total" placeholder="0" min="0"
                           value="<?php echo esc_attr($final_semester['oral_total'] ?? ''); ?>">
                </div>
                <div class="ahp-marks-group">
                    <label>Oral Obtained</label>
                    <input type="number" name="subjects[<?php echo esc_attr($index); ?>][final_semester][oral_obtained]"
                           class="ahp-input marks-input oral-obtained" placeholder="0" min="0"
                           value="<?php echo esc_attr($final_semester['oral_obtained'] ?? ''); ?>">
                </div>
                <div class="ahp-marks-group">
                    <label>Written Total</label>
                    <input type="number" name="subjects[<?php echo esc_attr($index); ?>][final_semester][written_total]"
                           class="ahp-input marks-input written-total" placeholder="0" min="0"
                           value="<?php echo esc_attr($final_semester['written_total'] ?? ''); ?>">
                </div>
                <div class="ahp-marks-group">
                    <label>Written Obtained</label>
                    <input type="number" name="subjects[<?php echo esc_attr($index); ?>][final_semester][written_obtained]"
                           class="ahp-input marks-input written-obtained" placeholder="0" min="0"
                           value="<?php echo esc_attr($final_semester['written_obtained'] ?? ''); ?>">
                </div>
            </div>
            <div class="ahp-marks-result" <?php if (empty($final_semester['percentage'])) echo 'style="display:none;"'; ?>>
                <?php if (!empty($final_semester['percentage'])):
                    $pct = $final_semester['percentage'];
                    $statusClass = $pct >= 80 ? 'excellent' : ($pct >= 60 ? 'good' : 'poor');
                ?>
                <div class="ahp-result-box ahp-result-<?php echo $statusClass; ?>">
                    <span><strong><?php echo $final_semester['overall_obtained']; ?></strong>/<?php echo $final_semester['overall_total']; ?></span>
                    <span><strong><?php echo $pct; ?>%</strong></span>
                    <span class="ahp-grade-badge"><?php echo $final_semester['grade']; ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Teacher Assessment -->
        <div class="ahp-teacher-assessment">
            <h4><i class="fas fa-comment-dots"></i> Teacher Assessment</h4>
            <div class="ahp-assessment-grid">
                <div class="ahp-form-group">
                    <label>Strengths</label>
                    <textarea name="subjects[<?php echo esc_attr($index); ?>][strengths]" class="ahp-input" rows="2"
                              placeholder="Student's strengths in this subject..."><?php echo esc_textarea($subject['strengths'] ?? ''); ?></textarea>
                </div>
                <div class="ahp-form-group">
                    <label>Areas for Improvement</label>
                    <textarea name="subjects[<?php echo esc_attr($index); ?>][areas_for_improvement]" class="ahp-input" rows="2"
                              placeholder="Areas that need improvement..."><?php echo esc_textarea($subject['areas_for_improvement'] ?? ''); ?></textarea>
                </div>
                <div class="ahp-form-group ahp-col-full">
                    <label>Teacher Comments</label>
                    <textarea name="subjects[<?php echo esc_attr($index); ?>][teacher_comments]" class="ahp-input" rows="2"
                              placeholder="Additional comments..."><?php echo esc_textarea($subject['teacher_comments'] ?? ''); ?></textarea>
                </div>
            </div>
        </div>
    </div>
</div>
