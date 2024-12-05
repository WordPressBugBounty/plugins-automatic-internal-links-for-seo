<?php
namespace Pagup\AutoLinks\Controllers;

use Pagup\AutoLinks\Traits\ErrorHandler;
use Pagup\AutoLinks\Traits\BulkFetchQueries;
use Pagup\AutoLinks\Traits\BulkAddQueries;
use Pagup\AutoLinks\Controllers\SettingsController;

class BulkOperationsController extends SettingsController {
   use ErrorHandler, BulkFetchQueries, BulkAddQueries;

   private string $table_log;
   
   public function __construct() {
       $this->table_log = AILS_LOG_TABLE;
   }

    /**
     * Handles the fetching of posts via an AJAX request with pagination.
     *
     * Validates the request and pagination parameters before proceeding to fetch posts.
     * If the request is invalid, an error is handled and logged. If no posts are found,
     * a successful response is returned with an empty items array and 100% progress.
     * Otherwise, it fetches the items and calculates the progress based on the current page.
     * In case of an exception, it handles the error.
     *
     * @return void
     */
   public function fetch() {
        if (!$this->validateRequest('ails__nonce')) {
            return;
        }
    
        $request = $this->validateFetchRequest();
        if (!$request) {
            $this->handleError(
                'invalid_data',
                'Invalid pagination parameters', 
                ['required' => ['batchSize', 'page', 'totalPages', 'offset']]
            );
        }
    
        try {
            $posts = $this->fetchPosts($request);
            
            if (empty($posts)) {
                wp_send_json_success([
                    'items' => [],
                    'progress' => 100
                ]);
            }
    
            $items = $this->get_items($posts, true, false);
            $progress = ($request['page'] / $request['totalPages']) * 100;
    
            wp_send_json_success([
                'items' => $items,
                'progress' => $progress
            ]);
    
        } catch (\Exception $e) {
            $this->handleError('query_error', 'Error fetching posts', null, 500);
        }
    }
    
    /**
     * Handle add link request
     *
     * @return void
     *
     * @throws \Exception
     */
    public function add() {
        if (!$this->validateRequest('ails__nonce')) {
            return;
        }
    
        if (!isset($_POST['data'])) {
            $this->handleError('invalid_data', 'No data provided');
        }
    
        $data = $this->validateAddRequest($_POST['data']);
        
        if (empty($data['post_id']) || empty($data['title']) || empty($data['keyword'])) {
            error_log('Missing required fields');
            $this->handleError(
                'missing_required', 
                'Required fields missing', 
                ['required' => ['post_id', 'title', 'keyword']]
            );
        }
    
        try {
            if ($this->logExists($data['post_id'])) {
                error_log('Log exists for post: ' . $data['post_id']);
                $this->handleError(
                    'duplicate_entry', 
                    'Log already exists for post: ' . $data['title'], 
                    $data
                );
            }
    
            $result = $this->insertLog($data);
            // error_log('Insert result: ' . ($result ? 'success' : 'failed'));
            
            if (!$result) {
                $this->handleError(
                    'db_error',
                    'Failed to create log for: ' . $data['title'],
                    $data
                );
            }
    
            $log = $this->getLogById($result);
            
            wp_send_json_success([
                'message' => $log->title . " log has been created",
                'link' => $log
            ]);
    
        } catch (\Exception $e) {
            error_log('Exception: ' . $e->getMessage());
            $this->handleError(
                'db_error', 
                'Database operation failed', 
                $data,
                500
            );
        }
    }

    /**
     * Validates the request for fetching posts with pagination.
     *
     * @return array|null returns the sanitized request data or null if the request is invalid
     */
   private function validateFetchRequest(): ?array {
       $required = ['batchSize', 'page', 'totalPages', 'offset'];
   
       foreach ($required as $field) {
           if (!isset($_POST[$field])) {
               return null;
           }
       }

       $batchSize = absint(sanitize_text_field($_POST['batchSize']));
       $page = absint(sanitize_text_field($_POST['page']));
       $totalPages = absint(sanitize_text_field($_POST['totalPages']));
       $offset = absint(sanitize_text_field($_POST['offset']));

       // Ensure we have valid positive numbers
       if ($batchSize < 1 || $page < 1 || $totalPages < 1) {
           return null;
       }

       return compact('batchSize', 'page', 'totalPages', 'offset');
   }

    /**
     * Validates and sanitizes the given data for adding a new item.
     *
     * This function ensures that the required fields are present, sanitizes the inputs,
     * and returns the sanitized data in the correct order. It handles specific fields
     * such as 'post_id', 'title', 'url', and 'post_type', and performs boolean checks
     * for fields like 'use_custom', 'new_tab', 'nofollow', 'partial_match', 'bold', and
     * 'case_sensitive'. Text fields including 'keyword', 'priority', and 'max_links'
     * are also sanitized.
     *
     * @param array $data The input data to validate and sanitize.
     * @return array The sanitized data.
     */
    private function validateAddRequest(array $data): array 
    {
        $sanitized = [];

        $field_order = [
            'post_id', 'title', 'keyword', 'url', 'use_custom', 'new_tab', 'nofollow', 
            'partial_match', 'bold', 'case_sensitive', 'priority', 'max_links', 'post_type'
        ];

        $boolean_fields = ['use_custom', 'new_tab', 'nofollow', 'partial_match', 'bold', 'case_sensitive'];
        $text_fields = ['keyword', 'priority', 'max_links'];

        // Handle post_id and related fields
        $post_id = isset($data['post_id']) ? absint($data['post_id']) : 0;
        $sanitized['post_id'] = $post_id;
        $sanitized['title'] = sanitize_text_field($data['title'] ?? '');
        $sanitized['url'] = sanitize_text_field($data['url'] ?? '');
        $sanitized['post_type'] = $this->post_type($post_id);

        // Sanitize other fields
        foreach ($field_order as $field) {
            if (!isset($sanitized[$field])) {
                if (in_array($field, $boolean_fields)) {
                    $sanitized[$field] = isset($data[$field]) ? (filter_var($data[$field], FILTER_VALIDATE_BOOLEAN) ? 1 : 0) : 0;
                } elseif (in_array($field, $text_fields)) {
                    $sanitized[$field] = isset($data[$field]) ? sanitize_text_field($data[$field]) : '';
                }
            }
        }

        // Ensure all fields are present in correct order
        return array_merge(array_flip($field_order), $sanitized);
    }
}