<?php

/**
 * Controller Responsible for getting orders,view,cancel
 * @Route("/user/{id}/orders")
 * @Route("/user/{id}/orders/{id}")
 * @Method("GET")
 * @Method("PUT")
 */
class Stacks_UserPointsController extends Stacks_AbstractController {
    /**
     * Route base.
     * @var string
     */
    protected $rest_api = '/user/(?P<id>[\d]+)/points';

    /**
     * @var string 
     */
    protected $type = 'users';

    /**
     * @inherit_doc
     */
    protected $allowed_params = [
        'id' => 'user_id'
    ];

    public function register_routes() {
        register_rest_route($this->get_api_endpoint(), $this->rest_api, array( // V3
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_user_points'),
                'permission_callback' => array($this, 'get_user_items_permission'),
                'args'                => $this->get_collection_params()
            ),
            'schema' => array($this, 'get_public_item_schema'),
        ));
    }

    /**
     * get user orders
     * @param object $request
     * @return array
     */
    public function get_user_points($request) {
        $this->map_request_parameters($request);

        if (!stacks_is_points_plugin_activated()) {
            return $this->return_addon_not_activated_response();
        }

        $user_id = get_current_user_id();

        $user_points = WC_Points_Rewards_Manager::get_users_points($user_id);

        $log = WC_Points_Rewards_Points_Log::get_points_log_entries(array('user' => $user_id));

        return $this->return_success_response(array('points' => $user_points, 'log' => StacksWoocommerceDataFormating::format_log(array_reverse($log))));
    }

    /**
     * Options to pass to limit the response 
     * 
     * @return string
     */
    public function get_collection_params() {
        $params = array();

        $params['id'] = array(
            'description'       => __('user ID', 'plates'),
            'type'              => 'integer',
            'required'          => true,
            'sanitize_callback' => 'sanitize_text_field',
            'validate_callback' => array($this, 'validate_integer')
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
