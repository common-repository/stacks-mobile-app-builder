<?php

/**
 * Controller Responsible for Registering User
 * @Route("/change-password")
 * @Method("/PUT")
 */
class Stacks_ChangePasswordController extends Stacks_AbstractController {
    /**
     * Route base.
     * @var string
     */
    protected $rest_base = '/user/(?P<id>[\d]+)/change-password';

    /**
     * @var string 
     */
    protected $type = 'users';

    /**
     * @inherit_doc
     */
    protected $allowed_params = [
        'new_password'  => 'new_password',
        'old_password'  => 'old_password'
    ];

    public function register_routes() {
        register_rest_route($this->get_api_endpoint(), $this->rest_base, array( // V3
            array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array($this, 'update_user_password'),
                'permission_callback' => array($this, 'get_user_items_permission'),
                'args'                => $this->get_collection_params()
            ),
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'update_user_password'),
                'permission_callback' => array($this, 'get_user_items_permission'),
                'args'                => $this->get_collection_params()
            ),
            'schema' => array($this, 'get_public_item_schema'),
        ));
    }

    /**
     * update user shipping address 
     * @param object $request
     * @return array
     */
    public function update_user_password($request) {
        $this->map_request_params($request->get_params());

        $user_new_password = $this->get_request_param('new_password');
        $user_old_password = $this->get_request_param('old_password');

        if (!$this->validate_old_password($user_old_password)) {
            return $this->return_error_response(Stacks_WC_Api_Response_Service::INVALID_PARAMETER_CODE, __('user old password is wrong', 'plates'), array(__('user old password is wrong', 'plates')));
        }

        wp_set_password($user_new_password, get_current_user_id());

        return $this->return_success_response(true);
    }

    /**
     * validate user password not empty 
     * @param string $password
     * @param object $request
     * @param string $key
     * @return boolean
     */
    public function validate_password($password, $request, $key) {
        return $password == '' ? false : true;
    }

    /**
     * validate old password
     * @param string $password
     * @return boolean
     */
    public function validate_old_password($password) {
        $user = new \WP_User(get_current_user_id());

        if ($user && wp_check_password($password, $user->data->user_pass, $user->ID)) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * Options to pass to limit the response 
     * 
     * @return string
     */
    public function get_collection_params() {
        $params = array();

        $params['new_password'] = array(
            'description'       => __('user new password', 'plates'),
            'type'              => 'string',
            'required'          => true,
            'sanitize_callback' => 'sanitize_text_field',
            'validate_callback' => array($this, 'validate_password')
        );

        $params['old_password'] = array(
            'description'       => __('user old password', 'plates'),
            'type'              => 'string',
            'required'          => true,
            'sanitize_callback' => 'sanitize_text_field',
            'validate_callback' => array()
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
