<?php

// get composer autoloader to load required packages 
require_once trailingslashit(STACKS_PLUGIN_DIR) . 'vendor/autoload.php';

/**
 * Class Stacks_Facade_JWT
 *
 * this file is part of Creiden Api Authentication System
 */
class Stacks_Facade_JWT implements Stacks_Api_Auth_Facade_Interface {

    /**
     * @var WP_Error
     */
    protected $error = null;

    /**
     * Authentication Service Name
     * @var string 
     */
    protected $name = 'JWT';

    /**
     * @var Stacks_Api_Auth_Repository_Interface
     */
    protected $repository = null;


    /**
     * Get Service Name 
     * @return string
     */
    public function get_name() {
        return $this->name;
    }

    /**
     * start our filters to add tokens to required requests
     */
    public function __construct() {
        add_filter('_stacks_api_before_sending_login_success',     array($this, 'add_user_token'), 10, 2);
        add_filter('_stacks_api_before_sending_register_success',  array($this, 'add_user_token'), 10, 2);
        add_filter('_stacks_api_before_sending_anonymous_token',   array($this, 'add_anonymous_token'), 10, 2);
    }

    /**
     * add anonymous token to 
     * @param array $data
     * @return array
     */
    public function add_anonymous_token($data) {
        $token = $this->generate_anonymous_token();
        $data['token'] = $token;
        return $data;
    }

    /**
     * Add User token to returned data 
     * @param array $data
     * @param int $user_id
     * @return array
     */
    public function add_user_token($data, $user_id) {
        $user = new WP_User($user_id);
        $data['token'] = $this->generate_user_token($user);
        return $data;
    }


    /**
     * Get Authentication Module Controller
     * @return Stacks_Repository_JWT
     */
    protected static function getRepository() {
        return Stacks_Repository_JWT::getInstance();
    }

    /**
     * Authenticate Current User to know who is this user and what is his permissions
     * @return user_id
     */
    public function authenticate() {
        return self::getRepository()->authenticate();
    }

    /**
     * is current request is user 
     * @return boolean
     */
    public function is_user() {
        return self::getRepository()->validate_user_token();
    }

    /**
     * is current request is guest 
     * @return boolean
     */
    public function is_guest() {
        return self::getRepository()->validate_anonymous_token();
    }

    /**
     * Generate Anonymous Token
     * @return string
     */
    public function generate_anonymous_token() {
        return self::getRepository()->generate_anonymous_token();
    }

    /**
     * Generate User token 
     * @param \WP_User $user
     * @return string
     */
    public function generate_user_token(\WP_User $user) {
        return self::getRepository()->generate_user_token($user);
    }
}
