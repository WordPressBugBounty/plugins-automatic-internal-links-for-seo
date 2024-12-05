<?php

namespace Pagup\AutoLinks\Controllers;

use Pagup\AutoLinks\Core\Option;
use Pagup\AutoLinks\Traits\SettingHelper;
use Pagup\AutoLinks\Controllers\DBController;
use Pagup\AutoLinks\Controllers\SettingsController;

class PagesController extends SettingsController
{
    use SettingHelper;
    
    /**
     * Add a top-level menu page for the plugin.
     *
     * This creates the "Auto Links" menu item in the WordPress admin sidebar.
     *
     * @since 1.0.0
     */
    public function add_page()
    {
        add_menu_page (
			'Automatic Internal Links for SEO',
            'Auto Links',
            'manage_options',
            'automatic-internal-links-for-seo',
			array( &$this, 'page_options' ),
            'data:image/svg+xml;base64,CjxzdmcgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4bWxuczp4bGluaz0iaHR0cDovL3d3dy53My5vcmcvMTk5OS94bGluayIgdmlld0JveD0iMCAwIDMyIDMyIj4KPHBhdGggZD0iTTE4LjcwNiAyNy41ODVhNS4yNjEgNS4yNjEgMCAwIDEtMy43MjMtOC45ODNsMS40MTUgMS40MTRhMy4yNjQgMy4yNjQgMCAxIDAgNC42MTYgNC42MTZsNi4wMy02LjAzYTMuMjY0IDMuMjY0IDAgMCAwLTQuNjE2LTQuNjE2bC0xLjQxNC0xLjQxNGE1LjI2NCA1LjI2NCAwIDAgMSA3LjQ0NCA3LjQ0NGwtNi4wMyA2LjAzYTUuMjQ2IDUuMjQ2IDAgMCAxLTMuNzIyIDEuNTM5eiIgZmlsbD0iY3VycmVudENvbG9yIj48L3BhdGg+CjxwYXRoIGQ9Ik0xMC4yNjQgMjkuOTk3YTUuMjYyIDUuMjYyIDAgMCAxLTMuNzIyLTguOTgzbDYuMDMtNi4wM2E1LjI2NCA1LjI2NCAwIDEgMSA3LjQ0NCA3LjQ0M2wtMS40MTQtMS40MTRhMy4yNjQgMy4yNjQgMCAxIDAtNC42MTYtNC42MTVsLTYuMDMgNi4wM2EzLjI2NCAzLjI2NCAwIDAgMCA0LjYxNiA0LjYxNmwxLjQxNCAxLjQxNGE1LjI0NSA1LjI0NSAwIDAgMS0zLjcyMiAxLjU0eiIgZmlsbD0iY3VycmVudENvbG9yIj48L3BhdGg+CjxwYXRoIGQ9Ik0yIDEwaDh2MkgyeiIgZmlsbD0iY3VycmVudENvbG9yIj48L3BhdGg+CjxwYXRoIGQ9Ik0yIDZoMTJ2MkgyeiIgZmlsbD0iY3VycmVudENvbG9yIj48L3BhdGg+CjxwYXRoIGQ9Ik0yIDJoMTJ2MkgyeiIgZmlsbD0iY3VycmVudENvbG9yIj48L3BhdGg+Cjwvc3ZnPgo='
		);

        add_submenu_page(
            'automatic-internal-links-for-seo',
            'Settings',  // Page title
            'Settings',  // This shows in submenu instead of "Auto Links"
            'manage_options',
            'automatic-internal-links-for-seo',  // Same as parent slug
            array(&$this, 'page_options')
        );

        add_submenu_page(
            'automatic-internal-links-for-seo',
            'Sync Posts & Pages',
            'Sync Pages',
            'manage_options',
            'automatic-internal-links-for-seo#/sync',  // Changed to new options page with hash
            array( &$this, 'page_new_options' )  // Use the same callback as your main options page
        );

        add_submenu_page(
            'automatic-internal-links-for-seo',
            'Manual Internal & External Links',
            'Manual Links',
            'manage_options',
            'automatic-internal-links-for-seo#/manual-links',  // Changed to new options page with hash
            array( &$this, 'page_new_options' )  // Use the same callback as your main options page
        );

        add_submenu_page(
            'automatic-internal-links-for-seo',
            'Activity Logs',
            'Activity Logs',
            'manage_options',
            'automatic-internal-links-for-seo#/activity-log',  // Changed to new options page with hash
            array( &$this, 'page_new_options' )  // Use the same callback as your main options page
        );

    }

    /**
     * The main entry point for the Auto Links admin page.
     * This function is responsible for rendering the page, including
     * setting up the necessary JavaScript variables, and enqueueing
     * the necessary scripts and styles.
     *
     * @return void
     */
    public function page_options()
    {

        $database = new DBController();
        $system_status = $database->check_system_status();  

        // Get list of post types to display as checkbox options
        $post_types = $this->cpts( ['attachment'] );
        $get_options = new Option;
        $options = $get_options::all();
        $blacklist = $this->blacklist();
        $options['blacklist'] = $blacklist;

        if (isset($options['post_types']) && !empty($options['post_types'])) {
            $options['post_types'] = maybe_unserialize($options['post_types']);
        }

        $post_types = $this->cpts( ['attachment'] );

        // Get blacklisted post IDs
        $blacklisted_posts = isset($options['blacklist']) ? (array)$options['blacklist'] : [];
        
        // Format blacklisted posts with titles
        $formatted_blacklist = array_map(function($post_id) {
            return [
                'id' => $post_id,
                'title' => get_the_title($post_id)
            ];
        }, $blacklisted_posts);

        // Get sync status including auto-sync information
        $auto_sync_status = $this->get_auto_sync_status();

        wp_localize_script( 'autolinks__main', 'data', array(
            'post_types' => $post_types,
            'blacklistedPosts' => $formatted_blacklist,
            'total_items_require_sync' => $this->get_total_pages_and_items(),
            'auto_sync_status' => $auto_sync_status,
            'batch_size' => $this->batch_size,
            'options' => Option::normalize_option_types($options),
            'syncDate' => get_option( "autolinks_sync" ),
            'pro' => ails__fs()->can_use_premium_code__premium_only(),
            'plugins' => $this->installable_plugins(),
            'language' => get_locale(),
            'nonce' => wp_create_nonce( 'ails__nonce' ),
            'purchase_url' => ails__fs()->get_upgrade_url(),
            'memory_limit' => $this->check_memory_limit(),
            'onboarding_status' => $this->get_onboarding_status(),
            'system_status' => $system_status
        ));

        if ($system_status['tables_recreated']) {
            add_action('admin_notices', function() use ($system_status) {
                ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php echo esc_html($system_status['message']); ?></p>
                </div>
                <?php
            });
        } elseif (!$system_status['success']) {
            add_action('admin_notices', function() use ($system_status) {
                ?>
                <div class="notice notice-error">
                    <p><?php echo esc_html($system_status['message']); ?></p>
                </div>
                <?php
            });
        }

        if (AILS_PLUGIN_MODE !== "prod") {
            echo $this->devNotification();
        }

        echo '<div id="autolinks__app"></div>';
    }
}