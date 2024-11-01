<?php

class Stacks_WishlistController extends Stacks_WishlistAbstractController {

    /**
     * Route base.
     * @var string
     */

    protected $rest_api = '/wishlist/(?P<wishlist_id>[\d]+)';
    protected $rest_api_edit = '/wishlist_edit/(?P<wishlist_id>[\d]+)';
    protected $rest_api_products = '/wishlist/(?P<wishlist_id>[\d]+)/products';
    protected $rest_api_add = '/wishlist';

    /**
     * @var string 
     */
    protected $type = 'wishlists';

    /**
     * @inherit_doc
     */
    protected $allowed_params = [
        'wishlist_id'       => 'wishlist_id',
        'title'             => 'title',
    ];

    /**
     * @var WP_User
     */
    protected $user = null;

    public function register_routes() {
        register_rest_route($this->get_api_endpoint(), $this->rest_api, array( // V3
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_wishlist'),
                'permission_callback' => array($this, 'get_user_items_permission'),
                'args'                => $this->get_collection_params()
            ),
            array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array($this, 'edit_wishlist'),
                'permission_callback' => array($this, 'get_user_items_permission'),
                'args'                => $this->get_collection_params_edit()
            ),
            array(
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => array($this, 'delete_wishlist'),
                'permission_callback' => array($this, 'get_user_items_permission'),
                'args'                => $this->get_collection_params()
            ),
            'schema' => array($this, 'get_public_item_schema'),
        ));

        register_rest_route($this->get_api_endpoint(), $this->rest_api_edit, array( // V3
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'edit_wishlist'),
                'permission_callback' => array($this, 'get_user_items_permission'),
                'args'                => $this->get_collection_params_edit()
            ),
            'schema' => array($this, 'get_public_item_schema'),
        ));

        register_rest_route($this->get_api_endpoint(), $this->rest_api_add, array( // V3
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array($this, 'create_wishlist'),
                'permission_callback' => array($this, 'get_user_items_permission'),
                'args'                => $this->get_collection_params_add()
            ),
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'create_wishlist'),
                'permission_callback' => array($this, 'get_user_items_permission'),
                'args'                => $this->get_collection_params_add()
            ),
            'schema' => array($this, 'get_public_item_schema'),
        ));

        register_rest_route($this->get_api_endpoint(), $this->rest_api_products, array( // V3
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_wishlist_products'),
                'permission_callback' => array($this, 'get_user_items_permission'),
                'args'                => $this->get_collection_params()
            ),
            'schema' => array($this, 'get_public_item_schema'),
        ));
    }

    /**
     * Get all wishlist products
     * @param object $request
     * @return array
     */
    public function get_wishlist_products($request) {
        $this->map_request_params($request->get_params());

        $wishlist_id = $this->get_request_param_int('wishlist_id');

        $validation_result = $this->validation_middleware($wishlist_id);

        if ($validation_result !== true) {
            return $validation_result;
        }

        $items = WC_Wishlists_Wishlist_Item_Collection::get_items($wishlist_id);

        return $this->return_success_response($this->organize_cart_contents($items));
    }


    /**
     * Get user wishlists 
     * 
     * @param object $request
     * @return array
     */
    public function get_wishlist($request) {
        $this->map_request_params($request->get_params());

        $validation_result = $this->validation_middleware($this->get_request_param('wishlist_id'));

        if ($validation_result !== true) {
            return $validation_result;
        }

        $wishlist = new WC_Wishlists_Wishlist($this->get_request_param('wishlist_id'));

        return $this->return_success_response($wishlist);
    }

    /**
     * Delete user wishlist
     * 
     * @param object $request
     * @return array
     */
    public function delete_wishlist($request) {
        $this->map_request_params($request->get_params());

        $validation_result = $this->validation_middleware($this->get_request_param('wishlist_id'));

        if ($validation_result !== true) {
            return $validation_result;
        }

        $wishlist_id = $this->get_request_param('wishlist_id');

        $wishlist = new WC_Wishlists_Wishlist($wishlist_id);

        if (!$wishlist->delete_list($wishlist_id)) {
            return $this->return_error_response(Stacks_WC_Api_Response_Service::UNEXPECTED_ERROR_CODE, $this->unexpected_error_message(), array($this->unexpected_error_message()), 500);
        }

        return $this->return_success_response(true);
    }

    /**
     * Edit wishlist
     * @param object $request
     * @return boolean
     */
    public function edit_wishlist($request) {
        $this->map_request_params($request->get_params());

        $validation_result = $this->validation_middleware($this->get_request_param('wishlist_id'));

        if ($validation_result !== true) {
            return $validation_result;
        }

        $id         = $this->get_request_param('wishlist_id');
        $title  = $this->get_request_param('title');

        $wishlist_data = array(
            'ID'           => $id,
            'post_title'   => $title
        );

        $result = wp_update_post($wishlist_data);

        if ($result) {
            return $this->return_success_response(true);
        } else {
            return $this->return_error_response(Stacks_WC_Api_Response_Service::UNEXPECTED_ERROR_CODE, $this->unexpected_error_message(), array(), 500);
        }
    }

    /**
     * Create wishlist
     * @param object $request
     * @return boolean
     */
    public function create_wishlist($request) {
        $this->map_request_params($request->get_params());

        if (!self::stacks_is_wishlist_plugin_activated()) {
            return $this->return_addon_not_activated_response();
        }

        $title = $this->get_request_param('title');

        $wishlist_id = WC_Wishlists_Wishlist::create_list($title);

        return $this->return_success_response($wishlist_id);
    }

    /**
     * Options to pass to limit the response 
     * 
     * @return string
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

        return $params;
    }

    /**
     * Options to pass to limit the response 
     * 
     * @return string
     */
    public function get_collection_params_edit() {
        $params = array_merge($this->get_collection_params(), $this->get_collection_params_add());

        return $params;
    }

    /**
     * Options to pass to limit the response 
     * 
     * @return string
     */
    public function get_collection_params_add() {
        $params = array();

        $params['title'] = array(
            'description'       => __('Wishlist Title', 'woocommerce'),
            'type'              => 'string',
            'required'          => true,
            'sanitize_callback' => 'sanitize_text_field',
            'validate_callback' => 'rest_validate_request_arg'
        );

        return $params;
    }
}
