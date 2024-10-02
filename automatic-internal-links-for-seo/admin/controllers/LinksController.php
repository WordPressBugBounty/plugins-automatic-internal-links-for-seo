<?php
namespace Pagup\AutoLinks\Controllers;

use Pagup\AutoLinks\Traits\Helpers;
use Pagup\AutoLinks\Controllers\SettingsController;

class LinksController extends SettingsController
{
    use Helpers;

    private $table = AILS_TABLE;
    private $table_log = AILS_LOG_TABLE;

    /**
     * Ajax request to bulk fetch items (sync request)
    */
    public function bulk_fetch() {

        global $wpdb;

        if ( check_ajax_referer( 'crud_link', 'nonce', false ) == false ) {
            wp_send_json_error( "Invalid nonce", 401 );
            wp_die();
        }
        
        $batchSize = intval($_POST['batchSize']); // Set the batch size
        $page = intval($_POST['page']);
        $totalPages = intval($_POST['totalPages']);
        $offset = intval($_POST['offset']); // Calculate the offset

        $query = $wpdb->prepare("
            SELECT pm.post_id
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
                    WHERE pm2.post_id = pm.post_id
                        AND pm2.meta_key = 'disable_ails'
                )
            ORDER BY pm.meta_id ASC
            LIMIT %d OFFSET %d
        ", $batchSize, $offset);

        $post_ids = $wpdb->get_results($query, ARRAY_N);

            if (empty($post_ids)) {
                return;
            }

            $post_ids = array_column($post_ids, 0); // Convert the result set to a flat array

            $items = $this->get_items( $post_ids, true, false);

            $progress = ($page / $totalPages) * 100; // Calculate the progress percentage

            wp_send_json_success([
                'items' => $items,
                'progress' => $progress
            ]);

        wp_die();

    }

    /**
     * Ajax request to bulk add items in logs table (sync request)
    */
    public function bulk_add() {

        global $wpdb;

        $log_table = AILS_LOG_TABLE;
    
        // check the nonce
        if ( check_ajax_referer( 'crud_link', 'nonce', false ) == false ) {
            wp_send_json_error( "Invalid nonce", 401 );
            wp_die();
        }

        $post_id = sanitize_text_field($_POST['post_id']);
        
        $wptexturize = remove_filter( 'the_title', 'wptexturize' );
        $title = sanitize_text_field($_POST['title']);
        if ( $wptexturize ) add_filter( 'the_title', 'wptexturize' );

        $data = array(
            'post_id' => $post_id,
            'title' => $title,
            'keyword' => sanitize_text_field($_POST['keyword']),
            'url' => sanitize_text_field($_POST['url']),
            'use_custom' => sanitize_text_field(boolval($_POST['use_custom'])),
            'new_tab' => sanitize_text_field(boolval($_POST['new_tab'])) ? 1 : 0,
            'nofollow' => sanitize_text_field(boolval($_POST['nofollow'])) ? 1 : 0,
            'partial_match' => sanitize_text_field(boolval($_POST['partial_match'])) ? 1 : 0,
            'bold' => sanitize_text_field(boolval($_POST['bold'])) ? 1 : 0,
            'case_sensitive' => sanitize_text_field(boolval($_POST['case_sensitive'])) ? 1 : 0,
            'priority' => sanitize_text_field($_POST['priority']),
            'max_links' => sanitize_text_field($_POST['max_links']),
            'post_type' => $this->post_type($post_id)
        );

        $errors = [];
        $errors = $this->required($data, $errors);

        if ( count($errors) > 0 ) {
            wp_send_json_error( array( 
                'errors' => $errors,
                'link' => $data
            ), 400 );
            wp_die();
        }
        
        if ( empty($errors) ) {

            $item = $wpdb->get_row( "SELECT * FROM $log_table WHERE post_id = $post_id" );

            if ( $item === NULL ) {

                $result = $wpdb->insert(
                    $log_table, 
                    $data,
                    array( '%d', '%s', '%s', '%s', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%s' )
                );
            
                if ( $result === false ) {
                    
                    array_push($errors, '"'. $data['title'] . '" is not added. Something went wrong.' );

                    wp_send_json_error( array( 
                        'errors' => $errors,
                        'link' => $data
                    ), 400 );

                } else {

                    $lastid = $wpdb->insert_id;
            
                    $link = $wpdb->get_row( "SELECT * FROM $log_table WHERE id = $lastid" );
            
                    wp_send_json_success( [
                        'message' => $link->title . " link has been created",
                        'link' => $link
                    ] );
                }
            } else {
                wp_send_json_error( array( 
                    'errors' => ['Link already exists'],
                    'link' => $data
                ), 400 );
            }

        }
    
        wp_die();
    
    }

    /**
     * Ajax request to create link (manual internal links)
    */
    public function create_link() {

        global $wpdb;
    
        // check the nonce
        if ( check_ajax_referer( 'crud_link', 'nonce', false ) == false ) {
            wp_send_json_error( "Invalid nonce", 401 );
            wp_die();
        }

        $post_id = sanitize_text_field($_POST['post_id']);
        $url = ( isset($post_id) && !empty($post_id) ) ? get_permalink($post_id) : sanitize_text_field($_POST['url']);        

        $data = array(
            'post_id' => $post_id,
            'title' => sanitize_text_field($_POST['title']),
            'keyword' => sanitize_text_field($_POST['keyword']),
            'url' => $url,
            'use_custom' => sanitize_text_field(boolval($_POST['use_custom'])),
            'new_tab' => sanitize_text_field(boolval($_POST['new_tab'])) ? 1 : 0,
            'nofollow' => sanitize_text_field(boolval($_POST['nofollow'])) ? 1 : 0,
            'partial_match' => sanitize_text_field(boolval($_POST['partial_match'])) ? 1 : 0,
            'bold' => sanitize_text_field(boolval($_POST['bold'])) ? 1 : 0,
            'case_sensitive' => sanitize_text_field(boolval($_POST['case_sensitive'])) ? 1 : 0,
            'priority' => sanitize_text_field($_POST['priority']),
            'max_links' => sanitize_text_field($_POST['max_links']),
            'post_type' => "Post"
        );

        $errors = [];
        $errors = $this->required($data, $errors);
        if (!$data['use_custom'] && empty($data['post_id'])) array_push($errors, "Please select a Page / Post / Product");
        if ($data['use_custom'] && empty($data['url'])) array_push($errors, "URL is required");

        if ( count($errors) > 0 ) {
            wp_send_json_error( array( 'errors' => $errors), 400 );
            wp_die();
        }
        
        if ( empty($errors) ) {

            $result = $wpdb->insert( 
                $this->table, 
                $data,
                array( '%d', '%s', '%s', '%s', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%s' )
            );
        
            if ( $result === false ) {
                wp_send_json_error( "No title", 400 );
            } else {

                // Delete cache items
                $delete = $this->delete_all_transients();
        
                $link = $wpdb->get_row( "SELECT * FROM $this->table ORDER BY created_at DESC" );
        
                wp_send_json_success( [
                    'message' => $link->title . " link has been created",
                    'link' => $link,
                    'delete_status' => $delete
                ] );

            }
        
            wp_die();

        }
    
    }

    /**
     * Ajax request to update link (manual internal links)
    */
    public function update_link() {
        global $wpdb;
    
        // check the nonce
        if ( check_ajax_referer( 'crud_link', 'nonce', false ) == false ) {
            wp_send_json_error( "Invalid nonce", 401 );
            wp_die();
        }
    
        $post_id = sanitize_text_field($_POST['post_id']);
        $url = ( isset($post_id) && !empty($post_id) ) ? get_permalink($post_id) : sanitize_text_field($_POST['url']);

        $data = array(
            'post_id' => $post_id,
            'title' => sanitize_text_field($_POST['title']),
            'keyword' => sanitize_text_field($_POST['keyword']),
            'url' => $url,
            'new_tab' => sanitize_text_field(boolval($_POST['new_tab'])) ? 1 : 0,
            'nofollow' => sanitize_text_field(boolval($_POST['nofollow'])) ? 1 : 0,
            'partial_match' => sanitize_text_field(boolval($_POST['partial_match'])) ? 1 : 0,
            'bold' => sanitize_text_field(boolval($_POST['bold'])) ? 1 : 0,
            'case_sensitive' => sanitize_text_field(boolval($_POST['case_sensitive'])) ? 1 : 0,
            'priority' => sanitize_text_field($_POST['priority']),
            'max_links' => sanitize_text_field($_POST['max_links']),
            'post_type' => $this->post_type($post_id)
        );

        $errors = [];
        $errors = $this->required($data, $errors);

        $link_id = sanitize_text_field(intval($_POST['id']));
        $link = $wpdb->get_row( "SELECT * FROM $this->table WHERE id = $link_id" );

        if (!$link->use_custom) {
            if (empty($data['post_id'])) array_push($errors, "Please select a Page / Post / Product");
        } else {
            if (empty($data['url'])) array_push($errors, "URL is required");
        }

        if ( count($errors) > 0 ) {
            wp_send_json_error( array( 'errors' => $errors), 400 );
            wp_die();
        }

        if ( empty($errors) ) {
            
            $updated = $wpdb->update( 
                $this->table, 
                $data,
                array( 
                    'id' => $link_id
                ),
                array( '%d', '%s', '%s', '%s', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%s' ),
                array( '%d' )
            );
        
            if ( $updated === false ) {
    
                wp_send_json_error( 'Something went wrong', 400);
    
            } else {
        
                wp_send_json_success( [
                    'message' => "Link has been updated successfully",
                    'link' => $link
                ] );

                // Delete cache items
                $this->delete_all_transients();
            }
        
            wp_die();

        }
    
    }

    /**
     * Ajax request to update status for manual internal links (status checkbox)
    */
    public function update_status() {
        global $wpdb;
    
        // check the nonce
        if ( check_ajax_referer( 'crud_link', 'nonce', false ) == false ) {
            wp_send_json_error( "Invalid nonce", 401 );
            wp_die();
        }

        $link_id = sanitize_text_field(intval($_POST['id']));
    
        $updated = $wpdb->update( 
            $this->table, 
            array(
                'status' => sanitize_text_field($_POST['status']) == 'true' ? 1 : 0,
            ),
            array( 
                'id' => $link_id
            ),
            array( '%d' ),
            array( '%d' )
        );
    
        if ( $updated === false ) {

            wp_send_json_error( "Something went wrong", 401 );

        } else {
    
            $link = $wpdb->get_row( "SELECT * FROM $this->table WHERE id = $link_id" );
    
            wp_send_json_success( [
                'message' => "Status has been updated successfully",
                'link' => $link
            ] );

            // Delete cache items
            $this->delete_all_transients();
        }
    
        wp_die();
    
    }

    /**
     * Ajax request to delete item (manual internal links)
    */
    public function delete_item() {
        global $wpdb;
    
        // check the nonce
        if ( check_ajax_referer( 'crud_link', 'nonce', false ) == false ) {
            wp_send_json_error( "Invalid nonce", 401 );
            wp_die();
        }
    
        $id = sanitize_text_field(intval($_POST['id']));

        if (isset($_POST['table']) && !empty( $_POST['table'] )) {
            $table = $this->table_log;
        } else {
            $table = $this->table;
        }
    
        $item = $wpdb->get_row( "SELECT title FROM $table WHERE id = $id" );
    
        $deleted = $wpdb->delete(
            $table, 
            array(
                'id' => $id
            )
        );
    
        if ( $deleted === false ) {

            wp_send_json_error( 'Something went wrong. Item not deleted', 400);

        } else {

            wp_send_json_success( [
                'message' => $item->title . " has been deleted",
            ] );

            // Delete cache items
            $this->delete_all_transients();

        }
    
        wp_die();
    
    }

    /**
     * Ajax request to update Sync Date option & Delete transient cache
    */
    public function sync_date()
    {
        if ( check_ajax_referer( 'crud_link', 'nonce', false ) == false ) {
            wp_send_json_error( "Invalid nonce", 401 );
            wp_die();
        }

        if (isset($_POST['alldone']) && !empty($_POST['alldone'])) {
            date_default_timezone_set(wp_timezone_string());
            $date = date("F d, Y h:i:sa");
            update_option( "autolinks_sync", $date );
            
            // Delete cache items
            $this->delete_all_transients();
        }
    
        wp_die();
    }

}
$links = new LinksController();