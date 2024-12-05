<?php

namespace Pagup\AutoLinks\Bootstrap;

use Pagup\AutoLinks\Core\Option;
use Pagup\AutoLinks\Controllers\DBController;
use Pagup\AutoLinks\Controllers\SettingsController;
class PluginManager {
    private string $pluginSlug;

    private const ERROR_PREFIX = 'Auto Links';

    private const DEFAULT_OPTIONS = [
        'post_types'           => ['post', 'page'],
        'exclude_tags'         => "h1\nh2\nh3\n#ad",
        'remove_settings'      => false,
        "enable_override"      => false,
        "new_tab"              => false,
        "nofollow"             => false,
        "partial_match"        => false,
        "bold"                 => false,
        "case_sensitive"       => false,
        "max_links"            => 3,
        'auto_sync_frequency'  => 'fifteen_minutes',
        'auto_sync_batch_size' => 25,
    ];

    private const PRO_OPTIONS = [
        'auto_sync'         => true,
        'disable_autolinks' => false,
    ];

    public function __construct( string $pluginSlug ) {
        $this->pluginSlug = $pluginSlug;
    }

    public function init() : void {
        try {
            $this->registerHooks();
            $this->initializeControllers();
        } catch ( \Exception $e ) {
            error_log( sprintf( "%s Plugin Error: %s", self::ERROR_PREFIX, $e->getMessage() ) );
        }
    }

    private function registerHooks() : void {
        add_action( 'init', [$this, 'loadTextDomain'] );
    }

    private function initializeControllers() : void {
        $database = new DBController();
        $settings = new SettingsController();
        $mainFile = AILS_PLUGIN_ROOT . 'automatic-internal-links-for-seo.php';
        register_activation_hook( $mainFile, [$database, 'migration'] );
        add_action( 'plugins_loaded', [$database, 'db_check'] );
        add_action(
            'ails_transients',
            [$settings, 'delete_transient'],
            10,
            1
        );
    }

    public function activate() : void {
        try {
            $options = get_option( 'automatic-internal-links-for-seo' );
            if ( !is_array( $options ) ) {
                $defaultOptions = self::DEFAULT_OPTIONS;
                update_option( 'automatic-internal-links-for-seo', $defaultOptions );
            }
        } catch ( \Exception $e ) {
            error_log( sprintf( "%s Activation Error: %s", self::ERROR_PREFIX, $e->getMessage() ) );
        }
    }

    public function deactivate() : void {
        try {
            if ( Option::check( 'remove_settings' ) ) {
                $this->cleanupPluginData();
            }
        } catch ( \Exception $e ) {
            error_log( sprintf( "%s Deactivation Error: %s", self::ERROR_PREFIX, $e->getMessage() ) );
        }
    }

    private static function cleanupPluginData() : void {
        global $wpdb;
        delete_option( 'automatic-internal-links-for-seo' );
        delete_option( "ails_onboarding_status" );
        delete_option( "ails_last_synced_items" );
        delete_option( "autolinks_db_version" );
        delete_option( "autolinks_sync" );
        delete_option( "ails_auto_sync_offset" );
        $wpdb->query( "DROP TABLE IF EXISTS " . AILS_TABLE . ", " . AILS_LOG_TABLE );
        $settings = new SettingsController();
        $settings->delete_all_transients();
    }

    public function loadTextDomain() : void {
        load_plugin_textdomain( $this->pluginSlug, false, basename( AILS_PLUGIN_ROOT ) . '/languages' );
    }

}
