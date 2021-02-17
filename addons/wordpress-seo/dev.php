<?php
/*
Some things to help Sunshine work with Yoast SEO plugin better
*/

add_action( 'wp', 'sunshine_wpseo_disable_meta' );
function sunshine_wpseo_disable_meta( ) {
    if ( defined( 'WPSEO_PATH' ) ) {
        remove_filter( 'wp_head', array( 'SunshineFrontend', 'meta' ), 1 );
    }
}
add_filter( 'wp_title', 'sunshine_wpseo_title', 1000 );
add_filter( 'wpseo_opengraph_title', 'sunshine_wpseo_title', 1000 );
function sunshine_wpseo_title( $title ) {
    global $post;
    if ( ! defined( 'WPSEO_PATH' ) ) {
        return $title;
    }
    if ( !empty( SunshineFrontend::$current_image ) ) {
        //$title = 'This is a Sunshine image';
    } elseif ( !empty( SunshineFrontend::$current_gallery ) ) {
        $alt_post = $post;
        $post = SunshineFrontend::$current_gallery;
        $frontend = WPSEO_Frontend::get_instance();
        $frontend->reset();
        remove_filter( 'wpseo_title', 'sunshine_wpseo_title' );
        $title = $frontend->title( $title );
        $post = $alt_post;
    }
    return $title;
}

// Change the meta description in Yoast when viewing single Sunshine Gallery
add_filter( 'wpseo_metadesc', 'sunshine_wpseo_metadesc' );
function sunshine_wpseo_metadesc( $metadesc ) {
    if ( !empty( SunshineFrontend::$current_image ) ) {
        //$metadesc = 'This is a Sunshine image';
    } elseif ( !empty( SunshineFrontend::$current_gallery ) ) {
        $template = WPSEO_Options::get( 'metadesc-sunshine-gallery' );
        $metadesc_override = WPSEO_Meta::get_value( 'metadesc', SunshineFrontend::$current_gallery->ID );
        if ( is_string( $metadesc_override ) && '' !== $metadesc_override ) {
            $metadesc = $metadesc_override;
        } elseif ( '' !== $template ) {
            $metadesc  = $template;
        }
        $replacer = new WPSEO_Replace_Vars();
        $metadesc = $replacer->replace( $metadesc, SunshineFrontend::$current_gallery, array() );
    }
    return $metadesc;
}
