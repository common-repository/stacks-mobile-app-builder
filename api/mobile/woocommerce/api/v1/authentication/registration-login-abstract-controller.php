<?php

abstract class Stacks_RegisterationLoginAbstractController extends Stacks_AbstractController {
    /**
     * Set allowed registration methods 
     */
    const ALLOWED_PROVIDERS = array('facebook', 'manual');

    /**
     * current active registration|login method 
     * @var string
     */
    protected $active_method = null;

    /**
     * Registration Providers Required parameters 
     * @var array 
     */
    protected $facebook_required_parameters = array('access_token', 'device_type', 'device_id');
    protected $manual_required_parameters   = array('email', 'first_name', 'last_name', 'password', 'device_type', 'device_id');
    protected $manual_required_parameters_phone   = array('phone', 'first_name', 'last_name', 'password', 'device_type', 'device_id');
    protected $allowed_device_types        = array('android', 'ios');

    /**
     * Abbreviation for direct returning invalid parameter response for dry 
     * 
     * @return array
     */
    public function return_invalid_parameter_response() {
        return $this->return_error_response(
            Stacks_WC_Api_Response_Service::INVALID_PARAMETER_CODE,
            __('Some Required Parameters are Missing', 'plates'),
            $this->get_errors(),
            Stacks_WC_Api_Response_Service::CLIENT_ERROR_STATUS_CODE
        );
    }

    /**
     * Return Invalid Device Type Response 
     * 
     * @return array
     */
    public function return_invalid_device_type_response() {
        return $this->return_error_response(
            Stacks_WC_Api_Response_Service::INVALID_PARAMETER_CODE,
            __('Invalid Data Submitted Device Type', 'plates'),
            $this->get_errors(),
            Stacks_WC_Api_Response_Service::CLIENT_ERROR_STATUS_CODE
        );
    }

    /**
     * Return Invalid Device Type Response 
     * 
     * @return array
     */
    public function return_fetch_parameters_error_response($errors) {
        return $this->return_error_response(Stacks_WC_Api_Response_Service::UNEXPECTED_ERROR_CODE, Translatable_Strings::get_code_message('could_not_fetch_data'), $errors, 500);
    }


    /**
     * check no users exist with the same email 
     * @param string $email
     * @return boolean
     */
    protected function is_there_users_exists_with_the_same_email($email) {
        $user = get_user_by('email', $email);
        return $user ? $user : false;
    }

    /**
     * validate required parameters exists according to registration type 
     * @return $this
     */
    protected function validate_required_parameters_exists($registeration_type = 'email') {
        switch ($this->active_method) {
            case 'facebook':
                $this->validate_parameters($this->facebook_required_parameters);
                break;
            case 'manual':
                if($registeration_type == 'phone') {
                    $this->validate_parameters($this->manual_required_parameters_phone);
                } else {
                    $this->validate_parameters($this->manual_required_parameters);
                }
                
                break;
        }
        return $this;
    }

    /**
     * validate Parameters 
     */
    protected function validate_parameters($params) {
        foreach ($params as $param) {
            if (!$this->get_request_param($param)) {
                $this->set_error($this->invalid_parameter_exception($param)->get_error_message());
            }
        }
    }

    /**
     * Validate Registration|login Method type is valid 
     * @return $this
     */
    protected function validate_type() {
        $type_param_name = 'type';

        $registration_type = $this->get_request_param($type_param_name);

        if (in_array($registration_type, static::ALLOWED_PROVIDERS)) {
            $this->active_method = $registration_type;
        } else {
            $this->set_error($this->invalid_parameter_exception($type_param_name)->get_error_message());
        }

        return $this;
    }

    /**
     * Validate Registration|login Method type is valid 
     * @return $this
     */
    protected function validate_device_settings() {
        $type_param_name = 'device_type';
        $device_id_name = 'device_id';

        $device_type = strtolower($this->get_request_param($type_param_name));
        $device_id = $this->get_request_param($device_id_name);

        // parameters not submitted
        if (!$device_type && !$device_id) {
            $this->set_error($this->missing_parameter_exception($type_param_name)->get_error_message());
            $this->set_error($this->missing_parameter_exception($device_id_name)->get_error_message());

            return $this;
        }

        // device type not submitted or submitted but invalid 
        if (!$device_type) {
            $this->set_error($this->missing_parameter_exception($type_param_name)->get_error_message());
        } else {
            if (!in_array($device_type, $this->allowed_device_types)) {
                $this->set_error($this->invalid_parameter_exception($type_param_name)->get_error_message());
            }
        }

        // device id is invalid or submitted but invalid
        if (!$device_id) {
            $this->set_error($this->missing_parameter_exception($device_id_name)->get_error_message());
        } else {
            if (strlen($device_id) < 10) {
                $this->set_error($this->invalid_parameter_exception($device_id_name)->get_error_message());
            }
        }

        return $this;
    }
}
