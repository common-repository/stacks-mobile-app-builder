<?php

class Stacks_ProductVariationAttributesValidator extends Stacks_WishlistAbstractController {

    /**
     * Route base.
     * 
     * @var string
     */
    protected $rest_api = '/validate_variation_attributes';

    /**
     * @inherit_doc
     */
    protected $allowed_params = [
        'id'        => 'product_id',
        'attributes'    => 'attributes'
    ];

    /**
     * @var WP_User
     */
    protected $user = null;

    public function register_routes() {
        register_rest_route($this->get_api_endpoint(), $this->rest_api, array( // V3
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'validate_variation_attributes'),
                'permission_callback' => array($this, 'get_items_permissions_check'),
                'args'                => $this->get_collection_params()
            ),
            'schema' => array($this, 'get_public_item_schema'),
        ));
    }

    /**
     * validate variation
     * 
     * @param object $request
     * 
     * @return array
     */
    public function validate_variation_attributes($request) {
        $this->map_request_params($request->get_params());

        $product_id = $this->get_request_param_int('product_id');
        $product    = wc_get_product($product_id);
        $attributes = $this->get_request_param('attributes');

        // is valid product 
        if (!$this->is_valid_product_id($product_id)) {
            return $this->not_valid_product_id_error_response();
        }

        $result = $this->validate_variation($product, $attributes, 0);

        if (!$result['variation_id']) {
            add_filter('stacks_woocommerce_api_message', function () {
                return $this->get_message('variation_does_not_exist');
            });

            return $this->return_success_response(0);
        } else {
            return $this->return_success_response($result['variation_id']);
        }
    }


    /**
     * Options to pass to limit the response 
     * 
     * @return string
     */
    public function get_collection_params() {
        $params = array();
        $params['attributes'] = array(
            'description'       => __('Array of Attributes to validate', 'plates'),
            'type'              => 'object',
            'required'          => true
        );

        $params['id'] = array(
            'description'       => __('Product ID', 'plates'),
            'type'              => 'integer',
            'required'          => true,
            'sanitize_callback' => 'sanitize_text_field',
            'validate_callback' => array($this, 'validate_integer')
        );

        return $params;
    }
}
