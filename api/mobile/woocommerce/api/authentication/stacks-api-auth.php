<?php

class Stacks_Api_Auth {

    protected static $instance = null;

    public $authentication_service = null;

    public static function get_instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct() {
        // ensure no other services have determined current user or invoked get_current_user function( like revolution slider) before us
        $this->remove_current_user();

        $this->includes();

        $this->authentication_service = Stacks_Auth_Factory::get_auth_Service('jwt');

        add_filter('determine_current_user', array($this, 'authenticate'), 10);
    }


    private function remove_current_user() {
        global $current_user;

        $current_user = null;
    }

    public function includes() {
        require_once 'avaris-auth-factory.php';

        /* Interfaces */
        require_once 'interfaces/stacks-api-auth-facade-interface.php';

        /* Jwt Auth Service */
        require_once 'repositories/stacks-repository-jwt.php';
        require_once 'facades/stacks-facade-jwt.php';
    }


    public function authenticate($user_id) {
        // Do not authenticate twice
        if (!empty($user_id)) {
            return $user_id;
        }

        // determine user id using authentication function exists in all auth services
        return $this->authentication_service->authenticate();
    }

    public function is_current_user_logged_in() {
        return $this->authentication_service->is_user();
    }
}
