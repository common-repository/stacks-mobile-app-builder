<?php

/**
 * Controller Responsible for Registering User
 * @Route("/user/(?P<id>[\d]+)/details")
 * @Method("/PUT","/GET")
 */
class Stacks_UserDataController extends Stacks_AbstractController {
    /**
     * Route base.
     * @var string
     */
    protected $rest_base = '/user/(?P<id>[\d]+)/details';
    protected $rest_base_update = '/user/(?P<id>[\d]+)/update_details';

    /**
     * @var string 
     */
    protected $type = 'users';

    /**
     * @inherit_doc
     */
    protected $allowed_params = [
        'firstName'     => 'first_name',
        'lastName'      => 'last_name',
        'email'         => 'email',
        'phone'         => 'phone'
    ];

    /**
     * @var WP_User
     */
    protected $user = null;

    public function register_routes() {
        register_rest_route($this->get_api_endpoint(), $this->rest_base, array( // V3
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_user_details'),
                'permission_callback' => array($this, 'get_user_items_permission'),
                'args'                => $this->get_collection_params_get()
            ),
            array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array($this, 'update_user_details'),
                'permission_callback' => array($this, 'get_user_items_permission'),
                'args'                => $this->get_collection_params_update()
            ),
            'schema' => array($this, 'get_public_item_schema'),
        ));
        register_rest_route($this->get_api_endpoint(), $this->rest_base_update, array( // V3
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'update_user_details'),
                'permission_callback' => array($this, 'get_user_items_permission'),
                'args'                => $this->get_collection_params_update()
            ),
            'schema' => array($this, 'get_public_item_schema'),
        ));
    }

    /**
     * Get user details request 
     * @return array
     */
    public function get_user_details($request) {

        // $current_user_id = $request->get_params();
        // $current_user_id = (int) $current_user_id["id"];

        $current_user_id = get_current_user_id();

        $this->user = new \WP_User($current_user_id);

        $data = array(
            'first_name'    => $this->user->first_name,
            'last_name'     => $this->user->last_name,
            'user_email'    => $this->user->user_email,
            'phone'         => get_user_meta($this->user->ID, 'billing_phone', true)
        );

        return $this->return_success_response($data);
    }

    /**
     * update user shipping address 
     * @param object $request
     * @return array
     */
    public function update_user_details($request) {
        $this->map_request_params($request->get_params());

        $this->user = new \WP_User(get_current_user_id());

        // if email changed && if users have the same email return error message
        $errors = array();

        if ($this->email_changed() && $this->users_have_same_email()) {
            $errors[] = __('another user has the same email', 'plates');
        }

        if ($this->phone_changed() && $this->users_have_same_phone()) {
            $errors[] = __('another user has the same phone', 'plates');
        }

        if (!empty($errors)) {
            return $this->return_error_response(Stacks_WC_Api_Response_Service::INVALID_PARAMETER_CODE, $this->invalid_parameter_message(), $errors);
        }

        $data = array(
            'ID'            => $this->user->ID,
            'first_name'    => $this->get_request_param('first_name'),
            'last_name'     => $this->get_request_param('last_name'),
            'user_email'    => $this->get_request_param('email')
        );

        $response = wp_update_user($data);

        if ($response instanceof \WP_Error) {
            return $this->return_error_response(Stacks_WC_Api_Response_Service::INVALID_PARAMETER_CODE, $this->invalid_parameter_message(), array($response->get_error_message()));
        }

        $this->update_meta();

        return $this->return_success_response(true);
    }

    /**
     * Update meta for user 
     */
    public function update_meta() {
        $meta = array('billing_phone');

        foreach ($meta as $m) {
            update_user_meta($this->user->ID, $m, $this->get_request_param($m));
        }
    }


    /**
     * validate users have same email
     * @return boolean
     */
    public function users_have_same_email() {
        $users = get_user_by('email', $this->get_request_param('email'));
        return $users ? true : false;
    }

    /**
     * validate email changed or not 
     * @return boolean
     */
    public function email_changed() {
        if ($this->user->user_email !== $this->get_request_param('email')) {
            return true;
        }
        return false;
    }

    /**
     * check if phone changed 
     * @return boolean
     */
    public function phone_changed() {
        $phone = get_user_meta($this->user->ID, 'billing_phone', true);

        return $phone !== $this->get_request_param('phone');
    }

    /**
     * check if other users have the same email
     * @return boolean
     */
    public function users_have_same_phone() {
        $args = array(

            'meta_key'     => 'phone',
            'meta_value'   => $this->get_request_param('phone'),
            'meta_compare' => '='
        );

        $users = get_users($args);

        return !empty($users);
    }


    /**
     * validate user password not empty 
     * @param string $param
     * @param object $request
     * @param string $key
     * @return boolean
     */
    public function validate_email($param, $request, $key) {
        if (!filter_var($param, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        return true;
    }

    /**
     * validates string not empty
     * @param string $param
     * @param object $request
     * @param string $key
     * @return boolean
     */
    public function validate_string($param, $request, $key) {
        return !$param == '';
    }

    /**
     * Options to pass to limit the response 
     * 
     * @return string
     */
    public function get_collection_params_get() {
        $params = array();

        return $params;
    }

    /**
     * Options to pass to limit the response 
     * 
     * @return string
     */
    public function get_collection_params_update() {
        $params = array();

        $params['firstName'] = array(
            'description'       => __('user first name', 'plates'),
            'type'              => 'string',
            'required'          => true,
            'sanitize_callback' => 'sanitize_text_field',
            'validate_callback' => array($this, 'validate_string')
        );

        $params['lastName'] = array(
            'description'       => __('user last name', 'plates'),
            'type'              => 'string',
            'required'          => true,
            'sanitize_callback' => 'sanitize_text_field',
            'validate_callback' => array($this, 'validate_string')
        );

        $params['email'] = array(
            'description'       => __('user email', 'plates'),
            'type'              => 'string',
            'required'          => true,
            'sanitize_callback' => 'sanitize_text_field',
            'validate_callback' => array($this, 'validate_email')
        );

        $params['phone'] = array(
            'description'       => __('user phone number', 'plates'),
            'type'              => 'string',
            'required'          => true,
            'sanitize_callback' => 'sanitize_text_field',
            'validate_callback' => array($this, 'validate_string')
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
