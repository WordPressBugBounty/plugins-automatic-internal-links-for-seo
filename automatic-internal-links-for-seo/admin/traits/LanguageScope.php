<?php

namespace Pagup\AutoLinks\Traits;

trait LanguageScope
{
    private array $ails_language_scope_cache = [];

    private function get_post_language_code(int $post_id): string
    {
        if ($post_id <= 0) {
            return '';
        }

        if (isset($this->ails_language_scope_cache[$post_id])) {
            return $this->ails_language_scope_cache[$post_id];
        }

        $post_type = get_post_type($post_id);
        if (empty($post_type) || !has_filter('wpml_element_language_code')) {
            $this->ails_language_scope_cache[$post_id] = '';
            return '';
        }

        $language = apply_filters(
            'wpml_element_language_code',
            null,
            [
                'element_id' => $post_id,
                'element_type' => $post_type,
            ]
        );

        $this->ails_language_scope_cache[$post_id] = is_string($language) ? sanitize_key($language) : '';
        return $this->ails_language_scope_cache[$post_id];
    }

    private function get_link_target_post_id(object $item): int
    {
        if ((string)($item->use_custom ?? '0') === '0') {
            return isset($item->post_id) ? absint($item->post_id) : 0;
        }

        $url = isset($item->url) ? (string) $item->url : '';
        return $url !== '' ? absint(url_to_postid($url)) : 0;
    }

    private function is_same_language_link_target(int $source_post_id, object $item): bool
    {
        $source_language = $this->get_post_language_code($source_post_id);
        if ($source_language === '') {
            return true;
        }

        $target_post_id = $this->get_link_target_post_id($item);
        if ($target_post_id <= 0) {
            return true;
        }

        $target_language = $this->get_post_language_code($target_post_id);
        if ($target_language === '') {
            return true;
        }

        return $source_language === $target_language;
    }
}
