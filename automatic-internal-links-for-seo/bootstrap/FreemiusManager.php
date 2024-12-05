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
            add_filter('fs_' . $filter . '_' . $this->pluginSlug, function() use ($settingsUrl) {
                return $settingsUrl;
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
        $break = "<br><br>";
        $morePlugins = $this->getMorePluginsHtml();
        
        return sprintf(
            esc_html__($this->messages['connect']['title'], 'automatic-internal-links-for-seo'),
            $userFirstName,
            $break
        ) . $morePlugins;
    }

    /**
     * Customize plugin icon
     */
    public function customPluginIcon(): string {
        return AILS_PLUGIN_ROOT . 'admin/assets/icon.jpg';
    }

    /**
     * Get HTML for recommended plugins
     */
    private function getMorePluginsHtml(): string {
        $plugins = $this->messages['connect']['more_plugins'];

        return '<p>' . implode(', ', array_map(function($name, $slug) {
            return sprintf(
                '<a target="_blank" href="https://wordpress.org/plugins/%s/">%s</a>',
                esc_attr($slug),
                esc_html($name)
            );
        }, array_keys($plugins), $plugins)) . '</p>';
    }
}