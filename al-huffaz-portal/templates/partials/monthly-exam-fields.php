<?php
/**
 * Monthly Exam Fields Partial Template
 * Used for monthly exams repeater within subjects
 */

defined('ABSPATH') || exit;
?>

<div class="ahp-monthly-exam" data-month="MONTH_INDEX">
    <div class="ahp-monthly-header">
        <input type="text" name="subjects[SUBJECT_INDEX][monthly_exams][MONTH_INDEX][month_name]"
               class="ahp-input ahp-month-name" placeholder="Month (e.g., January, February)">
        <button type="button" class="ahp-btn ahp-btn-danger ahp-btn-xs ahp-remove-monthly">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <div class="ahp-marks-row">
        <div class="ahp-marks-group">
            <label>Oral Total</label>
            <input type="number" name="subjects[SUBJECT_INDEX][monthly_exams][MONTH_INDEX][oral_total]"
                   class="ahp-input marks-input oral-total" placeholder="0" min="0">
        </div>
        <div class="ahp-marks-group">
            <label>Oral Obtained</label>
            <input type="number" name="subjects[SUBJECT_INDEX][monthly_exams][MONTH_INDEX][oral_obtained]"
                   class="ahp-input marks-input oral-obtained" placeholder="0" min="0">
        </div>
        <div class="ahp-marks-group">
            <label>Written Total</label>
            <input type="number" name="subjects[SUBJECT_INDEX][monthly_exams][MONTH_INDEX][written_total]"
                   class="ahp-input marks-input written-total" placeholder="0" min="0">
        </div>
        <div class="ahp-marks-group">
            <label>Written Obtained</label>
            <input type="number" name="subjects[SUBJECT_INDEX][monthly_exams][MONTH_INDEX][written_obtained]"
                   class="ahp-input marks-input written-obtained" placeholder="0" min="0">
        </div>
    </div>
    <div class="ahp-marks-result" style="display:none;"></div>
</div>
