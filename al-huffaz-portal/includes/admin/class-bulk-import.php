<?php
/**
 * Bulk Import
 *
 * @package AlHuffaz
 */

namespace AlHuffaz\Admin;

use AlHuffaz\Core\Helpers;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Bulk_Import
 */
class Bulk_Import {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_ajax_alhuffaz_process_import', array($this, 'process_import'));
        add_action('wp_ajax_alhuffaz_download_template', array($this, 'download_template'));
    }

    /**
     * Process CSV import
     */
    public function process_import() {
        check_ajax_referer('alhuffaz_admin_nonce', 'nonce');

        if (!current_user_can('alhuffaz_bulk_import')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'al-huffaz-portal')));
        }

        if (empty($_FILES['import_file'])) {
            wp_send_json_error(array('message' => __('No file uploaded.', 'al-huffaz-portal')));
        }

        $file = $_FILES['import_file'];

        // Validate file type
        $allowed_types = array('text/csv', 'application/vnd.ms-excel', 'text/plain');

        if (!in_array($file['type'], $allowed_types)) {
            wp_send_json_error(array('message' => __('Invalid file type. Please upload a CSV file.', 'al-huffaz-portal')));
        }

        // Check file size (max 5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            wp_send_json_error(array('message' => __('File too large. Maximum size is 5MB.', 'al-huffaz-portal')));
        }

        $handle = fopen($file['tmp_name'], 'r');

        if ($handle === false) {
            wp_send_json_error(array('message' => __('Failed to read file.', 'al-huffaz-portal')));
        }

        // Read headers
        $headers = fgetcsv($handle);

        if (!$headers) {
            fclose($handle);
            wp_send_json_error(array('message' => __('Empty or invalid CSV file.', 'al-huffaz-portal')));
        }

        // Clean headers
        $headers = array_map('trim', $headers);
        $headers = array_map('strtolower', $headers);
        $headers = array_map(function($h) {
            return str_replace(' ', '_', $h);
        }, $headers);

        // Validate required headers
        if (!in_array('student_name', $headers)) {
            fclose($handle);
            wp_send_json_error(array('message' => __('CSV must contain a "student_name" column.', 'al-huffaz-portal')));
        }

        $imported = 0;
        $updated = 0;
        $errors = array();
        $row_number = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $row_number++;

            if (count($row) !== count($headers)) {
                $errors[] = sprintf(__('Row %d: Column count mismatch.', 'al-huffaz-portal'), $row_number);
                continue;
            }

            $data = array_combine($headers, $row);

            if (empty($data['student_name'])) {
                $errors[] = sprintf(__('Row %d: Missing student name.', 'al-huffaz-portal'), $row_number);
                continue;
            }

            // Check for existing student by GR number
            $existing_id = 0;

            if (!empty($data['gr_number'])) {
                $existing = get_posts(array(
                    'post_type'      => 'alhuffaz_student',
                    'posts_per_page' => 1,
                    'meta_query'     => array(
                        array(
                            'key'   => '_gr_number',
                            'value' => $data['gr_number'],
                        ),
                    ),
                ));

                if (!empty($existing)) {
                    $existing_id = $existing[0]->ID;
                }
            }

            if ($existing_id) {
                // Update existing student
                $result = Student_Manager::update_student($existing_id, $data);

                if (is_wp_error($result)) {
                    $errors[] = sprintf(__('Row %d: %s', 'al-huffaz-portal'), $row_number, $result->get_error_message());
                } else {
                    $updated++;
                }
            } else {
                // Create new student
                $result = Student_Manager::create_student($data);

                if (is_wp_error($result)) {
                    $errors[] = sprintf(__('Row %d: %s', 'al-huffaz-portal'), $row_number, $result->get_error_message());
                } else {
                    $imported++;
                }
            }
        }

        fclose($handle);

        // Log activity
        Helpers::log_activity(
            'bulk_import',
            'import',
            0,
            sprintf('Imported %d, updated %d students', $imported, $updated)
        );

        wp_send_json_success(array(
            'message'  => sprintf(
                __('Import complete. %d new students imported, %d updated.', 'al-huffaz-portal'),
                $imported,
                $updated
            ),
            'imported' => $imported,
            'updated'  => $updated,
            'errors'   => $errors,
        ));
    }

    /**
     * Download CSV template
     */
    public function download_template() {
        check_ajax_referer('alhuffaz_admin_nonce', 'nonce');

        if (!current_user_can('alhuffaz_bulk_import')) {
            wp_die(__('Permission denied.', 'al-huffaz-portal'));
        }

        $headers = array(
            'student_name',
            'gr_number',
            'roll_number',
            'gender',
            'date_of_birth',
            'admission_date',
            'grade_level',
            'islamic_category',
            'father_name',
            'father_cnic',
            'father_phone',
            'father_email',
            'guardian_name',
            'guardian_phone',
            'guardian_whatsapp',
            'guardian_email',
            'relationship',
            'permanent_address',
            'current_address',
            'monthly_fee',
            'course_fee',
            'uniform_fee',
            'admission_fee',
            'zakat_eligible',
            'donation_eligible',
            'blood_group',
            'allergies',
            'medical_conditions',
        );

        $sample_data = array(
            'Ahmed Khan',
            'GR-2024-001',
            '101',
            'male',
            '2015-05-15',
            '2024-01-10',
            'class1',
            'nazra',
            'Muhammad Khan',
            '12345-6789012-3',
            '+92-300-1234567',
            'father@email.com',
            'Muhammad Khan',
            '+92-300-1234567',
            '+92-300-1234567',
            'guardian@email.com',
            'Father',
            '123 Main Street, Karachi',
            '123 Main Street, Karachi',
            '5000',
            '2000',
            '3000',
            '10000',
            'yes',
            'yes',
            'O+',
            'None',
            'None',
        );

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="al-huffaz-import-template.csv"');

        $output = fopen('php://output', 'w');

        fputcsv($output, $headers);
        fputcsv($output, $sample_data);

        fclose($output);
        exit;
    }

    /**
     * Get field mapping
     */
    public static function get_field_mapping() {
        return array(
            'student_name'      => __('Student Name', 'al-huffaz-portal'),
            'gr_number'         => __('GR Number', 'al-huffaz-portal'),
            'roll_number'       => __('Roll Number', 'al-huffaz-portal'),
            'gender'            => __('Gender (male/female)', 'al-huffaz-portal'),
            'date_of_birth'     => __('Date of Birth (YYYY-MM-DD)', 'al-huffaz-portal'),
            'admission_date'    => __('Admission Date (YYYY-MM-DD)', 'al-huffaz-portal'),
            'grade_level'       => __('Grade Level', 'al-huffaz-portal'),
            'islamic_category'  => __('Islamic Category', 'al-huffaz-portal'),
            'father_name'       => __('Father Name', 'al-huffaz-portal'),
            'father_cnic'       => __('Father CNIC', 'al-huffaz-portal'),
            'father_phone'      => __('Father Phone', 'al-huffaz-portal'),
            'father_email'      => __('Father Email', 'al-huffaz-portal'),
            'guardian_name'     => __('Guardian Name', 'al-huffaz-portal'),
            'guardian_phone'    => __('Guardian Phone', 'al-huffaz-portal'),
            'guardian_whatsapp' => __('Guardian WhatsApp', 'al-huffaz-portal'),
            'guardian_email'    => __('Guardian Email', 'al-huffaz-portal'),
            'relationship'      => __('Relationship', 'al-huffaz-portal'),
            'permanent_address' => __('Permanent Address', 'al-huffaz-portal'),
            'current_address'   => __('Current Address', 'al-huffaz-portal'),
            'monthly_fee'       => __('Monthly Fee', 'al-huffaz-portal'),
            'course_fee'        => __('Course Fee', 'al-huffaz-portal'),
            'uniform_fee'       => __('Uniform Fee', 'al-huffaz-portal'),
            'admission_fee'     => __('Admission Fee', 'al-huffaz-portal'),
            'zakat_eligible'    => __('Zakat Eligible (yes/no)', 'al-huffaz-portal'),
            'donation_eligible' => __('Donation Eligible (yes/no)', 'al-huffaz-portal'),
            'blood_group'       => __('Blood Group', 'al-huffaz-portal'),
            'allergies'         => __('Allergies', 'al-huffaz-portal'),
            'medical_conditions'=> __('Medical Conditions', 'al-huffaz-portal'),
        );
    }
}
