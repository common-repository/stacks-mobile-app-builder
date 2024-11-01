<?php

class stacks_apis {

    public function __construct() {
        add_action( 'rest_api_init', array( $this, 'stacks_rest_cors' ), 15 );
        $this->initialize();
    }

    public function initialize() {
        require_once 'builder/builder-api.php';
        require_once 'mobile/main.php';
    }

    public function stacks_rest_cors() {
        add_filter( 'rest_pre_echo_response', function( $value ) {
            header( 'Access-Control-Allow-Headers: stacksAuthorization, Accept-Language' );
            return $value;
        });
    }

}

new stacks_apis();
