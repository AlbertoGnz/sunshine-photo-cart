<?php
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

$options = get_option( 'sunshine_options' );

if ( $options['uninstall_delete_data'] ) {

    global $wpdb, $current_user;

    $galleries = $wpdb->query( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'sunshine-gallery';" );
    sunshine_log( $galleries, 'GALLERIES DURING DELETE' );

    // Remove settings
    delete_option( 'sunshine_options' );

    // Remove any scheduled hooks
    wp_clear_scheduled_hook( 'sunshine_paypal_cleanup' );

    // Remove pages
    wp_delete_post( $options['page'], true );
    wp_delete_post( $options['page_cart'], true );
    wp_delete_post( $options['page_checkout'], true );
    wp_delete_post( $options['page_account'], true );
    wp_delete_post( $options['page_favorites'], true );

    // Get all galleries for use in deleting attachments
    $galleries = $wpdb->query( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'sunshine-gallery';" );

    // Remove user meta
    if ( !get_user_meta( $current_user->ID, 'sunshine_capabilities', true ) ) {
        $wpdb->query( "DELETE FROM $wpdb->usermeta WHERE meta_key LIKE 'sunshine_%';" );
    }

    // Remove post type data
    $wpdb->query( "DELETE FROM {$wpdb->posts} WHERE post_type IN ( 'sunshine-product', 'sunshine-gallery', 'sunshine-order' );" );
    // Delete all meta data that is not assigned anymore
    $wpdb->query( "DELETE meta FROM {$wpdb->postmeta} meta LEFT JOIN {$wpdb->posts} posts ON posts.ID = meta.post_id WHERE posts.ID IS NULL;" );

    // Remove taxonomy data
    foreach ( array( 'sunshine-product-category', 'sunshine-product-price-level', 'sunshine-order-status' ) as $taxonomy ) {
        $wpdb->delete(
            $wpdb->term_taxonomy,
            array(
                'taxonomy' => $taxonomy,
            )
        );
    }

}

/* Not ready yet
// Remove attachments
if ( $options['uninstall_delete_attachments'] ) {

    if ( !empty( $galleries ) ) {

        $gallery_ids = array();
        foreach ( $galleries as $gallery_id ) {
            $gallery_ids[] = $gallery_id;
        }

        // Build single query to delete all attachment posts
        $wpdb->query( "DELETE FROM {$wpdb->posts} WHERE post_type='attachment' AND post_parent IN ( " . join( ',', $gallery_ids ) . " );" );

        // Clear all unattached meta data query
        $wpdb->query( "DELETE meta FROM {$wpdb->postmeta} meta LEFT JOIN {$wpdb->posts} posts ON posts.ID = meta.post_id WHERE posts.ID IS NULL;" );

        // Delete files from server
        $upload_dir = wp_upload_dir();
        $folder = $upload_dir['basedir'] . '/sunshine/*';
        array_map( 'unlink', array_filter( (array) glob( $folder ) ) );

    }

}
*/

wp_cache_flush();
?>
