<?php

/*
* Plugin Name: Automatic Internal Links for SEO
* Description: This fully automated plugin creates and boosts your internal linking in 2 clicks, using Yoast / Rank Math Focus keywords as anchor text for internal link building.
* Author: Pagup
* Version: 1.2.1
* Author URI: https://pagup.com/
* Text Domain: automatic-internal-links-for-seo
* Domain Path: /languages/
*/
use Pagup\AutoLinks\Controllers\SettingsController;
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
/******************************************
                Freemius Init
*******************************************/
if ( function_exists( 'ails__fs' ) ) {
    ails__fs()->set_basename( false, __FILE__ );
} else {
    if ( !function_exists( 'ails__fs' ) ) {
        if ( !defined( 'AILS_PLUGIN_BASE' ) ) {
            define( 'AILS_PLUGIN_BASE', plugin_basename( __FILE__ ) );
        }
        if ( !defined( 'AILS_PLUGIN_DIR' ) ) {
            define( 'AILS_PLUGIN_DIR', plugins_url( '', __FILE__ ) );
        }
        if ( !defined( 'AILS_TABLE' ) ) {
            define( 'AILS_TABLE', $GLOBALS['wpdb']->prefix . "auto_internal_links" );
        }
        if ( !defined( 'AILS_LOG_TABLE' ) ) {
            define( 'AILS_LOG_TABLE', $GLOBALS['wpdb']->prefix . "auto_internal_log" );
        }
        require 'vendor/autoload.php';
        // Create a helper function for easy SDK access.
        function ails__fs() {
            global $ails__fs;
            if ( !isset( $ails__fs ) ) {
                // Include Freemius SDK.
                require_once dirname( __FILE__ ) . '/vendor/freemius/start.php';
                $ails__fs = fs_dynamic_init( array(
                    'id'              => '8985',
                    'slug'            => 'automatic-internal-links-for-seo',
                    'type'            => 'plugin',
                    'public_key'      => 'pk_4ab073489df5c689f54a07bfd51d6',
                    'is_premium'      => false,
                    'premium_suffix'  => 'Pro',
                    'has_addons'      => false,
                    'has_paid_plans'  => true,
                    'trial'           => array(
                        'days'               => 7,
                        'is_require_payment' => true,
                    ),
                    'has_affiliation' => 'all',
                    'menu'            => array(
                        'slug'    => 'automatic-internal-links-for-seo',
                        'support' => false,
                    ),
                    'is_live'         => true,
                ) );
            }
            return $ails__fs;
        }

        // Init Freemius.
        ails__fs();
        // Signal that SDK was initiated.
        do_action( 'ails__fs_loaded' );
        function ails__fs_settings_url() {
            return admin_url( 'admin.php?page=automatic-internal-links-for-seo' );
        }

        ails__fs()->add_filter( 'connect_url', 'ails__fs_settings_url' );
        ails__fs()->add_filter( 'after_skip_url', 'ails__fs_settings_url' );
        ails__fs()->add_filter( 'after_connect_url', 'ails__fs_settings_url' );
        ails__fs()->add_filter( 'after_pending_connect_url', 'ails__fs_settings_url' );
        function ails__fs_custom_icon() {
            return dirname( __FILE__ ) . '/admin/assets/icon.jpg';
        }

        ails__fs()->add_filter( 'plugin_icon', 'ails__fs_custom_icon' );
        // freemius opt-in
        function ails__fs_custom_connect_message(
            $message,
            $user_first_name,
            $product_title,
            $user_login,
            $site_link,
            $freemius_link
        ) {
            $break = "<br><br>";
            $more_plugins = '<p><a target="_blank" href="https://wordpress.org/plugins/meta-tags-for-seo/">Meta Tags for SEO</a>, <a target="_blank" href="https://wordpress.org/plugins/automatic-internal-links-for-seo/">Auto internal links for SEO</a>, <a target="_blank" href="https://wordpress.org/plugins/bulk-image-alt-text-with-yoast/">Bulk auto image Alt Text</a>, <a target="_blank" href="https://wordpress.org/plugins/bulk-image-title-attribute/">Bulk auto image Title Tag</a>, <a target="_blank" href="https://wordpress.org/plugins/mobilook/">Mobile view</a>, <a target="_blank" href="https://wordpress.org/plugins/better-robots-txt/">Wordpress Better-Robots.txt</a>, <a target="_blank" href="https://wordpress.org/plugins/wp-google-street-view/">Wp Google Street View</a>, <a target="_blank" href="https://wordpress.org/plugins/vidseo/">VidSeo</a>, ...</p>';
            return sprintf( esc_html__( 'Hey %1$s, %2$s Click on Allow & Continue to optimize your internal linking and boost your Ranking on search engines. You have no idea how much this plugin will simplify your life. %2$s Never miss an important update -- opt-in to our security and feature updates notifications. %2$s See you on the other side. %2$s Looking for more Wp plugins?', 'automatic-internal-links-for-seo' ), $user_first_name, $break ) . $more_plugins;
        }

        ails__fs()->add_filter(
            'connect_message',
            'ails__fs_custom_connect_message',
            10,
            6
        );
    }
    class AutoLinksForSEO {
        function __construct() {
            $database = new \Pagup\AutoLinks\Controllers\DBController();
            register_activation_hook( __FILE__, array(&$database, 'migration') );
            add_action( 'plugins_loaded', array(&$database, 'db_check') );
            register_activation_hook( __FILE__, array(&$this, 'activate') );
            register_deactivation_hook( __FILE__, array(&$this, 'deactivate') );
            add_action( 'init', array(&$this, 'ails__textdomain') );
            $settings = new SettingsController();
            add_action(
                'ails_transients',
                array(&$settings, 'delete_transient'),
                10,
                1
            );
        }

        public function activate() {
            $options = get_option( 'automatic-internal-links-for-seo' );
            if ( !is_array( $options ) ) {
                update_option( 'automatic-internal-links-for-seo', [
                    "post_types"      => ['post', 'page'],
                    "exclude_tags"    => "h1\nh2\nh3\n#ad",
                    "remove_settings" => false,
                ] );
            }
        }

        public function deactivate() {
            global $wpdb;
            if ( \Pagup\AutoLinks\Core\Option::check( 'remove_settings' ) ) {
                // Delete Option which contains Settings data
                delete_option( 'automatic-internal-links-for-seo' );
                $manual_links_table = AILS_TABLE;
                $logs_table = AILS_LOG_TABLE;
                // Drop custom tables created by auto internal links
                $wpdb->query( "DROP TABLE IF EXISTS {$manual_links_table}, {$logs_table}" );
                // Delete transient cache
                $settings = new SettingsController();
                $settings->delete_all_transients();
            }
        }

        function ails__textdomain() {
            load_plugin_textdomain( "automatic-internal-links-for-seo", false, basename( dirname( __FILE__ ) ) . '/languages' );
        }

    }

    $ails = new AutoLinksForSEO();
    /*-----------------------------------------
                  REPLACE CONTROLLER
      ------------------------------------------*/
    include_once \Pagup\AutoLinks\Core\Plugin::path( 'admin/controllers/ReplaceController.php' );
    /*-----------------------------------------
                      Settings
      ------------------------------------------*/
    if ( is_admin() ) {
        include_once \Pagup\AutoLinks\Core\Plugin::path( 'admin/Settings.php' );
    }
}