<?php

class Stacks_REST_Authentication {

    // @var \WP_Error
    protected $errors = null;

    // @var Stacks_Api_Auth_Facade_Interface
    public $authentication_service = null;

    // @var self
    protected static $instance = null;

    /**
     * Get new Instance from Stacks Api Authentication Services
     * @param Stacks_Api_Auth_Facade_Interface $authentication_service
     * @return self
     */
    public static function get_instance(Stacks_Api_Auth_Facade_Interface $authentication_service) {
        if (is_null(static::$instance)) {
            static::$instance = new self($authentication_service);
        }
        return static::$instance;
    }

    /**
     * @param Stacks_Api_Auth_Facade_Interface $authentication_service
     */
    public function __construct(Stacks_Api_Auth_Facade_Interface $authentication_service) {
        $this->authentication_service = $authentication_service;

        // determine current user according to token payload
        add_filter('determine_current_user', array($this, 'authenticate'), 10);
    }

    /**
     * Simple way of determining who is this user by allowing authentication services to validate user
     *
     * @param $user_id
     * @return false|int
     */
    public function authenticate($user_id) {
        // Do not authenticate twice
        if (!empty($user_id)) {
            return $user_id;
        }

        // determine user id using authentication function exists in all auth services
        return $this->authentication_service->authenticate();
    }

    /**
     * checks if there is a valid anonymous token in our api 
     * @return boolean
     */
    public function do_we_have_valid_anonymous_token() {
        return $this->authentication_service->validate_anonymous_token();
    }

    /**
     * checks if there is a valid user token in our api 
     * @return boolean
     */
    public function do_we_have_valid_user_token() {
        return $this->authentication_service->validate_user_token();
    }
}
