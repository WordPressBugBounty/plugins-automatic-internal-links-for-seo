<?php

namespace Pagup\AutoLinks\Controllers;

use Pagup\AutoLinks\Traits\ErrorHandler;
use Pagup\AutoLinks\Traits\BulkFetchQueries;
use Pagup\AutoLinks\Traits\BulkAddQueries;
use Pagup\AutoLinks\Core\Option;
class AutoSyncController extends SettingsController {
    use ErrorHandler, BulkFetchQueries, BulkAddQueries;
    private string $table_log;

    private string $hook_name = 'ails_auto_sync_event';

    private int $lock_timeout = 300;

    // 5 minutes timeout
    private const LOG_PREFIX = 'Auto Links Sync';

    public function __construct() {
        $this->table_log = AILS_LOG_TABLE;
    }

    /**
     * Process auto sync process in background
     *
     * @since 2.0.0
     *
     * @return void
     */
    public function process_auto_sync() {
        return;
        if ( !Option::check( 'auto_sync' ) || !Option::get( 'auto_sync' ) ) {
            return;
        }
        $lock_key = 'ails_auto_sync_lock';
        if ( get_transient( $lock_key ) ) {
            return;
        }
        set_transient( $lock_key, true, $this->lock_timeout );
        try {
            $offset = (int) get_option( 'ails_auto_sync_offset', 0 );
            $batch_size = $this->get_batch_size();
            $request = [
                'batchSize' => $batch_size,
                'offset'    => $offset,
            ];
            $posts = $this->fetchPosts( $request );
            if ( empty( $posts ) ) {
                delete_option( 'ails_auto_sync_offset' );
                update_option( 'autolinks_sync', current_time( 'mysql' ) );
                delete_transient( $lock_key );
                return;
            }
            $processed = 0;
            $total_posts = count( $posts );
            $batch_processed_items = [];
            // Process posts in reverse order so newest are processed last
            $posts = array_reverse( $posts );
            foreach ( $posts as $post_id ) {
                try {
                    $post_data = $this->prepare_post_data( $post_id );
                    if ( $post_data && !$this->logExists( $post_id ) ) {
                        if ( $this->insertLog( $post_data ) ) {
                            // Store processed item data
                            $batch_processed_items[] = [
                                'post_id'   => $post_data['post_id'],
                                'title'     => sanitize_text_field( $post_data['title'] ),
                                'keyword'   => sanitize_text_field( $post_data['keyword'] ),
                                'synced_at' => current_time( 'mysql' ),
                                'sync_type' => 'cron',
                            ];
                            $processed++;
                        }
                    }
                } catch ( \Exception $e ) {
                    continue;
                }
            }
            // Update the last synced items after batch is complete
            if ( !empty( $batch_processed_items ) ) {
                // Get only the last 5 items
                $last_five = array_slice( $batch_processed_items, -5 );
                update_option( 'ails_last_synced_items', $last_five, false );
            }
            // Update offset
            $new_offset = $offset + $batch_size;
            update_option( 'ails_auto_sync_offset', $new_offset );
            // UPDATE CACHE ARITHMETICALLY INSTEAD OF CLEARING
            if ( $processed > 0 ) {
                $this->update_sync_cache_count( $processed );
            }
            // Schedule next run if needed
            if ( !wp_next_scheduled( $this->hook_name ) ) {
                wp_schedule_event( time(), 'fifteen_minutes', $this->hook_name );
            }
        } catch ( \Exception $e ) {
            $this->log( "Sync process failed: {$e->getMessage()}", 'error' );
        } finally {
            delete_transient( $lock_key );
        }
    }

    /**
     * Logs a message to the error log with a specified severity level.
     *
     * This function logs messages only when WordPress debugging is enabled.
     * Messages are formatted with a timestamp, a prefix, and an uppercase
     * log level. The message is logged to the error log if the log level is
     * 'error' or if it's 'info' and WP_DEBUG_LOG is defined and true.
     *
     * @param string $message The message to log.
     * @param string $level   The severity level of the log message. Default is 'info'.
     */
    private function log( $message, $level = 'info' ) {
        if ( !WP_DEBUG ) {
            return;
        }
        $timestamp = current_time( 'mysql' );
        $formatted = sprintf(
            '[%s] [%s] %s',
            self::LOG_PREFIX,
            strtoupper( $level ),
            $message
        );
        switch ( $level ) {
            case 'error':
                error_log( $formatted );
                break;
            case 'info':
                if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
                    error_log( $formatted );
                }
                break;
        }
    }

    /**
     * Schedule the auto sync event with the given frequency.
     *
     * Clears any existing scheduled event before scheduling a new one.
     *
     * @since 2.0.0
     */
    public function schedule_sync() {
        return;
        if ( !wp_next_scheduled( $this->hook_name ) ) {
            $frequency = Option::get( 'auto_sync_frequency' ) ?? 'fifteen_minutes';
            wp_schedule_event( time(), $frequency, $this->hook_name );
            error_log( 'Auto Links: Scheduled sync event with frequency: ' . $frequency );
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
    public function unschedule_sync() {
        $timestamp = wp_next_scheduled( $this->hook_name );
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, $this->hook_name );
            error_log( 'Auto Links: Unscheduled sync event' );
        }
    }

    /**
     * Prepare post data for auto-syncing.
     *
     * @param int $post_id Post ID to prepare data for
     *
     * @return array|null Prepared post data or null if post is not found
     * @since 2.0.0
     */
    private function prepare_post_data( $post_id ) {
        $post = get_post( $post_id );
        if ( !$post ) {
            return null;
        }
        return [
            'post_id'        => $post_id,
            'title'          => $post->post_title,
            'keyword'        => $this->get_focus_keyword( $post_id ),
            'url'            => get_permalink( $post_id ),
            'use_custom'     => 0,
            'new_tab'        => 0,
            'nofollow'       => 0,
            'partial_match'  => 0,
            'bold'           => 0,
            'case_sensitive' => 0,
            'priority'       => 0,
            'max_links'      => 3,
            'post_type'      => $post->post_type,
        ];
    }

    /**
     * Retrieves the focus keyword for a given post ID.
     *
     * Depending on the SEO plugin in use, this function fetches the focus keyword 
     * from different sources. If 'aioseo_table' is the focus keyword type, it 
     * queries the aioseo_posts table for keyphrases, decodes the JSON, and returns 
     * the keyphrase. Otherwise, it retrieves the keyword from post meta using the 
     * specified focus keyword type.
     *
     * @param int $post_id The ID of the post to retrieve the focus keyword for.
     * @return string The focus keyword associated with the post, or an empty string 
     *                if no keyword is found.
     */
    private function get_focus_keyword( $post_id ) {
        $focus_keyword_type = $this->focus_keyword();
        if ( $focus_keyword_type === 'aioseo_table' ) {
            global $wpdb;
            $keyphrases = $wpdb->get_var( $wpdb->prepare( "SELECT keyphrases FROM {$wpdb->prefix}aioseo_posts WHERE post_id = %d", $post_id ) );
            if ( $keyphrases ) {
                $data = json_decode( $keyphrases, true );
                return $data['focus']['keyphrase'] ?? '';
            }
        } else {
            return get_post_meta( $post_id, $focus_keyword_type, true );
        }
        return '';
    }

    /**
     * Update cached sync counts by subtracting processed items
     */
    private function update_sync_cache_count( int $processed_count ) : void {
        // Update badge cache
        $badge_cache = get_transient( 'ails_badge_count' );
        if ( $badge_cache !== false ) {
            $new_count = max( 0, (int) $badge_cache - $processed_count );
            set_transient( 'ails_badge_count', $new_count, 24 * HOUR_IN_SECONDS );
        }
        // Update sync totals cache
        $sync_cache = get_transient( 'ails_sync_totals' );
        if ( $sync_cache !== false && is_array( $sync_cache ) ) {
            $sync_cache['items'] = max( 0, $sync_cache['items'] - $processed_count );
            // Recalculate pages
            $batch_size = $this->get_batch_size();
            $sync_cache['pages'] = ceil( $sync_cache['items'] / $batch_size );
            set_transient( 'ails_sync_totals', $sync_cache, 24 * HOUR_IN_SECONDS );
        }
        $this->log( "Updated cache: subtracted {$processed_count} synced items", 'info' );
    }

}
