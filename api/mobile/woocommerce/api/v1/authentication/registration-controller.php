<?php

/**
 * Controller Responsible for Registering User
 * @Route("/register")
 * @Method("/POST")
 */
class Stacks_RegistrationController extends Stacks_RegisterationLoginAbstractController {
    /**
     * Route base.
     *
     * @var string
     */
    protected $rest_base = 'register';

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
        'facebook_access_token' => 'access_token',
        'type'                  => 'type',
        'email'                 => 'email',
        'first_name'            => 'first_name',
        'last_name'             => 'last_name',
        'phone'                 => 'phone',
        'password'              => 'password',
        'device_id'             => 'device_id',
        'device_type'        => 'device_type',
    ];

    /**
     * @var Stacks_RegistrationModel 
     */
    protected $model = null;


    public function register_routes() {
        register_rest_route($this->get_api_endpoint(), $this->rest_base, array( // V3
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array($this, 'register_user'),
                'permission_callback' => array($this, 'get_items_permissions_check'),
                'args'                => $this->get_collection_params(),
            ),
            'schema' => array($this, 'get_public_item_schema'),
        ));
        register_rest_route($this->get_api_endpoint(), $this->rest_base, array( // V3
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'register_user'),
                'permission_callback' => array($this, 'get_items_permissions_check'),
                'args'                => $this->get_collection_params(),
            ),
            'schema' => array($this, 'get_public_item_schema'),
        ));

        register_rest_route($this->get_api_endpoint(), 'register_phone', array( // V3
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'register_phone_number'),
                // 'permission_callback' => array($this, 'get_items_permissions_check'),
                'args'                => $this->get_collection_params(),
            ),
            'schema' => array($this, 'get_public_item_schema'),
        ));
    }

    /**
     * Get Registration model instance providing it the registration provider instance 
     * @return \Stacks_CategoriesModel
     */
    public function get_model_instance() {
        if (is_null($this->model)) {
            $this->model = new Stacks_RegistrationModel($this->get_registeration_provider_object());
        }
        return $this->model;
    }

    /**
     * Get Registration Provider object according to type 
     * @return \FacebookRegisterationProvider|\ManualRegisterationProvider
     */
    protected function get_registeration_provider_object() {
        switch ($this->active_method) {
            case 'facebook':
                return new Stacks_FacebookRegisterationProvider();
                break;
            case 'manual':
                return new Stacks_ManualRegisterationProvider();
                break;
        }
    }

    /**
     * user already exists error code 
     * @return string
     */
    public function user_already_exists_code() {
        return 'user_already_exists';
    }


    /**
     * Main Function Respond to request 
     * @Route("/register")
     * @Method("POST")
     * @return array
     */
    public function register_user($request) {
        $this->map_request_params($request->get_params());

        /** 1- type exists and we validate it exists **/
        $this->validate_type();
        $this->validate_required_parameters_exists();

        if ($this->has_errors()) {
            return $this->return_invalid_parameter_response();
        }

        $this->validate_device_settings();

        if ($this->has_errors()) {
            return $this->return_invalid_device_type_response();
        }

        // contact model and set parameters and get array to persist 
        $result = $this->get_model_instance()->set_params($this->data)->collect_parameters();

        // errors appeared from thrid party or wordpress 
        if (!$result['success']) {
            return $this->return_fetch_parameters_error_response($result['errors']);
        }

        // Are there any users exist with the same email 
        if ($this->is_there_users_exists_with_the_same_email($result['params']['user_email'])) {
            return $this->return_error_response(
                Stacks_WC_Api_Response_Service::EMAIL_ALREADY_REGISTERED_CODE,
                $this->get_message('email_already_registered'),
                array($this->get_message('email_already_registered')),
                Stacks_WC_Api_Response_Service::CLIENT_ERROR_STATUS_CODE
            );
        }

        $user_registered = $this->model->persist($result['params']);

        // error happened while registering user 
        if (!$user_registered['success']) {
            return $this->return_error_response(Stacks_WC_Api_Response_Service::UNEXPECTED_ERROR_CODE, $this->unexpected_error_message(), $user_registered['errors'], 500);
        }

        $user_id = $user_registered['user_id'];

        // save user meta 
        update_user_meta($user_id, 'billing_phone', $result['params']['phone']);

        // set current user to inform all other filters and plugins that there is a user working 
        $user = new WP_User($user_id);
        wp_set_current_user($user->ID, $user->user_login);

        // let other plugins hook and edit returned array
        $returned_data = apply_filters('_stacks_api_before_sending_register_success', [], $user->ID);

        $this->update_success_return_message($this->get_message('registeration_successfull'));

        // every thing okay 
        return $this->return_success_response($returned_data);
    }

    public function register_phone_number( $request ) {
        $this->map_request_params($request->get_params());

        $password = sanitize_text_field( $request->get_param( 'password' ) );
        $phone = sanitize_text_field( $request->get_param( 'phone' ) );
    
        if ( empty( $password ) || empty( $phone ) ) {
            return $this->return_error_response('registration_failed',  __( 'Please fill all required fields.' ),array( 'status' => 400 ));
        }

        /** 1- type exists and we validate it exists **/
        $this->validate_type();
        $this->validate_required_parameters_exists('phone');

        if ($this->has_errors()) {
            return $this->return_invalid_parameter_response();
        }

        $this->validate_device_settings();

        if ($this->has_errors()) {
            return $this->return_invalid_device_type_response();
        }

        // contact model and set parameters and get array to persist 
        $result = $this->get_model_instance()->set_params($this->data)->collect_parameters();

        // errors appeared from thrid party or wordpress 
        if (!$result['success']) {
            return $this->return_fetch_parameters_error_response($result['errors']);
        }

        // Check if the phone number is already registered
        $user_by_phone = get_users( array(
            'meta_key' => 'shipping_phone',
            'meta_value' => $phone,
            'fields' => 'ID',
        ) );
        if ( ! empty( $user_by_phone ) ) {
            return $this->return_error_response('registration_failed',  __( 'This phone number is already registered.' ),array( 'status' => 400 ));
        }
        $result['params']['user_login'] = $phone;
        $user_registered = $this->model->persist($result['params']);

        // error happened while registering user 
        if (!$user_registered['success']) {
            return $this->return_error_response(Stacks_WC_Api_Response_Service::UNEXPECTED_ERROR_CODE, $this->unexpected_error_message(), $user_registered['errors'], 500);
        }
        $user_id = $user_registered['user_id'];
        update_user_meta( $user_id, 'shipping_phone', $phone );
        
        // save user meta 
        update_user_meta($user_id, 'billing_phone', $phone);
        
    
        $user = new WP_User($user_id);
        wp_set_current_user($user->ID, $user->user_login);

        // let other plugins hook and edit returned array
        $returned_data = apply_filters('_stacks_api_before_sending_register_success', [], $user->ID);

        $this->update_success_return_message($this->get_message('registeration_successfull'));

        // every thing okay 
        return $this->return_success_response($returned_data);
    }

    /**
     * Save User meta 
     * @param int $user_id
     * @param string $phone
     * @return boolean
     */
    public function save_useRegistrationControllerr_meta($user_id, $phone) {
        $meta = array(
            'type'            => $this->active_method,
            'phone'            => $phone,

            // for information later about mobile users and site users we will add this meta
            'registeration_method'    => 'mobile',
        );

        // saving meta 
        $this->model->persist_user_meta($user_id, $meta);

        // let plugins hook here and user devices service to save device id and device type
        do_action('_stacks_api_user_registered', $user_id, $this->get_request_param('device_type'), $this->get_request_param('device_id'));
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
            'required'          => true,
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'validate_callback' => 'rest_validate_request_arg',
        );
        $params['facebook_access_token'] = array(
            'description'       => __('facebook access token if the registration type is equal facebook and no facebook_access_token provided expect 403 error', 'plates'),
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
        $params['first_name'] = array(
            'description'       => __('User First Name', 'plates'),
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'validate_callback' => 'rest_validate_request_arg',
        );
        $params['last_name'] = array(
            'description'       => __('User Last Name', 'plates'),
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'validate_callback' => 'rest_validate_request_arg',
        );
        $params['phone'] = array(
            'description'       => __('User Phone', 'plates'),
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
