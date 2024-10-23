<?php
namespace Pagup\AutoLinks\Controllers;

use Pagup\AutoLinks\Core\Option;
use Pagup\AutoLinks\Core\Request;
use Pagup\AutoLinks\Traits\Helpers;

class SettingsController
{
    use Helpers;

    private $table_log = AILS_LOG_TABLE;
    public $batch_size = 20;

    /**
     * Update plugin settings. Accepts $safe values array
     * 
     * @param array $safe
     * @return string
    */
    public function update_settings(array $safe): string
    {
        if ( function_exists( 'current_user_can' ) && !current_user_can( 'manage_options' ) )
            {    
                die( 'Sorry, not allowed...' );
            }

            check_admin_referer( 'automatic-internal-links-settings', 'ails__nonce' );

            if ( ! isset( $_POST['ails__nonce'] ) || ! wp_verify_nonce( $_POST['ails__nonce'], 'automatic-internal-links-settings' )
            ) {
                die( 'Sorry, not allowed. Nonce doesn\'t verify' );
            }
            
            $exclude_tags = [];
            if (isset( $_POST['exclude_tags'] ) && !empty( $_POST['exclude_tags'] )) {
                $exclude = sanitize_textarea_field(trim($_POST['exclude_tags']));
                $exclude_tags = str_replace(' ', '', $exclude);
            }

            $exclude_keywords = [];
            if (isset( $_POST['exclude_keywords'] ) && !empty( $_POST['exclude_keywords'] )) {
                $exclude_keywords = sanitize_textarea_field(trim($_POST['exclude_keywords']));
            }

            $max_links = Request::check('max_links') ? sanitize_text_field(intval($_POST['max_links'])) : 0;

            $options = [
                'post_types' => Request::array($_POST['post_types']),
                'disable_autolinks' => Request::safe('disable_autolinks', $safe),
                'exclude_tags' => $exclude_tags,
                'exclude_keywords' => $exclude_keywords,
                'blacklist' => sanitize_textarea_field(trim($_POST['blacklist'])),
                'remove_settings' => Request::safe('remove_settings', $safe),
                'enable_override' => Request::safe('enable_override', $safe),
                'max_links' => $max_links,
                'new_tab' => Request::safe('new_tab', $safe),
                'nofollow' => Request::safe('nofollow', $safe),
                'partial_match' => Request::safe('partial_match', $safe),
                'bold' => Request::safe('bold', $safe),
                'case_sensitive' => Request::safe('case_sensitive', $safe),
            ];

            update_option( 'automatic-internal-links-for-seo', $options );
        
            return 'Settings saved.';
    }

    /**
     * Delete all transients items related to auto links plugin
     * 
     * @return string
    */
    public function delete_all_transients() {

        global $wpdb;
        $prefix = $wpdb->prefix;
    
        // Get all the transients matching your pattern
        $transients = $wpdb->get_col("
            SELECT `option_name` 
            FROM `{$prefix}options` 
            WHERE `option_name` LIKE '%\_transient\_%' 
            AND `option_name` LIKE '%ails_item_%'
        ");

        if (empty($transients)) {
            return "There is no transient cache available to delete.";
        }

        // Schedule the deletion if transients are more than 500
        if (count($transients) > 500) {
            
            $delay = 0.2; // initial delay by 200ms
            foreach ($transients as $transient) {
                
                // Pass each transient to schedule event via ails_transients actions defined in plugin root file. Which used delete_transient function for job on defined timestamp.
                wp_schedule_single_event(microtime(true) + $delay, 'ails_transients', array($transient));

                // increment delay by 200ms
                $delay += 0.2;
            }

            return "Transient Cached items are scheduled to delete. It can take some time depending on the number of cached items.";

        } else {

            foreach ($transients as $transient) {
                delete_option($transient);
            }
    
            return "Transient Cached items are successfully deleted.";
        }
    
        
    }

    /**
     * This method will delete transient using ails_transients action
    */
    public function delete_transient($transient) {
        delete_option($transient);
    }

    /**
     * Get count of total items (for syncing with logs table) & total pages (for batch query)
     * 
     * @return array
    */
    public function get_total_pages_and_items(): array 
    {
        global $wpdb;

        /** LEFT JOIN the wp_auto_internal_log table to the wp_postmeta table and filters out rows where
         * 1. meta_key value have focus keywords (yoast or rankmath)
         * 2. meta_key doesn't have disable_ails value (autolink meta checkbox to disable the post)
         * 3. wp_auto_internal_log.post_id is not null. 
         * Since we're doing a left join, a non-null value in al.post_id means that the same post_id value exists in logs table. So we exclude these rows.
         * The DISTINCT keyword is used to count only distinct post_id values to avoid double-counting. 
         **/

        // Get allowed post types from options
        $totalRows = $wpdb->get_var("
            SELECT COUNT(DISTINCT pm.post_id)
            FROM {$wpdb->postmeta} pm
            LEFT JOIN {$this->table_log} log ON pm.post_id = log.post_id
            LEFT JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE 
                p.post_type IN ({$this->post_types()})
                AND p.post_status = 'publish'
                AND p.post_title != ''
                AND log.post_id IS NULL
                AND pm.meta_key = '{$this->focus_keyword()}'
                AND NOT EXISTS (
                    SELECT 1 FROM {$wpdb->postmeta} pm2
                    WHERE 
                        pm2.post_id = pm.post_id
                        AND pm2.meta_key = 'disable_ails'
                )
        "); // Get the total number of rows

        $totalPages = ceil($totalRows / $this->batch_size); // Calculate the total number of pages

        // Return total pages for batch processing and total number of rows to show whats need to synced
        return [
            'pages' => (int) $totalPages,
            'items' => (int) $totalRows
        ];
    }

    /**
     * Get count of total items from auto links logs table
     * 
     * @return int
    */
    public function get_count_from_logs_table(): int
    {
        global $wpdb;
        
        return $wpdb->get_var("SELECT COUNT(*) FROM $this->table_log WHERE keyword IS NOT NULL AND keyword != ''");
    }

    /**
     * Checks WordPress memory limit. Display notification if there are 1000+ items for Fetching or in Auto link logs table
     * 
     * @return string|boolean
    */
    public function check_memory_limit() {
        $memory_limit = WP_MEMORY_LIMIT;
        $required_memory_limit = '64M';
    
        $memory_limit_in_bytes = wp_convert_hr_to_bytes($memory_limit);
        $required_memory_limit_in_bytes = wp_convert_hr_to_bytes($required_memory_limit);

        $notification = '<div class="ails-notice ails-notice-error"><p style="text-transform: none;">Your WordPress Memory Limit is currently set to '.$memory_limit.'. It is recommended to set the Memory Limit to at least '.$required_memory_limit.' for optimal performance. <a href="admin.php?page=automatic-internal-links-for-seo&tab=help#memory">Read More</a></p></div>';
    
        if ($memory_limit_in_bytes < $required_memory_limit_in_bytes) {
            return $notification;
        }

        return false;
    }

    /**
     * Required data fields, Title, Keyword and Max Links and return errors
     * 
     * @param array $data
     * @param array $errors
     * @return array $errors
    */
    public function required($data, $errors)
    {
        if (empty($data['keyword'])) {
            array_push($errors, __("Keyword is required", "automatic-internal-links-for-seo"));
        }
        if (intval(strlen($data['url'])) > 355) {
            array_push($errors, __("URL is too long (bigger than 255 characters)", "automatic-internal-links-for-seo"));
        }
        if ($data['max_links'] == 0 || $data['max_links'] < -1) {
            array_push($errors, __("Max links value should be at-least 1 OR -1 (-1 for unlimited links)", "automatic-internal-links-for-seo"));
        }
        return $errors;
    }

    /**
     * Get list of items with id, title, url. set $keyword to true to get yoast focus keyword
     * 
     * @param array $ids
     * @param boolean $keyword
     * @param boolean $type
     * @return array $list
    */
    public function get_items($ids, $keyword = false, $type = false)
    {
        $list = [];
        $i = 0;
        foreach ( $ids as $id) {
            // Create Array of Objects
            $post_type = ($type === true) ? " (".$this->post_type($id).")" : "";

            $list[$i]['id'] = $id;
            $list[$i]['title'] = get_the_title($id) . $post_type;
            $list[$i]['url'] = get_permalink($id);

            // Set Keywords if its true and Yoast SEO or Rank Math Plugin is active
            if ($keyword === true) {

                if( class_exists('WPSEO_Meta') ) {
                    $list[$i]['keyword'] = get_post_meta( $id, '_yoast_wpseo_focuskw', true );
                } 
                
                elseif ( class_exists('RankMath') ) {
                    $list[$i]['keyword'] = get_post_meta( $id, 'rank_math_focus_keyword', true );
                }

                else {
                    $list[$i]['keyword'] = "";
                }
                
            }
            $i++;
        }

        return $list;
    }
    
    /**
     * Get post type label from post type object
     * 
     * @param int $post_id
     * @return string
    */
    public function post_type($post_id)
    {
        $post_type_obj = get_post_type_object( get_post_type($post_id) );
        return $post_type_obj->labels->singular_name;
    }

    /**
     * Get the list of blacklist URL's string from Options, converts it to an array, and use the array map function to convert each URL to a string.
     * 
     * @return string
     */
    public function blacklist_urls(): string
    {
        $blacklist = Option::check('blacklist') ? Option::get('blacklist') : '';
        
        // If empty, return empty string
        if (empty($blacklist)) {
            return '';
        }
        
        // If it's a serialized array of IDs, convert to URLs
        if (is_serialized($blacklist)) {
            $ids_array = maybe_unserialize($blacklist);
            
            // Convert IDs to URLs
            $urls_array = array_map(function($id) {
                return get_permalink($id);
            }, $ids_array);
            
            return implode("\n", array_filter($urls_array));
        }
        
        // If it's not serialized, it's already URLs, just return as is
        return $blacklist;
    }

}
$settings = new SettingsController();