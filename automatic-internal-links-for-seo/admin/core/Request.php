<?php
namespace Pagup\AutoLinks\Core;

/**
 * Handles request data sanitization and validation
 */
class Request {
    private const ERROR_PREFIX = 'Auto Links';

    /**
     * Gets sanitized POST value if it exists in safe values array
     *
     * @param string $key POST parameter key
     * @param array $safe Array of allowed values
     * @return string|bool Sanitized value or false if invalid
     */
    public static function safe(string $key, array $safe) {
        try {
            if (!self::check($key)) {
                return false;
            }

            $value = $_POST[$key];
            
            if (!in_array($value, $safe, true)) {
                return false;
            }

            return sanitize_text_field($value);
        } catch (\Exception $e) {
            error_log(sprintf("%s Request Error: %s", self::ERROR_PREFIX, $e->getMessage()));
            return false;
        }
    }

    /**
     * Converts POST value to boolean integer (0 or 1)
     *
     * @param string $key POST parameter key
     * @return int 1 for true values, 0 for false
     */
    public static function bool(string $key): int {
        try {
            if (!isset($_POST[$key])) {
                return 0;
            }

            return filter_var($_POST[$key], FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
        } catch (\Exception $e) {
            error_log(sprintf("%s Request Error: %s", self::ERROR_PREFIX, $e->getMessage()));
            return 0;
        }
    }

    /**
     * Checks if POST key exists and has non-empty value
     *
     * @param string $key POST parameter key
     * @return bool Whether key exists and has value
     */
    public static function check(string $key): bool {
        return isset($_POST[$key]) && !empty($_POST[$key]);
    }

    /**
     * Recursively sanitizes array values
     *
     * @param array $data Data to sanitize
     * @return array Sanitized array
     */
    public static function array(array $data): array {
        try {
            if (!is_array($data)) {
                return [];
            }

            return array_map(function($value) {
                if (is_array($value)) {
                    return self::array($value);
                }
                return sanitize_text_field($value);
            }, $data);
        } catch (\Exception $e) {
            error_log(sprintf("%s Request Error: %s", self::ERROR_PREFIX, $e->getMessage()));
            return [];
        }
    }

    /**
     * Gets all POST data as sanitized array
     *
     * @return array Sanitized POST data
     */
    public static function all(): array {
        return self::array($_POST);
    }

    /**
     * Gets POST value with optional default
     *
     * @param string $key POST parameter key
     * @param mixed $default Default value if key not found
     * @return mixed Sanitized value or default
     */
    public static function get(string $key, $default = null) {
        try {
            if (!self::check($key)) {
                return $default;
            }

            $value = $_POST[$key];
            
            if (is_array($value)) {
                return self::array($value);
            }

            return sanitize_text_field($value);
        } catch (\Exception $e) {
            error_log(sprintf("%s Request Error: %s", self::ERROR_PREFIX, $e->getMessage()));
            return $default;
        }
    }

    /**
     * Validates if all required keys exist in POST
     *
     * @param array $required Array of required keys
     * @return bool Whether all required keys exist
     */
    public static function has(array $required): bool {
        foreach ($required as $key) {
            if (!self::check($key)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Gets only specified keys from POST data
     *
     * @param array $keys Keys to get
     * @return array Filtered and sanitized data
     */
    public static function only(array $keys): array {
        return array_intersect_key(
            self::all(),
            array_flip($keys)
        );
    }

    /**
     * Gets POST data except specified keys
     *
     * @param array $keys Keys to exclude
     * @return array Filtered and sanitized data
     */
    public static function except(array $keys): array {
        return array_diff_key(
            self::all(),
            array_flip($keys)
        );
    }
}