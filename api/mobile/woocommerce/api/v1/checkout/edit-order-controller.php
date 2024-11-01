<?php

class Stacks_EditOrderController extends Stacks_AbstractOrderController {

    /**
     * Route base.
     * @var string
     */
    protected $rest_api = '/orders/(?P<order_id>[\d]+)';

    /**
     * @inherit_doc
     */
    protected $allowed_params = [
        'order_id'    => 'order_id',
        'success'    => 'success'
    ];

    public function register_routes() {
        register_rest_route($this->get_api_endpoint(), $this->rest_api . '/status', array( // V3
            array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array($this, 'edit_order'),
                'permission_callback' => array($this, 'get_user_items_permission'),
                'args'                => $this->get_collection_params()
            ),
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'edit_order'),
                'permission_callback' => array($this, 'get_user_items_permission'),
                'args'                => $this->get_collection_params()
            ),
            'schema' => array($this, 'get_public_item_schema'),
        ));

        register_rest_route($this->get_api_endpoint(), $this->rest_api . '/cancel', array( // V3
            array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array($this, 'cancel_order'),
                'permission_callback' => array($this, 'get_user_items_permission'),
                'args'                => $this->get_collection_params_cancel()
            ),
            'schema' => array($this, 'get_public_item_schema'),
        ));
    }

    /**
     * cancel order 
     * @param object $request
     * @return type
     */
    public function cancel_order($request) {
        $this->map_request_parameters($request);

        $order_id = $this->get_request_param('order_id');

        $order = wc_get_order($order_id);

        // if order is not valid
        if (!$order) {
            return $this->return_error_response(Stacks_WC_Api_Response_Service::INVALID_PARAMETER_CODE, $this->invalid_parameter_message(), array($this->invalid_order_id_message()), 500);
        }

        if (!$this->order_can_be_canceled($order)) {
            return $this->return_error_response(Stacks_WC_Api_Response_Service::INVALID_PARAMETER_CODE, $this->invalid_parameter_message(), array(__('this order can not be canceled', 'plates')), 500);
        }

        $order->update_status('cancelled');

        return $this->return_success_response(true);
    }

    /**
     * creates the order 
     * @return array
     */
    public function edit_order($request) {
        $this->map_request_parameters($request);

        $order_id            = $this->get_request_param('order_id');
        $payment_success    = $this->get_request_param('success');

        $order = wc_get_order($order_id);

        // if order is not valid
        if (!$order) {
            return $this->return_error_response(Stacks_WC_Api_Response_Service::INVALID_PARAMETER_CODE, $this->invalid_parameter_message(), array($this->invalid_order_id_message()), 500);
        }

        // if order do not need payment
        if (!$this->order_needs_payment($order)) {
            return $this->return_error_response(Stacks_WC_Api_Response_Service::INVALID_PARAMETER_CODE, $this->invalid_parameter_message(), array(__('Order do not need payment', 'plates')), 500);
        }

        if ($payment_success == true) {
            $order->payment_complete();
        } else {
            $order->update_status('failed');
        }

        return $this->return_success_response(true);
    }


    /**
     * Options to pass to limit the response 
     * 
     * @return array
     */
    public function get_collection_params() {
        $params = array();

        $params['order_id'] = array(
            'description'       => __('Order Id', 'plates'),
            'type'              => 'integer',
            'required'          => true,
            'sanitize_callback' => 'sanitize_text_field'
        );

        $params['success'] = array(
            'description'       => __('paypal payment process  succeeded or not ', 'plates'),
            'type'              => 'boolean',
            'required'          => true,
            'sanitize_callback' => 'sanitize_text_field'
        );

        return $params;
    }

    /**
     * Options to pass to limit the response 
     * 
     * @return array
     */
    public function get_collection_params_cancel() {
        $params = array();

        $params['order_id'] = array(
            'description'       => __('Order Id', 'plates'),
            'type'              => 'integer',
            'required'          => true,
            'sanitize_callback' => 'sanitize_text_field'
        );

        return $params;
    }
}
