<?php

/**
 * Load Authentication Services
 */
class Stacks_Auth_Factory {

    public static function get_auth_Service($auth_Service) {
        switch ($auth_Service) {
            case 'jwt':
                return new Stacks_Facade_JWT();

            default:
                throw new Exception(sprintf(__('Auth Service %s not Found', 'plates'), $auth_Service));
        }
    }
}
