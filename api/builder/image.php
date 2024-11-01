<?php

class stacks_builder_image_uploader extends WP_REST_Controller {

    public function __construct() {
    }

    public function stacks_register_routes() {

        register_rest_route(
            'avaris-wc-rest/v3/',
            'receive_builder_image',
            array(
                'methods'             => "POST",
                'callback'            => array($this, 'stacks_receive_builder_image'),
                'args'                => array()
            ),
        );
    }

    public function stacks_receive_builder_image() {
        $file_name = sanitize_file_name( $_FILES['file']['name'] );
        $uploaddir = wp_upload_dir()['basedir'] . '/stacks-uploads/builder';
        $uploadfile = $uploaddir . '/' . basename( $file_name );
        $uploadurl = wp_upload_dir()['baseurl'] . '/stacks-uploads/builder';
        $uploadfile_url = $uploadurl . '/' . basename( $file_name );

        if (!file_exists($uploaddir)) {
            mkdir($uploaddir, 0755, true);
        }
        if( $_SERVER['SERVER_NAME'] == 'localhost' ) {
            move_uploaded_file( sanitize_text_field( $_FILES['file']['tmp_name'] ), $uploadfile);
            return esc_url(stripslashes($uploadfile_url));
        } else if ( move_uploaded_file( sanitize_text_field( $_FILES['file']['tmp_name'] ), $uploadfile)) {
            return esc_url(stripslashes(str_replace("http://", "https://", $uploadfile_url)));
        } else {
            return false;
        }
    }
}
add_action('rest_api_init', function () {
    $rest_registeration_controller = new stacks_builder_image_uploader();
    $rest_registeration_controller->stacks_register_routes();
});
