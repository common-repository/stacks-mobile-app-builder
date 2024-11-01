<?php

interface Stacks_RegistrationProviderInterface {

    /**
     * Return First name 
     */
    public function get_first_name();

    /**
     * Return Last name 
     */
    public function get_last_name();

    /**
     * Return Phone 
     */
    public function get_phone();

    /**
     * Return Password
     */
    public function get_password();

    /**
     * Return Email
     */
    public function get_email();

    /**
     * Set parameters Required for Registration Provider to Work
     * @param array $parameters
     * @return $this
     */
    public function set_parameters($parameters);

    /**
     * check if registration provider successfully fetched user data.
     */
    public function successfully_fetched_user_params();
}
