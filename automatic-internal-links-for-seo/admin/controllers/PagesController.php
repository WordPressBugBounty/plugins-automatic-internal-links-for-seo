<?php

namespace Pagup\AutoLinks\Controllers;

use Pagup\AutoLinks\Core\Option;
use Pagup\AutoLinks\Core\Plugin;
use Pagup\AutoLinks\Controllers\LinksController;
use Pagup\AutoLinks\Controllers\SettingsController;

class PagesController extends SettingsController
{
    private $table = AILS_TABLE;
    private $table_log = AILS_LOG_TABLE;
    
    public function add_page()
    {
        add_menu_page (
			'Automatic Internal Links for SEO',
            'Auto Links for SEO',
            'manage_options',
            'automatic-internal-links-for-seo',
			array( &$this, 'page_settings' ),
            'dashicons-admin-links'
		);

        add_submenu_page(
            'automatic-internal-links-for-seo',
            'Activity Logs',
            'Activity Logs',
            'manage_options',
            'automatic-internal-links-logs',
			array( &$this, 'page_logs' )
        );

        add_submenu_page(
            'automatic-internal-links-for-seo',
            'Manual Internal & External Links',
            'Manual Internal Links',
            'manage_options',
            'automatic-internal-links-manual',
			array( &$this, 'page_manual_links' )
        );
    }

    public function add_settings()
    {
        add_menu_page (
			'Automatic Internal Links for SEO',
            'Auto Links for SEO',
            'manage_options',
            'automatic-internal-links-for-seo',
			array( &$this, 'page' ),
            'dashicons-admin-links'
		);
    }

    public function page_settings() {

        $safe = [ "apply_pages", "apply_posts", "apply_products", "disable_autolinks", "remove_settings", "remove_links", "settings", "help", "enable_override", "new_tab", "nofollow", "partial_match", "case_sensitive", "bold" ];

        $updated = "";

        if ( isset( $_POST['update'] ) ) {

            $updated = $this->update_settings($safe);

        }

        $deleting = "";

        if ( isset( $_POST['clear_transients'] ) ) {

            if ( function_exists( 'current_user_can' ) && !current_user_can( 'manage_options' ) )
            {    
                die( 'Sorry, not allowed...' );
            }

            check_admin_referer( 'automatic-internal-links-settings', 'ails__nonce' );

            if ( ! isset( $_POST['ails__nonce'] ) || ! wp_verify_nonce( $_POST['ails__nonce'], 'automatic-internal-links-settings' )
            ) {
                die( 'Sorry, not allowed. Nonce doesn\'t verify' );
            }

            $deleting = $this->delete_all_transients();

        }

        $options = new Option;

        $post_types = $this->cpts( ['attachment'] );

        $blacklist = $this->blacklist_urls();

        $nonce = wp_create_nonce( 'crud_link' );

        $total_items_require_sync = $this->get_total_pages_and_items();
        $total_items_in_logs = $this->get_count_from_logs_table();
        $memory_notification = $this->check_memory_limit();

        wp_localize_script( 'ails__script', 'data', array(
            'total_pages_and_items' => $total_items_require_sync,
            'batch_size' => $this->batch_size,
            'syncDate' => get_option( "autolinks_sync" ),
            'nonce' => $nonce,
        ));

        // var_dump( $options->all() );
        
        $active_tab = ( isset( $_GET['tab'] ) && in_array( $_GET['tab'], $safe ) ? sanitize_key($_GET['tab']) : 'settings' );

        if( $active_tab == 'settings' ) {
            return Plugin::view('settings', compact('options', 'total_items_require_sync', 'total_items_in_logs', 'memory_notification', 'post_types', 'blacklist', 'updated', 'deleting'));
        }

        if( $active_tab == 'help' ) {
            return Plugin::view('help');
        }

    }

    public function page_logs() {

        global $wpdb;

        // Send options data to app.js
        $items = $wpdb->get_results("SELECT * FROM $this->table_log WHERE COALESCE(Keyword, '') != '' ORDER BY updated_at DESC", OBJECT);

        $nonce = wp_create_nonce( 'crud_link' );
        
        $settings = new SettingsController;

        wp_localize_script( 'ails__script', 'data', array(
            'items' => $items,
            'total_pages_and_items' => $settings->get_total_pages_and_items(),
            'nonce' => $nonce,
        ));
        
        return Plugin::view('log', compact('items'));
    
    }

    public function page_manual_links() {

        global $wpdb;

        // Send options data to app.js
        $items = $wpdb->get_results("SELECT * FROM $this->table WHERE COALESCE(Keyword, '') != '' ORDER BY created_at DESC", OBJECT);
        
        // $options_data = Option::all();
        $nonce = wp_create_nonce( 'crud_link' );

        $links = new LinksController;

        // Get Posts Titles and URL's
        $allowed_post_types = Option::check('post_types') ? Option::get('post_types') : [];
        $pages = $links->get_items( get_posts(array(
            'post_type' => $allowed_post_types,
            'orderby'   => 'title',
            'order'   => 'ASC',
            'fields' => 'ids',
            'numberposts' => -1
        )), false, true);

        $per_page = Option::check('per_page') ? intval(Option::get('per_page')) : 10;

        // var_dump($pages);
        
        wp_localize_script( 'ails__script', 'data', array(
            'items' => $items,
            'all_pages' => $pages,
            'per_page' => $per_page,
            'nonce' => $nonce,
        ));
        
        return Plugin::view('links');
    
    }
}