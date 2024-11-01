<?php

class Stacks_FacebookRegisterationProvider implements Stacks_RegistrationProviderInterface {

    use stacks_facebook;

    public function get_email() {
        return $this->parameters['email'];
    }

    public function get_first_name() {
        return $this->parameters['first_name'];
    }

    public function get_last_name() {
        return $this->parameters['last_name'];
    }

    public function get_password() {
        return wp_generate_password();
    }

    public function get_phone() {
        return null;
    }

    public function set_parameters($parameters) {
        $this->token = $parameters['access_token'];
        return $this;
    }

    public function successfully_fetched_user_params() {
        $response = $this->contact_facebook();

        $result = $this->debug_response($response);

        if ($result['success'] === true) {
            $this->parameters = (array) $result['body'];
            return true;
        }

        return $this->errors;
    }
}
