<?php

class Stacks_get_checkout_page extends Stacks_AbstractController {

    /**
     * Route base.
     * @var string
     */
    protected $rest_api = 'get_checkout_page';

    /**
     * @inherit_doc
     */
    protected $allowed_params = [
        'id' => 'id'
    ];

    public function register_routes() {
        register_rest_route($this->get_api_endpoint(), $this->rest_api, array( // V3
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_checkout_page'),
                'permission_callback' => array($this, 'get_user_items_permission'),
                'args'                => $this->get_collection_params()
            ),
            'schema' => array($this, 'get_public_item_schema'),
        ));
    }

    /**
     * Get Payment Options
     * 
     * @param object $request
     * 
     * @return array
     */
    public function get_checkout_page($request) {
        $this->map_request_params($request->get_params());
        $order = new WC_Order($this->get_request_param('id'));
        $pay_now_url = esc_url( $order->get_checkout_payment_url() );
        return $this->return_success_response(html_entity_decode($pay_now_url));
    }

    public function create_order() {
        
    }

    /**
     * Options to pass to limit the response 
     * 
     * @return array
     */
    public function get_collection_params() {
        $params = array();

        return $params;
    }
}
