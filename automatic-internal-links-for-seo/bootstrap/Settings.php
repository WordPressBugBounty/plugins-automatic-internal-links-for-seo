<?php

namespace Pagup\AutoLinks\Bootstrap;

use Pagup\AutoLinks\Controllers\{
    SettingsController,
    PagesController,
    LinksController,
    MetaboxController,
    BulkOperationsController,
    AutoSyncController,
    CronController
};
use Pagup\AutoLinks\Core\Asset;
use Pagup\AutoLinks\Traits\ErrorHandler;
class Settings {
    use ErrorHandler;
    private const PLUGIN_PAGE = 'automatic-internal-links-for-seo';

    private const ERROR_PREFIX = 'Auto Links';

    private SettingsController $settingsController;

    private PagesController $pagesController;

    private LinksController $linksController;

    private MetaboxController $metaboxController;

    private BulkOperationsController $bulkController;

    private AutoSyncController $autoSyncController;

    private CronController $cronController;

    /**
     * Initialize the plugin settings
     */
    public function __construct() {
        $this->initializeControllers();
        $this->registerHooks();
        $this->registerAjaxHandlers();
    }

    /**
     * Initialize controller instances
     */
    private function initializeControllers() : void {
        $this->settingsController = new SettingsController();
        $this->pagesController = new PagesController();
        $this->linksController = new LinksController();
        $this->metaboxController = new MetaboxController();
        $this->bulkController = new BulkOperationsController();
        $this->autoSyncController = new AutoSyncController();
        $this->cronController = new CronController();
    }

    /**
     * Register WordPress hooks
     */
    private function registerHooks() : void {
        // Menu and Assets
        add_action( 'admin_menu', [$this->pagesController, 'add_page'] );
        add_action( 'admin_menu', [$this, 'add_custom_menu_badge'] );
        add_action( 'admin_enqueue_scripts', [$this, 'assets'], 999 );
        add_action( 'wp_footer', [$this, 'app_script'] );
        // Plugin Settings
        add_filter( "plugin_action_links_" . AILS_PLUGIN_BASE, [$this, 'setting_link'] );
        add_filter(
            'script_loader_tag',
            [$this, 'add_module_to_script'],
            10,
            3
        );
        // Cron Related
        add_filter( 'cron_schedules', [$this->cronController, 'register_schedules'] );
        add_action( 'ails_auto_sync_event', [$this->autoSyncController, 'process_auto_sync'] );
        // Options Update
        add_action(
            'update_option_' . self::PLUGIN_PAGE,
            [$this, 'handle_auto_sync_option_update'],
            10,
            2
        );
    }

    /**
     * Register AJAX handlers
     */
    private function registerAjaxHandlers() : void {
        // Settings Actions
        $this->addAjaxAction( 'ails_save_options', [$this->settingsController, 'save_options'] );
        $this->addAjaxAction( 'ails_search_posts', [$this->settingsController, 'search_posts_callback'], true );
        $this->addAjaxAction( 'ails_update_onboarding', [$this->settingsController, 'update_onboarding'] );
        // Bulk Operations
        $this->addAjaxAction( 'ails_bulk_add', [$this->bulkController, 'add'] );
        $this->addAjaxAction( 'ails_bulk_fetch', [$this->bulkController, 'fetch'] );
        // Link Management
        $this->addAjaxAction( 'ails_create_link', [$this->linksController, 'create_link'] );
        $this->addAjaxAction( 'ails_sync_date', [$this->linksController, 'sync_date'] );
        $this->addAjaxAction( 'ails_update_link', [$this->linksController, 'update_link'] );
        $this->addAjaxAction( 'ails_update_status', [$this->linksController, 'update_status'] );
        $this->addAjaxAction( 'ails_delete_item', [$this->linksController, 'delete_item'] );
        $this->addAjaxAction( 'ails_bulk_delete_items', [$this->linksController, 'bulk_delete_items'] );
        // Data Retrieval
        $this->addAjaxAction( 'ails_get_manual_links', [$this->linksController, 'get_manual_links'] );
        $this->addAjaxAction( 'ails_get_activity_logs', [$this->linksController, 'get_activity_logs'] );
        // Delete Transients
        $this->addAjaxAction( 'ails_delete_transients', function () {
            $this->settingsController->delete_all_transients( true );
        } );
        // Cron Testing
        $this->addAjaxAction( 'ails_test_cron', [$this->cronController, 'test_trigger'] );
    }

    /**
     * Initialize premium features
     */
    private function initializePremiumFeatures() : void {
        add_action( 'add_meta_boxes', [$this->metaboxController, 'add_metabox'] );
        add_action( 'save_post', [$this->metaboxController, 'metadata'] );
        add_action(
            'updated_post_meta',
            [$this->metaboxController, 'insert_update_data'],
            1000,
            4
        );
    }

    /**
     * Add an AJAX action with error handling
     *
     * @param string $action The AJAX action name
     * @param callable $callback The callback function
     * @param boolean $nopriv Whether to add nopriv action
     */
    private function addAjaxAction( string $action, callable $callback, bool $nopriv = false ) : void {
        $hook = "wp_ajax_{$action}";
        $unique_id = uniqid();
        add_action(
            $hook,
            function () use($action, $callback, $unique_id) {
                // error_log("[{$unique_id}] Action wp_ajax_{$action} starting");
                ob_start();
                try {
                    $result = call_user_func( $callback );
                    $output = ob_get_clean();
                    if ( !empty( $output ) ) {
                        error_log( "[{$unique_id}] Unexpected output before response: " . $output );
                    }
                    error_log( "[{$unique_id}] Action wp_ajax_{$action} completed with result: " . print_r( $result, true ) );
                } catch ( \Exception $e ) {
                    ob_end_clean();
                    error_log( "[{$unique_id}] Error in action wp_ajax_{$action}: " . $e->getMessage() );
                    wp_send_json_error( $e->getMessage() );
                }
            },
            10,
            0
        );
        if ( $nopriv ) {
            add_action( "wp_ajax_nopriv_{$action}", $callback );
        }
    }

    /**
     * Handle auto sync option updates
     */
    public function handle_auto_sync_option_update( $old_value, $new_value ) : void {
        $old_auto_sync = $old_value['auto_sync'] ?? false;
        $new_auto_sync = $new_value['auto_sync'] ?? false;
        if ( $old_auto_sync !== $new_auto_sync ) {
            ( $new_auto_sync ? $this->autoSyncController->schedule_sync() : $this->autoSyncController->unschedule_sync() );
        }
    }

    /**
     * Add settings link to plugins page
     */
    public function setting_link( array $links ) : array {
        array_unshift( $links, sprintf( '<a href="admin.php?page=%s">Settings</a>', self::PLUGIN_PAGE ) );
        return $links;
    }

    /**
     * Enqueue assets for the plugin
     */
    public function assets() : void {
        if ( !$this->is_plugin_page() ) {
            return;
        }
        if ( AILS_PLUGIN_MODE === "dev" ) {
            $this->enqueue_development_assets();
        } else {
            $this->enqueue_production_assets();
        }
    }

    /**
     * Check if current page is plugin page
     */
    private function is_plugin_page() : bool {
        return isset( $_GET['page'] ) && !empty( $_GET['page'] ) && $_GET['page'] === self::PLUGIN_PAGE;
    }

    /**
     * Enqueue production assets
     */
    private function enqueue_production_assets() : void {
        Asset::style( 'autolinks__styles', 'admin/ui/index.css' );
        Asset::script(
            'autolinks__main',
            'admin/ui/index.js',
            ['wp-i18n'],
            true
        );
    }

    /**
     * Enqueue development assets
     */
    private function enqueue_development_assets() : void {
        Asset::script_remote(
            'autolinks__client',
            'http://localhost:5173/@vite/client',
            [],
            true,
            true
        );
        Asset::script_remote(
            'autolinks__main',
            'http://localhost:5173/src/main.ts',
            [],
            true,
            true
        );
    }

    /**
     * Add module type to script tags
     */
    public function add_module_to_script( string $tag, string $handle ) : string {
        $module_scripts = ['ails__script', 'autolinks__main', 'autolinks__client'];
        if ( in_array( $handle, $module_scripts ) ) {
            $tag = preg_replace( '/type=[\'"]text\\/javascript[\'"]/', '', $tag );
            return str_replace( ' src', ' type="module" src', $tag );
        }
        return $tag;
    }

    /**
     * Add custom badge to menu item
     */
    public function add_custom_menu_badge() : void {
        global $menu, $wpdb;
        // Check if the required tables exist first
        $log_table = AILS_LOG_TABLE;
        $table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$log_table}'" ) === $log_table;
        // Only proceed if table exists
        if ( $table_exists ) {
            $total_items = $this->settingsController->get_total_pages_and_items()["items"];
            $display_number = $this->get_display_number( $total_items );
            if ( !$display_number ) {
                return;
            }
            foreach ( $menu as $key => $item ) {
                if ( $item[2] === self::PLUGIN_PAGE ) {
                    $menu[$key][0] .= sprintf( ' <span class="update-plugins count-1"><span class="plugin-count">%s</span></span>', esc_html( $display_number ) );
                    break;
                }
            }
        }
    }

    /**
     * Get display number for badge
     */
    private function get_display_number( int $total_items ) {
        if ( $total_items >= 100 ) {
            return '99+';
        }
        if ( $total_items > 0 ) {
            return (string) $total_items;
        }
        return false;
    }

}
