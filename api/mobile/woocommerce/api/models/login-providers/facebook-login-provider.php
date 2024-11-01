<?php

class Stacks_FacebookLoginProvider extends Stacks_LoginProvider implements Stacks_LoginProvidersInterface {

    use stacks_facebook;

    protected $provider_name = 'facebook';

    public function get_email() {
        return $this->parameters['email'];
    }

    public function set_parameters($parameters) {
        $this->token = $parameters['access_token'];
        return $this;
    }

    public function successfully_fetched_fields() {
        $response = $this->contact_facebook();

        $result = $this->debug_response($response);

        if ($result['success'] === true) {
            $this->parameters = (array) $result['body'];

            return true;
        }

        return $this->errors;
    }

    /**
     * login user 
     * @return boolean
     */
    public function login() {
        $user = $this->get_users_by_email($this->get_email());

        if ($user && !empty($user)) {
            // if (Stacks_RegistrationModel::get_user_registeration_type($user->ID) !== $this->provider_name) {
            //     return $this->return_registeration_method_mismatch();
            // }

            return $this->return_valid_user_response($user);
        }

        return $this->return_user_not_found_error();
    }
}
