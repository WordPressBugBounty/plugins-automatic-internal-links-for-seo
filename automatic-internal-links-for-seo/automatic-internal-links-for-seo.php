<?php

/*
* Plugin Name: Automatic Internal Links for SEO
* Description: This fully automated plugin creates and boosts your internal linking in 2 clicks, using Yoast / Rank Math Focus keywords as anchor text for internal link building.
* Author: Pagup
* Version: 2.0.1
* Author URI: https://pagup.com/
* Text Domain: automatic-internal-links-for-seo
* Domain Path: /languages/
* Requires PHP: 7.4
*/
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
// Define plugin root directory
if ( !defined( 'AILS_PLUGIN_ROOT' ) ) {
    define( 'AILS_PLUGIN_ROOT', plugin_dir_path( __FILE__ ) );
}
if ( function_exists( 'ails__fs' ) ) {
    ails__fs()->set_basename( false, __FILE__ );
} else {
    if ( !function_exists( 'ails__fs' ) ) {
        function ails__fs() {
            global $ails__fs;
            if ( !isset( $ails__fs ) ) {
                require_once AILS_PLUGIN_ROOT . 'vendor/freemius/start.php';
                $ails__fs = fs_dynamic_init( [
                    'id'              => '8985',
                    'slug'            => 'automatic-internal-links-for-seo',
                    'type'            => 'plugin',
                    'public_key'      => 'pk_4ab073489df5c689f54a07bfd51d6',
                    'is_premium'      => false,
                    'premium_suffix'  => 'Pro',
                    'has_addons'      => false,
                    'has_paid_plans'  => true,
                    'trial'           => [
                        'days'               => 7,
                        'is_require_payment' => true,
                    ],
                    'has_affiliation' => 'all',
                    'menu'            => [
                        'slug'    => 'automatic-internal-links-for-seo',
                        'support' => false,
                    ],
                    'is_live'         => true,
                ] );
            }
            return $ails__fs;
        }

    }
    // Init Freemius
    ails__fs();
    do_action( 'ails__fs_loaded' );
    require_once AILS_PLUGIN_ROOT . 'vendor/autoload.php';
    // Create plugin instance
    $pluginManager = new Pagup\AutoLinks\Bootstrap\PluginManager('automatic-internal-links-for-seo');
    // Register activation hook here
    register_activation_hook( __FILE__, [$pluginManager, 'activate'] );
    register_deactivation_hook( __FILE__, [$pluginManager, 'deactivate'] );
    // Initialize the plugin
    add_action( 'plugins_loaded', function () {
        try {
            Pagup\AutoLinks\Bootstrap\Bootstrap::init();
        } catch ( \Exception $e ) {
            add_action( 'admin_notices', function () use($e) {
                ?>
                <div class="notice notice-error">
                    <p>Automatic Internal Links for SEO Error: <?php 
                echo esc_html( $e->getMessage() );
                ?></p>
                </div>
                <?php 
            } );
        }
    } );
}