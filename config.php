<?php
/**
 * Configuration file for Runner's Progress Tracker
 * Centralized settings for easy maintenance and customization
 */

// Application Settings
define('APP_NAME', "Runner's Progress Tracker");
define('APP_VERSION', '1.0.0');
define('APP_AUTHOR', 'PHP Development Team');

// Marathon Configuration
define('DEFAULT_MARATHON_DISTANCE', 50); // 50km
define('DEFAULT_TARGET_TIME', 4); // 4 hours default
define('DEFAULT_SPLIT_INTERVAL', 10); // 10km intervals for split times

// File Configuration
define('DATA_FILE', 'race_data.txt');
define('BACKUP_DIR', 'backups/');
define('EXPORT_DIR', 'exports/');

// Display Configuration
define('SPEED_DECIMAL_PLACES', 2);
define('DISTANCE_DECIMAL_PLACES', 2);
define('TIME_DECIMAL_PLACES', 1);

// Validation Settings
define('MIN_DISTANCE', 0.1);
define('MAX_DISTANCE', 1000);
define('MIN_TIME', 0.1);
define('MAX_TIME', 24);

// Session Configuration
define('SESSION_NAME', 'runner_progress');
define('SESSION_LIFETIME', 3600); // 1 hour

// Error Reporting (for development)
define('DISPLAY_ERRORS', true);
define('LOG_ERRORS', true);
define('ERROR_LOG_FILE', 'error.log');

// Data Management
define('MAX_HISTORICAL_ENTRIES', 1000);
define('AUTO_CLEANUP_THRESHOLD', 500);
define('BACKUP_FREQUENCY', 50); // Backup every 50 entries

// Performance Settings
define('CACHE_ENABLED', true);
define('CACHE_DURATION', 300); // 5 minutes

// Security Settings
define('CSRF_PROTECTION', true);
define('INPUT_SANITIZATION', true);
define('OUTPUT_ESCAPING', true);

// UI Configuration
define('THEME_COLOR_PRIMARY', '#667eea');
define('THEME_COLOR_SECONDARY', '#764ba2');
define('THEME_COLOR_SUCCESS', '#28a745');
define('THEME_COLOR_WARNING', '#ffc107');
define('THEME_COLOR_DANGER', '#dc3545');

// Feature Flags
define('FEATURE_ADVANCED_METRICS', true);
define('FEATURE_TREND_ANALYSIS', true);
define('FEATURE_DATA_EXPORT', true);
define('FEATURE_SPLIT_TIMES', true);
define('FEATURE_STATISTICS', true);

// Time Zone
date_default_timezone_set('UTC');

// Error Handling Configuration
if (DISPLAY_ERRORS) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
}

if (LOG_ERRORS) {
    ini_set('log_errors', 1);
    ini_set('error_log', ERROR_LOG_FILE);
}

// Session Configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 for HTTPS
ini_set('session.gc_maxlifetime', SESSION_LIFETIME);

// Create necessary directories if they don't exist
$directories = [BACKUP_DIR, EXPORT_DIR];
foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

/**
 * Get application configuration value
 * @param string $key Configuration key
 * @param mixed $default Default value if key not found
 * @return mixed Configuration value
 */
function getConfig($key, $default = null)
{
    return defined($key) ? constant($key) : $default;
}

/**
 * Set application configuration value (for runtime changes)
 * @param string $key Configuration key
 * @param mixed $value Configuration value
 */
function setConfig($key, $value)
{
    if (!defined($key)) {
        define($key, $value);
    }
}

/**
 * Get theme color
 * @param string $type Color type (primary, secondary, success, warning, danger)
 * @return string Color value
 */
function getThemeColor($type)
{
    $colorKey = 'THEME_COLOR_' . strtoupper($type);
    return getConfig($colorKey, '#667eea');
}

/**
 * Check if a feature is enabled
 * @param string $feature Feature name
 * @return bool True if feature is enabled
 */
function isFeatureEnabled($feature)
{
    $featureKey = 'FEATURE_' . strtoupper($feature);
    return getConfig($featureKey, false);
}

/**
 * Format number with configured decimal places
 * @param float $number Number to format
 * @param string $type Type of number (speed, distance, time)
 * @return string Formatted number
 */
function formatNumber($number, $type = 'speed')
{
    $decimalPlaces = getConfig(strtoupper($type) . '_DECIMAL_PLACES', 2);
    return number_format($number, $decimalPlaces);
}

/**
 * Validate configuration
 * @return array Array of validation errors
 */
function validateConfiguration()
{
    $errors = [];

    // Check required constants
    $requiredConstants = [
        'DEFAULT_MARATHON_DISTANCE',
        'DEFAULT_TARGET_TIME',
        'DATA_FILE'
    ];

    foreach ($requiredConstants as $constant) {
        if (!defined($constant)) {
            $errors[] = "Required constant '$constant' is not defined";
        }
    }

    // Check directory permissions
    $directories = [BACKUP_DIR, EXPORT_DIR];
    foreach ($directories as $dir) {
        if (!is_dir($dir) || !is_writable($dir)) {
            $errors[] = "Directory '$dir' is not writable";
        }
    }

    return $errors;
}

// Validate configuration on load
$configErrors = validateConfiguration();
if (!empty($configErrors)) {
    error_log("Configuration errors: " . implode(', ', $configErrors));
}
?>