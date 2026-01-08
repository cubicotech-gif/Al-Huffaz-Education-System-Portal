<?php
/**
 * Debug Logger for Al-Huffaz Portal
 *
 * @package AlHuffaz
 */

namespace AlHuffaz\Core;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Debug
 *
 * Handles logging of errors and debug information
 */
class Debug {

    /**
     * Log file path
     */
    private static $log_file = null;

    /**
     * Initialize the debug logger
     */
    public static function init() {
        self::$log_file = WP_CONTENT_DIR . '/alhuffaz-debug.log';

        // Set up error handler for our plugin
        set_error_handler(array(__CLASS__, 'error_handler'), E_ALL);

        // Log when plugin loads
        self::log('Plugin initialized', 'info');
    }

    /**
     * Get log file path
     */
    public static function get_log_file() {
        if (self::$log_file === null) {
            self::$log_file = WP_CONTENT_DIR . '/alhuffaz-debug.log';
        }
        return self::$log_file;
    }

    /**
     * Log a message
     *
     * @param string $message The message to log
     * @param string $level   Log level: info, warning, error, debug
     * @param array  $context Additional context data
     */
    public static function log($message, $level = 'info', $context = array()) {
        $log_file = self::get_log_file();

        $timestamp = current_time('Y-m-d H:i:s');
        $level = strtoupper($level);

        // Get current user info
        $user_info = 'Guest';
        if (function_exists('is_user_logged_in') && is_user_logged_in()) {
            $user = wp_get_current_user();
            $user_info = sprintf('User: %s (ID: %d, Roles: %s)',
                $user->user_login,
                $user->ID,
                implode(', ', $user->roles)
            );
        }

        // Get current URL
        $current_url = '';
        if (isset($_SERVER['REQUEST_URI'])) {
            $current_url = home_url($_SERVER['REQUEST_URI']);
        }

        // Build log entry
        $log_entry = sprintf(
            "[%s] [%s] %s\n  %s\n  URL: %s\n",
            $timestamp,
            $level,
            $message,
            $user_info,
            $current_url
        );

        // Add context if provided
        if (!empty($context)) {
            $log_entry .= "  Context: " . print_r($context, true) . "\n";
        }

        // Add backtrace for errors
        if ($level === 'ERROR') {
            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);
            $log_entry .= "  Backtrace:\n";
            foreach ($backtrace as $i => $trace) {
                $file = isset($trace['file']) ? $trace['file'] : 'unknown';
                $line = isset($trace['line']) ? $trace['line'] : 0;
                $function = isset($trace['function']) ? $trace['function'] : 'unknown';
                $log_entry .= sprintf("    #%d %s:%d - %s()\n", $i, $file, $line, $function);
            }
        }

        $log_entry .= str_repeat('-', 80) . "\n";

        // Write to log file
        @file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
    }

    /**
     * Log an error
     */
    public static function error($message, $context = array()) {
        self::log($message, 'error', $context);
    }

    /**
     * Log a warning
     */
    public static function warning($message, $context = array()) {
        self::log($message, 'warning', $context);
    }

    /**
     * Log debug info
     */
    public static function debug($message, $context = array()) {
        self::log($message, 'debug', $context);
    }

    /**
     * Custom error handler
     */
    public static function error_handler($errno, $errstr, $errfile, $errline) {
        // Only log errors from our plugin
        if (strpos($errfile, 'al-huffaz-portal') !== false) {
            $error_types = array(
                E_ERROR => 'Fatal Error',
                E_WARNING => 'Warning',
                E_NOTICE => 'Notice',
                E_USER_ERROR => 'User Error',
                E_USER_WARNING => 'User Warning',
                E_USER_NOTICE => 'User Notice',
            );

            $type = isset($error_types[$errno]) ? $error_types[$errno] : 'Unknown Error';

            self::log(
                sprintf('%s: %s in %s on line %d', $type, $errstr, $errfile, $errline),
                'error'
            );
        }

        // Let PHP handle the error as well
        return false;
    }

    /**
     * Log role check for debugging
     */
    public static function log_role_check($check_name, $result, $user_id = null) {
        if ($user_id === null) {
            $user_id = get_current_user_id();
        }

        $user = get_user_by('ID', $user_id);
        $roles = $user ? $user->roles : array();

        self::debug(
            sprintf('Role check: %s = %s', $check_name, $result ? 'true' : 'false'),
            array(
                'user_id' => $user_id,
                'user_roles' => $roles,
                'um_role' => get_user_meta($user_id, 'role', true),
            )
        );
    }

    /**
     * Clear log file
     */
    public static function clear_log() {
        $log_file = self::get_log_file();
        @file_put_contents($log_file, "Log cleared at " . current_time('Y-m-d H:i:s') . "\n" . str_repeat('-', 80) . "\n");
    }

    /**
     * Get recent log entries
     */
    public static function get_recent_logs($lines = 100) {
        $log_file = self::get_log_file();

        if (!file_exists($log_file)) {
            return 'No log file found.';
        }

        $content = file_get_contents($log_file);
        $all_lines = explode("\n", $content);
        $recent = array_slice($all_lines, -$lines);

        return implode("\n", $recent);
    }
}
