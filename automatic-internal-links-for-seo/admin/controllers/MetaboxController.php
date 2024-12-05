<?php
namespace Pagup\AutoLinks\Controllers;

use Pagup\AutoLinks\Core\Option;
use Pagup\AutoLinks\Core\Plugin;
use Pagup\AutoLinks\Core\Request;
use WP_Post;

class MetaboxController extends SettingsController {
    private string $table_log;
    private const META_KEY = 'disable_ails';
    private const METABOX_ID = 'ails_post_options';

    public function __construct() {
        $this->table_log = AILS_LOG_TABLE;
    }

    /**
     * Adds metabox to supported post types
     */
    public function add_metabox(): void {
        if (!$this->isPremiumEnabled()) {
            return;
        }

        $post_types = $this->getSupportedPostTypes();

        foreach ($post_types as $post_type) {
            add_meta_box(
                self::METABOX_ID,
                __('Automatic Internal Links'),
                [$this, 'metabox'],
                $post_type,
                'side',
                'low'
            );
        }
    }

    /**
     * Renders metabox content
     */
    public function metabox(WP_Post $post): void {
        if (!$this->isPremiumEnabled()) {
            return;
        }

        $data = [
            self::META_KEY => get_post_meta($post->ID, self::META_KEY, true)
        ];

        Plugin::view('metabox', $data);
    }

    /**
     * Handles metadata updates
     */
    public function metadata(int $post_id): bool {
        if (!$this->canProcessMetadata($post_id)) {
            return false;
        }

        $safe = [self::META_KEY];
        
        if (Request::safe(self::META_KEY, $safe)) {
            update_post_meta($post_id, self::META_KEY, true);
        } else {
            delete_post_meta($post_id, self::META_KEY);
        }

        return true;
    }

    /**
     * Handles post data insertion/update in logs table
     */
    public function insert_update_data(int $meta_id, int $post_id, string $meta_key, $meta_value): void {
        if (!$this->canProcessUpdate($post_id)) {
            return;
        }

        $post_data = $this->preparePostData($post_id);
        if (empty($post_data)) {
            return;
        }

        $this->updateLogTable($post_id, $post_data);
    }

    /**
     * Checks if premium features are enabled
     */
    private function isPremiumEnabled(): bool {
        return ails__fs()->can_use_premium_code__premium_only();
    }

    /**
     * Gets supported post types
     */
    private function getSupportedPostTypes(): array {
        return Option::check('post_types') 
            ? maybe_unserialize(Option::get('post_types')) 
            : ['post', 'page'];
    }

    /**
     * Validates if metadata can be processed
     */
    private function canProcessMetadata(int $post_id): bool {
        if (!$this->isPremiumEnabled()) {
            return false;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return false;
        }

        if (!current_user_can('edit_page', $post_id)) {
            return false;
        }

        return !empty($post_id);
    }

    /**
     * Validates if update can be processed
     */
    private function canProcessUpdate(int $post_id): bool {
        if (!$this->canProcessMetadata($post_id)) {
            return false;
        }

        $disabled_via_settings = Option::check('disable_autlinks') 
            ? Option::get('disable_autlinks') 
            : '';
        
        if (!empty($disabled_via_settings)) {
            return false;
        }

        $post_types = $this->getSupportedPostTypes();
        $screen = get_current_screen();
        
        return in_array($screen->post_type, $post_types);
    }

    /**
     * Prepares post data for database operations
     */
    private function preparePostData(int $post_id): ?array {
        $focus_keyword = get_post_meta($post_id, '_yoast_wpseo_focuskw', true);
        if (empty($focus_keyword)) {
            return null;
        }

        $disabled_via_meta = get_post_meta($post_id, self::META_KEY, true);
        if (!empty($disabled_via_meta)) {
            return null;
        }

        $wptexturize = remove_filter('the_title', 'wptexturize');
        $title = get_the_title();
        if ($wptexturize) {
            add_filter('the_title', 'wptexturize');
        }

        $link = new LinksController();

        return [
            'post_id' => $post_id,
            'title' => $title,
            'keyword' => $focus_keyword,
            'url' => get_permalink($post_id),
            'post_type' => $link->post_type($post_id),
        ];
    }

    /**
     * Updates the log table with post data
     */
    private function updateLogTable(int $post_id, array $data): void {
        global $wpdb;

        $item = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_log} WHERE post_id = %d",
                $post_id
            )
        );

        if ($item !== null) {
            $this->handleExistingRecord($post_id, $data);
        } else {
            $this->handleNewRecord($data);
        }

        delete_transient("ails_item_{$post_id}");
    }

    /**
     * Handles updating existing log record
     */
    private function handleExistingRecord(int $post_id, array $data): void {
        global $wpdb;

        $disabled_via_meta = get_post_meta($post_id, self::META_KEY, true);

        if (empty($disabled_via_meta)) {
            $result = $wpdb->update(
                $this->table_log,
                $data,
                ['post_id' => $post_id],
                ['%d', '%s', '%s', '%s', '%s'],
                ['%d']
            );
        } else {
            $result = $wpdb->delete(
                $this->table_log,
                ['post_id' => $post_id]
            );
        }

        if ($result) {
            $this->delete_all_transients();
        }
    }

    /**
     * Handles inserting new log record
     */
    private function handleNewRecord(array $data): void {
        global $wpdb;

        if (get_post_status($data['post_id']) === 'publish') {
            $result = $wpdb->insert(
                $this->table_log,
                $data,
                ['%d', '%s', '%s', '%s', '%s']
            );

            if ($result) {
                $this->delete_all_transients();
            }
        }
    }
}