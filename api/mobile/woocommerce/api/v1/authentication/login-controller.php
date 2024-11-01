<?php

/**
 * Controller Responsible for Registering User
 * @Route("/login")
 * @Method("/GET")
 */
class Stacks_LoginController extends Stacks_RegisterationLoginAbstractController {
    /**
     * Route base.
     *
     * @var string
     */
    protected $rest_base = 'login';

    /**
     * Type of Taxonomy we are working on 
     * 
     * @var string 
     */
    protected $type = 'users';

    /**
     * @inherit_doc 
     */
    protected $allowed_params = [
        'type'                      => 'type',
        'password'                  => 'password',
        'email'                     => 'email',
        'facebook_access_token'     => 'access_token',
        'device_id'            => 'device_id',
        'device_type'            => 'device_type',
    ];

    /**
     * @var Stacks_RegistrationModel 
     */
    protected $model = null;

    /**
     * Login Providers Required parameters 
     * 
     * @var array 
     */
    protected $manual_required_parameters   = array('email', 'password');

    public function register_routes() {
        register_rest_route($this->get_api_endpoint(), $this->rest_base, array( // V3
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'login_user'),
                'permission_callback' => array($this, 'get_items_permissions_check'),
                'args'                => $this->get_collection_params(),
            ),
            'schema' => array($this, 'get_public_item_schema'),
        ));
    }

    /**
     * Get Registration model instance providing it the registration provider instance 
     * 
     * @return \Stacks_CategoriesModel
     */
    public function get_model_instance() {
        if (is_null($this->model)) {
            $this->model = new Stacks_LoginModel($this->get_login_provider_object());
        }
        return $this->model;
    }

    /**
     * Get Login Provider object according to type 
     * 
     * @return \FacebookLoginProvider|\ManualLoginProvider
     */
    protected function get_login_provider_object() {

        switch ($this->active_method) {
            case 'facebook':
                return new Stacks_FacebookLoginProvider();

            case 'manual':
                return new Stacks_ManualLoginProvider();

            default:
                throw new Exception('Login Method Not Supported');
        }
    }

    /**
     * Validate login parameters exists
     * 
     * @return void
     */
    public function validate_login_parameters_exists() {
        $shared = ['type'];
        $manual_params = ['password', 'email'];
        $facebook_params = ['access_token'];

        $type = $this->get_request_param('type');
        $parameters = [];

        if (!$type || !in_array($type, self::ALLOWED_PROVIDERS)) {
            $this->set_error($this->invalid_parameter_exception('type')->get_error_message());
            return;
        } elseif ($type == 'manual') {
            $parameters = array_merge($shared, $manual_params);
        } else {
            $parameters = array_merge($shared, $facebook_params);
        }

        // loop across parameters and check their existence
        foreach ($parameters as $parameter) {
            if (!$this->get_request_param($parameter)) {
                $this->set_error($this->missing_parameter_exception($parameter)->get_error_message());
            }
        }

        $this->active_method = $type;

        $this->validate_device_settings();
    }



    /**
     * Main Function Respond to request 
     * @Route("/register")
     * @Method("POST")
     * @return array
     */
    public function login_user($request) {
        $this->map_request_params($request->get_params());

        $this->validate_login_parameters_exists();

        if ($this->has_errors()) {
            return $this->return_invalid_parameter_response();
        }

        $result = $this->get_model_instance()->set_params($this->data)->login();

        $login_type = $this->get_request_param('type');

        // Invalid Credentials or errors appeared from thridparty or wordpress 
        if (!$result['success']) {
            if ($login_type === 'facebook') {
                // $message = Translatable_Strings::get_code_message( 'login_failed_fetch_data' );
            } else {
                $message = Translatable_Strings::get_code_message('invalid_credentials');
            }

            $error_code = Stacks_WC_Api_Response_Service::INVALID_CREDENTIALS_CODE;
            $error_status_code = Stacks_WC_Api_Response_Service::CLIENT_ERROR_STATUS_CODE;

            return $this->return_error_response($error_code, $message, $result['errors'], $error_status_code);
        }

        $user_id = $this->wp_login_user($result['user']);

        $returned_data = apply_filters('_stacks_api_before_sending_login_success', [], $user_id);

        // update returned message to customer 
        $this->update_success_return_message(Translatable_Strings::get_code_message('login_successfull'));

        return $this->return_success_response($returned_data);
    }

    /**
     * Login user to wordpress
     * 
     * @param WP_User $user
     */
    private function wp_login_user($user) {
        $user_id    = $user->ID;
        $user_login = $user->user_login;

        do_action('_stacks_api_user_logged_in', $user_id, $this->get_request_param('device_type'), $this->get_request_param('device_id'));

        wp_set_current_user($user_id, $user_login);

        return $user_id;
    }


    /**
     * Options to pass to limit the response 
     * 
     * @return string
     */
    public function get_collection_params() {
        $params = array();

        $params['type'] = array(
            'description'       => __('determine manual or facebook error with code 400 will appear if not provided', 'plates'),
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'validate_callback' => 'rest_validate_request_arg',
        );
        $params['facebook_access_token'] = array(
            'description'       => __('facebook access token if the login type is equal facebook and no facebook_access_token provided expect 403 error', 'plates'),
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'validate_callback' => 'rest_validate_request_arg',
        );
        $params['email'] = array(
            'description'       => __('User Email', 'plates'),
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'validate_callback' => 'rest_validate_request_arg',
        );
        $params['password'] = array(
            'description'       => __('User Password', 'plates'),
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'validate_callback' => 'rest_validate_request_arg',
        );
        $params['device_id'] = array(
            'description'            => __('Device ID', 'plates'),
            'type'                    => 'string',
            'sanitize_callback'    => 'sanitize_text_field',
            'validate_callback'    => 'rest_validate_request_arg',
        );
        $params['device_type'] = array(
            'description'       => __('Device Type', 'plates'),
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'validate_callback' => 'rest_validate_request_arg',
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
