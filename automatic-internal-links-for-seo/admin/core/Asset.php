<?php
namespace Pagup\AutoLinks\Core;
use Pagup\AutoLinks\Core\Plugin;

class Asset {
    private const ERROR_PREFIX = 'Auto Links Asset';

    /**
     * Registers and enqueues a local stylesheet
     *
     * @param string $name Handle name for the stylesheet
     * @param string $file Path to the stylesheet file
     * @throws \RuntimeException If file doesn't exist
     */
    public static function style(string $handle, string $path, array $deps = [], bool $inFooter = false): void {
        try {
            $fullPath = self::getAssetPath($path);
            if (!file_exists($fullPath)) {
                throw new \Exception("Stylesheet file not found: {$path}");
            }

            $url = plugins_url($path, dirname(__DIR__));
            wp_enqueue_style($handle, $url, $deps, filemtime($fullPath));
        } catch (\Exception $e) {
            error_log(sprintf("%s Error: %s", self::ERROR_PREFIX, $e->getMessage()));
        }
    }   

    /**
     * Registers and enqueues a remote stylesheet
     *
     * @param string $name Handle name for the stylesheet
     * @param string $url Remote URL of the stylesheet
     */
    public static function style_remote(string $name, string $url): void {
        try {
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                throw new \RuntimeException("Invalid remote stylesheet URL: {$url}");
            }

            wp_register_style($name, esc_url($url));
            wp_enqueue_style($name);
        } catch (\Exception $e) {
            error_log("Auto Links Asset Error: {$e->getMessage()}");
        }
    }

    public static function script(string $handle, string $path, array $deps = [], bool $inFooter = false): void {
        try {
            $fullPath = self::getAssetPath($path);
            if (!file_exists($fullPath)) {
                throw new \Exception("Script file not found: {$path}");
            }

            $url = plugins_url($path, dirname(__DIR__));
            wp_enqueue_script($handle, $url, $deps, filemtime($fullPath), $inFooter);
        } catch (\Exception $e) {
            error_log(sprintf("%s Error: %s", self::ERROR_PREFIX, $e->getMessage()));
        }
    }

    /**
     * Registers and enqueues a remote script
     *
     * @param string $name Handle name for the script
     * @param string $url Remote URL of the script
     * @param array $dependencies Array of script dependencies
     * @param string|null $version Version number of the script
     * @param bool $footer Whether to enqueue in footer
     *
     * @throws \RuntimeException If the remote URL is invalid
     */
    public static function script_remote(
        string $name,
        string $url,
        array $dependencies = [],
        $version = null,
        bool $footer = false
    ): void {
        try {
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                throw new \RuntimeException("Invalid remote script URL: {$url}");
            }

            wp_register_script(
                $name,
                esc_url($url),
                $dependencies,
                $version,
                $footer
            );

            wp_enqueue_script($name);
        } catch (\Exception $e) {
            error_log("Auto Links Asset Error: {$e->getMessage()}");
        }
    }

    private static function getAssetPath(string $path): string {
        $pluginDir = plugin_dir_path(dirname(__DIR__)); // Get plugin root directory
        return $pluginDir . $path;
    }
}