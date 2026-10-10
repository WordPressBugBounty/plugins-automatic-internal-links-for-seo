<?php
namespace Pagup\AutoLinks\Controllers;

/** Fresh, read-only diagnosis requested by the Sync screen, not every admin page. */
class SyncStatusController extends SettingsController
{
    public function status(): void
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['code' => 'forbidden'], 403);
            return;
        }
        if (!check_ajax_referer('ails__nonce', 'nonce', false)) {
            wp_send_json_error(['code' => 'invalid_nonce'], 403);
            return;
        }
        $result = $this->inspect();
        if ($result['state'] === 'error') {
            wp_send_json_error($result, 500);
            return;
        }
        wp_send_json_success($result);
    }

    public function inspect(): array
    {
        global $wpdb;
        $result = ['state' => 'no_source', 'items' => 0, 'pages' => 0, 'eligible' => 0, 'keywords' => 0];
        $source = $this->focus_keyword();
        if ($source === '') {
            return $result;
        }
        $types = $this->sync_post_types();
        $result['state'] = 'no_content';
        if (!$types) {
            return $result;
        }
        $placeholders = implode(',', array_fill(0, count($types), '%s'));
        $cursor = 0;
        // Materialize distinct log IDs once per batch, not a full log scan per post.
        // Keyset batches bound memory, including on sites with many posts. This
        // uses the same PHP keyword extraction as synchronization, without a
        // dependency on database-specific JSON functions.
        do {
            $raw = $source === 'aioseo_table'
                ? "(SELECT ap.keyphrases FROM {$wpdb->prefix}aioseo_posts ap WHERE ap.post_id = p.ID LIMIT 1)"
                : $wpdb->prepare("(SELECT pm.meta_value FROM {$wpdb->postmeta} pm WHERE pm.post_id = p.ID AND pm.meta_key = %s ORDER BY pm.meta_id DESC LIMIT 1)", $source);
            $query = $wpdb->prepare(
                "SELECT p.ID, $raw AS raw_keyword,
                    log.post_id IS NOT NULL AS synced
                 FROM {$wpdb->posts} p
                 LEFT JOIN (SELECT DISTINCT post_id FROM " . AILS_LOG_TABLE . ") log ON log.post_id = p.ID
                 WHERE p.ID > %d AND p.post_type IN ($placeholders)
                 AND p.post_status = 'publish' AND p.post_title != ''
                 AND NOT EXISTS(SELECT 1 FROM {$wpdb->postmeta} excluded WHERE excluded.post_id = p.ID AND excluded.meta_key = 'disable_ails')
                 ORDER BY p.ID ASC LIMIT 500",
                ...array_merge([$cursor], $types)
            );
            $rows = $wpdb->get_results($query);
            if ($wpdb->last_error !== '' || !is_array($rows)) {
                return ['state' => 'error', 'items' => null, 'pages' => null];
            }
            foreach ($rows as $row) {
                $cursor = (int) $row->ID;
                $result['eligible']++;
                $keyword = $source === 'aioseo_table'
                    ? $this->extract_aioseo_focus_keyword($row->raw_keyword)
                    : sanitize_text_field((string) $row->raw_keyword);
                if (trim($keyword) !== '') {
                    $result['keywords']++;
                    if (!(int) $row->synced) {
                        $result['items']++;
                    }
                }
            }
        } while (count($rows) === 500);

        if ($result['eligible'] === 0) {
            return $result;
        }
        $result['state'] = $result['keywords'] === 0 ? 'no_keywords' : ($result['items'] > 0 ? 'pending' : 'current');
        // Bulk fetch paginates source records, including empty keyword records
        // removed before display. Keep its page count separate from usable items.
        $totals = $this->get_total_pages_and_items();
        if ($wpdb->last_error !== '') {
            return ['state' => 'error', 'items' => null, 'pages' => null];
        }
        $result['pages'] = $totals['pages'];
        return $result;
    }
}
