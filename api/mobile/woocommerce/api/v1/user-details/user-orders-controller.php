<?php

/**
 * Controller Responsible for getting orders,view,cancel
 * @Route("/user/{id}/orders")
 * @Route("/user/{id}/orders/{id}")
 * @Method("GET")
 * @Method("PUT")
 */
class Stacks_UserOrdersController extends Stacks_AbstractOrderController {
    /**
     * Route base.
     * @var string
     */
    protected $orders_endpoint = '/user/(?P<id>[\d]+)/orders';

    /**
     * Route base.
     * @var string
     */
    protected $single_order_endpoint = '/user/(?P<id>[\d]+)/order/(?P<order_id>[\d]+)';

    /**
     * @var string 
     */
    protected $type = 'users';

    /**
     * @inherit_doc
     */
    protected $allowed_params = [
        'id'        => 'user_id',
        'order_id'  => 'order_id',
    ];

    /**
     * @var WP_User
     */
    protected $user = null;

    public function register_routes() {
        register_rest_route($this->get_api_endpoint(), $this->orders_endpoint, array( // V3
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_user_orders'),
                'permission_callback' => array($this, 'get_user_items_permission'),
                'args'                => $this->get_collection_params()
            ),
            'schema' => array($this, 'get_public_item_schema'),
        ));

        register_rest_route($this->get_api_endpoint(), $this->single_order_endpoint, array( // V3
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_user_order'),
                'permission_callback' => array($this, 'get_user_items_permission'),
                'args'                => $this->get_collection_params_single()
            ),
            array(
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => array($this, 'cancel_user_order'),
                'permission_callback' => array($this, 'get_user_items_permission'),
                'args'                => $this->get_collection_params_single()
            ),
            'schema' => array($this, 'get_public_item_schema'),
        ));

        register_rest_route($this->get_api_endpoint(), $this->single_order_endpoint . '/delete_order', array( // V3
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'cancel_user_order'),
                'permission_callback' => array($this, 'get_user_items_permission'),
                'args'                => $this->get_collection_params_single()
            ),
            'schema' => array($this, 'get_public_item_schema'),
        ));
    }

    /**
     * get user orders
     * @param object $request
     * @return array
     */
    public function get_user_orders($request) {
        $this->map_request_parameters($request);

        // $current_user_id = $request->get_params();
        // $current_user_id = (int) $current_user_id["id"];
        $current_user_id = get_current_user_id();
        $customer_orders = get_posts(array(
            'numberposts' => -1,
            'meta_key'    => '_customer_user',
            'meta_value'  => $current_user_id,
            'post_type'   => wc_get_order_types(),
            'post_status' => array_keys(wc_get_order_statuses()),
        ));

        return $this->return_success_response(StacksWoocommerceDataFormating::format_orders($customer_orders));
    }

    /**
     * get user orders
     * @param object $request
     * @return array
     */
    public function get_user_order($request) {
        $this->map_request_parameters($request);

        $order_id = $this->get_request_param('order_id');

        if (!$this->is_valid_order($order_id)) {
            return $this->return_error_response(Stacks_WC_Api_Response_Service::INVALID_PARAMETER_CODE, $this->invalid_parameter_message(), array($this->invalid_order_id_message()));
        }

        if (get_post_status($order_id)) {
            $order = get_post($order_id);

            if ($this->is_order_belong_to_user($order_id)) {
                return $this->return_success_response(StacksWoocommerceDataFormating::format_orders(array($order))[0]);
            } else {
                return $this->return_error_response(Stacks_WC_Api_Response_Service::INVALID_PARAMETER_CODE, $this->invalid_parameter_message(), array($this->order_do_not_belong_to_user_message()));
            }

            return $this->return_error_response(Stacks_WC_Api_Response_Service::INVALID_PARAMETER_CODE, $this->invalid_parameter_message(), array($this->order_do_not_belong_to_user_message()));
        }

        return $this->return_error_response(Stacks_WC_Api_Response_Service::INVALID_PARAMETER_CODE, $this->invalid_parameter_message(), array($this->invalid_order_id_message()));
    }

    /**
     * cancel user order 
     * 
     * @param type $request
     */
    public function cancel_user_order($request) {
        $this->map_request_params($request->get_params());

        $order_id = $this->get_request_param('order_id');

        if (!$this->is_valid_order($order_id)) {
            return $this->return_error_response(Stacks_WC_Api_Response_Service::INVALID_PARAMETER_CODE, $this->invalid_parameter_message(), array($this->invalid_order_id_message()));
        }

        if (!$this->is_order_belong_to_user($order_id) || !current_user_can('cancel_order', $order_id)) {
            return $this->return_error_response(Stacks_WC_Api_Response_Service::INVALID_PARAMETER_CODE, $this->invalid_parameter_message(), array($this->order_do_not_belong_to_user_message()));
        }

        $order = new WC_Order($order_id);

        // Cancel the order + restore stock
        WC()->session->set('order_awaiting_payment', false);

        $order->update_status('cancelled', __('Order cancelled by customer.', 'plates'));

        do_action('woocommerce_cancelled_order', $order->get_id());

        return $this->return_success_response(true);
    }

    /**
     * check if order is valid 
     * @param integer $id
     * @return boolean
     */
    protected function is_valid_order($id) {
        if (get_post_Status($id) && get_post_type($id) == 'shop_order') {
            return true;
        }
        return false;
    }

    /**
     * check if order belong to this user 
     * @param type $order_id
     * @return type
     */
    protected function is_order_belong_to_user($order_id) {
        $post = get_post($order_id);

        $_customer_user = (int) get_post_meta($order_id, '_customer_user', true);

        if ($_customer_user && $_customer_user === get_current_user_id()) {
            return true;
        }

        return false;
    }

    /**
     * Options to pass to limit the response 
     * 
     * @return string
     */
    public function get_collection_params() {
        $params = array();

        $params['id'] = array(
            'description'       => __('user ID', 'plates'),
            'type'              => 'integer',
            'required'          => true,
            'sanitize_callback' => 'sanitize_text_field',
            'validate_callback' => array($this, 'validate_integer')
        );

        return $params;
    }

    /**
     * Options to pass to limit the response 
     * 
     * @return string
     */
    public function get_collection_params_single() {
        $params = $this->get_collection_params();

        $params['order_id'] = array(
            'description'       => __('order id', 'plates'),
            'type'              => 'string',
            'required'          => true,
            'sanitize_callback' => 'sanitize_text_field',
            'validate_callback' => array($this, 'validate_integer')
        );

        return $params;
    }

    /**
     * the schema for the request 
     * 
     * @return string
     */
    public function get_public_item_schema() {
        $schema = array(
            '$schema'              => 'http://json-schema.org/draft-04/schema#',
            'title'                => $this->type,
            'type'                 => 'object',
            'properties'           => array(
                'id' => array(
                    'description'  => esc_html__('Unique identifier for the object.', 'plates'),
                    'type'         => 'integer',
                    'context'      => array('view'),
                    'readonly'     => true,
                )
            ),
        );

        return $schema;
    }
}
