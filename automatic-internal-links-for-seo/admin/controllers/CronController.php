<?php
namespace Pagup\AutoLinks\Controllers;

class CronController {
    private const CRON_HOOK = 'ails_auto_sync_event';
    private const ERROR_PREFIX = 'Auto Links';

    /**
     * Register cron schedules
     */
    public function register_schedules(array $schedules): array {
        $custom_schedules = [
            'fifteen_minutes' => [
                'interval' => 15 * MINUTE_IN_SECONDS,
                'display' => __('Every 15 Minutes')
            ],
            'thirty_minutes' => [
                'interval' => 30 * MINUTE_IN_SECONDS,
                'display' => __('Every 30 Minutes')
            ],
            'two_hours' => [
                'interval' => 2 * HOUR_IN_SECONDS,
                'display' => __('Every Two Hours')
            ],
            'six_hours' => [
                'interval' => 6 * HOUR_IN_SECONDS,
                'display' => __('Every Six Hours')
            ]
        ];
        
        return array_merge($schedules, $custom_schedules);
    }

    /**
     * Get current cron status
     */
    public function get_status(): array {
        $next_run = wp_next_scheduled(self::CRON_HOOK);
        $cron_array = _get_cron_array();
        
        return [
            'next_scheduled_run' => $next_run ? date('Y-m-d H:i:s', $next_run) : 'Not scheduled',
            'all_cron_jobs' => $cron_array ? array_keys($cron_array) : [],
            'our_cron_jobs' => $this->find_plugin_cron_jobs($cron_array),
            'cron_array' => $cron_array,
        ];
    }

    /**
     * Test cron trigger endpoint
     */
    public function test_trigger(): void {
        try {
            if (!wp_verify_nonce($_POST['nonce'] ?? '', 'ails__nonce')) {
                wp_send_json_error(['message' => 'Invalid nonce'], 419 );
                return;
            }
    
            $before_status = $this->get_status();
            $result = spawn_cron();
            $after_status = $this->get_status();
            
            wp_send_json_success([
                'message' => 'Cron trigger attempted',
                'trigger_result' => $result,
                'before' => $before_status,
                'after' => $after_status,
                'wp_cron_disabled' => defined('DISABLE_WP_CRON') && DISABLE_WP_CRON,
                'server_time' => current_time('mysql'),
                'gmt_time' => gmdate('Y-m-d H:i:s'),
            ]);
            
        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => 'Cron trigger failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Debug cron status
     */
    public function debug_status(): void {
        $next_run = wp_next_scheduled(self::CRON_HOOK);
        $this->log_debug_info($next_run);
    }

    /**
     * Log debug information
     */
    private function log_debug_info(?int $next_run): void {
        error_log(sprintf(
            '%s Debug: Next scheduled run at %s',
            self::ERROR_PREFIX,
            $next_run ? date('Y-m-d H:i:s', $next_run) : 'Not scheduled'
        ));
        
        if (defined('DISABLE_WP_CRON') && DISABLE_WP_CRON) {
            error_log(sprintf('%s Debug: WordPress cron is disabled in wp-config.php', self::ERROR_PREFIX));
        }
        
        $cron_array = _get_cron_array();
        error_log(sprintf('%s Debug: Current cron jobs: %s', self::ERROR_PREFIX, print_r($cron_array, true)));
    }

    /**
     * Find plugin specific cron jobs
     */
    private function find_plugin_cron_jobs(?array $cron_array): array {
        if (empty($cron_array)) {
            return [];
        }

        $our_jobs = [];
        foreach ($cron_array as $timestamp => $crons) {
            if (isset($crons[self::CRON_HOOK])) {
                $our_jobs[] = [
                    'timestamp' => $timestamp,
                    'datetime' => date('Y-m-d H:i:s', $timestamp),
                    'hooks' => array_keys($crons)
                ];
            }
        }
        
        return $our_jobs;
    }
}