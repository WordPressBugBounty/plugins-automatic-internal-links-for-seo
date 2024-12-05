<?php
namespace Pagup\AutoLinks\Traits;

trait BulkAddQueries {
    /**
     * Check if a log exists for a given post ID.
     *
     * @param int $post_id The post ID.
     *
     * @return bool True if a log exists, false otherwise.
     */
    private function logExists(int $post_id): bool {
        global $wpdb;
        
        $result = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->table_log} WHERE post_id = %d", $post_id)
        );
        
        return $result !== null;
    }

    /**
     * Inserts a log entry into the database.
     *
     * @param array $data Associative array containing the log data with keys
     *
     * @return int|false The ID of the inserted log entry on success, or false on failure.
     */
    private function insertLog(array $data) {
        global $wpdb;
        
        $result = $wpdb->insert(
            $this->table_log,
            $data,
            [
                '%d', // post_id
                '%s', // title
                '%s', // keyword
                '%s', // url
                '%d', // use_custom
                '%d', // new_tab
                '%d', // nofollow
                '%d', // partial_match
                '%d', // bold
                '%d', // case_sensitive
                '%d', // priority
                '%d', // max_links
                '%s'  // post_type
            ]
        );

        if ($result === false) {
            return false;
        }

        return $wpdb->insert_id;
    }

    /**
     * Get log by ID.
     *
     * @param int $id The log ID.
     *
     * @return object|null The log object or null if not found.
     */
    private function getLogById(int $id): ?object {
        global $wpdb;
        
        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->table_log} WHERE id = %d", $id)
        );
    }
}