<?php
/**
 * Autoloader for Al-Huffaz Portal
 *
 * @package AlHuffaz
 */

namespace AlHuffaz\Core;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Autoloader
 */
class Autoloader {

    /**
     * Register autoloader
     */
    public static function register() {
        spl_autoload_register(array(__CLASS__, 'autoload'));
    }

    /**
     * Autoload classes
     */
    public static function autoload($class) {
        // Only autoload our namespace
        if (strpos($class, 'AlHuffaz\\') !== 0) {
            return;
        }

        // Remove namespace prefix
        $class = str_replace('AlHuffaz\\', '', $class);

        // Convert to file path
        $class = strtolower($class);
        $class = str_replace('_', '-', $class);
        $class = str_replace('\\', DIRECTORY_SEPARATOR, $class);

        // Build file path
        $file = ALHUFFAZ_PLUGIN_DIR . 'includes/' . $class . '.php';

        // Check alternative paths
        if (!file_exists($file)) {
            $parts = explode(DIRECTORY_SEPARATOR, $class);
            $class_name = array_pop($parts);
            $path = implode(DIRECTORY_SEPARATOR, $parts);
            $file = ALHUFFAZ_PLUGIN_DIR . 'includes/' . $path . '/class-' . $class_name . '.php';
        }

        if (file_exists($file)) {
            require_once $file;
        }
    }
}

// Register autoloader
Autoloader::register();
