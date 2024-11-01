<?php

class Stacks_VariationsController extends Stacks_AbstractController {

    protected $rest_api = 'product/(?P<product_id>[\d]+)/variations';
    /**
     * @inherit_doc
     */
    protected $allowed_params = [
        'product_id'    => 'product_id',
        'variation_id'    => 'variation_id'
    ];

    protected $product;

    public function register_routes() {
        register_rest_route($this->get_api_endpoint(), $this->rest_api, array( // V3
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_product_variations'),
                'permission_callback' => array($this, 'get_items_permissions_check'),
                'args'                => $this->get_collection_params_get()
            ),
            'schema' => array($this, 'get_public_item_schema'),
        ));

        register_rest_route($this->get_api_endpoint(), $this->rest_api . '/(?P<variation_id>[\d]+)', array( // V3
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_product_variation_information'),
                'permission_callback' => array($this, 'get_items_permissions_check'),
                'args'                => $this->get_collection_params_variation()
            ),
            'schema' => array($this, 'get_public_item_schema'),
        ));
    }

    /**
     * Get product variations or just a single variation
     * @param object $product
     * @param int $variation_id
     * @return boolean
     */
    private function get_variations($product, $variation_id = false) {
        $variations = $product->get_available_variations();

        if (!$variation_id) {
            return $variations;
        }

        if (!empty($variations)) {
            foreach ($variations as $variation) {
                if ($variation_id == $variation['variation_id']) {
                    return $variation;
                }
            }
        }
        return false;
    }

    /**
     * Validate product id and validation id if exists 
     * @param int $product_id
     * @return array
     */
    public function perform_validation($product_id, $variation_id = false) {
        // validate this is a valid product id 
        if (!$this->is_valid_product_id($product_id)) {
            return $this->return_error_response(Stacks_WC_Api_Response_Service::INVALID_PARAMETER_CODE, $this->invalid_parameter_message(), array($this->invalid_product_id_message()), 400);
        }

        $product = wc_get_product($product_id);

        if ('variable' !==  $product->get_type()) {
            return $this->return_error_response(Stacks_WC_Api_Response_Service::INVALID_PARAMETER_CODE, $this->invalid_parameter_message(), array($this->not_variable_products_do_not_have_variations_message()), 400);
        }

        if ($variation_id) {
            if (!$this->get_variations($product, $variation_id)) {
                return $this->return_error_response(Stacks_WC_Api_Response_Service::INVALID_PARAMETER_CODE, $this->invalid_parameter_message(), array($this->not_valid_variation_id_message()), 400);
            }
        }

        return array();
    }


    /**
     * Get Product Variations
     * @param WP_REST_Request $request
     * @return array
     */
    public function get_product_variations($request) {
        $this->map_request_parameters($request);

        $product_id = $this->get_request_param('product_id');
        $errors        = $this->perform_validation($product_id);

        if (!empty($errors)) {
            return $errors;
        }

        $product    = wc_get_product($product_id);
        $variations = $this->get_variations($product);

        return $this->return_success_response(StacksWoocommerceDataFormating::format_product_variations($variations));
    }

    /**
     * Get Product Variation Data
     * @param object $request
     * @return array
     */
    public function get_product_variation_information($request) {
        $this->map_request_parameters($request);

        $product_id        = $this->get_request_param('product_id');
        $variation_id    = $this->get_request_param('variation_id');

        $errors = $this->perform_validation($product_id, $variation_id);

        if (!empty($errors)) {
            return $errors;
        }

        $product = wc_get_product($product_id);

        return $this->return_success_response($this->get_variations($product, $variation_id));
    }

    /**
     * Get collection parameters when editing item within cart
     * 
     * @return array
     */
    public function get_collection_params_get() {
        $params = array();

        $params['product_id'] = array(
            'description'       => __('Product id you would like to get variations.', 'woocommerce'),
            'type'              => 'int',
            'required'          => true,
            'sanitize_callback' => 'sanitize_text_field',
            'validate_callback' => array($this, 'validate_integer'),
        );

        return $params;
    }

    /**
     * Get collection parameters when editing item within cart
     * 
     * @return array
     */
    public function get_collection_params_variation() {
        $params = $this->get_collection_params_get();

        $params['variation_id'] = array(
            'description'       => __('Variation id you would like to get it\'s data.', 'woocommerce'),
            'type'              => 'int',
            'required'          => true,
            'sanitize_callback' => 'sanitize_text_field',
            'validate_callback' => array($this, 'validate_integer'),
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
