<?php
namespace Pagup\AutoLinks\Controllers;

use html_changer\HtmlChanger;
use Pagup\AutoLinks\Core\Option;

class ReplaceController
{
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
                "SELECT * FROM 
                (SELECT * FROM $this->table_log WHERE INSTR(%s, keyword) > 0 
                UNION ALL 
                SELECT * FROM $this->table WHERE INSTR(%s, keyword) > 0) 
                AS combined_tables 
                ORDER BY priority ASC, created_at ASC",
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

        $expiration_days = Option::check('expiration') ? Option::get('expiration') : 14;

        $this->items = $this->get_items($post_id, $content, $expiration_days);

        if ( !empty($post_id) && is_numeric($post_id) && in_array((int)$post_id, $this->blacklist()) ) {
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

                    if (in_array($item->keyword, $excluded_keywords)) {
                        continue;
                    }

                    if (Option::check("enable_override")) {
                        $keywords[$item->keyword] = array (
                            'value' => $item,
                            'caseInsensitive' => Option::check('case_sensitive') ?  false : true,
                            'wordBoundary' => Option::check('partial_match') ?  false : true,
                            'group' => intval($item->id),
                            'maxCount' => $max_links ? $max_links : intval($item->max_links),
                        );
                    } else {
                        $keywords[$item->keyword] = array (
                            'value' => $item,
                            'caseInsensitive' => boolval(!$item->case_sensitive),
                            'wordBoundary' => boolval(!$item->partial_match),
                            'group' => intval($item->id),
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
     * Get the list of blacklist URL's string from Options, converts it to an array, and use the array map function to convert each URL to ID.
     * 
     * @return array
    */
    public function blacklist(): array
    {
        $blacklist = Option::check('blacklist') ? Option::get('blacklist') : '';
        
        // If empty, return empty array
        if (empty($blacklist)) {
            return [];
        }

        // If it's serialized array, just return it unserialized
        if (is_serialized($blacklist)) {
            return maybe_unserialize($blacklist);
        }
        
        // If it's text URLs, split into array and convert to IDs
        $urls_array = explode("\n", str_replace("\r", "", $blacklist));
        $urls_array = array_filter($urls_array); // Remove empty lines

        // Convert URL's to Id's
        $ids_array = array_map(function($link) {
            return url_to_postid($link);
        }, $urls_array);

        return array_filter($ids_array); // Remove any zero IDs
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

$ReplaceController = new ReplaceController;