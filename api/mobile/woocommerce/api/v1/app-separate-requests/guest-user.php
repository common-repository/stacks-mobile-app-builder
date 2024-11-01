<?php
class Stacks_GuestDeviceIDController extends Stacks_DeviceIDController {
    /**
     * Route base.
     *
     * @var string
     */
    protected $rest_api    = 'guest/device';

    /**
     * @var string 
     */
    protected $type = 'guests';

    protected $allowed_params = [
        'device_id' => 'device_id',
        'device_type' => 'device_type',
    ];

    public function register_routes() {
        register_rest_route($this->get_api_endpoint(), $this->rest_api, array( // V3
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array($this, 'add_deivce_id_to_guest'),
                'permission_callback' => array($this, 'get_user_items_permission'),
                'args'                => $this->get_collection_params_get()
            ),
            'schema' => array($this, 'get_public_item_schema'),
        ));
    }

    public function add_deivce_id_to_guest($request) {

        $this->map_request_parameters($request);

        // Save device_id
        $guests_devices = $GLOBALS['builder_api']->stacks_get_multisite_option('guests_devices');

        $guests_devices[] = array(
            'device_type' => strtolower($this->get_request_param('device_type')),
            'device_id' => $this->get_request_param('device_id')
        );
        $GLOBALS['builder_api']->stacks_update_multisite_options(get_current_blog_id(), 'guests_devices', $guests_devices);

        return $this->return_success_response(true);
    }

    /**
     * Get schema
     * 
     * @return array
     */
    public function get_collection_params() {
        $params = array();

        $params['device_id'] = array(
            'description'       => __('device id', 'plates'),
            'type'              => 'string',
            'required'          => true,
            'sanitize_callback' => 'sanitize_text_field'
        );
        $params['device_type'] = array(
            'description'       => __('device id', 'plates'),
            'type'              => 'string',
            'required'          => true,
            'sanitize_callback' => 'sanitize_text_field'
        );

        return $params;
    }
}
