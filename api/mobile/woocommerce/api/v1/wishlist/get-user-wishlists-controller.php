<?php

class Stacks_GetUserWishlists extends Stacks_WishlistAbstractController {
    /**
     * Route base.
     * @var string
     */
    protected $rest_api = '/user/(?P<id>[\d]+)/wishlist';

    /**
     * @var string 
     */
    protected $type = 'wishlists';

    /**
     * @inherit_doc
     */
    protected $allowed_params = [
        'id' => 'user_id'
    ];

    /**
     * @var WP_User
     */
    protected $user = null;

    public function register_routes() {
        register_rest_route($this->get_api_endpoint(), $this->rest_api, array( // V3
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_user_wishlists'),
                'permission_callback' => array($this, 'get_user_items_permission'),
                'args'                => $this->get_collection_params()
            ),
            'schema' => array($this, 'get_public_item_schema'),
        ));
    }

    /**
     * Get user wishlists 
     * @param object $request
     * @return type
     */
    public function get_user_wishlists($request) {
        $this->map_request_params($request->get_params());

        if (!self::stacks_is_wishlist_plugin_activated()) {
            return $this->return_addon_not_activated_response();
        }

        $current_user_id = get_current_user_id();

        if ($current_user_id) {
            delete_transient(sprintf('wc_wishlists_users_lists_%s', get_current_user_id()));
        }

        $wishlists = WC_Wishlists_User::get_wishlists();

        if (empty($wishlists)) {
            return $this->return_success_response(array());
        }

        return $this->return_success_response($wishlists);
    }


    /**
     * Options to pass to limit the response 
     * 
     * @return string
     */
    public function get_collection_params() {
        $params = parent::get_collection_params();

        $params['id'] = array(
            'description'       => __('user ID', 'plates'),
            'type'              => 'integer',
            'required'          => true,
            'sanitize_callback' => 'sanitize_text_field',
            'validate_callback' => array($this, 'validate_integer')
        );

        return $params;
    }
}
