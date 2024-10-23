<?php

namespace Pagup\AutoLinks\Traits;

use Pagup\AutoLinks\Core\Option;
trait Helpers
{
    public function post_types() {
        global $wpdb;
        $allowed_post_types = ( Option::check( 'post_types' ) ? maybe_unserialize( Option::get( 'post_types' ) ) : [] );
        if ( in_array( 'product', $allowed_post_types ) ) {
            unset($allowed_post_types[array_search( 'product', $allowed_post_types )]);
        }
        // Prepare statement for each post type value
        $post_types = array_map( function ( $post_type ) use($wpdb) {
            return $wpdb->prepare( '%s', $post_type );
        }, $allowed_post_types );
        // Convert to string to use in query
        return implode( ',', $post_types );
    }

    public function focus_keyword( string $focus_keyword = '' ) {
        if ( class_exists( 'WPSEO_Meta' ) ) {
            $focus_keyword = '_yoast_wpseo_focuskw';
        } elseif ( class_exists( 'RankMath' ) ) {
            $focus_keyword = 'rank_math_focus_keyword';
        }
        return $focus_keyword;
    }

    public function cpts( $excludes = [] ) {
        // All CPTs.
        $post_types = get_post_types( array(
            'public' => true,
        ), 'objects' );
        // remove Excluded CPTs from All CPTs.
        foreach ( $excludes as $exclude ) {
            unset($post_types[$exclude]);
        }
        $types = [];
        foreach ( $post_types as $post_type ) {
            $label = get_post_type_labels( $post_type );
            $types[$label->name] = $post_type->name;
        }
        return $types;
    }

}