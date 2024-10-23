<?php

namespace Pagup\AutoLinks;

use Pagup\AutoLinks\Core\Asset;
class Settings {
    public function __construct() {
        $pages = new \Pagup\AutoLinks\Controllers\PagesController();
        $links = new \Pagup\AutoLinks\Controllers\LinksController();
        $metabox = new \Pagup\AutoLinks\Controllers\MetaboxController();
        // Create link
        add_action( 'wp_ajax_ails_create_link', array(&$links, 'create_link') );
        // Add Bulk links
        add_action( 'wp_ajax_ails_bulk_add', array(&$links, 'bulk_add') );
        // Sync Date
        add_action( 'wp_ajax_ails_sync_date', array(&$links, 'sync_date') );
        // Update link
        add_action( 'wp_ajax_ails_update_link', array(&$links, 'update_link') );
        // Update status
        add_action( 'wp_ajax_ails_update_status', array(&$links, 'update_status') );
        // Delete link
        add_action( 'wp_ajax_ails_delete_item', array(&$links, 'delete_item') );
        // Bulk fetch
        add_action( 'wp_ajax_ails_bulk_fetch', array(&$links, 'bulk_fetch') );
        // Add links and logs page
        add_action( 'admin_menu', array(&$pages, 'add_page') );
        // Add setting link to plugin page
        $plugin_base = AILS_PLUGIN_BASE;
        add_filter( "plugin_action_links_{$plugin_base}", array(&$this, 'setting_link') );
        // Add styles and scripts
        add_action( 'admin_enqueue_scripts', array(&$this, 'assets'), 999 );
        add_action( 'wp_footer', array(&$this, 'app_script') );
        add_filter(
            'script_loader_tag',
            array(&$this, 'add_module_to_script'),
            10,
            3
        );
    }

    public function setting_link( $links ) {
        array_unshift( $links, '<a href="admin.php?page=automatic-internal-links-for-seo">Settings</a>' );
        return $links;
    }

    public function assets() {
        if ( isset( $_GET['page'] ) && !empty( $_GET['page'] ) && ($_GET['page'] === "automatic-internal-links-for-seo" || $_GET['page'] === "automatic-internal-links-logs" || $_GET['page'] === "automatic-internal-links-manual") ) {
            Asset::style_remote( 'ails__font', 'https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap' );
            Asset::style( 'ails__selectcss', 'vendor/css/vue-select.css' );
            Asset::style( 'ails__flexboxgrid', 'admin/assets/flexboxgrid.min.css' );
            Asset::style( 'ails__styles', 'admin/assets/app.css' );
            Asset::script(
                'ails__qs',
                'vendor/js/qs.min.js',
                array(),
                true
            );
            Asset::script(
                'ails__axios',
                'vendor/js/axios.min.js',
                array(),
                true
            );
            Asset::script(
                'ails__vuejs',
                'vendor/js/vue.min.js',
                array(),
                true
            );
            Asset::script(
                'ails__vueselect',
                'vendor/js/vue-select.js',
                array(),
                true
            );
            Asset::script(
                'ails__sweetalert',
                'vendor/js/sweetalert2.js',
                array(),
                true
            );
            Asset::script(
                'ails__paginate',
                'vendor/js/vue-paginate.min.js',
                array(),
                true
            );
            Asset::script(
                'ails__script',
                'admin/js/app.js',
                array('moment'),
                true
            );
        }
    }

    public function add_module_to_script( $tag, $handle ) {
        if ( 'ails__script' === $handle ) {
            $tag = str_replace( 'type="text/javascript"', "", $tag );
            $tag = str_replace( "type='text/javascript'", "", $tag );
            $tag = str_replace( ' src', ' type="module" src', $tag );
            return $tag;
        }
        return $tag;
    }

}

$settings = new Settings();