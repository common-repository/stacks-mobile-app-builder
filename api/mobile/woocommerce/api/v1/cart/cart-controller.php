<?php

class Stacks_CartController extends Stacks_CartAbstractController {

    /**
     * @inherit_doc
     */
    protected $allowed_params = [
        'quantity'        => 'quantity',
        'id'            => 'product_id',
        'variation_id'        => 'variation_id',
        'attributes'        => 'attributes',
        'hash_id'        => 'hash_id',
        'coupon_id'        => 'coupon_id',
        'shipping_method'    => 'shipping_method',
        'instance_id'        => 'instance_id',
        'product_id'        => 'product_id'
    ];

    protected $product;

    public function register_routes() {
        register_rest_route($this->get_api_endpoint(), $this->rest_api, array( // V3
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_cart'),
                'permission_callback' => array($this, 'get_items_permissions_check'),
                'args'                => $this->get_collection_params_get()
            ),
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array($this, 'add_to_cart'),
                'permission_callback' => array($this, 'get_items_permissions_check'),
                'args'                => $this->get_collection_params_add()
            ),
            array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array($this, 'edit_item_in_cart'),
                'permission_callback' => array($this, 'get_items_permissions_check'),
                'args'                => $this->get_collection_params_edit()
            ),
            array(
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => array($this, 'delete_item_from_cart'),
                'permission_callback' => array($this, 'get_items_permissions_check'),
                'args'                => $this->get_collection_params_delete()
            ),
            'schema' => array($this, 'get_public_item_schema'),
        ));

        register_rest_route($this->get_api_endpoint(), $this->rest_api.'/remove_from_cart', array( // V3
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'delete_item_from_cart'),
                'permission_callback' => array($this, 'get_items_permissions_check'),
                'args'                => $this->get_collection_params_delete()
            ),
            'schema' => array($this, 'get_public_item_schema'),
        ));

        register_rest_route($this->get_api_endpoint(), $this->rest_api .'/add_to_cart', array( // V3
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'add_to_cart'),
                'permission_callback' => array($this, 'get_items_permissions_check'),
                'args'                => $this->get_collection_params_add()
            ),
            'schema' => array($this, 'get_public_item_schema'),
        ));

        register_rest_route($this->get_api_endpoint(), $this->rest_api .'/edit_item_in_cart', array( // V3
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'edit_item_in_cart'),
                'permission_callback' => array($this, 'get_items_permissions_check'),
                'args'                => $this->get_collection_params_edit()
            ),
            'schema' => array($this, 'get_public_item_schema'),
        ));

        register_rest_route($this->get_api_endpoint(), $this->rest_api . '/clear', array( // V3
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'empty_cart'),
                'permission_callback' => array($this, 'get_items_permissions_check'),
                'args'                => $this->get_collection_params_get()
            ),
            'schema' => array($this, 'get_public_item_schema'),
        ));

        register_rest_route($this->get_api_endpoint(), $this->rest_api . '/shipping/', array( // V3
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array($this, 'add_shipping_method'),
                'permission_callback' => array($this, 'get_items_permissions_check'),
                'args'                => $this->get_collection_params_add_shipping()
            ),
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'add_shipping_method'),
                'permission_callback' => array($this, 'get_items_permissions_check'),
                'args'                => $this->get_collection_params_add_shipping()
            ),
            'schema' => array($this, 'get_public_item_schema'),
        ));
    }

    /**
     * Adds Shipping method session to cart
     * 
     * @param object $request
     */
    public function add_shipping_method($request) {
        $this->map_request_parameters($request);

        wc_maybe_define_constant('WOOCOMMERCE_CART', true);

        WC()->session->set('chosen_shipping_methods', array(sprintf('%s:%s', $this->get_request_param('shipping_method'), $this->get_request_param_int('instance_id'))));

        return $this->return_success_response($this->get_cart_details());
    }

    /**
     * Get cart Data
     * @param object $request
     * @return array
     */
    public function get_cart($request) {
        $this->map_request_parameters($request);

        $cart_contents = $this->cart_model->get_cart_contents();

        $shipping_methods = array_values($this->get_zone_shipping_methods(wc()->customer->get_shipping_country(), wc()->customer->get_shipping_state(), wc()->customer->get_shipping_postcode()));

        $args = [
            'contents'        => array_values($this->organize_cart_contents($cart_contents)),
            'cart_details'    => $this->get_cart_details(),
            'shipping_methods'  => $shipping_methods
        ];

        if (empty($shipping_methods)) {
            $args['shipping_methods_empty_message'] = __('Unfortunately, we don\'t ship to your location yet.', 'plates');
        }

        return $this->return_success_response(apply_filters('_api_before_returning_cart', $args));
    }

    /**
     * Edit item in cart 
     * @param object $request
     * @return array
     */
    public function edit_item_in_cart($request) {
        $this->map_request_parameters($request);

        $hash_key = $this->get_request_param('hash_id');

        $remove_result = $this->remove_item_from_cart($hash_key);

        if (true) {
            $add_result = $this->add_to_cart($request);

            if (!is_wp_error($add_result) && $add_result['success']) {
                WC()->cart->calculate_totals();
            }

            return $add_result;
        } else {
            return $this->get_item_not_exists_in_cart_error();
        }
    }

    /**
     * empty_Cart
     * 
     * @param object $request
     * @return array
     */
    public function empty_cart($request) {
        $this->map_request_parameters($request);

        wc()->cart->empty_cart();

        return $this->return_success_response($this->get_cart_details());
    }

    /**
     * Delete Item from cart
     * @param object $request
     * @return array
     */
    public function delete_item_from_cart($request) {
        $this->map_request_parameters($request);

        $remove_result = $this->remove_item_from_cart($this->get_request_param('hash_id'));

        if ($remove_result) {
            WC()->cart->calculate_totals();

            return $this->return_success_response($this->get_cart_details());
        } else {
            return $this->get_item_not_exists_in_cart_error();
        }
    }


    /**
     * add to cart main controller function
     * @param object $request
     * @return array
     */
    public function add_to_cart($request) {
        // get request 
        $this->map_request_parameters($request);

        // System Valiation to allow it to send any kind of messages
        $proceed = apply_filters('_api_before_process_add_to_cart', true, $this);

        if (is_wp_error($proceed)) {
            return $proceed;
        }

        $product_id          = apply_filters('woocommerce_add_to_cart_product_id', absint($this->get_request_param('product_id')));
        $this->product       = wc_get_product($product_id);

        // validate this is a valid product id 
        if (!$this->product) {
            return $this->return_error_response(Stacks_WC_Api_Response_Service::INVALID_PARAMETER_CODE, $this->invalid_parameter_message(), array(__('invalid product id', 'plates')), 400);
        }

        $add_to_cart_handler = $this->product->is_type('variable') ? 'variable' : 'simple';
        $result = null;

        $this->check_addons($product_id);

        if ($add_to_cart_handler == 'variable') {
            $result = $this->add_variable_product_to_cart();
        } elseif ($add_to_cart_handler == 'simple') {
            $result = $this->add_simple_product_to_cart();
        } else {
            return $this->return_error_response(Stacks_WC_Api_Response_Service::INVALID_PARAMETER_CODE, $this->invalid_parameter_message(), array(__('product type is neither simple nor variable', 'plates')), 400);
        }

        // return response 
        if (!$result['success'] && $add_to_cart_handler == 'simple') {
            return $this->return_error_response(Stacks_WC_Api_Response_Service::UNEXPECTED_ERROR_CODE, $result['errors'][0], $result['errors'], 400);
        } elseif (!$result['success'] && $add_to_cart_handler == 'variable') {
            return $this->return_error_response(Stacks_WC_Api_Response_Service::UNEXPECTED_ERROR_CODE, $this->unexpected_error_message(), $result['errors'], 400);
        } else {
            WC()->cart->calculate_totals();

            $response = ['hash' => $result['hash'], 'totals' => $this->get_cart_details($result['hash'])];

            return $this->return_success_response(apply_filters('_api_before_returning_add_cart', $response));
        }
    }


    /**
     * add simple product to cart 
     * @return string|boolean
     */
    protected function add_simple_product_to_cart() {
        $hash_key = wc()->cart->add_to_cart($this->get_request_param('product_id'), $this->get_request_param('quantity'));

        if (!$hash_key) {
            return $this->return_error_out_of_stock();
        }

        return array('success' => true, 'hash' => $hash_key);
    }


    /**
     * 
     * @return array|boolean
     */
    protected function add_variable_product_to_cart() {
        $attributes     = $this->get_request_param('attributes');
        $result            = $this->validate_variation($this->product, $attributes, $this->get_request_param_int('variation_id'));

        $variation_id    = $result['variation_id'];
        $submitted_variation_values = $result['submitted_variations'];

        if (!$variation_id) {
            return array('success' => false, 'errors' => array(__('invalid variation id', 'plates')));
        }

        $result = $this->validate_submitted_variations($this->product, $submitted_variation_values, $variation_id);

        $missing_attributes = $result['missing_attributes'];
        $invalid_attributes = $result['invalid_attributes'];
        $variations         = $result['variations'];

        if (!empty($missing_attributes)) {
            return array('success' => false, 'errors' => $missing_attributes);
        }

        if (!empty($invalid_attributes)) {
            return array('success' => false, 'errors' => $invalid_attributes);
        }

        // get required variables
        $product_id = $this->product->get_id();
        $quantity   = $this->get_request_param('quantity');

        // validate user can add product to cart 
        $passed_validation     = apply_filters('woocommerce_add_to_cart_validation', true, $product_id, $quantity, $variation_id, $variations);

        $errors = $this->get_errors_variations_validation();

        if (!empty($errors)) {
            return array('success' => false, 'errors' => $errors);
        }

        if ($passed_validation) {
            // now add product to cart 
            $hash = WC()->cart->add_to_cart($product_id, $quantity, $variation_id, $variations);

            return array('success' => true, 'hash' => $hash);
        }
    }


    /**
     * Options to pass to limit the response 
     * 
     * @return string
     */
    public function get_collection_params_add() {
        $params = array();

        $params['id'] = array(
            'description'       => __('Product id you would like to add to cart.', 'woocommerce'),
            'type'              => 'int',
            'required'          => true,
            'sanitize_callback' => 'sanitize_text_field',
            'validate_callback' => array($this, 'validate_integer'),
        );

        $params['quantity'] = array(
            'description'       => __('product quantity you would like to add to cart.', 'woocommerce'),
            'type'              => 'int',
            'required'          => true,
            'sanitize_callback' => 'sanitize_text_field',
            'validate_callback' => array($this, 'validate_integer'),
        );


        return $params;
    }

    /**
     * get collection parameters for get request
     * @return array
     */
    public function get_collection_params_get() {
        return array();
    }

    /**
     * Get collection parameters when editing item within cart
     * 
     * @return array
     */
    public function get_collection_params_edit() {
        $params = array_merge($this->get_collection_params(), $this->get_collection_params_delete());

        $params['hash_id'] = array(
            'description'       => __('Hash id of the product you would like to add.', 'plates'),
            'type'              => 'string',
            'required'          => true,
            'sanitize_callback' => 'sanitize_text_field',
            'validate_callback' => 'rest_validate_request_arg'
        );

        return $params;
    }


    /**
     * Get collection parameters when editing item within cart
     * 
     * @return array
     */
    public function get_collection_params_delete() {
        $params = array();

        $params['hash_id'] = array(
            'description'       => __('Hash id of the product you would like to add.', 'plates'),
            'type'              => 'string',
            'required'          => true,
            'sanitize_callback' => 'sanitize_text_field',
            'validate_callback' => 'rest_validate_request_arg'
        );

        return $params;
    }

    /**
     * collection of parameters required before accepting request 
     * @return array
     */
    public function get_collection_params_add_shipping() {
        $params = array();

        $params['shipping_method'] = array(
            'description'       => __('Type of Shipping Method', 'plates'),
            'type'              => 'string',
            'required'          => true,
            'sanitize_callback' => 'sanitize_text_field'
        );

        $params['instance_id'] = array(
            'description'       => __('Shipping Method Instance id', 'plates'),
            'type'              => 'int',
            'required'          => true,
            'sanitize_callback' => 'sanitize_text_field',
            'validate_callback' => array($this, 'validate_integer')
        );

        return $params;
    }
}
