<?php
/* Integration with WP Offload Media plugin */
add_filter( 'as3cf_pre_upload_attachment', 'sunshine_stop_as3cf_at_upload' );
function sunshine_stop_as3cf_at_upload( $status ) {
    if ( !empty( $_FILES['sunshine_gallery_image'] ) || ( !empty( $_POST['action'] ) && $_POST['action'] == 'sunshine_file_save' ) ) {
        return true;
    }
    return $status;
}

add_action( 'sunshine_after_image_process', 'sunshine_allow_as3cf_at_upload' );
function sunshine_allow_as3cf_at_upload() {
    remove_filter( 'as3cf_pre_upload_attachment', 'sunshine_stop_as3cf_at_upload' );
}
