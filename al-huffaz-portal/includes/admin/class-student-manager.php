<?php
/**
 * Student Manager
 *
 * @package AlHuffaz
 */

namespace AlHuffaz\Admin;

use AlHuffaz\Core\Helpers;
use AlHuffaz\Core\Post_Types;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Student_Manager
 */
class Student_Manager {

    /**
     * Constructor
     */
    public function __construct() {
        // Additional hooks if needed
    }

    /**
     * Get student by ID
     */
    public static function get_student($student_id) {
        $student = get_post($student_id);

        if (!$student || $student->post_type !== 'alhuffaz_student') {
            return null;
        }

        $data = array(
            'id'           => $student->ID,
            'student_name' => $student->post_title,
            'created_at'   => $student->post_date,
            'updated_at'   => $student->post_modified,
        );

        // Get all meta
        $fields = Post_Types::get_student_fields();

        foreach ($fields as $key => $field) {
            $data[$key] = get_post_meta($student_id, '_' . $key, true);
        }

        return $data;
    }

    /**
     * Get students list
     */
    public static function get_students($args = array()) {
        $defaults = array(
            'page'      => 1,
            'per_page'  => 20,
            'search'    => '',
            'grade'     => '',
            'gender'    => '',
            'sponsored' => '',
            'orderby'   => 'date',
            'order'     => 'DESC',
        );

        $args = wp_parse_args($args, $defaults);

        $query_args = array(
            'post_type'      => 'alhuffaz_student',
            'posts_per_page' => $args['per_page'],
            'paged'          => $args['page'],
            'post_status'    => 'publish',
            'orderby'        => $args['orderby'],
            'order'          => $args['order'],
        );

        if (!empty($args['search'])) {
            $query_args['s'] = $args['search'];
        }

        $meta_query = array();

        if (!empty($args['grade'])) {
            $meta_query[] = array(
                'key'   => '_grade_level',
                'value' => $args['grade'],
            );
        }

        if (!empty($args['gender'])) {
            $meta_query[] = array(
                'key'   => '_gender',
                'value' => $args['gender'],
            );
        }

        if ($args['sponsored'] === 'yes' || $args['sponsored'] === 'no') {
            $meta_query[] = array(
                'key'   => '_is_sponsored',
                'value' => $args['sponsored'],
            );
        }

        if (!empty($meta_query)) {
            $query_args['meta_query'] = $meta_query;
        }

        $query = new \WP_Query($query_args);

        $students = array();

        foreach ($query->posts as $post) {
            $students[] = array(
                'id'               => $post->ID,
                'name'             => $post->post_title,
                'gr_number'        => get_post_meta($post->ID, '_gr_number', true),
                'roll_number'      => get_post_meta($post->ID, '_roll_number', true),
                'grade_level'      => get_post_meta($post->ID, '_grade_level', true),
                'grade_label'      => Helpers::get_grade_label(get_post_meta($post->ID, '_grade_level', true)),
                'islamic_category' => Helpers::get_islamic_category_label(get_post_meta($post->ID, '_islamic_category', true)),
                'gender'           => get_post_meta($post->ID, '_gender', true),
                'father_name'      => get_post_meta($post->ID, '_father_name', true),
                'photo'            => Helpers::get_student_photo($post->ID),
                'is_sponsored'     => get_post_meta($post->ID, '_is_sponsored', true) === 'yes',
                'monthly_fee'      => Helpers::format_currency(get_post_meta($post->ID, '_monthly_fee', true)),
                'created_at'       => Helpers::format_date($post->post_date),
            );
        }

        return array(
            'students'    => $students,
            'total'       => $query->found_posts,
            'total_pages' => $query->max_num_pages,
            'page'        => $args['page'],
        );
    }

    /**
     * Create student
     */
    public static function create_student($data) {
        if (empty($data['student_name'])) {
            return new \WP_Error('missing_name', __('Student name is required.', 'al-huffaz-portal'));
        }

        $student_id = wp_insert_post(array(
            'post_type'   => 'alhuffaz_student',
            'post_title'  => sanitize_text_field($data['student_name']),
            'post_status' => 'publish',
        ));

        if (is_wp_error($student_id)) {
            return $student_id;
        }

        // Save meta
        self::save_student_meta($student_id, $data);

        // Log activity
        Helpers::log_activity('create_student', 'student', $student_id, 'Created student: ' . $data['student_name']);

        return $student_id;
    }

    /**
     * Update student
     */
    public static function update_student($student_id, $data) {
        $student = get_post($student_id);

        if (!$student || $student->post_type !== 'alhuffaz_student') {
            return new \WP_Error('not_found', __('Student not found.', 'al-huffaz-portal'));
        }

        if (!empty($data['student_name'])) {
            wp_update_post(array(
                'ID'         => $student_id,
                'post_title' => sanitize_text_field($data['student_name']),
            ));
        }

        // Save meta
        self::save_student_meta($student_id, $data);

        // Log activity
        Helpers::log_activity('update_student', 'student', $student_id, 'Updated student: ' . $student->post_title);

        return $student_id;
    }

    /**
     * Save student meta
     */
    private static function save_student_meta($student_id, $data) {
        $fields = Post_Types::get_student_fields();

        foreach ($fields as $key => $field) {
            if (!isset($data[$key])) {
                continue;
            }

            $value = $data[$key];

            // Sanitize based on type
            switch ($field['type']) {
                case 'integer':
                    $value = intval($value);
                    break;
                case 'number':
                    $value = floatval($value);
                    break;
                case 'boolean':
                    $value = ($value === 'yes' || $value === true || $value === '1') ? 'yes' : 'no';
                    break;
                case 'array':
                    $value = is_array($value) ? $value : array();
                    break;
                case 'date':
                    $value = sanitize_text_field($value);
                    break;
                default:
                    $value = sanitize_text_field($value);
            }

            update_post_meta($student_id, '_' . $key, $value);
        }
    }

    /**
     * Delete student
     */
    public static function delete_student($student_id) {
        $student = get_post($student_id);

        if (!$student || $student->post_type !== 'alhuffaz_student') {
            return new \WP_Error('not_found', __('Student not found.', 'al-huffaz-portal'));
        }

        $name = $student->post_title;

        $result = wp_delete_post($student_id, true);

        if (!$result) {
            return new \WP_Error('delete_failed', __('Failed to delete student.', 'al-huffaz-portal'));
        }

        // Log activity
        Helpers::log_activity('delete_student', 'student', $student_id, 'Deleted student: ' . $name);

        return true;
    }

    /**
     * Get academic data for student
     */
    public static function get_academic_data($student_id) {
        $subjects = get_post_meta($student_id, '_subjects', true);

        if (!is_array($subjects)) {
            $subjects = array();
        }

        $overall_percentage = get_post_meta($student_id, '_overall_percentage', true);

        return array(
            'subjects'           => $subjects,
            'overall_percentage' => floatval($overall_percentage),
            'overall_grade'      => Helpers::get_grade_from_percentage($overall_percentage),
        );
    }

    /**
     * Save academic data
     */
    public static function save_academic_data($student_id, $subjects) {
        if (!is_array($subjects)) {
            return false;
        }

        // Calculate overall percentage
        $total_percentage = 0;
        $count = 0;

        foreach ($subjects as $subject) {
            if (!empty($subject['monthly_exams'])) {
                foreach ($subject['monthly_exams'] as $exam) {
                    if (!empty($exam['percentage'])) {
                        $total_percentage += floatval($exam['percentage']);
                        $count++;
                    }
                }
            }
        }

        $overall = $count > 0 ? $total_percentage / $count : 0;

        update_post_meta($student_id, '_subjects', $subjects);
        update_post_meta($student_id, '_overall_percentage', round($overall, 2));

        return true;
    }

    /**
     * Get attendance data
     */
    public static function get_attendance($student_id) {
        return array(
            'total_days'   => intval(get_post_meta($student_id, '_total_school_days', true)),
            'present_days' => intval(get_post_meta($student_id, '_present_days', true)),
            'percentage'   => self::calculate_attendance_percentage($student_id),
        );
    }

    /**
     * Calculate attendance percentage
     */
    public static function calculate_attendance_percentage($student_id) {
        $total = intval(get_post_meta($student_id, '_total_school_days', true));
        $present = intval(get_post_meta($student_id, '_present_days', true));

        if ($total <= 0) {
            return 0;
        }

        return round(($present / $total) * 100, 2);
    }

    /**
     * Export students to CSV
     */
    public static function export_csv($args = array()) {
        $result = self::get_students(array_merge($args, array('per_page' => -1)));

        $students = $result['students'];

        $csv = array();

        // Headers
        $csv[] = array(
            'ID',
            'Name',
            'GR Number',
            'Roll Number',
            'Grade',
            'Gender',
            'Father Name',
            'Sponsored',
            'Monthly Fee',
            'Created',
        );

        foreach ($students as $student) {
            $csv[] = array(
                $student['id'],
                $student['name'],
                $student['gr_number'],
                $student['roll_number'],
                $student['grade_label'],
                $student['gender'],
                $student['father_name'],
                $student['is_sponsored'] ? 'Yes' : 'No',
                $student['monthly_fee'],
                $student['created_at'],
            );
        }

        return $csv;
    }
}
