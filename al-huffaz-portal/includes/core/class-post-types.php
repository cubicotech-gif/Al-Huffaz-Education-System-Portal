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

        // Template loading for student single page
        add_filter('single_template', array($this, 'load_student_template'));
        add_filter('archive_template', array($this, 'load_students_archive_template'));
    }

    /**
     * Load custom single student template from plugin
     */
    public function load_student_template($template) {
        global $post;

        if ($post && $post->post_type === 'student') {
            // Check if theme has a template first
            $theme_template = locate_template(array('single-student.php'));

            if ($theme_template) {
                return $theme_template;
            }

            // Use plugin template
            $plugin_template = ALHUFFAZ_TEMPLATES_DIR . 'public/single-student.php';

            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }

        return $template;
    }

    /**
     * Load custom students archive template from plugin
     */
    public function load_students_archive_template($template) {
        if (is_post_type_archive('student')) {
            // Check if theme has a template first
            $theme_template = locate_template(array('archive-student.php'));

            if ($theme_template) {
                return $theme_template;
            }

            // Use plugin template
            $plugin_template = ALHUFFAZ_TEMPLATES_DIR . 'public/archive-student.php';

            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }

        return $template;
    }

    /**
     * Register custom post types
     */
    public function register_post_types() {
        // Student Post Type - Only register if not already registered by CPT UI or another plugin
        if (!post_type_exists('student')) {
            register_post_type('student', array(
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
                'public'              => true,
                'publicly_queryable'  => true,
                'show_ui'             => true,
                'show_in_menu'        => false, // We use our custom admin menu
                'show_in_rest'        => true,
                'capability_type'     => 'post',
                'hierarchical'        => false,
                'supports'            => array('title', 'thumbnail', 'custom-fields'),
                'has_archive'         => true,
                'rewrite'             => array('slug' => 'students', 'with_front' => false),
                'query_var'           => true,
            ));
        }

        // REMOVED: Legacy 'alhuffaz_sponsor' CPT registration
        // The sponsorship system now uses 'sponsorship' CPT registered in alhuffaz-payment-collection.php
        // This consolidation eliminates duplicate CPT registrations and standardizes on one system.
        // Migration date: 2026-01-13

        // Sponsor Profile Post Type - One CPT per sponsor person
        // Auto-created when sponsor user is approved
        if (!post_type_exists('sponsor')) {
            register_post_type('sponsor', array(
                'labels' => array(
                    'name'               => __('Sponsors', 'al-huffaz-portal'),
                    'singular_name'      => __('Sponsor', 'al-huffaz-portal'),
                    'menu_name'          => __('Sponsors', 'al-huffaz-portal'),
                    'add_new'            => __('Add New', 'al-huffaz-portal'),
                    'add_new_item'       => __('Add New Sponsor', 'al-huffaz-portal'),
                    'edit_item'          => __('Edit Sponsor', 'al-huffaz-portal'),
                    'new_item'           => __('New Sponsor', 'al-huffaz-portal'),
                    'view_item'          => __('View Sponsor', 'al-huffaz-portal'),
                    'search_items'       => __('Search Sponsors', 'al-huffaz-portal'),
                    'not_found'          => __('No sponsors found', 'al-huffaz-portal'),
                    'not_found_in_trash' => __('No sponsors found in trash', 'al-huffaz-portal'),
                ),
                'public'              => true,
                'publicly_queryable'  => true,
                'show_ui'             => true,
                'show_in_menu'        => true,
                'show_in_rest'        => true,
                'menu_icon'           => 'dashicons-heart',
                'capability_type'     => 'post',
                'hierarchical'        => false,
                'supports'            => array('title', 'custom-fields'),
                'has_archive'         => false,
                'rewrite'             => array('slug' => 'sponsor', 'with_front' => false),
                'query_var'           => true,
            ));
        }
    }

    /**
     * Register taxonomies
     */
    public function register_taxonomies() {
        // Grade Level Taxonomy
        register_taxonomy('student_grade', 'student', array(
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
            'public'            => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'rewrite'           => array('slug' => 'grade'),
        ));

        // Islamic Studies Category
        register_taxonomy('islamic_category', 'student', array(
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
            'public'            => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'rewrite'           => array('slug' => 'islamic-category'),
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

    /**
     * Get sponsor CPT meta fields
     */
    public static function get_sponsor_fields() {
        return array(
            'sponsor_user_id'        => array('type' => 'integer', 'label' => 'User ID'),
            'sponsor_name'           => array('type' => 'string', 'label' => 'Name'),
            'sponsor_email'          => array('type' => 'string', 'label' => 'Email'),
            'sponsor_phone'          => array('type' => 'string', 'label' => 'Phone'),
            'sponsor_country'        => array('type' => 'string', 'label' => 'Country'),
            'sponsor_whatsapp'       => array('type' => 'string', 'label' => 'WhatsApp'),
            'account_status'         => array('type' => 'string', 'label' => 'Account Status'),
            'created_date'           => array('type' => 'datetime', 'label' => 'Created Date'),
            'reactivated_date'       => array('type' => 'datetime', 'label' => 'Reactivated Date'),
            'account_deleted_date'   => array('type' => 'datetime', 'label' => 'Account Deleted Date'),
        );
    }
}
