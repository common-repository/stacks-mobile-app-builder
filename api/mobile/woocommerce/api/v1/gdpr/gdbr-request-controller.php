<?php

class Stacks_GDPR_Request_Controller extends Stacks_AbstractController {

    /**
     * Route base.
     * @var string
     */
    protected $rest_api = '/gdpr/';


    public function register_routes() {
        register_rest_route($this->get_api_endpoint(), $this->rest_api . 'erase', array( // V3
            array(
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => array($this, 'gdpr_remove_request'),
                'permission_callback' => array($this, 'get_user_items_permission'),
                'args'                => $this->get_collection_params()
            ),
            'schema' => array($this, 'get_public_item_schema'),
        ));
        register_rest_route($this->get_api_endpoint(), $this->rest_api . 'export', array( // V3
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'gdpr_export_request'),
                'permission_callback' => array($this, 'get_user_items_permission'),
                'args'                => $this->get_collection_params()
            ),
            'schema' => array($this, 'get_public_item_schema'),
        ));
    }

    /**
     * Gdbr remove request handler
     * 
     * @param WP_Rest_Request $request
     * @return array
     */
    public function gdpr_remove_request($request) {
        return $this->process_gdpr_request($request, PlatesGDPR::ERASE_PERSONAL_DATA_OPERATION_KEY);
    }

    /**
     * Gdbr export request handler
     * 
     * @param WP_Rest_Request $request
     * @return array
     */
    public function gdpr_export_request($request) {
        return $this->process_gdpr_request($request, PlatesGDPR::EXPORT_PERSONAL_DATA_OPERATION_KEY);
    }

    /**
     * Start request operation whether it's export or erase 
     * 
     * @param WP_REST_Request $request
     * @param string $operation
     * @return array
     */
    private function process_gdpr_request(WP_REST_Request $request, string $operation) {
        $this->map_request_parameters($request);

        $current_user_id = get_current_user_id();

        $wp_user = new WP_User($current_user_id);

        $gdpr_instance = new PlatesGDPR($wp_user->user_email, $operation);

        // validate 
        $validation_result = $gdpr_instance->validate();

        if (is_wp_error($validation_result)) {
            return $this->generate_error_response($validation_result);
        }

        $request_result = $gdpr_instance->proceed();

        if (is_wp_error($request_result)) {
            return $this->generate_error_response($request_result);
        }

        $this->populate_success_response('gdpr_request_waiting_confirmation');

        return $this->return_success_response($request_result);
    }

    /**
     * Change Success Message to be another code
     * 
     * @param string $code
     */
    public function populate_success_response($code) {
        $success_callback = function ($message) use ($code) {
            return Translatable_Strings::get_code_message($code);
        };
        add_filter('stacks_woocommerce_api_message', $success_callback);
    }

    /**
     * generate error response 
     * 
     * @param WP_Error $wp_error
     * @return array
     */
    private function generate_error_response(WP_Error $wp_error) {
        return $this->return_error_response(
            $wp_error->get_error_code(),
            $wp_error->get_error_message(),
            [$wp_error->get_error_message()],
            PlatesGDPR::get_error_status_code($wp_error->get_error_code())
        );
    }

    /**
     * Options to pass to limit the response 
     * 
     * @return array
     */
    public function get_collection_params() {
        return [];
    }
}
