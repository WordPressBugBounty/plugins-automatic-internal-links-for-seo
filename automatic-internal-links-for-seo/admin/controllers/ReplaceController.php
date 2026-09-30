<?php
namespace Pagup\AutoLinks\Controllers;

use html_changer\HtmlChanger;
use Pagup\AutoLinks\Core\Option;
use Pagup\AutoLinks\Traits\LanguageScope;

class ReplaceController
{
    use LanguageScope;

    private $table = AILS_TABLE;
    private $table_log = AILS_LOG_TABLE;
    private $items;

    public function __construct()
    {
        add_filter( 'the_content', array( &$this, 'replace' ), 99999 );
    }

    /**
     * Get all items from the database
     * 
     * @return object
    */
    public function get_items($post_id, $content, $expiration)
    {
        global $wpdb;

        $item = get_transient( "ailsitem{$post_id}" );

        if ( false === $item ) {
            // Prepare the SQL query with proper escaping
            $sql = $wpdb->prepare(
                // ails_source and id close the ordering. Without them, two rows with
                // the same priority AND the same creation date arrive in whatever
                // order the database happens to return, and the rule elected below
                // would change from one request to the next.
                "SELECT * FROM
                (SELECT *, 0 AS ails_source FROM $this->table_log WHERE INSTR(%s, keyword) > 0
                UNION ALL
                SELECT *, 1 AS ails_source FROM $this->table WHERE INSTR(%s, keyword) > 0)
                AS combined_tables
                ORDER BY priority ASC, created_at ASC, ails_source ASC, id ASC",
                $content, $content
            );

            $item = $wpdb->get_results($sql);

            $time = 60 * 60 * 24 * $expiration;

            // Cache the query results in transient for future use
            set_transient( "ailsitem{$post_id}", $item, $time );
        }

        return $item;
    }

    /**
     * Accepts text (to modify) and item object (with settings) and do link formation
     * 
     * @param string $content
     * @return string
    */
    public function replace( string $content ) {

        $post_id = get_the_ID();

        // Check if internal links are disabled for this specific page
        if (!empty($post_id) && get_post_meta($post_id, 'disable_internal_links', true)) {
            return $content;
        }

        $expiration_days = Option::check('expiration') ? Option::get('expiration') : 14;

        $this->items = $this->get_items($post_id, $content, $expiration_days);

        if ( !empty($post_id) && is_numeric($post_id) && in_array((int)$post_id, $this->blacklist(), true) ) {
            return $content;
        }

        $post_types = Option::check('post_types') ? maybe_unserialize(Option::get('post_types')) : ['post', 'page'];

        $exclude = Option::check('exclude_keywords') ? Option::get('exclude_keywords') : "";

        $excluded_keywords = explode("\n", str_replace("\r", "", $exclude));

        if ( is_singular($post_types) ) {

            $post_id = get_the_id();

            $keywords = [];

            $max_links = $this->override('max_links') ? intval(Option::get('max_links')) : "";

            foreach ($this->items as $item) {
                
                if ( $post_id !== intval($item->post_id) ) {

                    if (!$this->is_same_language_link_target((int) $post_id, $item)) {
                        continue;
                    }

                    // Strict on purpose. A loose in_array() compares numeric
                    // strings as numbers, and PHP 8 accepts a trailing space in
                    // one, so a keyword '123' matched an exclusion line '123 '
                    // on PHP 8 and not on 7.4. A line now excludes only the
                    // keyword it spells exactly, on every version.
                    if (in_array((string) $item->keyword, $excluded_keywords, true)) {
                        continue;
                    }

                    // The last row of the ordering wins, deliberately. The
                    // plugin's own interface states it in every locale: the
                    // higher priority value takes over, then the later creation
                    // date. The query orders by priority, created_at, ails_source
                    // and id, all ascending, so the last row is the highest on
                    // that tuple. When priority and date are both equal, the
                    // manual table wins over the automatic one, then the higher
                    // id within it. That is a convention, not a guarantee of
                    // which row was really added last: the two tables carry
                    // independent AUTO_INCREMENTs and dates are kept to the
                    // second.

                    if (Option::check("enable_override")) {
                        $keywords[$item->keyword] = array (
                            'value' => $item,
                            'caseInsensitive' => Option::check('case_sensitive') ?  false : true,
                            'wordBoundary' => Option::check('partial_match') ?  false : true,
                            'group' => $item->keyword,
                            'maxCount' => $max_links ? $max_links : intval($item->max_links),
                        );
                    } else {
                        $keywords[$item->keyword] = array (
                            'value' => $item,
                            'caseInsensitive' => boolval(!$item->case_sensitive),
                            'wordBoundary' => boolval(!$item->partial_match),
                            'group' => $item->keyword,
                            'maxCount' => $max_links ? $max_links : intval($item->max_links),
                        );
                    }
    
                }
    
            }

            // var_dump($keywords);
     
            // Parse HTML with HTMLChanger Class
            $htmlChanger = new HtmlChanger($content);

            $exclude = Option::check('exclude_tags') ? Option::get('exclude_tags') : "";
            if (!empty($exclude)) {
                $excluded_tags = explode("\n", str_replace("\r", "", $exclude));
            } else {
                $excluded_tags = [];
            }
            
            $instance = [
                'search' => $keywords,
                'ignore' => array_merge([
                    'a'
                ], $excluded_tags )
            ];
    
            $htmlChanger = new HtmlChanger($content, $instance);
    
            $htmlChanger->replace(function ($text, $value) {
                return $this->link($text, $value);
            });
    
            return $htmlChanger->html();
    
        }

		return $content;
    
    }

    /**
     * Accepts text (to modify) and item object (with settings) and do link formation
     * 
     * @param string $text
     * @param object $item
     * @return string
    */
    public function link(string $text, object $item): string
    {
		if ($item->use_custom == "0" ) {
			$url = get_permalink($item->post_id);
		} else {
			$url = $item->url;
		}

        if (Option::check("enable_override")) {
            $new_tab = Option::check('new_tab') ? " target='_blank'" : "";
            $nofollow = Option::check('nofollow') ? " rel='nofollow'" : "";
            $attributes = $new_tab . $nofollow;
            $anchor = Option::check('bold') ? "<strong>{$text}</strong>" : $text;
        } else {
            $new_tab = boolval($item->new_tab) ? " target='_blank'" : "";
		    $nofollow = boolval($item->nofollow) ? " rel='nofollow'" : "";
		    $attributes = $new_tab . $nofollow;
            $anchor = boolval($item->bold) ? "<strong>{$text}</strong>" : $text;
        }

        return "<a href='{$url}' title='{$item->title}' $attributes>{$anchor}</a>";
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
     * Check if Enable Override & Given Option fields are checked
     * 
     * @param string $key
     * @return bool
    */
    public function override(string $key): bool
    {
        return (Option::check("enable_override") && Option::check($key));
    }

}
