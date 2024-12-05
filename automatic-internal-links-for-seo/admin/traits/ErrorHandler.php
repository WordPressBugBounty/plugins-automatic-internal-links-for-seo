<?php

namespace Pagup\AutoLinks\Traits;

trait ErrorHandler {
    private $is_test_mode = false;
    private $test_error = '';

    // Method to enable test mode
    protected function enableTestMode($error_type = '') {
        $this->is_test_mode = true;
        $this->test_error = $error_type;
    }

    /**
     * Handle common error responses for CRUD operations
     * // Test specific error in the CRUD controller:
     * // $this->enableTestMode('nonce');     // Test nonce failure
     * // $this->enableTestMode('permission'); // Test permission failure
     * // $this->enableTestMode('not_found'); // Test item not found
     * // $this->enableTestMode('db_error');  // Test database error
     * // $this->enableTestMode('invalid_data');  // Test invalid data
     */
    protected function handleError($type, $message = '', $data = null, $status = 400) {

        if ($this->is_test_mode) {
            $type = $this->test_error ?: $type;
        }

        $error_messages = [
            'permission' => [
                'message' => 'Permission denied: You do not have sufficient permissions',
                'status' => 403
            ],
            'nonce' => [
                'message' => 'Security verification failed',
                'status' => 401
            ],
            'not_found' => [
                'message' => 'Requested item(s) not found',
                'status' => 404
            ],
            'db_error' => [
                'message' => 'Database operation failed',
                'status' => 500
            ],
            'invalid_data' => [
                'message' => 'Invalid or missing data provided',
                'status' => 400
            ],
            'pagination_error' => [
                'message' => 'Invalid pagination parameters',
                'status' => 400
            ],
            'query_error' => [
                'message' => 'Error executing database query',
                'status' => 500
            ],
            'duplicate_entry' => [
                'message' => 'Log already exists for this post',
                'status' => 400
            ],
            'invalid_post' => [
                'message' => 'Invalid post ID or post does not exist',
                'status' => 400
            ],
            'missing_required' => [
                'message' => 'Required fields are missing',
                'status' => 400
            ],
            'validation_error' => [
                'message' => 'Data validation failed',
                'status' => 400
            ]
        ];

        $error = isset($error_messages[$type]) 
            ? $error_messages[$type] 
            : ['message' => 'Unknown error occurred', 'status' => 500];

        $final_message = !empty($message) ? $message : $error['message'];
        $final_status = $status ?: $error['status'];

        wp_send_json_error(
            [
                'message' => $final_message,
                'data' => $data
            ], 
            $final_status
        );
    }

    /**
     * Validate common requirements for CRUD operations
     */
    protected function validateRequest($nonce_action = 'ails__nonce') {
        if ($this->is_test_mode) {
            $this->handleError($this->test_error);
            return false;
        }

        if (!current_user_can('manage_options')) {
            $this->handleError('permission');
            return false;
        }

        if (!check_ajax_referer($nonce_action, 'nonce', false)) {
            $this->handleError('nonce');
            return false;
        }

        return true;
    }
}