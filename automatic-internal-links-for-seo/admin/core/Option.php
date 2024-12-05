<?php
namespace Pagup\AutoLinks\Core;

use WP_Post;

/**
 * Handles WordPress options management for Auto Links plugin
 */
class Option {
    private const OPTION_NAME = 'automatic-internal-links-for-seo';
    private const ERROR_PREFIX = 'Auto Links';

    /**
     * Gets all plugin options
     *
     * @return array|false Plugin options or false if not set
     */
    public static function all() {
        return get_option(self::OPTION_NAME);
    }

    /**
     * Gets a specific option value
     *
     * @param string $key Option key to retrieve
     * @return mixed|null Option value or null if not found
     */
    public static function get(string $key) {
        try {
            $options = static::all();
            
            if (!is_array($options)) {
                return null;
            }

            return $options[$key] ?? null;
        } catch (\Exception $e) {
            error_log(sprintf("%s Option Error: %s", self::ERROR_PREFIX, $e->getMessage()));
            return null;
        }
    }

    /**
     * Checks if an option exists and is not empty
     *
     * @param string $key Option key to check
     * @return bool True if option exists and has value
     */
    public static function check(string $key): bool {
        try {
            $options = static::all();
            
            if (!is_array($options)) {
                return false;
            }

            return isset($options[$key]) && !empty($options[$key]);
        } catch (\Exception $e) {
            error_log(sprintf("%s Option Error: %s", self::ERROR_PREFIX, $e->getMessage()));
            return false;
        }
    }

    /**
     * Validates if an option matches a specific value
     *
     * @param string $option Option key to check
     * @param mixed $value Value to compare against
     * @return bool True if option exists and matches value
     */
    public static function valid(string $option, $value): bool {
        try {
            return static::check($option) && static::get($option) == $value;
        } catch (\Exception $e) {
            error_log(sprintf("%s Option Error: %s", self::ERROR_PREFIX, $e->getMessage()));
            return false;
        }
    }

    /**
     * Gets post meta value for current post
     *
     * @param string $key Meta key to retrieve
     * @return mixed Meta value or empty string if not found
     */
    public static function post_meta(string $key) {
        try {
            global $post;

            if (!$post instanceof WP_Post) {
                throw new \RuntimeException('No post object available');
            }

            return get_post_meta($post->ID, $key, true);
        } catch (\Exception $e) {
            error_log(sprintf("%s Option Error: %s", self::ERROR_PREFIX, $e->getMessage()));
            return '';
        }
    }

    /**
     * Recursively sanitizes an array of values
     *
     * @param array $array Array to sanitize
     * @return array Sanitized array
     */
    public static function sanitize_array(array $array): array {
        try {
            return array_map(function($value) {
                if (is_array($value)) {
                    return static::sanitize_array($value);
                }
                return sanitize_text_field($value);
            }, $array);
        } catch (\Exception $e) {
            error_log(sprintf("%s Option Error: %s", self::ERROR_PREFIX, $e->getMessage()));
            return [];
        }
    }

    /**
     * Updates a plugin option
     *
     * @param string $key Option key to update
     * @param mixed $value New value
     * @return bool Success status
     */
    public static function update(string $key, $value): bool {
        try {
            $options = static::all() ?: [];
            
            if (!is_array($options)) {
                $options = [];
            }

            $options[$key] = $value;
            return update_option(self::OPTION_NAME, $options);
        } catch (\Exception $e) {
            error_log(sprintf("%s Option Error: %s", self::ERROR_PREFIX, $e->getMessage()));
            return false;
        }
    }

    /**
     * Deletes a plugin option
     *
     * @param string $key Option key to delete
     * @return bool Success status
     */
    public static function delete(string $key): bool {
        try {
            $options = static::all();
            
            if (!is_array($options) || !isset($options[$key])) {
                return false;
            }

            unset($options[$key]);
            return update_option(self::OPTION_NAME, $options);
        } catch (\Exception $e) {
            error_log(sprintf("%s Option Error: %s", self::ERROR_PREFIX, $e->getMessage()));
            return false;
        }
    }

    /**
     * Normalizes option types to ensure they are of the correct data type.
     *
     * This function processes an array of options to ensure that certain fields
     * are converted to boolean or numeric types as needed. It also ensures that
     * the 'blacklist' option is an array of integers if it exists.
     *
     * @param array $options Array of options to normalize.
     * @return array The normalized array of options.
     */
    public static function normalize_option_types(array $options): array {
        // Ensure boolean types with default values
        $boolean_fields = [
            'disable_autolinks' => false,
            'remove_settings' => false, 
            'enable_override' => false,
            'new_tab' => false,
            'nofollow' => false,
            'partial_match' => false,
            'bold' => false,
            'case_sensitive' => false
        ];
    
        // Set default values for boolean fields if they don't exist
        foreach ($boolean_fields as $field => $default) {
            if (!isset($options[$field])) {
                $options[$field] = $default;
            } else {
                $options[$field] = filter_var($options[$field], FILTER_VALIDATE_BOOLEAN);
            }
        }
    
        // Ensure numeric types with default values 
        $numeric_fields = [
            'auto_sync_batch_size' => 25,
            'max_links' => 3
        ];
    
        foreach ($numeric_fields as $field => $default) {
            if (!isset($options[$field])) {
                $options[$field] = $default;
            } else {
                $options[$field] = intval($options[$field]); 
            }
        }
    
        // Ensure blacklist is array of integers
        if (!isset($options['blacklist'])) {
            $options['blacklist'] = [];
        } else if (is_array($options['blacklist'])) {
            $options['blacklist'] = array_map('intval', $options['blacklist']);
        }
    
        return $options;
    }
}