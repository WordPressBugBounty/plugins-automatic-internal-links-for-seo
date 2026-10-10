<?php
namespace Pagup\AutoLinks\Bootstrap;

class FreemiusManager {
    private string $pluginId;
    private string $pluginSlug;
    private array $messages;
    private const ERROR_PREFIX = 'Auto Links';

    public function __construct(
        string $pluginId,
        string $pluginSlug,
        array $messages
    ) {
        $this->pluginId = $pluginId;
        $this->pluginSlug = $pluginSlug;
        $this->messages = $messages;
    }

    /**
     * Initialize Freemius customization
     */
    public function init(): void {
        try {
            $this->addFilters();
            $this->addUrlFilters();
        } catch (\Exception $e) {
            error_log(sprintf("%s Freemius Error: %s", self::ERROR_PREFIX, $e->getMessage()));
        }
    }

    /**
     * Add Freemius filters
     */
    private function addFilters(): void {
        add_filter('fs_connect_message_' . $this->pluginSlug, [$this, 'customConnectMessage'], 10, 6);
        add_filter('fs_connect-message_on-premium_' . $this->pluginSlug, [$this, 'customPremiumConnectMessage'], 10, 3);
        add_filter('fs_plugin_icon_' . $this->pluginSlug, [$this, 'customPluginIcon']);
    }

    /**
     * Add URL filters for Freemius
     */
    private function addUrlFilters(): void {
        $settingsUrl = admin_url('admin.php?page=' . $this->pluginSlug);
        
        $urlFilters = [
            'connect_url',
            'after_skip_url',
            'after_connect_url',
            'after_pending_connect_url'
        ];
        
        foreach ($urlFilters as $filter) {
            add_filter('fs_' . $filter . '_' . $this->pluginSlug, function() use ($settingsUrl, $filter) {
                return $settingsUrl . ($filter === 'connect_url' ? '' : '#/sync');
            });
        }
    }

    /**
     * Customize Freemius connect message
     */
    public function customConnectMessage(
        $message,
        $userFirstName,
        $productTitle,
        $userLogin,
        $siteLink,
        $freemiusLink
    ): string {
        // Retain the SDK's own opt-in / permission explanation and controls.
        // Product copy stays outside the unmodified upstream SDK.
        $copy = $this->welcomeCopy();
        return esc_html($copy['benefit']) . '<br><br>' . esc_html($copy['next']) . '<br><br>' . $message;
    }

    public function customPremiumConnectMessage($message, $userFirstName, $productTitle): string
    {
        $copy = $this->welcomeCopy();
        return esc_html($copy['benefit']) . '<br><br>' . esc_html($copy['licenseNext'] ?? $copy['next']) . '<br><br>' . $message;
    }

    private function welcomeCopy(): array
    {
        $locale = str_replace('_', '-', function_exists('determine_locale') ? determine_locale() : get_locale());
        // Same locale fallback order as the Vue admin. Generated from its catalog.
        $catalog = json_decode(file_get_contents(AILS_PLUGIN_ROOT . 'admin/onboarding-messages.json'), true);
        if (!is_array($catalog)) {
            return [
                'benefit' => __('Create internal links from the focus keywords in your existing content, without adding each link by hand.', 'automatic-internal-links-for-seo'),
                'next' => __('After this step, open Sync to import your keywords and review the links to create. Your connection choice is explained below.', 'automatic-internal-links-for-seo'),
            ];
        }
        if (isset($catalog[$locale])) {
            return $catalog[$locale];
        }
        $language = strtolower(explode('-', $locale)[0]);
        foreach ($catalog as $supported => $copy) {
            if (explode('-', $supported)[0] === $language) {
                return $copy;
            }
        }
        return $catalog['en-US'];
    }

    /**
     * Customize plugin icon
     */
    public function customPluginIcon(): string {
        return AILS_PLUGIN_ROOT . 'admin/assets/icon.jpg';
    }

}
