<?php
namespace Pagup\AutoLinks\Controllers;

class DBController {

    private $table = AILS_TABLE;
    private $table_log = AILS_LOG_TABLE;
    private $db_version = '1.0.4';

    public function migration()
    {
        global $wpdb;
        
        // Auto links Table
        $charset_collate = $wpdb->get_charset_collate();

        $links_table = $this->db($this->table, $charset_collate);
        $table_log = $this->db($this->table_log, $charset_collate);

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $links_table );
        dbDelta( $table_log );

        add_option( 'autolinks_db_version', $this->db_version );

        $installed_ver = get_option( "autolinks_db_version" );

        if ( $installed_ver != $this->db_version ) {

            // Modify Table
            $row = $wpdb->get_row("SELECT * FROM $this->table");

            if(isset($row->url)){
                $wpdb->query("ALTER TABLE $this->table MODIFY COLUMN url varchar(255) DEFAULT '' NOT NULL;");
            }

            if(isset($row->keyword)){
                $wpdb->query("ALTER TABLE $this->table MODIFY COLUMN keyword mediumtext NOT NULL;");
            }

            $row = $wpdb->get_row("SELECT * FROM $this->table_log");

            if(isset($row->url)){
                $wpdb->query("ALTER TABLE $this->table_log MODIFY COLUMN url varchar(255) DEFAULT '' NOT NULL;");
            }

            if(isset($row->keyword)){
                $wpdb->query("ALTER TABLE $this->table_log MODIFY COLUMN keyword mediumtext NOT NULL;");
            }

            $links_table = $this->db($this->table);
            $table_log = $this->db($this->table_log);
            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            dbDelta( $links_table );
            dbDelta( $table_log );

            update_option( "autolinks_db_version", $this->db_version );
        }
    }

    public function db($table, $charset_collate = "")
    {
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

    public function db_check() {
        if ( get_site_option( 'autolinks_db_version' ) != $this->db_version ) {
            $this->migration();
        }
    }

}

$database = new DBController();