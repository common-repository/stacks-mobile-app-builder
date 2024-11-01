<?php

class Stacks_ManualRegisterationProvider implements Stacks_RegistrationProviderInterface {

    protected $parameters;

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
        return $this->parameters['password'];
    }

    public function get_phone() {
        return $this->parameters['phone'];
    }

    public function set_parameters($parameters) {
        $this->parameters = $parameters;
        return $this;
    }

    public function successfully_fetched_user_params() {
        return true;
    }
}
