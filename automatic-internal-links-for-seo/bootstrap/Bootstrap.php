<?php
namespace Pagup\AutoLinks\Bootstrap;

use Pagup\AutoLinks\Bootstrap\Settings;
use Pagup\AutoLinks\Bootstrap\PluginManager;
use Pagup\AutoLinks\Bootstrap\FreemiusManager;
use Pagup\AutoLinks\Controllers\ReplaceController;

class Bootstrap {
    /**
     * Plugin configuration
     */
    private const PLUGIN_CONFIG = [
        // Basic plugin info
        'id' => '8985',
        'slug' => 'automatic-internal-links-for-seo',
        'public_key' => 'pk_4ab073489df5c689f54a07bfd51d6',
        'error_prefix' => 'Auto Links',
        
        'messages' => []
    ];

    /**
     * @var FreemiusManager
     */
    private static FreemiusManager $freemiusManager;

    /**
     * @var PluginManager
     */
    private static PluginManager $pluginManager;

    /**
     * Initialize the plugin
     */
    public static function init(): void {
        try {
            self::defineConstants();
            self::initializePlugin();
        } catch (\Exception $e) {
            error_log(sprintf("%s Bootstrap Error: %s", self::PLUGIN_CONFIG['error_prefix'], $e->getMessage()));
        }
    }

    /**
     * Define plugin constants
     */
    private static function defineConstants(): void {
        $constants = [
            'AILS_PLUGIN_BASE' => plugin_basename(AILS_PLUGIN_ROOT . 'automatic-internal-links-for-seo.php'),
            'AILS_PLUGIN_DIR' => plugins_url('', AILS_PLUGIN_ROOT),
            'AILS_TABLE' => $GLOBALS['wpdb']->prefix . "auto_internal_links",
            'AILS_LOG_TABLE' => $GLOBALS['wpdb']->prefix . "auto_internal_log",
            'AILS_PLUGIN_MODE' => "prod"
        ];

        foreach ($constants as $name => $value) {
            if (!defined($name)) {
                define($name, $value);
            }
        }
    }

    /**
     * Initialize plugin managers
     */
    private static function initializePlugin(): void {
        // Initialize Freemius customization
        self::$freemiusManager = new FreemiusManager(
            self::PLUGIN_CONFIG['id'],
            self::PLUGIN_CONFIG['slug'],
            self::PLUGIN_CONFIG['messages']
        );
        self::$freemiusManager->init();
    
        // Initialize plugin manager
        self::$pluginManager = new PluginManager(self::PLUGIN_CONFIG['slug']);
        self::$pluginManager->init();
    
        // Initialize admin functionality
        if (is_admin()) {
            new Settings();
        }
    
        // Initialize ReplaceController
        new ReplaceController();
    }

    /**
     * Get plugin configuration
     */
    public static function getConfig(string $key): string {
        return self::PLUGIN_CONFIG[$key] ?? '';
    }
}