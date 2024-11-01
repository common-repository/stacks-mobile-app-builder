<?php

class Stacks_ProductSingleController extends Stacks_AbstractController {


    /**
     * @inherit_doc
     */
    protected $allowed_params = [
        'product_id'            => 'product_id',
        'featured_image_size'    => 'featured_image_size'
    ];

    protected $rest_api = '/products/(?P<product_id>[\d]+)';

    /**
     * Default Attributes for the product
     * @var array 
     */
    protected $default_attributes = null;

    /**
     * @var object 
     */
    protected $product = null;

    public function register_routes() {
        register_rest_route($this->get_api_endpoint(), $this->rest_api, array( // V3
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_item'),
                'permission_callback' => array($this, 'get_items_permissions_check'),
                'args'                => $this->get_collection_params_get(),
            ),
            'schema' => array($this, 'get_public_item_schema'),
        ));
    }

    /**
     * Get Product Information 
     * @param object $request
     * @return array
     */
    public function get_item($request) {
        $this->map_request_params($request->get_params());

        $product_id = $this->get_request_param('product_id');

        if (!$this->is_valid_product_id($product_id)) {
            return $this->return_error_response(Stacks_WC_Api_Response_Service::INVALID_PARAMETER_CODE, $this->invalid_parameter_message(), array(__('this is not a valid product id', 'plates')), 400);
        }

        if ($this->get_request_param('featured_image_size')) {
            $result = StacksWoocommerceDataFormating::format_single_product($product_id, $this->get_request_param('featured_image_size'));
        } else {
            $result = StacksWoocommerceDataFormating::format_single_product($product_id);
        }

        return $this->return_success_response($result);
    }


    /**
     * Get collection parameters when editing item within cart
     * 
     * @return array
     */
    public function get_collection_params_get() {
        $params = array();

        $params['product_id'] = array(
            'description'       => __('product id you want to Get addons', 'plates'),
            'type'              => 'string',
            'required'          => true,
            'sanitize_callback' => 'sanitize_text_field',
            'validate_callback' => array($this, 'validate_integer')
        );

        $params['featured_image_size'] = array(
            'description'       => __('Featured Image Size default full', 'plates'),
            'type'              => 'string',
            'required'          => false,
            'sanitize_callback' => 'sanitize_text_field'
        );

        return $params;
    }
}
