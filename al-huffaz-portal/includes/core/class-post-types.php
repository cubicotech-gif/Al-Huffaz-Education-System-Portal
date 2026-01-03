<?php
/**
 * Custom Post Types Registration
 *
 * @package AlHuffaz
 */

namespace AlHuffaz\Core;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Post_Types
 */
class Post_Types {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'register_post_types'));
        add_action('init', array($this, 'register_taxonomies'));
    }

    /**
     * Register custom post types
     */
    public function register_post_types() {
        // Student Post Type
        register_post_type('alhuffaz_student', array(
            'labels' => array(
                'name'               => __('Students', 'al-huffaz-portal'),
                'singular_name'      => __('Student', 'al-huffaz-portal'),
                'menu_name'          => __('Students', 'al-huffaz-portal'),
                'add_new'            => __('Add New', 'al-huffaz-portal'),
                'add_new_item'       => __('Add New Student', 'al-huffaz-portal'),
                'edit_item'          => __('Edit Student', 'al-huffaz-portal'),
                'new_item'           => __('New Student', 'al-huffaz-portal'),
                'view_item'          => __('View Student', 'al-huffaz-portal'),
                'search_items'       => __('Search Students', 'al-huffaz-portal'),
                'not_found'          => __('No students found', 'al-huffaz-portal'),
                'not_found_in_trash' => __('No students found in trash', 'al-huffaz-portal'),
            ),
            'public'              => false,
            'publicly_queryable'  => false,
            'show_ui'             => false,
            'show_in_menu'        => false,
            'show_in_rest'        => true,
            'capability_type'     => 'post',
            'hierarchical'        => false,
            'supports'            => array('title', 'thumbnail'),
            'has_archive'         => false,
            'rewrite'             => false,
            'query_var'           => false,
        ));

        // Sponsorship Post Type
        register_post_type('alhuffaz_sponsor', array(
            'labels' => array(
                'name'               => __('Sponsorships', 'al-huffaz-portal'),
                'singular_name'      => __('Sponsorship', 'al-huffaz-portal'),
                'menu_name'          => __('Sponsorships', 'al-huffaz-portal'),
                'add_new'            => __('Add New', 'al-huffaz-portal'),
                'add_new_item'       => __('Add New Sponsorship', 'al-huffaz-portal'),
                'edit_item'          => __('Edit Sponsorship', 'al-huffaz-portal'),
                'new_item'           => __('New Sponsorship', 'al-huffaz-portal'),
                'view_item'          => __('View Sponsorship', 'al-huffaz-portal'),
                'search_items'       => __('Search Sponsorships', 'al-huffaz-portal'),
                'not_found'          => __('No sponsorships found', 'al-huffaz-portal'),
                'not_found_in_trash' => __('No sponsorships found in trash', 'al-huffaz-portal'),
            ),
            'public'              => false,
            'publicly_queryable'  => false,
            'show_ui'             => false,
            'show_in_menu'        => false,
            'show_in_rest'        => true,
            'capability_type'     => 'post',
            'hierarchical'        => false,
            'supports'            => array('title'),
            'has_archive'         => false,
            'rewrite'             => false,
            'query_var'           => false,
        ));
    }

    /**
     * Register taxonomies
     */
    public function register_taxonomies() {
        // Grade Level Taxonomy
        register_taxonomy('alhuffaz_grade', 'alhuffaz_student', array(
            'labels' => array(
                'name'              => __('Grade Levels', 'al-huffaz-portal'),
                'singular_name'     => __('Grade Level', 'al-huffaz-portal'),
                'search_items'      => __('Search Grades', 'al-huffaz-portal'),
                'all_items'         => __('All Grades', 'al-huffaz-portal'),
                'edit_item'         => __('Edit Grade', 'al-huffaz-portal'),
                'update_item'       => __('Update Grade', 'al-huffaz-portal'),
                'add_new_item'      => __('Add New Grade', 'al-huffaz-portal'),
                'new_item_name'     => __('New Grade Name', 'al-huffaz-portal'),
                'menu_name'         => __('Grades', 'al-huffaz-portal'),
            ),
            'hierarchical'      => true,
            'public'            => false,
            'show_ui'           => false,
            'show_admin_column' => false,
            'show_in_rest'      => true,
            'rewrite'           => false,
        ));

        // Islamic Studies Category
        register_taxonomy('alhuffaz_islamic_cat', 'alhuffaz_student', array(
            'labels' => array(
                'name'              => __('Islamic Studies', 'al-huffaz-portal'),
                'singular_name'     => __('Islamic Study', 'al-huffaz-portal'),
                'search_items'      => __('Search Categories', 'al-huffaz-portal'),
                'all_items'         => __('All Categories', 'al-huffaz-portal'),
                'edit_item'         => __('Edit Category', 'al-huffaz-portal'),
                'update_item'       => __('Update Category', 'al-huffaz-portal'),
                'add_new_item'      => __('Add New Category', 'al-huffaz-portal'),
                'new_item_name'     => __('New Category Name', 'al-huffaz-portal'),
                'menu_name'         => __('Islamic Studies', 'al-huffaz-portal'),
            ),
            'hierarchical'      => true,
            'public'            => false,
            'show_ui'           => false,
            'show_admin_column' => false,
            'show_in_rest'      => true,
            'rewrite'           => false,
        ));
    }

    /**
     * Get student meta fields
     */
    public static function get_student_fields() {
        return array(
            // Basic Info
            'gr_number'           => array('type' => 'string', 'label' => 'GR Number'),
            'roll_number'         => array('type' => 'string', 'label' => 'Roll Number'),
            'gender'              => array('type' => 'string', 'label' => 'Gender'),
            'date_of_birth'       => array('type' => 'date', 'label' => 'Date of Birth'),
            'admission_date'      => array('type' => 'date', 'label' => 'Admission Date'),
            'grade_level'         => array('type' => 'string', 'label' => 'Grade Level'),
            'islamic_category'    => array('type' => 'string', 'label' => 'Islamic Studies Category'),
            'student_photo'       => array('type' => 'integer', 'label' => 'Student Photo'),

            // Address
            'permanent_address'   => array('type' => 'string', 'label' => 'Permanent Address'),
            'current_address'     => array('type' => 'string', 'label' => 'Current Address'),

            // Family Info
            'father_name'         => array('type' => 'string', 'label' => 'Father Name'),
            'father_cnic'         => array('type' => 'string', 'label' => 'Father CNIC'),
            'father_phone'        => array('type' => 'string', 'label' => 'Father Phone'),
            'father_email'        => array('type' => 'string', 'label' => 'Father Email'),
            'guardian_name'       => array('type' => 'string', 'label' => 'Guardian Name'),
            'guardian_cnic'       => array('type' => 'string', 'label' => 'Guardian CNIC'),
            'guardian_phone'      => array('type' => 'string', 'label' => 'Guardian Phone'),
            'guardian_whatsapp'   => array('type' => 'string', 'label' => 'Guardian WhatsApp'),
            'guardian_email'      => array('type' => 'string', 'label' => 'Guardian Email'),
            'relationship'        => array('type' => 'string', 'label' => 'Relationship'),
            'emergency_contact'   => array('type' => 'string', 'label' => 'Emergency Contact'),
            'emergency_whatsapp'  => array('type' => 'string', 'label' => 'Emergency WhatsApp'),

            // Fees
            'monthly_fee'         => array('type' => 'number', 'label' => 'Monthly Tuition Fee'),
            'course_fee'          => array('type' => 'number', 'label' => 'Course Fee'),
            'uniform_fee'         => array('type' => 'number', 'label' => 'Uniform Fee'),
            'annual_fee'          => array('type' => 'number', 'label' => 'Annual Fee'),
            'admission_fee'       => array('type' => 'number', 'label' => 'Admission Fee'),
            'zakat_eligible'      => array('type' => 'boolean', 'label' => 'Zakat Eligible'),
            'donation_eligible'   => array('type' => 'boolean', 'label' => 'Donation Eligible'),

            // Health
            'blood_group'         => array('type' => 'string', 'label' => 'Blood Group'),
            'allergies'           => array('type' => 'string', 'label' => 'Allergies'),
            'medical_conditions'  => array('type' => 'string', 'label' => 'Medical Conditions'),
            'health_rating'       => array('type' => 'integer', 'label' => 'Health Rating'),
            'cleanness_rating'    => array('type' => 'integer', 'label' => 'Cleanness Rating'),

            // Attendance
            'total_school_days'   => array('type' => 'integer', 'label' => 'Total School Days'),
            'present_days'        => array('type' => 'integer', 'label' => 'Present Days'),
            'academic_term'       => array('type' => 'string', 'label' => 'Academic Term'),
            'academic_year'       => array('type' => 'string', 'label' => 'Academic Year'),

            // Academic
            'subjects'            => array('type' => 'array', 'label' => 'Subjects'),
            'overall_percentage'  => array('type' => 'number', 'label' => 'Overall Percentage'),

            // Behavior
            'homework_completion' => array('type' => 'string', 'label' => 'Homework Completion'),
            'class_participation' => array('type' => 'string', 'label' => 'Class Participation'),
            'group_work'          => array('type' => 'string', 'label' => 'Group Work'),
            'problem_solving'     => array('type' => 'string', 'label' => 'Problem Solving'),
            'organization'        => array('type' => 'string', 'label' => 'Organization'),
            'teacher_comments'    => array('type' => 'string', 'label' => 'Teacher Comments'),
            'goals'               => array('type' => 'array', 'label' => 'Goals'),

            // Sponsorship
            'is_sponsored'        => array('type' => 'boolean', 'label' => 'Is Sponsored'),
            'sponsor_id'          => array('type' => 'integer', 'label' => 'Sponsor ID'),
        );
    }

    /**
     * Get sponsorship meta fields
     */
    public static function get_sponsorship_fields() {
        return array(
            'student_id'          => array('type' => 'integer', 'label' => 'Student'),
            'sponsor_user_id'     => array('type' => 'integer', 'label' => 'Sponsor User'),
            'sponsorship_type'    => array('type' => 'string', 'label' => 'Type'),
            'amount'              => array('type' => 'number', 'label' => 'Amount'),
            'sponsor_name'        => array('type' => 'string', 'label' => 'Sponsor Name'),
            'sponsor_email'       => array('type' => 'string', 'label' => 'Sponsor Email'),
            'sponsor_phone'       => array('type' => 'string', 'label' => 'Sponsor Phone'),
            'sponsor_country'     => array('type' => 'string', 'label' => 'Sponsor Country'),
            'transaction_id'      => array('type' => 'string', 'label' => 'Transaction ID'),
            'payment_method'      => array('type' => 'string', 'label' => 'Payment Method'),
            'payment_date'        => array('type' => 'date', 'label' => 'Payment Date'),
            'payment_screenshot'  => array('type' => 'integer', 'label' => 'Payment Screenshot'),
            'notes'               => array('type' => 'string', 'label' => 'Notes'),
            'status'              => array('type' => 'string', 'label' => 'Status'),
            'linked'              => array('type' => 'boolean', 'label' => 'Linked'),
            'verified_by'         => array('type' => 'integer', 'label' => 'Verified By'),
            'verified_at'         => array('type' => 'datetime', 'label' => 'Verified At'),
        );
    }
}
