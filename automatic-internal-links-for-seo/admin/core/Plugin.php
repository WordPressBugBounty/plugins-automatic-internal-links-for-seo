<?php
namespace Pagup\AutoLinks\Core;

class Plugin {
    private const ERROR_PREFIX = 'Auto Links';
    private const VIEW_PATH = 'views';

    /**
     * Gets the URL for a plugin file
     *
     * @param string $filePath Relative path to the file
     * @return string Full URL to the file
     */
    public static function url(string $filePath): string {
        try {
            $baseUrl = plugins_url('', __DIR__);
            return $baseUrl . '/' . ltrim($filePath, '/');
        } catch (\Exception $e) {
            error_log(sprintf("%s Plugin Error: %s", self::ERROR_PREFIX, $e->getMessage()));
            return '';
        }
    }

    /**
     * Gets the filesystem path for a plugin file
     *
     * @param string $filePath Relative path to the file
     * @return string Full filesystem path to the file
     */
    public static function path(string $filePath): string {
        try {
            return plugin_dir_path( __DIR__ ) . "{$filePath}";
        } catch (\Exception $e) {
            error_log(sprintf("%s Plugin Error: %s", self::ERROR_PREFIX, $e->getMessage()));
            return '';
        }
    }
    
    /**
     * Renders a view PHP file, passing through any provided data
     *
     * @param string $file Name of the view file to render (without .view.php suffix)
     * @param array $data Optional data to be passed to the view
     *
     * @throws \RuntimeException If the view file can't be found
     */
    public static function view(string $file, array $data = []): void {
        try {
            $fullPath = static::path(self::VIEW_PATH . "/{$file}.view.php");
            $viewPath = realpath($fullPath);

            if (!$viewPath || !file_exists($viewPath)) {
                throw new \RuntimeException(
                    sprintf("View file not found: %s (Looking in: %s)", $file, $fullPath)
                );
            }

            if (!empty($data)) {
                extract($data, EXTR_SKIP);
            }

            require $viewPath;

        } catch (\Exception $e) {
            error_log(sprintf("%s Plugin Error: %s", self::ERROR_PREFIX, $e->getMessage()));
            if (defined('WP_DEBUG') && WP_DEBUG) {
                echo "Error loading view: " . esc_html($e->getMessage());
            }
        }
    }

    /**
     * Debug function - dumps variables and dies
     * Should only be used during development
     *
     * @param mixed ...$args Variables to dump
     * @return never
     */
    public static function dd(...$args) {
        if (!(defined('WP_DEBUG') && WP_DEBUG)) {
            die();
        }

        echo '<pre>';
        array_map(function($x) {
            var_dump($x);
            echo "\n";
        }, $args);
        echo '</pre>';
        
        die();
    }

    /**
     * Safely gets a plugin configuration value
     *
     * @param string $key Configuration key
     * @param mixed $default Default value if key not found
     * @return mixed Configuration value or default
     */
    public static function config(string $key, $default = null) {
        try {
            // Add config handling logic here if needed
            return $default;
        } catch (\Exception $e) {
            error_log(sprintf("%s Plugin Error: %s", self::ERROR_PREFIX, $e->getMessage()));
            return $default;
        }
    }

    /**
     * Checks if a file exists within the plugin directory
     *
     * @param string $filePath Relative path to the file
     * @return bool Whether file exists
     */
    public static function fileExists(string $filePath): bool {
        try {
            return file_exists(self::path($filePath));
        } catch (\Exception $e) {
            error_log(sprintf("%s Plugin Error: %s", self::ERROR_PREFIX, $e->getMessage()));
            return false;
        }
    }
}