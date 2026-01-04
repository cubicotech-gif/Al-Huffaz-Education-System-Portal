<?php
/**
 * Student Display
 *
 * @package AlHuffaz
 */

namespace AlHuffaz\Frontend;

use AlHuffaz\Core\Helpers;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Student_Display
 */
class Student_Display {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_ajax_alhuffaz_filter_students', array($this, 'filter_students'));
        add_action('wp_ajax_nopriv_alhuffaz_filter_students', array($this, 'filter_students'));
    }

    /**
     * Filter students via AJAX
     */
    public function filter_students() {
        check_ajax_referer('alhuffaz_public_nonce', 'nonce');

        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 12;
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        $grade = isset($_POST['grade']) ? sanitize_text_field($_POST['grade']) : '';
        $category = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '';
        $gender = isset($_POST['gender']) ? sanitize_text_field($_POST['gender']) : '';
        $sponsored = isset($_POST['sponsored']) ? sanitize_text_field($_POST['sponsored']) : '';

        $result = self::get_students(array(
            'page'      => $page,
            'per_page'  => $per_page,
            'search'    => $search,
            'grade'     => $grade,
            'category'  => $category,
            'gender'    => $gender,
            'sponsored' => $sponsored,
        ));

        wp_send_json_success($result);
    }

    /**
     * Get students for display
     */
    public static function get_students($args = array()) {
        $defaults = array(
            'page'      => 1,
            'per_page'  => 12,
            'search'    => '',
            'grade'     => '',
            'category'  => '',
            'gender'    => '',
            'sponsored' => '',
        );

        $args = wp_parse_args($args, $defaults);

        $query_args = array(
            'post_type'      => 'student',
            'posts_per_page' => $args['per_page'],
            'paged'          => $args['page'],
            'post_status'    => 'publish',
        );

        if (!empty($args['search'])) {
            $query_args['s'] = $args['search'];
        }

        $meta_query = array();

        if (!empty($args['grade'])) {
            $meta_query[] = array(
                'key'   => 'grade_level',
                'value' => $args['grade'],
            );
        }

        if (!empty($args['category'])) {
            $meta_query[] = array(
                'key'   => 'islamic_studies_category',
                'value' => $args['category'],
            );
        }

        if (!empty($args['gender'])) {
            $meta_query[] = array(
                'key'   => 'gender',
                'value' => $args['gender'],
            );
        }

        // Filter by sponsorship status
        if ($args['sponsored'] === 'available') {
            $meta_query[] = array(
                'relation' => 'OR',
                array(
                    'key'     => 'is_sponsored',
                    'value'   => 'no',
                ),
                array(
                    'key'     => 'is_sponsored',
                    'compare' => 'NOT EXISTS',
                ),
            );
        } elseif ($args['sponsored'] === 'sponsored') {
            $meta_query[] = array(
                'key'   => 'is_sponsored',
                'value' => 'yes',
            );
        }

        if (!empty($meta_query)) {
            $query_args['meta_query'] = $meta_query;
        }

        $query = new \WP_Query($query_args);

        $students = array();

        foreach ($query->posts as $post) {
            $students[] = self::format_student_for_display($post);
        }

        return array(
            'students'    => $students,
            'total'       => $query->found_posts,
            'total_pages' => $query->max_num_pages,
            'page'        => $args['page'],
        );
    }

    /**
     * Format student for display
     */
    public static function format_student_for_display($post) {
        $student_id = is_object($post) ? $post->ID : $post;
        $student = is_object($post) ? $post : get_post($post);

        if (!$student) {
            return null;
        }

        $monthly_fee = get_post_meta($student_id, 'monthly_tuition_fee', true);
        $is_sponsored = get_post_meta($student_id, 'is_sponsored', true) === 'yes';

        return array(
            'id'           => $student_id,
            'name'         => $student->post_title,
            'photo'        => Helpers::get_student_photo($student_id, 'medium'),
            'grade'        => Helpers::get_grade_label(get_post_meta($student_id, 'grade_level', true)),
            'category'     => Helpers::get_islamic_category_label(get_post_meta($student_id, 'islamic_studies_category', true)),
            'gender'       => get_post_meta($student_id, 'gender', true),
            'age'          => self::calculate_age(get_post_meta($student_id, 'date_of_birth', true)),
            'monthly_fee'  => Helpers::format_currency($monthly_fee),
            'is_sponsored' => $is_sponsored,
            'description'  => self::get_student_description($student_id),
        );
    }

    /**
     * Calculate age from date of birth
     */
    public static function calculate_age($dob) {
        if (empty($dob)) {
            return null;
        }

        $birthdate = new \DateTime($dob);
        $today = new \DateTime();
        $age = $birthdate->diff($today);

        return $age->y;
    }

    /**
     * Get student description for display
     */
    public static function get_student_description($student_id) {
        $grade = Helpers::get_grade_label(get_post_meta($student_id, 'grade_level', true));
        $category = Helpers::get_islamic_category_label(get_post_meta($student_id, 'islamic_studies_category', true));
        $age = self::calculate_age(get_post_meta($student_id, 'date_of_birth', true));

        $parts = array();

        if ($age) {
            $parts[] = sprintf(__('%d years old', 'al-huffaz-portal'), $age);
        }

        if ($grade) {
            $parts[] = $grade;
        }

        if ($category) {
            $parts[] = sprintf(__('studying %s', 'al-huffaz-portal'), $category);
        }

        return implode(', ', $parts);
    }

    /**
     * Get available students for sponsorship
     */
    public static function get_available_students($limit = -1) {
        return self::get_students(array(
            'per_page'  => $limit,
            'sponsored' => 'available',
        ));
    }
}
