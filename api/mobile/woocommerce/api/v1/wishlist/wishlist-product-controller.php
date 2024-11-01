<?php

class Stacks_WishlistProductController extends Stacks_WishlistAbstractController {

    /**
     * Route base.
     * @var string
     */
    protected $rest_api = '/wishlist/(?P<wishlist_id>[\d]+)/product/';

    /**
     * @var string 
     */
    protected $type = 'wishlists';

    /**
     * @inherit_doc
     */
    protected $allowed_params = [
        'wishlist_id'   => 'wishlist_id',
        'product_id'    => 'product_id',
        'variation_id'  => 'variation_id',
        'attributes'    => 'attributes',
        'hash_key'      => 'hash_key',
    ];

    /**
     * @var WP_User
     */
    protected $user = null;

    public function register_routes() {
        register_rest_route($this->get_api_endpoint(), $this->rest_api, array( // V3
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_wishlist_item'),
                'permission_callback' => array($this, 'get_user_items_permission'),
                'args'                => $this->get_collection_params()
            ),
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array($this, 'add_wishlist_product'),
                'permission_callback' => array($this, 'get_user_items_permission'),
                'args'                => $this->get_collection_params_add()
            ),
            array(
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => array($this, 'delete_wishlist_product'),
                'permission_callback' => array($this, 'get_user_items_permission'),
                'args'                => $this->get_collection_params()
            ),
            'schema' => array($this, 'get_public_item_schema'),
        ));
    }

    /**
     * validate that user is valid 
     * @param int $wishlist_id
     * @param int $product_id
     * @return boolean
     */
    public function validation_middleware($wishlist_id, $product_id = null) {
        $validation_result = parent::validation_middleware($wishlist_id);

        if ($validation_result !== true) {
            return $validation_result;
        }

        if ($product_id) {
            // is valid product 
            if (!$this->is_valid_product_id($product_id)) {
                return $this->not_valid_product_id_error_response();
            }
        }

        return true;
    }

    /**
     * Get user wishlists
     *  
     * @param object $request
     * @return array
     */
    public function get_wishlist_item($request) {
        $this->map_request_params($request->get_params());

        $wishlist_id    = $this->get_request_param_int('wishlist_id');
        $hash_key       = $this->get_request_param('hash_key');

        $validation_result = $this->validation_middleware($wishlist_id);

        if ($validation_result !== true) {
            return $validation_result;
        }

        $wishlist_items = WC_Wishlists_Wishlist_Item_Collection::get_items($wishlist_id);

        if (isset($wishlist_items[$hash_key])) {
            $item = $this->organize_cart_contents(array($hash_key => $wishlist_items[$hash_key]));

            return $this->return_success_response($item[0]);
        } else {
            return $this->return_error_response(Stacks_WC_Api_Response_Service::INVALID_PARAMETER_CODE, $this->invalid_parameter_message(), array(__('this is not a valid hash key', 'plates')));
        }
    }

    /**
     * Delete user wishlist
     * @param object $request
     * @return array
     */
    public function add_wishlist_product($request) {
        $this->map_request_params($request->get_params());

        $wishlist_id    = $this->get_request_param_int('wishlist_id');
        $product_id     = $this->get_request_param_int('product_id');

        // validate user has this wishlist and product exists and this current user owns this product.
        $validation_result = $this->validation_middleware($wishlist_id, $product_id);
        if ($validation_result !== true) {
            return $validation_result;
        }

        $product    = wc_get_product($product_id);
        $result     = null;

        switch ($product->get_type()) {
                // Variable Products
            case 'variable':
                $this->check_addons($product_id);

                $result = $this->add_variable_product_to_wishlist($product, $wishlist_id, $product_id);
                break;
            case 'simple':
                $result = $this->add_simple_product_to_wishlist($wishlist_id, $product_id);
                break;
            default:
                return $this->return_error_response(Stacks_WC_Api_Response_Service::UNEXPECTED_ERROR_CODE, $this->unexpected_error_message(), array(__('product type unknown', 'plates')), 500);
        }

        return apply_filters('_stacks_response_after_adding_product_to_wishlist', $result);
    }

    /**
     * 
     * @param int $product
     * @param int $wishlist_id
     * @param int $product_id
     * @return array
     */
    public function add_variable_product_to_wishlist(&$product, &$wishlist_id, &$product_id) {
        $attribues            = $this->get_request_param('attributes');
        $validation_result    = $this->validate_variation($product, $attribues, $this->get_request_param_int('variation_id'));
        $variation_id        = $validation_result['variation_id'];
        $submitted_variation_values = $validation_result['submitted_variations'];

        if (!$variation_id) {
            return $this->return_error_response(Stacks_WC_Api_Response_Service::UNEXPECTED_ERROR_CODE, $this->unexpected_error_message(), array(__('invalid variation id', 'plates')), 400);
        }

        $result = $this->validate_submitted_variations($product, $submitted_variation_values, $variation_id);

        $missing_attributes = $result['missing_attributes'];
        $invalid_attributes = $result['invalid_attributes'];
        $variations         = $result['variations'];

        if (!empty($missing_attributes)) {
            return $this->return_error_response(Stacks_WC_Api_Response_Service::UNEXPECTED_ERROR_CODE, $this->unexpected_error_message(), $missing_attributes, 400);
        }

        if (!empty($invalid_attributes)) {
            return $this->return_error_response(Stacks_WC_Api_Response_Service::UNEXPECTED_ERROR_CODE, $this->unexpected_error_message(), $invalid_attributes, 400);
        }

        $product_id = apply_filters('woocommerce_add_to_cart_product_id', $product_id);

        $errors = $this->get_errors_variations_validation();

        if (!empty($errors)) {
            return $this->return_error_response(Stacks_WC_Api_Response_Service::UNEXPECTED_ERROR_CODE, $this->unexpected_error_message(), $errors, 500);
        }

        if (WC_Wishlists_Wishlist_Item_Collection::add_item($wishlist_id, $product_id, 1, $variation_id, $variations)) {
            return $this->return_success_response(true);
        } else {
            return $this->return_error_response(Stacks_WC_Api_Response_Service::UNEXPECTED_ERROR_CODE, $this->unexpected_error_message(), $this->unexpected_error_message(), 500);
        }
    }

    /**
     * Add simple product to wishlist 
     * @param int $wishlist_id
     * @param int $product_id
     * @return boolean
     */
    public function add_simple_product_to_wishlist(&$wishlist_id, &$product_id) {
        $quantity = 1;
        //Add to wishlist validation
        $passed_validation = apply_filters('woocommerce_add_to_wishlist_validation', apply_filters('woocommerce_add_to_cart_validation', true, $product_id, $quantity), $product_id, $quantity);

        if ($passed_validation) {
            //Add the product to the wishlist
            if (WC_Wishlists_Wishlist_Item_Collection::add_item($wishlist_id, $product_id, $quantity)) {
                return $this->return_success_response(true);
            }
            return $this->return_error_response(Stacks_WC_Api_Response_Service::UNEXPECTED_ERROR_CODE, $this->unexpected_error_message(), $this->unexpected_error_message(), 500);
        } else {
            return $this->return_error_out_of_stock();
        }
    }


    /**
     * Edit wishlist
     * @param object $request
     * @return boolean
     */
    public function delete_wishlist_product($request) {
        $this->map_request_params($request->get_params());

        $wishlist_id        = $this->get_request_param_int('wishlist_id');
        $hash_key           = $this->get_request_param('hash_key');
        $validation_result  = $this->validation_middleware($wishlist_id);

        if ($validation_result !== true) {
            return $validation_result;
        }

        $result = WC_Wishlists_Wishlist_Item_Collection::remove_item($wishlist_id, $hash_key);

        if ($result) {
            return $this->return_success_response(true);
        } else {
            return $this->return_error_response(Stacks_WC_Api_Response_Service::UNEXPECTED_ERROR_CODE, $this->unexpected_error_message(), array(), 500);
        }
    }


    /**
     * Options to pass to limit the response 
     * 
     * @return array
     */
    public function get_collection_params() {
        $params = parent::get_collection_params();

        $params['wishlist_id'] = array(
            'description'       => __('Wishlist ID', 'plates'),
            'type'              => 'integer',
            'required'          => true,
            'sanitize_callback' => 'sanitize_text_field',
            'validate_callback' => array($this, 'validate_integer')
        );

        $params['hash_key'] = array(
            'description'       => __('Product ID', 'plates'),
            'type'              => 'string',
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
    public function get_collection_params_add() {
        $params = $this->get_collection_params();

        unset($params['hash_key']);

        $params['product_id'] = array(
            'description'       => __('Product ID', 'plates'),
            'type'              => 'integer',
            'required'          => true,
            'sanitize_callback' => 'sanitize_text_field',
            'validate_callback' => array($this, 'validate_integer')
        );

        return $params;
    }
}
