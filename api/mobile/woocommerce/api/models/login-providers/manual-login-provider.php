<?php

class Stacks_ManualLoginProvider extends Stacks_LoginProvider implements Stacks_LoginProvidersInterface {

    protected $parameters = null;

    protected $provider_name = 'manual';

    public function get_email() {
        return $this->parameters['email'];
    }

    public function get_password() {
        return $this->parameters['password'];
    }

    public function set_parameters($parameters) {
        $this->parameters = $parameters;
        return $this;
    }

    public function successfully_fetched_fields() {
        return true;
    }

    public function login() {
        $user = $this->get_users_by_email($this->get_email());
        if( empty($user) ) {
            $user = $this->get_users_by_username($this->get_email());
        }
        if ($user && !empty($user)) {

            // validate registration type
            if (Stacks_RegistrationModel::get_user_registeration_type($user->ID) !== $this->provider_name) {
                return $this->return_registeration_method_mismatch();
            }

            //check password 
            if (!$this->validate_password($user, $this->get_password())) {
                return $this->return_wrong_password_error();
            }

            return $this->return_valid_user_response($user);
        }

        return $this->return_user_not_found_error();
    }

    /**
     * validate user password is correct 
     * @param object $user
     * @param string $password
     * @return boolean
     */
    private function validate_password($user, $password) {
        if (!wp_check_password($password, $user->user_pass, $user->ID)) {
            return false;
        } else {
            return true;
        }
    }
}
