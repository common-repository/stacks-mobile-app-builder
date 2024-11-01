<?php

class Stacks_LoginProvider {

    /**
     * return array contains information about the error 
     * 
     * @return array
     */
    public function return_registeration_method_mismatch() {
        return ['success' => false, 'errors'  => [Translatable_Strings::get_code_message('registeration_method_mismatch')]];
    }

    /**
     * return array contains information about the error
     * 
     * @return array
     */
    public function return_wrong_password_error() {
        return ['success' => false, 'errors'  => [Translatable_Strings::get_code_message('wrong_password')]];
    }

    /**
     * return array contains information about the error 
     * 
     * @return array
     */
    public function return_user_not_found_error() {
        return ['success' => false, 'errors'  => [Translatable_Strings::get_code_message('no_users_found_matching')]];
    }

    /**
     * Get all users by email 
     * 
     * @param string $email
     * 
     * @return array|false
     */
    public function get_users_by_email($email) {
        return get_user_by('email', $email);
    }

    /**
     * Get all users by email 
     * 
     * @param string $email
     * 
     * @return array|false
     */
    public function get_users_by_username($username) {
        return get_user_by('login', $username);
    }

    /**
     * return valid user response
     * 
     * @param object $user
     * 
     * @return array
     */
    public function return_valid_user_response($user) {
        return ['success' => true, 'user' => $user];
    }
}
