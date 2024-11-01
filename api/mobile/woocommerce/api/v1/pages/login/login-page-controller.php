<?php

class Stacks_LoginPageController extends Stacks_AbstractPagesController {

    protected $rest_api = '/pages/login';

    public function register_routes() {
        register_rest_route($this->get_api_endpoint(), $this->rest_api, array( // V3
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_login_data'),
                'permission_callback' => array($this, 'get_items_permissions_check'),
                'args'                => $this->get_collection_params_get()
            ),
            'schema' => array($this, 'get_public_item_schema'),
        ));
    }

    /**
     * Get Page Background Image
     * 
     * @param object $request
     * 
     * @return array
     */
    public function get_login_data($request) {
        return $this->return_success_response([
            'image' => Stacks_ContentSettings::get_login_signup_background(),
            'text'  => Stacks_ContentSettings::get_login_signup_text()
        ]);
    }
    /**
     * Get Page Background Image
     * 
     * @param object $request
     * 
     * @return array
     */
    public function get_login_data_v1_v2($request) {
        return $this->return_success_response([
            'image' => Stacks_ContentSettings::get_login_signup_background_v1_v2(),
            'text'  => Stacks_ContentSettings::get_login_signup_text_v1_v2()
        ]);
    }
}
