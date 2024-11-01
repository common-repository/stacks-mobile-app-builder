<?php

class Stacks_WC_Api_Response_Service {

    // Error status
    const INVALID_PARAMETER_STATUS_CODE = 400;
    const CLIENT_ERROR_STATUS_CODE = 400;
    const SERVER_ERROR_STATUS_CODE = 500;

    // Error Codes
    const ADDON_NOT_ACTIVATED_CODE        = 'addon_not_activated_code';
    const INVALID_PARAMETER_CODE        = 'stacks_rest_invalid_parameter';
    const UNEXPECTED_ERROR_CODE            = 'stacks_unexpected_error';
    const UNAUTHORIZED_ERROR_CODE        = 'stacks_rest_cannot_view';
    const INVALID_CREDENTIALS_CODE        = 'invalid_credentials';
    const EMAIL_ALREADY_REGISTERED_CODE        = 'email_already_registered';
    const COUPONS_NOT_ACTIVE_CODE        = 'coupons_is_not_active';
    const CAN_NOT_aPPLY_COUPON            = 'coupon_can_not_be_applied';


    /**
     * @var Stacks_Wc_Api_Response
     */
    public static $instance = null;

    /**
     * Get new instance from static
     * 
     * @return Stacks_Wc_Api_Response
     */
    public static function get_instance() {
        if (is_null(static::$instance)) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    /**
     * Get error Message using 
     * 
     * @param string $code
     * 
     * @return string
     */
    public function get_message($code) {
        return Translatable_Strings::get_code_message($code);
    }

    /**
     * Send Success Response to User
     *
     * @param array $data
     * 
     * @return array
     */
    public function return_success_response($data, $message = false) {
        return [
            "success"   => true,
            "message"    => $message ? $message : apply_filters('stacks_woocommerce_api_message', Translatable_Strings::get_code_message('data_returned_successfully')),
            "data"      => $data
        ];
    }

    /**
     * Invalid parameters passed message
     * 
     * @return string
     */
    public function invalid_parameter_message() {
        return $this->get_message('invalid_parameters_message');
    }

    /**
     * invalid parameters passed message
     * 
     * @return string
     */
    public function unexpected_error_message() {
        return $this->get_message('unexpected_error_message');
    }

    /**
     * Error Message when user try to add to cart but add ons are required
     * 
     * @return string
     */
    public function addons_required_add_to_cart_message() {
        return $this->get_message('some_addons_are_required');
    }

    /**
     * return order do not belong to user message
     * 
     * @return string
     */
    public function order_do_not_belong_to_user_message() {
        return $this->get_message('order_do_not_belong_user');
    }

    /**
     * return invalid product id message
     * 
     * @return string
     */
    public function invalid_product_id_message() {
        return $this->get_message('invalid_product_id');
    }

    /**
     * return invalid product id message
     * 
     * @return string
     */
    public function not_variable_products_do_not_have_variations_message() {
        return $this->get_message('no_variations_for_simple_product');
    }

    /**
     * return invalid variation id message
     * 
     * @return string
     */
    public function not_valid_variation_id_message() {
        return $this->get_message('not_valid_variation_id_message');
    }

    /**
     * return invalid order id message
     * 
     * @return string
     */
    public function invalid_order_id_message() {
        return $this->get_message('order_does_not_exist');
    }

    /**
     * return out of stock error message 
     * 
     * @return type
     */
    public function get_out_of_stock_message() {
        return $this->get_message('product_out_of_stock');
    }

    /**
     * return add-on not activated string 
     * 
     * @return string
     */
    public function addon_not_activated_message() {
        return $this->get_message('addon_not_activated');
    }

    /**
     * return user doesn't has any points to use
     * @return string
     */
    public function no_points_exists_message() {
        return $this->get_message('you_do_not_have_points');
    }

    /**
     * return points discount already applied message
     * @return string
     */
    public function points_discount_already_applied_message() {
        return $this->get_message('points_discount_already_applied_message');
    }


    /**
     * return new \WP_Error for invalid parameters
     * 
     * @return \WP_Error
     */
    public function invalid_parameter_exception($param_name) {
        $code           = self::INVALID_PARAMETER_CODE;
        $message        = __('Invalid parameter ', 'plates') . $param_name;
        $status_code    = self::INVALID_PARAMETER_STATUS_CODE;

        return $this->return_error_response($code, $message, array(), $status_code);
    }

    /**
     * return new \WP_Error for missing parameters
     * 
     * @return \WP_Error
     */
    public function missing_parameter_exception($param_name) {
        $code           = self::INVALID_PARAMETER_CODE;
        $message        = __('Missing parameter ', 'plates') . $param_name;
        $status_code    = self::INVALID_PARAMETER_STATUS_CODE;

        return $this->return_error_response($code, $message, array(), $status_code);
    }


    /**
     * Return unauthorized response 
     * 
     * @return \WP_Error
     */
    public function return_unauthorized_user_response() {
        $code           = self::UNAUTHORIZED_ERROR_CODE;
        $message        = $this->get_message('unauthorized_access');
        $status_code    = rest_authorization_required_code();

        return $this->return_error_response($code, $message, array(), $status_code);
    }


    /**
     * Return Invalid Parameters Response
     * 
     * @return array
     */
    public function invalid_parameter_response($message) {
        $errors = !is_array($message) ? array($message) : $message;

        return $this->return_error_response(self::INVALID_PARAMETER_CODE, $this->invalid_parameter_message(), $errors, self::INVALID_PARAMETER_STATUS_CODE);
    }

    /**
     * send error Response to User
     * 
     * @param string $message
     * @param string $status_code
     * 
     * @return \WP_Error
     */
    public function return_error_response($code, $message, $errors, $status_code = 400) {
        return new WP_Error($code, $message, array('status' => $status_code, "errors" => $errors));
    }

    /**
     * return add-on not activated response 
     * 
     * @return array
     */
    public function return_addon_not_activated_response() {
        return $this->return_error_response(self::ADDON_NOT_ACTIVATED_CODE, $this->addon_not_activated_message(), array($this->addon_not_activated_message()), self::SERVER_ERROR_STATUS_CODE);
    }

    /**
     * Return Coupon is Expired Response
     * 
     * @return array
     */
    public function return_invalid_coupon_response($woo_message) {
        return $this->return_error_response(self::INVALID_PARAMETER_CODE, $woo_message, array($woo_message), self::CLIENT_ERROR_STATUS_CODE);
    }

    /**
     * Build and Return response user not found
     * 
     * @return array
     */
    public function return_forgot_password_no_users_found() {
        $message = $this->get_message('forgot_password_no_users_found');

        return $this->return_error_response(self::UNEXPECTED_ERROR_CODE, $message, array($message), self::SERVER_ERROR_STATUS_CODE);
    }

    /**
     * Build and Return response Email Did not sent
     * 
     * @return array
     */
    public function return_forgot_password_couldnot_send_email() {
        $message = $this->get_message('forgot_password_error_Sending_email');

        return $this->return_error_response(self::UNEXPECTED_ERROR_CODE, $message, array($message), self::SERVER_ERROR_STATUS_CODE);
    }
}
