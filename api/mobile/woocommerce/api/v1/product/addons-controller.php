<?php

class Stacks_AddonsController extends Stacks_AbstractController {


    /**
     * @inherit_doc
     */
    protected $allowed_params = [
        'product_id' => 'product_id'
    ];

    protected $rest_api = '/products/(?P<product_id>[\d]+)/addons';

    public function register_routes() {
        register_rest_route($this->get_api_endpoint(), $this->rest_api, array( // V3
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_addons'),
                'permission_callback' => array($this, 'get_items_permissions_check'),
                'args'                => $this->get_collection_params_get()
            ),
            'schema' => array($this, 'get_public_item_schema'),
        ));
    }

    /**
     * Get cart Data
     * @param object $request
     * @return array
     */
    public function get_addons($request) {
        $this->map_request_params($request->get_params());

        if (!stacks_is_addon_plugin_activated()) {
            return $this->return_error_response(Stacks_WC_Api_Response_Service::UNEXPECTED_ERROR_CODE, $this->unexpected_error_message(), array(__('Sorry Addon plugin not Activated', 'plates')), 500);
        }

        $product_id = $this->get_request_param('product_id');
        $product    = wc_get_product($product_id);

        if (!$product) {
            return $this->return_error_response(Stacks_WC_Api_Response_Service::INVALID_PARAMETER_CODE, $this->invalid_parameter_message(), array(__('this is not a valid product id', 'plates')), 500);
        }

        $addons = get_product_addons($product_id);

        return $this->return_success_response($addons);
    }

    /**
     * Get collection parameters when editing item within cart
     * 
     * @return array
     */
    public function get_collection_params_get() {
        $params = array();

        $params['product_id'] = array(
            'description'       => __('product id you want to Get addons', 'woocommerce'),
            'type'              => 'string',
            'required'          => true,
            'sanitize_callback' => 'sanitize_text_field',
            'validate_callback' => array($this, 'validate_integer')
        );

        return $params;
    }
}
