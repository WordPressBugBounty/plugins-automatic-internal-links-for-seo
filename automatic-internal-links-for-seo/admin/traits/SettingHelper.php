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
        } elseif ( defined( 'SEOPRESS_VERSION' ) || function_exists( 'seopress_get_service' ) ) {
            return '_seopress_analysis_target_kw';
        } elseif ( function_exists( 'aioseo' ) ) {
            return 'aioseo_table';
        }
        return '';
    }

    public function get_focus_keyword_value( int $post_id ) : string {
        $focus_keyword_type = $this->focus_keyword();

        if ( $focus_keyword_type === '' ) {
            return '';
        }

        if ( $focus_keyword_type === 'aioseo_table' ) {
            global $wpdb;

            $keyphrases = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT keyphrases FROM {$wpdb->prefix}aioseo_posts WHERE post_id = %d",
                    $post_id
                )
            );

            return $this->extract_aioseo_focus_keyword( $keyphrases );
        }

        return (string) get_post_meta( $post_id, $focus_keyword_type, true );
    }

    private function extract_aioseo_focus_keyword( $keyphrases ) : string {
        if ( empty( $keyphrases ) || !is_string( $keyphrases ) ) {
            return '';
        }

        $data = json_decode( $keyphrases, true );

        if ( !is_array( $data ) || empty( $data['focus']['keyphrase'] ) ) {
            return '';
        }

        return sanitize_text_field( $data['focus']['keyphrase'] );
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
        // ADD MENU BADGE DISABLE OPTION
        if ( isset( $options['disable_menu_badge'] ) ) {
            $sanitized['disable_menu_badge'] = filter_var( $options['disable_menu_badge'], FILTER_VALIDATE_BOOLEAN );
        } else {
            $sanitized['disable_menu_badge'] = false;
            // Default: menu badge enabled
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
            'exclude_tags'        => '',
            'exclude_keywords'    => '',
            'blacklist'           => maybe_serialize( [] ),
            'disable_sync_badges' => false,
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
     * Get the IDs of the posts excluded from auto-linking.
     *
     * The settings screen stores an array of post IDs, which
     * sanitize_options() serialises before saving. get_option() only
     * unserialises the option envelope, so that inner value still arrives
     * here as a serialised string and has to be decoded explicitly.
     *
     * The newline-separated URL branch is kept for configurations saved
     * before the post selector replaced the free-text field.
     *
     * @return array
    */
    public function blacklist(): array
    {
        if ( ! Option::check('blacklist') ) {
            return array();
        }

        $blacklist = Option::get('blacklist');

        if ( is_string($blacklist) && is_serialized($blacklist) ) {
            $members = $this->blacklist_members($blacklist);

            if ( null === $members ) {
                return array();
            }

            return $this->blacklist_post_ids($members);
        }

        if ( is_array($blacklist) ) {
            return $this->blacklist_post_ids($blacklist);
        }

        if ( ! is_string($blacklist) ) {
            return array();
        }

        // Convert URL's to Id's, skipping URLs that don't return an ID
        $ids_array = array();

        foreach ( explode("\n", str_replace("\r", "", $blacklist)) as $link ) {
            $post_id = url_to_postid($link);

            if ( $post_id > 0 ) {
                $ids_array[] = $post_id;
            }
        }

        return $ids_array;
    }

    /**
     * Read a stored blacklist without handing it to unserialize().
     *
     * Only one shape is accepted: a flat array of scalars with no references
     * and no nesting. Keys must be non-negative integers, representable and
     * distinct; they are not required to run 0..n-1, and the values come back
     * re-indexed in reading order, so a sparse list is read as the list of its
     * values. The reader follows the declared byte length of every string,
     * checks the member count and the keys, and requires the whole input to be
     * consumed.
     * Nothing is decoded that it did not read itself, so an object or an enum
     * token can neither be instantiated nor autoloaded, and a member whose
     * declared length lies is refused instead of followed.
     *
     * Back references (R: and r:) are refused on purpose. The settings screen
     * never emits one, since values pass through Request::array() first. A
     * configuration written programmatically with two references to the same
     * scalar does serialise with one, and is not supported: the whole list is
     * then refused rather than partially read.
     *
     * A pattern cannot do this: it sees delimiters, while unserialize() obeys
     * declared lengths, so the two disagree on exactly the inputs that matter.
     *
     * @param string $data
     * @return array|null The members, or null when the shape is not accepted.
     */
    private function blacklist_members(string $data)
    {
        $length = strlen($data);

        if ( 0 !== strpos($data, 'a:') ) {
            return null;
        }

        $colon = strpos($data, ':', 2);

        if ( false === $colon ) {
            return null;
        }

        $count = substr($data, 2, $colon - 2);

        // Validated and converted once. serialize() never pads a count, so a
        // leading zero is refused, and the cast is checked for an exact round
        // trip so an absurd count cannot clamp into a plausible one.
        if ( '' === $count || ! ctype_digit($count) || (string) (int) $count !== $count ) {
            return null;
        }

        $count = (int) $count;
        $cursor = $colon + 1;

        if ( '{' !== substr($data, $cursor, 1) ) {
            return null;
        }

        $cursor++;
        $members = array();
        $seen_keys = array();

        for ( $index = 0; $index < $count; $index++ ) {
            // Key. The list this plugin writes only ever has integer keys.
            if ( 'i:' !== substr($data, $cursor, 2) ) {
                return null;
            }

            $semicolon = strpos($data, ';', $cursor + 2);

            if ( false === $semicolon ) {
                return null;
            }

            $key = substr($data, $cursor + 2, $semicolon - $cursor - 2);

            // A key must be a representable integer, and no key may repeat:
            // PHP keeps the last value for a repeated key, and a reader that
            // kept both would invent a list the stored value does not hold.
            if ( '' === $key || ! ctype_digit($key) || (string) (int) $key !== $key || isset($seen_keys[$key]) ) {
                return null;
            }

            $seen_keys[$key] = true;
            $cursor = $semicolon + 1;
            $token = substr($data, $cursor, 2);

            if ( 'N;' === $token ) {
                $members[] = null;
                $cursor += 2;
                continue;
            }

            if ( 'i:' === $token || 'd:' === $token || 'b:' === $token ) {
                $semicolon = strpos($data, ';', $cursor + 2);

                if ( false === $semicolon ) {
                    return null;
                }

                $raw = substr($data, $cursor + 2, $semicolon - $cursor - 2);
                $cursor = $semicolon + 1;

                if ( 'b:' === $token ) {
                    if ( '0' !== $raw && '1' !== $raw ) {
                        return null;
                    }

                    $members[] = ( '1' === $raw );
                    continue;
                }

                if ( 'd:' === $token ) {
                    // serialize() writes these three for non-finite floats. They
                    // name no post, but the member next to them may, so they are
                    // read as what they are and dropped later by is_finite().
                    if ( 'INF' === $raw ) {
                        $members[] = INF;
                        continue;
                    }

                    if ( '-INF' === $raw ) {
                        $members[] = -INF;
                        continue;
                    }

                    if ( 'NAN' === $raw ) {
                        $members[] = NAN;
                        continue;
                    }

                    // is_numeric() tolerates surrounding whitespace on PHP 8 and
                    // refuses it on 7.4, and unserialize() refuses it everywhere.
                    // A lexeme with any whitespace is refused outright, so the
                    // reader behaves the same on every supported version.
                    if ( '' === $raw || strlen($raw) !== strcspn($raw, " \t\n\r\v\f") || ! is_numeric($raw) ) {
                        return null;
                    }

                    $members[] = (float) $raw;
                    continue;
                }

                // An integer written past PHP_INT_MAX would clamp on cast, and
                // then name a post nobody selected.
                if ( '' === $raw || (string) (int) $raw !== $raw ) {
                    return null;
                }

                $members[] = (int) $raw;
                continue;
            }

            if ( 's:' === $token ) {
                $colon = strpos($data, ':', $cursor + 2);

                if ( false === $colon ) {
                    return null;
                }

                $declared = substr($data, $cursor + 2, $colon - $cursor - 2);

                if ( '' === $declared || ! ctype_digit($declared) ) {
                    return null;
                }

                $size = (int) $declared;

                if ( '"' !== substr($data, $colon + 1, 1) ) {
                    return null;
                }

                $start = $colon + 2;

                // The declared length is honoured, then the closing delimiters
                // must sit exactly where it says they do.
                if ( $start + $size + 2 > $length || '";' !== substr($data, $start + $size, 2) ) {
                    return null;
                }

                $members[] = substr($data, $start, $size);
                $cursor = $start + $size + 2;
                continue;
            }

            return null;
        }

        // Trailing input is refused: a prefix that parses is not a valid value.
        if ( '}' !== substr($data, $cursor, 1) || $cursor + 1 !== $length ) {
            return null;
        }

        return $members;
    }

    /**
     * Keep only the values usable as a post ID.
     *
     * intval() alone would not do: it turns "123foo" into 123 and true into 1,
     * so a malformed entry would silently exclude an unrelated post. The two
     * shapes intval() used to accept without any ambiguity, an integral float
     * and a space-padded string, are kept, because an exclusion already stored
     * that way must not vanish from the settings screen.
     *
     * @param array $values
     * @return array
     */
    private function blacklist_post_ids(array $values): array
    {
        $ids = array();

        foreach ( $values as $value ) {
            if ( is_int($value) && $value > 0 ) {
                $ids[] = $value;
                continue;
            }

            // A float is kept only when the round trip is exact, so 123.0 is
            // accepted while 123.5 and anything beyond PHP_INT_MAX is not.
            if ( is_float($value) && is_finite($value) && $value > 0 && (float) (int) $value === $value ) {
                $ids[] = (int) $value;
                continue;
            }

            if ( ! is_string($value) ) {
                continue;
            }

            // A NUL byte is not whitespace. trim() would strip it, but intval()
            // has always stopped at it, so "\0 123\0" never designated post 123
            // and must not start doing so now.
            if ( false !== strpos($value, "\0") ) {
                continue;
            }

            // Only real whitespace is trimmed, then leading zeros are dropped:
            // intval("0123") is 123 in decimal, so "0123" and "123" have always
            // named the same post.
            $candidate = trim( $value, " \t\n\r\v\f" );

            // A single leading plus names the same post: intval("+123") is 123.
            if ( 0 === strpos($candidate, '+') ) {
                $candidate = substr($candidate, 1);
            }

            $candidate = ltrim( $candidate, '0' );

            if ( '' === $candidate || ! ctype_digit($candidate) ) {
                continue;
            }

            // A numeric string past PHP_INT_MAX would clamp on conversion and
            // silently name a different post, so the round trip must be exact.
            if ( (string) (int) $candidate !== $candidate ) {
                continue;
            }

            $ids[] = (int) $candidate;
        }

        return $ids;
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
            $label = $post_type->labels;
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
