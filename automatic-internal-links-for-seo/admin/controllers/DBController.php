<?php
namespace Pagup\AutoLinks\Controllers;

class DBController {
    private string $table;
    private string $table_log;
    private string $db_version = '1.0.4';
    private const ERROR_PREFIX = 'Auto Links';  
    private const OPTION_NAME = 'autolinks_db_version';

    public function __construct() {
        $this->table = AILS_TABLE;
        $this->table_log = AILS_LOG_TABLE;
    }

    /**
     * Handles database migration and updates
     *
     * @return void
     */
    public function migration(): void {
        global $wpdb;
        
        try {
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

            // Create/update both tables with WordPress default charset
            $this->createTables($wpdb->get_charset_collate());
            
            // Add version option if it doesn't exist
            add_option(self::OPTION_NAME, $this->db_version);

            $installed_ver = get_option(self::OPTION_NAME);

            // Handle table modifications for version updates
            if ($installed_ver !== $this->db_version) {
                $this->updateTableStructures();
                update_option(self::OPTION_NAME, $this->db_version);
            }
        } catch (\Exception $e) {
            error_log('Auto Links DB Migration Error: ' . $e->getMessage());
        }
    }

    /**
     * Creates or updates database tables
     *
     * @param string $charset_collate Database charset
     * @return void
     */
    private function createTables(string $charset_collate): void {
        dbDelta($this->getTableSchema($this->table, $charset_collate));
        dbDelta($this->getTableSchema($this->table_log, $charset_collate));
    }

    /**
     * Updates table structures for version changes
     *
     * @return void
     */
    private function updateTableStructures(): void {
        global $wpdb;

        $tables = [$this->table, $this->table_log];
        
        foreach ($tables as $table) {
            $row = $wpdb->get_row("SELECT * FROM $table");
            
            if ($row) {
                if (isset($row->url)) {
                    $wpdb->query("ALTER TABLE $table MODIFY COLUMN url varchar(255) DEFAULT '' NOT NULL;");
                }
                
                if (isset($row->keyword)) {
                    $wpdb->query("ALTER TABLE $table MODIFY COLUMN keyword mediumtext NOT NULL;");
                }
            }
        }

        // Rerun table creation to ensure all columns are properly set
        $this->createTables($wpdb->get_charset_collate());
    }

    /**
     * Returns the SQL schema for table creation
     *
     * @param string $table Table name
     * @param string $charset_collate Character set collation
     * @return string SQL create table statement
     */
    private function getTableSchema(string $table, string $charset_collate = ''): string {
        global $wpdb;
        
        // If no charset_collate provided, get it from WordPress
        if (empty($charset_collate)) {
            $charset_collate = $wpdb->get_charset_collate();
        }

        return "CREATE TABLE $table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            post_id INT UNSIGNED,
            title text,
            keyword mediumtext NOT NULL,
            url varchar(255) DEFAULT '' NOT NULL,
            use_custom TINYINT(1) DEFAULT 0 NOT NULL,
            new_tab TINYINT(1) DEFAULT 0 NOT NULL,
            nofollow TINYINT(1) DEFAULT 0 NOT NULL,
            case_sensitive TINYINT(1) DEFAULT 0 NOT NULL,
            partial_match TINYINT(1) DEFAULT 0 NOT NULL,
            bold TINYINT(1) DEFAULT 0 NOT NULL,
            priority SMALLINT UNSIGNED DEFAULT 0,
            max_links SMALLINT DEFAULT 3 NOT NULL,
            status TINYINT(1) DEFAULT 1 NOT NULL,
            post_type varchar(55),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
    }

    /**
     * Checks if database version needs updating
     *
     * @return void
     */
    public function db_check(): void {
        if (get_site_option(self::OPTION_NAME) !== $this->db_version) {
            $this->migration();
        }
    }

    /**
     * Check system status and recreate tables if needed
     */
    public function check_system_status(): array {
        try {
            global $wpdb;
            
            $status = [
                'success' => true,
                'message' => '',
                'tables_exist' => true,
                'tables_recreated' => false
            ];

            // Check if tables exist
            $logs_exist = $wpdb->get_var("SHOW TABLES LIKE '" . AILS_LOG_TABLE . "'");
            $links_exist = $wpdb->get_var("SHOW TABLES LIKE '" . AILS_TABLE . "'");

            if (empty($logs_exist) || empty($links_exist)) {
                $status['tables_exist'] = false;
                
                // Attempt to recreate tables
                $this->migration();
                
                // Verify tables were created
                $logs_exist = $wpdb->get_var("SHOW TABLES LIKE '" . AILS_LOG_TABLE . "'");
                $links_exist = $wpdb->get_var("SHOW TABLES LIKE '" . AILS_TABLE . "'");
                
                if ($logs_exist && $links_exist) {
                    $status['message'] = 'Tables were successfully recreated.';
                    $status['tables_recreated'] = true;
                } else {
                    $status['success'] = false;
                    $status['message'] = 'Failed to recreate tables. Please deactivate and reactivate the plugin.';
                }
            }

            return $status;

        } catch (\Exception $e) {
            error_log(sprintf("%s Database Error: %s", self::ERROR_PREFIX, $e->getMessage()));
            return [
                'success' => false,
                'message' => 'Database error occurred. Please check error logs.',
                'tables_exist' => false,
                'tables_recreated' => false
            ];
        }
    }

    /**
     * Check if both plugin tables exist in the database
     *
     * @return bool True if both tables exist, false otherwise
     */
    public function tables_exist(): bool {
        global $wpdb;
        
        $logs_exist = $wpdb->get_var("SHOW TABLES LIKE '" . AILS_LOG_TABLE . "'");
        $links_exist = $wpdb->get_var("SHOW TABLES LIKE '" . AILS_TABLE . "'");
        
        return !empty($logs_exist) && !empty($links_exist);
    }
}