<?php
namespace Pagup\AutoLinks\Controllers;

use Pagup\AutoLinks\Core\Option;
use Pagup\AutoLinks\Traits\SettingHelper;

class SettingsController
{
    use SettingHelper;

    private $table_log = AILS_LOG_TABLE;
    public $batch_size = 20;
    private $valid_frequencies = [
        'fifteen_minutes',
        'thirty_minutes',
        'hourly',
        'two_hours',
        'six_hours',
        'daily'
    ];
    private $valid_batch_sizes = [10, 25, 50, 100];
    private const ONBOARDING_OPTION = 'ails_onboarding_status';

    /**
     * Save the options using an AJAX call
     *
     * Sanitizes the options and handles scheduling/unscheduling of the auto-sync
     * based on the auto_sync setting.
     *
     * @return void
     * @since 2.0.0
     */
    public function save_options() {

        // check the nonce
        if ( check_ajax_referer( 'ails__nonce', 'nonce', false ) == false ) {
            wp_send_json_error( "Invalid nonce", 419 );
            wp_die();
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error( "Unauthorized user", 403 );
            wp_die();
        }
  
        $options = $this->sanitize_options($_POST['options']);

        // Handle scheduling/unscheduling based on auto_sync setting
        $this->handle_auto_sync_settings($options);

        $result = update_option('automatic-internal-links-for-seo', $options);

        if ($result) {

            wp_send_json_success([
                'options' => $options,
                'message' => "Saved Successfully",
            ]);

        } else {
            wp_send_json_error([
                'options' => $options,
                'message' => "Error Saving Options"
            ]);
        }
    }

    /**
     * Handles scheduling/unscheduling auto-sync based on the auto_sync setting
     * and rescheduling if the frequency changed while auto sync is enabled.
     *
     * @param array $options The options to check for changes
     *
     * @return void
     */
    private function handle_auto_sync_settings(array $options): void {
        $old_options = get_option('automatic-internal-links-for-seo', []);
        
        // Get values with defaults
        $old_auto_sync = isset($old_options['auto_sync']) ? $old_options['auto_sync'] : false;
        $new_auto_sync = isset($options['auto_sync']) ? $options['auto_sync'] : false;
        $old_frequency = isset($old_options['auto_sync_frequency']) ? $old_options['auto_sync_frequency'] : 'hourly';
        $new_frequency = isset($options['auto_sync_frequency']) ? $options['auto_sync_frequency'] : 'hourly';
    
        // Check if auto sync setting changed
        if ($new_auto_sync !== $old_auto_sync) {
            if ($new_auto_sync) {
                $this->schedule_auto_sync($new_frequency);
            } else {
                $this->unschedule_auto_sync();
            }
        } 
        // Check if frequency changed while auto sync is enabled
        elseif ($new_auto_sync && $new_frequency !== $old_frequency) {
            $this->unschedule_auto_sync();
            $this->schedule_auto_sync($new_frequency);
        }
    }

    /**
     * Get the batch size for auto-sync from options, or return default if not set.
     *
     * @return int The batch size
     * @since 2.0.0
     */
    protected function get_batch_size(): int {
        // First try to get from options
        if (Option::check('auto_sync_batch_size')) {
            $size = (int)Option::get('auto_sync_batch_size');
            if ($size > 0) {
                return $size;
            }
        }
        return 25; // Default if not set in options
    }

    /**
     * Schedule the auto-sync event with the given frequency.
     *
     * Clears any existing scheduled event before scheduling a new one.
     *
     * @param string $frequency One of 'fifteen_minutes', 'thirty_minutes', 'two_hours', 'six_hours', or 'daily'.
     * @since 2.0.0
     */
    private function schedule_auto_sync(string $frequency): void {
        $timestamp = wp_next_scheduled('ails_auto_sync_event');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'ails_auto_sync_event');
        }

        // Convert our custom frequencies to WordPress cron schedules
        $schedule = 'hourly'; // default
        switch ($frequency) {
            case 'fifteen_minutes':
                wp_schedule_event(time(), 'fifteen_minutes', 'ails_auto_sync_event');
                break;
            case 'thirty_minutes':
                wp_schedule_event(time(), 'thirty_minutes', 'ails_auto_sync_event');
                break;
            case 'two_hours':
                wp_schedule_event(time(), 'two_hours', 'ails_auto_sync_event');
                break;
            case 'six_hours':
                wp_schedule_event(time(), 'six_hours', 'ails_auto_sync_event');
                break;
            case 'daily':
                wp_schedule_event(time(), 'daily', 'ails_auto_sync_event');
                break;
            default:
                wp_schedule_event(time(), 'hourly', 'ails_auto_sync_event');
        }
    }

    /**
     * Clear any existing auto-sync event from the WordPress cron system.
     *
     * Useful when the user disables the auto-sync feature or changes the
     * frequency, or when the plugin is deactivated.
     *
     * @since 2.0.0
     */
    private function unschedule_auto_sync(): void {
        $timestamp = wp_next_scheduled('ails_auto_sync_event');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'ails_auto_sync_event');
        }
    }

    /**
     * Handles AJAX search for posts based on a query.
     *
     * Validates nonce and user capabilities before executing a search query 
     * against the database for post titles matching the input query. 
     * Filters results by allowed post types and ensures posts are published.
     *
     * @throws Exception If an error occurs during database query execution.
     * 
     * @return void Outputs a JSON response with the search results or an error message.
     */
    public function search_posts_callback() {
        try {
            if (!check_ajax_referer('ails__nonce', 'nonce', false)) {
                wp_send_json_error("Invalid nonce", 401);
                wp_die();
            }
    
            if (!current_user_can('manage_options')) {
                wp_send_json_error("Unauthorized user", 403);
                wp_die();
            }
    
            $query = isset($_POST['query']) ? sanitize_text_field($_POST['query']) : '';
            
            $allowed_post_types = Option::check('post_types') ? maybe_unserialize(Option::get('post_types')) : ['post', 'page'];
    
            global $wpdb;
        
            // Create placeholders for post types
            $placeholders = array_fill(0, count($allowed_post_types), '%s');
            $placeholders_string = implode(',', $placeholders);
            
            // Prepare the query parameters
            $query_params = array_merge(
                ['%' . $wpdb->esc_like($query) . '%'],
                $allowed_post_types
            );

            // Build and execute the query
            $sql = $wpdb->prepare(
                "SELECT ID, post_title 
                FROM {$wpdb->posts} 
                WHERE post_title LIKE %s 
                AND post_type IN ($placeholders_string)
                AND post_status = 'publish'
                LIMIT 20",
                $query_params
            );

            $results = $wpdb->get_results($sql);
            
            if (is_wp_error($results)) {
                wp_send_json_error($results->get_error_message(), 500);
                wp_die();
            }

            $formatted_posts = array_map(function($post) {
                return [
                    'id' => $post->ID,
                    'title' => $post->post_title
                ];
            }, $results);
    
            wp_send_json_success($formatted_posts);
            
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage(), 500);
        }
    }

    /**
     * Delete all transients that match the pattern of 
     * '_transient_ails_item_%' from the database.
     * 
     * If there are more than 500 transients, it will schedule the deletion
     * using {@see wp_schedule_single_event()} with a delay of 200ms to prevent
     * overloading the server.
     * 
     * @return string The result message.
     */
    public function delete_all_transients($send_json = false) {
        if ($send_json && !check_ajax_referer('ails__nonce', 'nonce', false)) {
            wp_send_json_error("Invalid nonce", 401);
        }
    
        global $wpdb;
        $prefix = $wpdb->prefix;
    
        // Get all the transients
        $transients = $wpdb->get_col("
            SELECT `option_name` 
            FROM `{$prefix}options` 
            WHERE `option_name` LIKE '%\_transient\_%' 
            AND `option_name` LIKE '%ailsitem%'
        ");
    
        if (empty($transients)) {
            if ($send_json) {
                wp_send_json_success([
                    'message' => 'There is no transient cache available to delete.',
                    'status' => 'info'
                ]);
            }
            return true;
        }
    
        // Handle large number of transients
        if (count($transients) > 500) {
            $delay = 0.2;
            foreach ($transients as $transient) {
                wp_schedule_single_event(
                    microtime(true) + $delay, 
                    'ails_transients', 
                    array($transient)
                );
                $delay += 0.2;
            }
    
            if ($send_json) {
                wp_send_json_success([
                    'message' => 'Transient Cached items are scheduled to delete. It can take some time depending on the number of cached items.',
                    'status' => 'success',
                    'scheduled' => true,
                    'count' => count($transients)
                ]);
            }
            return true;
        } else {
            foreach ($transients as $transient) {
                delete_option($transient);
            }
    
            if ($send_json) {
                wp_send_json_success([
                    'message' => 'Transient Cached items are successfully deleted.',
                    'status' => 'success',
                    'count' => count($transients)
                ]);
            }
            return true;
        }
    }

    /**
     * Delete a transient cache item by its name
     * 
     * @param string $transient
     * @return void
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
        
        $post_types = $this->get_post_types_array();
        if (empty($post_types)) {
            return ['pages' => 0, 'items' => 0];
        }

        // Create placeholders for IN clause
        $placeholders = implode(',', array_fill(0, count($post_types), '%s'));
        $focus_keyword_type = $this->focus_keyword();
        
        if ($focus_keyword_type === 'aioseo_table') {
            $query = $wpdb->prepare("
                SELECT COUNT(DISTINCT p.ID)
                FROM {$wpdb->posts} p
                INNER JOIN {$wpdb->prefix}aioseo_posts ap ON p.ID = ap.post_id
                LEFT JOIN {$this->table_log} log ON p.ID = log.post_id
                WHERE 
                    p.post_type IN ($placeholders)
                    AND p.post_status = 'publish'
                    AND p.post_title != ''
                    AND p.post_title IS NOT NULL
                    AND log.post_id IS NULL
                    AND ap.keyphrases IS NOT NULL
                    AND ap.keyphrases != ''
                    AND NOT EXISTS (
                        SELECT 1 FROM {$wpdb->postmeta} pm2
                        WHERE 
                            pm2.post_id = p.ID
                            AND pm2.meta_key = 'disable_ails'
                    )
                ",
                ...$post_types
            );
        } else {
            $query = $wpdb->prepare("
                SELECT COUNT(DISTINCT pm.post_id)
                FROM {$wpdb->postmeta} pm
                LEFT JOIN {$this->table_log} log ON pm.post_id = log.post_id
                LEFT JOIN {$wpdb->posts} p ON pm.post_id = p.ID
                WHERE 
                    p.post_type IN ($placeholders)
                    AND p.post_status = 'publish'
                    AND p.post_title != ''
                    AND p.post_title IS NOT NULL
                    AND log.post_id IS NULL
                    AND pm.meta_key = %s
                    AND NOT EXISTS (
                        SELECT 1 FROM {$wpdb->postmeta} pm2
                        WHERE 
                            pm2.post_id = pm.post_id
                            AND pm2.meta_key = 'disable_ails'
                    )
                ",
                ...array_merge($post_types, [$focus_keyword_type])
            );
        }

        $totalRows = (int) $wpdb->get_var($query);
        $totalPages = ceil($totalRows / $this->get_batch_size());

        return [
            'pages' => (int) $totalPages,
            'items' => $totalRows
        ];
    }

    /**
     * Get properly formatted array of post types
     */
    private function get_post_types_array(): array 
    {
        $post_types = Option::check('post_types') 
            ? maybe_unserialize(Option::get('post_types')) 
            : ['post', 'page'];

        return array_filter($post_types); // Remove any empty values
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
     * Get auto sync status
     * 
     * @return array {
     *     @type bool $is_enabled Is auto sync enabled?
     *     @type bool $is_scheduled Is auto sync scheduled?
     *     @type bool $is_running Is auto sync currently running?
     *     @type int $total_items Total items available for syncing
     *     @type int $items_processed Number of items processed so far
     *     @type int $items_remaining Number of items remaining to be synced
     *     @type string|null $next_run Scheduled time for next auto sync run
     *     @type int $progress_percentage Progress percentage of auto sync
     *     @type string $message Status message
     * }
     */
    public function get_auto_sync_status(): array {
        $is_scheduled = wp_next_scheduled('ails_auto_sync_event');
        $total_items = $this->get_total_pages_and_items()['items'];
        $current_offset = (int)get_option('ails_auto_sync_offset', 0);
        $is_running = (bool)get_transient('ails_auto_sync_lock');

        // Get last synced items
        $last_synced = array_map(
            function($item) {
                return [
                    'post_id' => absint($item['post_id']),
                    'title' => sanitize_text_field($item['title']),
                    'keyword' => sanitize_text_field($item['keyword']),
                    'synced_at' => sanitize_text_field($item['synced_at']),
                    'sync_type' => sanitize_text_field($item['sync_type'])
                ];
            },
            maybe_unserialize(get_option('ails_last_synced_items', []))
        );
        
        $status = [
            'is_enabled' => Option::check('auto_sync') && Option::get('auto_sync'),
            'is_scheduled' => (bool)$is_scheduled,
            'is_running' => $is_running,
            'total_items' => $total_items,
            'items_processed' => $current_offset,
            'items_remaining' => max(0, $total_items - $current_offset),
            'next_run' => $is_scheduled ? wp_date('Y-m-d H:i:s', $is_scheduled) : null,
            'progress_percentage' => $total_items > 0 ? 
                round(($current_offset / $total_items) * 100, 1) : 0,
            'last_synced_items' => $last_synced
        ];
    
        // Generate status message
        if ($status['is_enabled']) {
            if ($status['total_items'] > 0) {
                if ($status['is_running']) {
                    $status['message'] = sprintf(
                        'Auto-sync is currently running. %d of %d items processed (%s%% complete). %d items remaining.',
                        $status['items_processed'],
                        $status['total_items'],
                        $status['progress_percentage'],
                        $status['items_remaining']
                    );
                } else {
                    $status['message'] = sprintf(
                        'Auto-sync is scheduled to run at %s. %d items queued for processing.',
                        wp_date('g:i A', $is_scheduled),
                        $status['total_items']  // Changed from items_remaining to total_items
                    );
                }
            } else {
                $status['message'] = 'Auto-sync is enabled and all items are currently synchronized.';
            }
        }
    
        return $status;
    }

    /**
     * Handles AJAX request to update onboarding status of a tour type
     * 
     * @return void
     */
    public function update_onboarding() {
        if (!check_ajax_referer('ails__nonce', 'nonce', false)) {
            wp_send_json_error("Invalid nonce", 401);
        }
    
        $tour_type = sanitize_text_field($_POST['tour_type']);
        $valid_types = ['settings', 'sync', 'links', 'logs'];
    
        if (!in_array($tour_type, $valid_types)) {
            wp_send_json_error("Invalid tour type", 400);
        }
    
        // Get existing onboarding status or initialize new array
        $onboarding_status = get_option(self::ONBOARDING_OPTION, []);
        $onboarding_status[$tour_type] = true;
        
        $updated = update_option(self::ONBOARDING_OPTION, $onboarding_status);
    
        if ($updated) {
            wp_send_json_success([
                'message' => 'Tour status updated',
                'tour_type' => $tour_type
            ]);
        } else {
            wp_send_json_error(['message' => 'Failed to update tour status']);
        }
    }

    /**
     * Retrieves the onboarding status of the plugin's major features.
     * 
     * @return array
     */
    public function get_onboarding_status(): array {
        $status = get_option(self::ONBOARDING_OPTION, []);
        return [
            'settings' => !empty($status['settings']),
            'sync' => !empty($status['sync']),
            'links' => !empty($status['links']),
            'logs' => !empty($status['logs'])
        ];
    }

    /**
     * Checks WordPress memory limit and returns alert data if memory is insufficient
     * 
     * @return array|false Returns alert data object if memory is insufficient, false otherwise
     */
    public function check_memory_limit() {
        $memory_limit = WP_MEMORY_LIMIT;
        $required_memory_limit = '48M';

        $memory_limit_in_bytes = wp_convert_hr_to_bytes($memory_limit);
        $required_memory_limit_in_bytes = wp_convert_hr_to_bytes($required_memory_limit);

        if ($memory_limit_in_bytes < $required_memory_limit_in_bytes) {
            return [
                'current_limit' => $memory_limit,
                'required_limit' => $required_memory_limit
            ];
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
        global $wpdb;
        $list = [];
        $i = 0;

        // Pre-fetch keywords if needed
        $keywords = [];
        if ($keyword === true) {
            $ids_string = implode(',', array_map('intval', $ids));
            
            if (class_exists('WPSEO_Meta')) {
                // Fetch all Yoast keywords in one query
                $meta_key = '_yoast_wpseo_focuskw';
                $query = "SELECT post_id, meta_value 
                        FROM {$wpdb->postmeta} 
                        WHERE post_id IN ($ids_string) 
                        AND meta_key = '$meta_key'";
                
                $results = $wpdb->get_results($query);
                foreach ($results as $result) {
                    $keywords[$result->post_id] = $result->meta_value;
                }
            } 
            elseif (class_exists('RankMath')) {
                // Fetch all Rank Math keywords in one query
                $meta_key = 'rank_math_focus_keyword';
                $query = "SELECT post_id, meta_value 
                        FROM {$wpdb->postmeta} 
                        WHERE post_id IN ($ids_string) 
                        AND meta_key = '$meta_key'";
                
                $results = $wpdb->get_results($query);
                foreach ($results as $result) {
                    $keywords[$result->post_id] = $result->meta_value;
                }
            }
            elseif (function_exists('aioseo')) {
                // Fetch all AIOSEO keywords in one query
                $query = "SELECT post_id, keyphrases 
                        FROM {$wpdb->prefix}aioseo_posts 
                        WHERE post_id IN ($ids_string)";
                
                $results = $wpdb->get_results($query);
                foreach ($results as $result) {
                    $keyphrases_data = json_decode($result->keyphrases, true);
                    if (isset($keyphrases_data['focus']['keyphrase'])) {
                        $keywords[$result->post_id] = $keyphrases_data['focus']['keyphrase'];
                    }
                }
            }
        }

        foreach ($ids as $id) {
            $title = get_the_title($id);
            
            // Skip posts with empty titles
            if (empty($title)) {
                continue;
            }
    
            $post_type = ($type === true) ? " (" . $this->post_type($id) . ")" : "";
    
            $list[$i]['id'] = $id;
            $list[$i]['title'] = $title . $post_type;
            $list[$i]['url'] = get_permalink($id);
    
            if ($keyword === true) {
                $list[$i]['keyword'] = $keywords[$id] ?? '';
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

}
$settings = new SettingsController();