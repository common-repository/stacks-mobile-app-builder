<?php

interface Stacks_Api_Auth_Facade_Interface {

    /**
     * All Authentication Services should have name
     * @return string
     */
    public function get_name();

    /**
     * All authentication Services should have a way to authenticate user and set define him once request comes 
     * Define user for wordpress when get_current_user function called
     * @return int|boolena
     */
    public function authenticate();

    /**
     * All authentication Services should know if user is guest or not 
     * @return boolean
     */
    public function is_guest();

    /**
     * All authentication Services should know if user is user logged in or not
     * @return boolean
     */
    public function is_user();
}
