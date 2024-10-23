<?php

namespace Pagup\AutoLinks\Controllers;

use Pagup\AutoLinks\Core\Option;
use Pagup\AutoLinks\Core\Plugin;
use Pagup\AutoLinks\Core\Request;
use Pagup\AutoLinks\Controllers\SettingsController;
class MetaboxController extends SettingsController {
    private $table_log = AILS_LOG_TABLE;

    public function add_metabox() {
    }

    public function metabox( $post ) {
    }

    public function metadata( $postid ) {
    }

    public function insert_update_data(
        $meta_id,
        $post_id,
        $meta_key,
        $meta_value
    ) {
    }

}

$metabox = new MetaboxController();