<?php
namespace Pagup\AutoLinks\Controllers;

use Pagup\AutoLinks\Controllers\SettingsController;
use Pagup\AutoLinks\Traits\ErrorHandler;

class LinksController extends SettingsController
{
    use ErrorHandler;

    private $table = AILS_TABLE;
    private $table_log = AILS_LOG_TABLE;

    /**
     * Fetches manual links from the database and returns them in JSON format.
     *
     * Validates the request before proceeding. Queries the database for manual
     * links where the 'Keyword' field is not empty and orders them by creation date
     * in descending order. If an error occurs during the database query, it handles
     * the error and returns. If no records are found, it returns an empty JSON response.
     * On success, returns the manual links in JSON format.
     *
     * @return void
     */
    public function get_manual_links() {
        if (!$this->validateRequest()) {
            return;
        }
        
        global $wpdb;
        
        $manual_links = $wpdb->get_results(
            "SELECT * FROM $this->table WHERE COALESCE(Keyword, '') != '' ORDER BY created_at DESC",
            OBJECT
        );
        
        if ($manual_links === false) {
            $this->handleError(
                'db_error',
                'Failed to fetch manual links'
            );
            return;
        }
        
        if (empty($manual_links)) {
            // Optionally handle no records found
            wp_send_json_success([]);
            return;
        }
        
        wp_send_json_success($manual_links);
    }
    
    /**
     * Fetches activity logs from the database and returns them in JSON format.
     *
     * Validates the request before proceeding. Queries the database for activity
     * logs where the 'Keyword' field is not empty and orders them by update date
     * in descending order. If an error occurs during the database query, it handles
     * the error and returns. If no records are found, it returns an empty JSON response.
     * On success, returns the activity logs in JSON format.
     *
     * @return void
     */
    function get_activity_logs() {
        if (!$this->validateRequest()) {
            return;
        }
        
        global $wpdb;
        
        $logs = $wpdb->get_results(
            "SELECT id, post_id, title, keyword, post_type, created_at, updated_at 
             FROM $this->table_log 
             WHERE COALESCE(Keyword, '') != '' 
             ORDER BY updated_at DESC",
            OBJECT
        );
        
        if ($logs === false) {
            $this->handleError(
                'db_error',
                'Failed to fetch activity logs'
            );
            return;
        }
        
        if (empty($logs)) {
            // Optionally handle no records found
            wp_send_json_success([]);
            return;
        }
        
        wp_send_json_success($logs);
    }
    
    /**
     * Handles the link creation process from the link creation form.
     *
     * Checks the nonce, sanitizes and validates the form data, and inserts
     * the link into the database. If an error occurs during the insertion
     * process, it handles the error and returns. If the insertion is successful,
     * deletes cache items and fetches the newly inserted row by its ID and
     * returns the link in JSON format.
     *
     * @return void
     */
    public function create_link() {

        global $wpdb;
    
        if (!$this->validateRequest()) {
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- validateRequest() verifies AJAX nonce and capability before reading input.
        $post_data = isset($_POST['data']) ? wp_unslash($_POST['data']) : [];
        if (!is_array($post_data)) {
            $this->handleError('invalid_data', 'Invalid link data');
            return;
        }

        $data = $this->sanitize_link_data($post_data);

        $errors = [];
        $errors = $this->required($data, $errors);

        if (!$data['use_custom'] && empty($data['post_id'])) array_push($errors, "Please select a Page / Post / Product");
        
        if ($data['use_custom'] && empty($data['url'])) array_push($errors, "URL is required");

        if ($data['use_custom'] && empty($data['title'])) array_push($errors, "Title is required for custom link");

        if ( count($errors) > 0 ) {
            wp_send_json_error( array( 'message' => 'Validation failed', 'errors' => $errors), 422 );
        } else {

            $result = $wpdb->insert( 
                $this->table, 
                $data,
                array( '%d', '%s', '%s', '%s', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%s' )
            );
            
            if ( $result === false ) {
                wp_send_json_error( "No title", 400 );
            } else {
                $new_link_id = $wpdb->insert_id;
            
                // Fetch the newly inserted row by its ID
                $link = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $this->table WHERE id = %d", $new_link_id ) );
            
                wp_send_json_success( [
                    'message' => $link->title . " link has been created",
                    'link' => $link,
                ] );

                // Delete cache items
                $this->delete_all_transients();
            }
        
            wp_die();

        }
    
    }

    /**
     * Update an existing link with provided data.
     *
     * This function validates the request and updates an existing link in the database.
     * It checks the nonce, sanitizes input data, and validates required fields. If there
     * are validation errors, it returns an error response. Otherwise, it updates the link
     * in the database and returns a success response.
     *
     * @global wpdb $wpdb WordPress database abstraction object.
     *
     * @throws WPDieException If nonce validation fails or if there are validation errors.
     *
     * @return void Responds with a JSON success or error message.
     */
    public function update_link() {
        global $wpdb;
    
        if (!$this->validateRequest()) {
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- validateRequest() verifies AJAX nonce and capability before reading input.
        $post_data = isset($_POST['data']) ? wp_unslash($_POST['data']) : [];
        if (!is_array($post_data)) {
            $this->handleError('invalid_data', 'Invalid link data');
            return;
        }

        $data = $this->sanitize_link_data($post_data);

        $errors = [];
        $errors = $this->required($data, $errors);

        $link_id = isset($post_data['id']) ? absint($post_data['id']) : 0;
        $link = $wpdb->get_row($wpdb->prepare("SELECT * FROM $this->table WHERE id = %d", $link_id));

        if (!$data['use_custom'] && empty($data['post_id'])) array_push($errors, "Please select a Page / Post / Product");
        
        if ($data['use_custom'] && empty($data['url'])) array_push($errors, "URL is required");

        if ($data['use_custom'] && empty($data['title'])) array_push($errors, "Title is required for custom link");

        if ( count($errors) > 0 ) {
            
            wp_send_json_error( array( 'errors' => $errors), 400 );
            wp_die();

        } else {
            
            $updated = $wpdb->update( 
                $this->table, 
                $data,
                array( 
                    'id' => $link_id
                ),
                array( '%d', '%s', '%s', '%s', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%s' ),
                array( '%d' )
            );
        
            if ( $updated === false ) {
    
                wp_send_json_error( array(
                    'message' => 'Something went wrong',
                ), 400);
    
            } else {
        
                wp_send_json_success( [
                    'message' => "Link has been updated successfully",
                    'link' => $updated,
                ] );

                // Delete cache items
                $this->delete_all_transients();
            }
        
            wp_die();

        }
    
    }

    /**
     * Update the status of a link with the given ID.
     *
     * This function validates the request and updates the status of a link in the database.
     * It checks the nonce, sanitizes input data, and validates required fields. If there
     * are validation errors, it returns an error response. Otherwise, it updates the link
     * in the database and returns a success response.
     *
     * @global wpdb $wpdb WordPress database abstraction object.
     *
     * @throws WPDieException If nonce validation fails or if there are validation errors.
     *
     * @return void Responds with a JSON success or error message.
     */
    public function update_status() {
        global $wpdb;
    
        if (!$this->validateRequest()) {
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- validateRequest() verifies AJAX nonce and capability before reading input.
        $link_id = isset($_POST['id']) ? absint(wp_unslash($_POST['id'])) : 0;
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- validateRequest() verifies AJAX nonce and capability before reading input.
        $status = isset($_POST['status']) ? sanitize_text_field(wp_unslash($_POST['status'])) : '';
    
        $updated = $wpdb->update( 
            $this->table, 
            array(
                'status' => $status == 'true' ? 1 : 0,
            ),
            array( 
                'id' => $link_id
            ),
            array( '%d' ),
            array( '%d' )
        );
    
        if ( $updated === false ) {

            wp_send_json_error( "Something went wrong" );

        } else {
    
            $link = $wpdb->get_row($wpdb->prepare("SELECT * FROM $this->table WHERE id = %d", $link_id));
    
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
     * Delete an item from the database and remove related cache.
     *
     * Validates the request, retrieves the item ID from the POST data,
     * and checks if the item exists in the specified table. If the item
     * exists, it attempts to delete the item from the database. If successful,
     * it sends a JSON success response with a deletion message. If any
     * error occurs during validation or deletion, appropriate error handling
     * is performed.
     *
     * @return void
     */
    public function delete_item() {
        // Test mode if needed
        // $this->enableTestMode('nonce');
     
        if (!$this->validateRequest()) {
            return;
        }
     
        global $wpdb;
        
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- validateRequest() verifies AJAX nonce and capability before reading input.
        $id = isset($_POST['id']) ? absint(wp_unslash($_POST['id'])) : 0;
        
        // Validate ID
        if (!$id) {
            $this->handleError(
                'invalid_data',
                'Invalid or missing item ID'
            );
            return;
        }
     
        // Determine table
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- validateRequest() verifies AJAX nonce and capability before reading input.
        $table_flag = isset($_POST['table']) ? sanitize_text_field(wp_unslash($_POST['table'])) : '';
        $table = !empty($table_flag)
            ? $this->table_log 
            : $this->table;
        
        // Check if item exists
        $item = $wpdb->get_row($wpdb->prepare("SELECT title FROM $table WHERE id = %d", $id));
        if (!$item) {
            $this->handleError(
                'not_found',
                "Item with ID $id not found"
            );
            return;
        }
        
        // Try to delete
        $deleted = $wpdb->delete(
            $table, 
            ['id' => $id]
        );
        
        if ($deleted === false) {
            $this->handleError(
                'db_error',
                'Failed to delete item'
            );
            return;
        }
        
        // Clear badge/sync cache
        delete_transient('ails_badge_count');
        delete_transient('ails_sync_totals');
     
        // Delete cache items
        $this->delete_all_transients();
     
        wp_send_json_success([
            'message' => $item->title . " has been deleted",
        ]);
    }

    /**
     * Deletes multiple items from the database in bulk.
     *
     * Validates the request, retrieves the IDs from the POST data, and checks
     * if the items exist in the specified table. If the items exist, it attempts
     * to delete the items from the database. If successful, it sends a JSON
     * success response with a deletion message. If any error occurs during
     * validation or deletion, appropriate error handling is performed.
     *
     * @return void
     */
    public function bulk_delete_items() {

        if (!$this->validateRequest()) {
            return;
        }

        global $wpdb;

        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- validateRequest() verifies AJAX nonce and capability before reading input.
        $ids = isset($_POST['ids']) ? array_filter(array_map('absint', (array)wp_unslash($_POST['ids']))) : [];
        if (empty($ids)) {
            $this->handleError('invalid_data', 'No items selected for deletion');
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- validateRequest() verifies AJAX nonce and capability before reading input.
        $table_flag = isset($_POST['table']) ? sanitize_text_field(wp_unslash($_POST['table'])) : '';
        $table = !empty($table_flag)
            ? $this->table_log 
            : $this->table;

        // Prepare the query with placeholders
        $placeholders = implode(',', array_fill(0, count($ids), '%d'));
        $query = $wpdb->prepare(
            "DELETE FROM $table WHERE id IN ($placeholders)",
            $ids
        );

        $result = $wpdb->query($query);
        if ($result === false) {
            $this->handleError('db_error', 'Failed to delete items');
            return;
        }

        // Partial deletion check
        if ($result < count($ids)) {
            $this->handleError(
                'db_error', 
                'Some items could not be deleted', 
                ['deleted_count' => $result],
                207
            );
            return;
        }

        // Clear badge/sync cache
        delete_transient('ails_badge_count');
        delete_transient('ails_sync_totals');

        // Delete cache items
        $this->delete_all_transients();

        wp_send_json_success([
            'message' => count($ids) . ' items have been deleted',
            'deleted_ids' => $ids
        ]);
    }

    /**
     * Handles the sync date update request sent via AJAX.
     *
     * Validates the request and checks if the "alldone" parameter is set.
     * If it is, it updates the "autolinks_sync" option with the current date and time.
     * Finally, it deletes all cache items and dies.
     *
     * @return void
     */
    public function sync_date()
    {
        if (!$this->validateRequest()) {
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- validateRequest() verifies AJAX nonce and capability before reading input.
        $all_done = isset($_POST['alldone']) ? sanitize_text_field(wp_unslash($_POST['alldone'])) : '';
        if (!empty($all_done)) {
            date_default_timezone_set(wp_timezone()->getName());
            $date = date("F d, Y h:i:sa");
            update_option( "autolinks_sync", $date );
            
            // Delete cache items
            $this->delete_all_transients();
        }
    
        wp_die();
    }

}
