<?php
namespace Pagup\AutoLinks\Controllers;

/** Admin-only diagnostics. No remote fetch, option changes or content writes. */
class PageCheckController
{
    private function authorized(): bool
    {
        if (!current_user_can('manage_options') || !check_ajax_referer('ails__nonce', 'nonce', false)) {
            wp_send_json_error(['code' => 'forbidden'], 403);
            return false;
        }
        return true;
    }

    public function search(): void
    {
        if (!$this->authorized()) {
            return;
        }
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- authorized() checks capability and nonce immediately above; the search string is sanitized here.
        $search = isset($_POST['search']) && is_string($_POST['search']) ? sanitize_text_field(wp_unslash($_POST['search'])) : '';
        $types = array_values(array_diff(get_post_types(['public' => true]), ['attachment']));
        $query = new \WP_Query([
            'post_type' => $types,
            'post_status' => 'publish',
            'has_password' => false,
            'posts_per_page' => 20,
            's' => mb_substr($search, 0, 100),
            'orderby' => ['date' => 'DESC', 'ID' => 'DESC'],
            'no_found_rows' => true,
        ]);
        $posts = [];
        foreach ($query->posts as $post) {
            if (current_user_can('read_post', $post->ID)) {
                $posts[] = ['id' => (int) $post->ID, 'title' => get_the_title($post->ID)];
            }
        }
        wp_send_json_success($posts);
    }

    public function check(): void
    {
        if (!$this->authorized()) {
            return;
        }
        // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- authorized() checks capability and nonce above; the following canonical positive-decimal check rejects all other input before use.
        $id = isset($_POST['post_id']) && is_scalar($_POST['post_id']) ? (string) wp_unslash($_POST['post_id']) : '';
        if (!ctype_digit($id) || (string) (int) $id !== $id || (int) $id <= 0) {
            wp_send_json_error(['code' => 'invalid_post'], 400);
            return;
        }
        $post = get_post((int) $id);
        if (!$post || $post->post_status !== 'publish' || $post->post_password !== '' || !is_post_type_viewable($post->post_type) || !current_user_can('read_post', $post->ID)) {
            wp_send_json_error(['code' => 'invalid_post'], 404);
            return;
        }
        try {
            $renderer = new ReplaceController(false);
            $result = $renderer->preview((int) $post->ID, (string) $post->post_content);
            $result['post'] = ['id' => (int) $post->ID, 'title' => get_the_title($post->ID), 'url' => esc_url_raw(get_permalink($post->ID), ['http','https'])];
            $result['scope'] = 'stored_content_preview';
            wp_send_json_success($result);
        } catch (\Throwable $error) {
            $code = in_array($error->getMessage(), ['content_too_large','too_many_rules','query_error'], true) ? $error->getMessage() : 'preview_error';
            wp_send_json_error(['code' => $code], 422);
        }
    }
}
