<?php
namespace Pagup\AutoLinks\Traits;

trait BulkFetchQueries {
    abstract protected function get_total_pages_and_items(): array;
    
    /**
     * Fetch posts from the database based on the given request array.
     *
     * @param array $request {
     *     @type int $batchSize The number of posts to fetch at once.
     *     @type int $offset The offset from which to start fetching posts.
     * }
     *
     * @return array An array of post IDs.
     */
    private function fetchPosts(array $request): array {
        global $wpdb;
        
        // Calculate remaining items for last page
        if ($request['page'] == $request['totalPages']) {
            $totalItems = $this->get_total_items();  // Get total items count
            $remainingItems = $totalItems - ($request['batchSize'] * ($request['totalPages'] - 1));
            $batchSize = $remainingItems;
        } else {
            $batchSize = $request['batchSize'];
        }

        // Update request with potentially modified batch size
        $request['batchSize'] = $batchSize;
        
        $query = $this->focus_keyword() === 'aioseo_table' 
            ? $this->getAioseoQuery($request)
            : $this->getDefaultQuery($request);

        $post_ids = $wpdb->get_results($query, ARRAY_N);
        return empty($post_ids) ? [] : array_column($post_ids, 0);
    }

    /**
     * SQL query to fetch posts from the database using the AIOSEO keyword table.
     *
     * The query fetches published posts with a non-empty title that do not have a log * entry yet and have a non-empty keyphrase in the AIOSEO table.
     * and is limited to the batch size specified in the request.
     *
     * @param array $request {
     *     @type int $batchSize The number of posts to fetch at once.
     *     @type int $offset The offset from which to start fetching posts.
     * }
     *
     * @return string The generated SQL query.
     */
    private function getAioseoQuery(array $request): string {
       global $wpdb;
       
       return $wpdb->prepare("
           SELECT p.ID as post_id
           FROM {$wpdb->posts} p
           INNER JOIN {$wpdb->prefix}aioseo_posts ap ON p.ID = ap.post_id
           LEFT JOIN {$this->table_log} log ON p.ID = log.post_id
           WHERE 
               p.post_type IN ({$this->post_types()})
               AND p.post_status = 'publish'
               AND p.post_title != ''
               AND p.post_title IS NOT NULL
               AND log.post_id IS NULL
               AND ap.keyphrases IS NOT NULL
               AND ap.keyphrases != ''
               AND NOT EXISTS (
                   SELECT 1 FROM {$wpdb->postmeta} pm2
                   WHERE pm2.post_id = p.ID
                       AND pm2.meta_key = 'disable_ails'
               )
           ORDER BY p.ID ASC
           LIMIT %d OFFSET %d
       ", $request['batchSize'], $request['offset']);
    }

    /**
     * SQL query to fetch posts from the database from default posts table.
     *
     * The query fetches published posts with a non-empty title that do not have a log * entry yet and have a non-empty keyphrase in the postmeta table.
     * and is limited to the batch size specified in the request.
     *
     * @param array $request {
     *     @type int $batchSize The number of posts to fetch at once.
     *     @type int $offset The offset from which to start fetching posts.
     * }
     *
     * @return string The generated SQL query.
     */ 
    private function getDefaultQuery(array $request): string {
        global $wpdb;
        
        // Ensure positive batchSize
        $batchSize = max(1, absint($request['batchSize']));
        $offset = absint($request['offset']);
        
        return $wpdb->prepare("
            SELECT pm.post_id
            FROM {$wpdb->postmeta} pm
            LEFT JOIN {$this->table_log} log ON pm.post_id = log.post_id
            LEFT JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE 
                p.post_type IN ({$this->post_types()})
                AND p.post_status = 'publish'
                AND p.post_title != ''
                AND p.post_title IS NOT NULL
                AND log.post_id IS NULL
                AND pm.meta_key = %s
                AND NOT EXISTS (
                    SELECT 1 FROM {$wpdb->postmeta} pm2
                    WHERE pm2.post_id = pm.post_id
                        AND pm2.meta_key = 'disable_ails'
                )
            ORDER BY pm.meta_id ASC
            LIMIT %d OFFSET %d
        ", $this->focus_keyword(), $batchSize, $offset);
     }

    private function get_total_items(): int {
        $totals = $this->get_total_pages_and_items();
        return $totals['items'];
    }
}