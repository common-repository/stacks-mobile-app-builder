<?php

/**
 * Controller Responsible for Adding new Device id to User
 * @Route("/devices")
 * @Method("/GET")
 */
class Stacks_DeviceIDController extends Stacks_AbstractController {
    /**
     * Route base.
     *
     * @var string
     */
    protected $rest_api    = '/user/(?P<id>[\d]+)/devices';

    /**
     * @var string 
     */
    protected $type = 'users';

    /**
     * @inherit_doc 
     */
    protected $allowed_params = [
        'device_id'    => 'device_id',
        'device_type'    => 'device_type',
    ];

    public function register_routes() {
        register_rest_route($this->get_api_endpoint(), $this->rest_api, array( // V3
            array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array($this, 'add_new_deivce_id_to_user'),
                'permission_callback' => array($this, 'get_user_items_permission'),
                'args'                => $this->get_collection_params_get()
            ),
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'add_new_deivce_id_to_user'),
                'permission_callback' => array($this, 'get_user_items_permission'),
                'args'                => $this->get_collection_params_get()
            ),
            'schema' => array($this, 'get_public_item_schema'),
        ));
    }

    /**
     * update user device id
     * 
     * @param object $request
     * 
     * @return array
     */
    public function add_new_deivce_id_to_user($request) {
        $this->map_request_parameters($request);

        do_action('_stacks_api_user_logged_in', get_current_user_id(), strtolower($this->get_request_param('device_type')), $this->get_request_param('device_id'));

        return $this->return_success_response(true);
    }

    /**
     * validate device type 
     * 
     * @param string $param
     * @param object $request
     * @param string $key
     * 
     * @return boolean
     */
    public function validate_device_type($param, $request, $key) {
        if (!in_array(strtolower($param), ['ios', 'android'])) {
            return false;
        }
        return true;
    }


    /**
     * Options to pass to limit the response 
     * 
     * @return string
     */
    public function get_collection_params_get() {
        $params = array();

        $params['device_id'] = array(
            'description'       => __('add new device id you would like to give to user', 'plates'),
            'type'              => 'string',
            'required'          => true
        );

        $params['device_type'] = array(
            'description'       => __('device type must be either ios or android', 'plates'),
            'type'              => 'string',
            'required'          => true,
            'validate_callback' => array($this, 'validate_device_type')
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
