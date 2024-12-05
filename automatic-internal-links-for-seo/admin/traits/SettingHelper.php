<?php

namespace Pagup\AutoLinks\Traits;

use Pagup\AutoLinks\Core\Option;
use Pagup\AutoLinks\Core\Request;
trait SettingHelper
{
    public function focus_keyword( string $focus_keyword = '' ) {
        if ( class_exists( 'WPSEO_Meta' ) ) {
            return '_yoast_wpseo_focuskw';
        } elseif ( class_exists( 'RankMath' ) ) {
            return 'rank_math_focus_keyword';
        } elseif ( function_exists( 'aioseo' ) ) {
            return 'aioseo_table';
        }
        return '';
    }

    /**
     * Sanitizes option values based on predefined rules and a list of safe values.
     *
     * @param array $options Array of options to be sanitized.
     * @param array $safe Array of values considered safe for certain options.
     * @return array Sanitized array of options.
     */
    public function sanitize_options( array $options ) : array {
        $sanitized = [];
        // Handle post_types specially
        if ( isset( $options['post_types'] ) ) {
            $sanitized['post_types'] = maybe_serialize( Request::array( $options['post_types'] ) );
        } else {
            $sanitized['post_types'] = maybe_serialize( ['post', 'page'] );
        }
        // Process non-pro options
        foreach ( $options as $key => $value ) {
            // Skip already processed keys
            if ( in_array( $key, [
                'post_types',
                'auto_sync',
                'auto_sync_frequency',
                'auto_sync_batch_size',
                'disable_autolinks'
            ] ) ) {
                continue;
            }
            if ( in_array( $key, ['exclude_tags', 'exclude_keywords'] ) ) {
                $value = sanitize_textarea_field( trim( $value ) );
                if ( $key === 'exclude_tags' ) {
                    $value = str_replace( ' ', '', $value );
                }
                $sanitized[$key] = $value;
            } elseif ( $key === 'blacklist' ) {
                $sanitized[$key] = maybe_serialize( Request::array( $value ) );
            } elseif ( in_array( $value, ['true'] ) || $key === 'max_links' ) {
                $sanitized[$key] = sanitize_text_field( $value );
            } else {
                $sanitized[$key] = "";
            }
        }
        // Set defaults based on pro status
        $defaults = [
            'exclude_tags'     => '',
            'exclude_keywords' => '',
            'blacklist'        => maybe_serialize( [] ),
        ];
        foreach ( $defaults as $key => $value ) {
            if ( !isset( $sanitized[$key] ) ) {
                $sanitized[$key] = $value;
            }
        }
        return $sanitized;
    }

    /**
     * Sanitizes an array of link data based on predefined rules and a list of safe values.
     *
     * @param array $data An array of link data to be sanitized.
     * @return array The sanitized array of link data.
     */
    public function sanitize_link_data( array $data ) : array {
        $sanitized = [];
        // Define the expected order of fields
        $field_order = [
            'post_id',
            'title',
            'keyword',
            'url',
            'use_custom',
            'new_tab',
            'nofollow',
            'partial_match',
            'bold',
            'case_sensitive',
            'priority',
            'max_links',
            'post_type'
        ];
        $boolean_fields = [
            'use_custom',
            'new_tab',
            'nofollow',
            'partial_match',
            'bold',
            'case_sensitive'
        ];
        $text_fields = ['keyword', 'priority', 'max_links'];
        // Handle post_id, url, and title specially
        $post_id = ( isset( $data['post_id'] ) ? intval( $data['post_id'] ) : 0 );
        // $sanitized['id'] = isset($data['id']) ? intval($data['id']) : 0;
        if ( !empty( $post_id ) ) {
            $sanitized['post_id'] = $post_id;
            $sanitized['url'] = get_permalink( $post_id );
            $sanitized['title'] = get_the_title( $post_id );
            $sanitized['post_type'] = $this->post_type( $post_id );
        } else {
            $sanitized['post_id'] = 0;
            $sanitized['url'] = esc_url_raw( $data['url'] ?? '' );
            $sanitized['title'] = sanitize_text_field( $data['title'] ?? '' );
            $sanitized['post_type'] = 'Custom';
        }
        // Sanitize other fields
        foreach ( $field_order as $field ) {
            if ( !isset( $sanitized[$field] ) ) {
                if ( in_array( $field, $boolean_fields ) ) {
                    $sanitized[$field] = ( isset( $data[$field] ) ? ( filter_var( $data[$field], FILTER_VALIDATE_BOOLEAN ) ? 1 : 0 ) : 0 );
                } elseif ( in_array( $field, $text_fields ) ) {
                    $sanitized[$field] = ( isset( $data[$field] ) ? sanitize_text_field( $data[$field] ) : '' );
                }
            }
        }
        // Ensure all fields are present, even if they weren't in the input data
        foreach ( $field_order as $field ) {
            if ( !isset( $sanitized[$field] ) ) {
                $sanitized[$field] = '';
            }
        }
        // Return the sanitized data in the correct order
        return array_merge( array_flip( $field_order ), $sanitized );
    }

    /**
     * Get the list of blacklist URL's string from Options, converts it to an array, and use the array map function to convert each URL to ID.
     * 
     * @return array
     */
    public function blacklist() : array {
        $blacklist = ( Option::check( 'blacklist' ) ? maybe_unserialize( Option::get( 'blacklist' ) ) : [] );
        if ( is_array( $blacklist ) ) {
            return $blacklist;
        }
        $urls_array = explode( "\n", str_replace( "\r", "", $blacklist ) );
        // Convert URL's to Id's, skipping URLs that don't return an ID
        $ids_array = array();
        foreach ( $urls_array as $link ) {
            $post_id = url_to_postid( $link );
            if ( $post_id > 0 ) {
                $ids_array[] = $post_id;
            }
        }
        return $ids_array;
    }

    /**
     * Retrieves a string of allowed post types, formatted for use in SQL queries.
     *
     * Global Variables:
     * @global wpdb $wpdb WordPress database abstraction object.
     * @return string A string of post types formatted for an SQL IN clause.
     */
    public function post_types() : string {
        global $wpdb;
        $allowed_post_types = ( Option::check( 'post_types' ) ? maybe_unserialize( Option::get( 'post_types' ) ) : [] );
        if ( in_array( 'product', $allowed_post_types ) ) {
            unset($allowed_post_types[array_search( 'product', $allowed_post_types )]);
        }
        // Create a string of placeholders and prepare the whole list of post types
        $placeholders = implode( ', ', array_fill( 0, count( $allowed_post_types ), '%s' ) );
        $post_types = $wpdb->prepare( $placeholders, $allowed_post_types );
        // $post_types is now a string ready to use in an IN clause
        return $post_types;
    }

    /**
     * Retrieves custom post types (CPTs), optionally excluding specified types.
     *
     * @param array $excludes An array of post type names to exclude from the results.
     * @return array An associative array of custom post types, excluding specified types,
     */
    public function cpts( $excludes = [] ) {
        // All CPTs.
        $post_types = get_post_types( array(
            'public' => true,
        ), 'objects' );
        // remove Excluded CPTs from All CPTs.
        foreach ( $excludes as $exclude ) {
            unset($post_types[$exclude]);
        }
        $types = [];
        foreach ( $post_types as $post_type ) {
            $label = get_post_type_labels( $post_type );
            $types[$label->name] = $post_type->name;
        }
        return $types;
    }

    /**
     * Get post type label from post type object
     * 
     * @param int $post_id
     * @return string
     */
    public function post_type( $post_id ) {
        $post_type_obj = get_post_type_object( get_post_type( $post_id ) );
        return $post_type_obj->labels->singular_name;
    }

    /**
     * Generates a URL for installing a specific WordPress plugin.
     *
     * @param string $plugin_slug The slug of the plugin to install.
     * @return string The URL for installing the specified WordPress plugin.
     */
    public function plugin_install_url( $plugin_slug ) {
        // Generate a nonce specifically for this plugin installation
        $nonce = wp_create_nonce( 'install-plugin_' . $plugin_slug );
        // Create the URL for installing the plugin
        $url = admin_url( "update.php?action=install-plugin&plugin=" . $plugin_slug . "&_wpnonce=" . $nonce );
        return $url;
    }

    /**
     * Checks if a plugin with a given slug is installed.
     *
     * @param string $plugin_slug The slug of the plugin to check.
     * @return bool True if the plugin is installed, false otherwise.
     */
    public function is_plugin_installed( $plugin_slug ) {
        // Include the plugin.php file if it's not already included
        if ( !function_exists( 'get_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        // Get all installed plugins
        $installed_plugins = get_plugins();
        // Loop through the installed plugins
        foreach ( $installed_plugins as $path => $details ) {
            // The plugin slug is typically the first segment of the path
            if ( strpos( $path, $plugin_slug ) === 0 ) {
                return true;
            }
        }
        return false;
    }

    /**
     * List of installable plugins
     *
     * @return array Array of objects with url string & installed boolean value for each plugin
     */
    public function installable_plugins() {
        return [
            'bialty'   => [
                'url'       => $this->plugin_install_url( 'bulk-image-alt-text-with-yoast' ),
                'installed' => $this->is_plugin_installed( 'bulk-image-alt-text-with-yoast' ),
            ],
            'bigta'    => [
                'url'       => $this->plugin_install_url( 'bulk-image-title-attribute' ),
                'installed' => $this->is_plugin_installed( 'bulk-image-title-attribute' ),
            ],
            'autofkw'  => [
                'url'       => $this->plugin_install_url( 'auto-focus-keyword-for-seo' ),
                'installed' => $this->is_plugin_installed( 'auto-focus-keyword-for-seo' ),
            ],
            'massPing' => [
                'url'       => $this->plugin_install_url( 'mass-ping-tool-for-seo' ),
                'installed' => $this->is_plugin_installed( 'mass-ping-tool-for-seo' ),
            ],
            'metaTags' => [
                'url'       => $this->plugin_install_url( 'meta-tags-for-seo' ),
                'installed' => $this->is_plugin_installed( 'meta-tags-for-seo' ),
            ],
            'appAds'   => [
                'url'       => $this->plugin_install_url( 'app-ads-txt' ),
                'installed' => $this->is_plugin_installed( 'app-ads-txt' ),
            ],
        ];
    }

    public function devNotification() {
        return '<div class="ep-alert ep-alert--error is-light" role="alert" style="width: 99%; margin-top: 1rem; font-weight: 700"><i class="ep-icon ep-alert__icon"><svg style="height: 1em; width: 1em;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1024 1024"><path fill="currentColor" d="M512 64a448 448 0 1 1 0 896 448 448 0 0 1 0-896m0 192a58.432 58.432 0 0 0-58.24 63.744l23.36 256.384a35.072 35.072 0 0 0 69.76 0l23.296-256.384A58.432 58.432 0 0 0 512 256m0 512a51.2 51.2 0 1 0 0-102.4 51.2 51.2 0 0 0 0 102.4"></path></svg></i><div class="ep-alert__content"><span class="ep-alert__title">PLUGIN IS RUNNING IN DEVELOPMENT MODE</span></div></div>';
    }

}