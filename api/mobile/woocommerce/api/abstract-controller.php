<?php

abstract class Stacks_AbstractController {
    /**
     * Endpoint namespace.
     *
     * @var string
     */
    protected $namespace = null;

    /**
     * Route base.
     *
     * @var string
     */
    protected $rest_base = '';

    /**
     * Allowed Parameters to be sent in the request and its mapping 
     * 
     * @example description ['category' => 'id' ] we will receive category and convert it to id 
     * 
     * @var array 
     */
    protected $allowed_params = array();

    /**
     * Submitted data mapping 
     * 
     * @var array 
     */
    protected $data = array();

    /**
     * @var array 
     */
    protected $errors = array();

    /**
     * Endpoint callback function suffix
     * @var array 
     */
    protected $callback_function_suffix = '';

    public function __call($name, $arguments) {
        if (method_exists($this->get_response_service(), $name)) {
            return call_user_func_array(array($this->get_response_service(), $name), $arguments);
        }
    }

    /**
     * return the endpoint for the application (v3)
     * 
     * @return string
     */
    public function get_api_endpoint() {
        return sprintf('%s/%s/', Stacks_Woocommerce_Integration_Api::ENDPOINT, Stacks_Woocommerce_Integration_Api::VERSION);
    }


    /**
     * Update Success Return Message 
     * 
     * @param string $message
     */
    public function update_success_return_message($message) {
        add_filter(
            'stacks_woocommerce_api_message',
            function () use ($message) {
                return $message;
            },
            10,
            1
        );
    }


    /**
     * return Stacks Response Service Instance
     * 
     * @return Stacks_WC_Api_Response_Service
     */
    public function get_response_service() {
        return Stacks_WC_Api_Response_Service::get_instance();
    }

    /**
     * Add new error message
     * @param array $error
     */
    protected function set_error($error) {
        $this->errors[] = $error;
    }

    /**
     * Get all errors 
     * @return array
     */
    public function get_errors() {
        return $this->errors;
    }

    /**
     * check if errors happened
     * @return boolean
     */
    public function has_errors() {
        return !empty($this->errors);
    }

    /**
     * set routes for the endpoint 
     */
    abstract public function register_routes();

    /**
     * Checks if user is allowed to view resources 
     * 
     * @return \WP_Error|boolean
     */
    public function get_items_permissions_check() {
        if (_stacks_api_is_guest() || _stacks_api_is_user()) {
            return true;
        }

        return $this->get_response_service()->return_unauthorized_user_response();
    }

    /**
     * Checks if there is a user to get his items 
     * 
     * @return \WP_Error|boolean
     */
    public function get_user_items_permission() {
        if (_stacks_api_is_user()) {
            return true;
        }

        return $this->get_response_service()->return_unauthorized_user_response();
    }


    /**
     * Mapping request Parameters 
     * 
     * @param object $request
     * @return $this
     */
    public function map_request_parameters($request) {
        $params = apply_filters('_api_before_mapping_api_params_to_controller', $request->get_params());

        $this->map_request_params($params);

        return $this;
    }

    /**
     * Convert arabic numbers to english 
     * 
     * @param string $string
     * 
     * @return string
     */
    private function convert_arabic_numbers_to_english($string) {
        if (!is_array($string)) {
            $arabic = ['٩', '٨', '٧', '٦', '٥', '٤', '٣', '٢', '١', '٠'];
            $num = array_reverse(range(0, 9));

            $string = str_replace($arabic, $num, $string);
        }

        return $string;
    }

    /**
     * Map Request parameters to local variables into data parameter
     * @param array $params
     */
    protected function map_request_params($params) {
        if (!empty($params) && !empty($this->allowed_params)) {
            foreach ($this->allowed_params as $param_sent => $param_save) {
                if (isset($params[$param_sent])) {
                    $this->data[$param_save] = $this->convert_arabic_numbers_to_english($params[$param_sent]);
                }
            }
        }
        return $this;
    }

    /**
     * Return single parameter after being mapped 
     * @param string $param
     * @return string|boolean
     */
    protected function get_request_param($param) {
        if (isset($this->data[$param])) {
            return $this->data[$param];
        }
        return false;
    }

    /**
     * Return single parameter after being mapped casted to integer
     * @param type $param
     * @return type
     */
    protected function get_request_param_int($param) {
        return (int) $this->get_request_param($param);
    }

    /**
     * set request parameter a default value
     * @param string $param
     * @param string $value
     * @return void
     */
    protected function set_request_param($param, $value) {
        $this->data[$param] = $value;
    }

    /**
     * Format Single item and Extract values needed
     * @param type $item
     * @return type
     */
    public function format_categories($item) {
        return StacksWoocommerceDataFormating::format_categories($item);
    }

    /**
     * validate if integer value 
     * @param integer $param
     * @param object $request
     * @param string $key
     * @return boolean
     */
    public function validate_integer($param, $request, $key) {
        if (is_numeric($param)) {
            return true;
        }
        return false;
    }

    /**
     * validate if string not empty 
     * @param integer $param
     * @param object $request
     * @param string $key
     * @return boolean
     */
    public function validate_string($param, $request, $key) {
        if (strlen($param) > 0) {
            return true;
        }
        return false;
    }

    /**
     * check if product is valid or not 
     * @param int $id
     * @return boolean
     */
    protected function is_valid_product_id($id) {
        $product = wc_get_product($id);

        if (!$product) {
            return false;
        }
        return $product;
    }
}
