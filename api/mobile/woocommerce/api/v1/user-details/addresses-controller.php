<?php

/**
 * Controller Responsible for All around user addresses management
 */
class Stacks_AddressesController extends Stacks_AbstractController {
    /**
     * Route base.
     *
     * @var string
     */
    protected $rest_api    = '/user/(?P<id>[\d]+)/addresses';

    /**
     * @var string 
     */
    protected $type = 'users';

    /**
     * @inherit_doc 
     */
    protected $allowed_params = [
        'first_name'        => 'first_name',
        'last_name'        => 'last_name',
        'company'        => 'company',
        'email'            => 'email',
        'phone'            => 'phone',
        'country'        => 'country',
        'city'            => 'city',
        'state'            => 'state',
        'postcode'        => 'postcode',
        'address_1'        => 'address_1',
        'address_2'        => 'address_2',
        'complete_name'        => 'complete_name',
    ];

    /**
     * @var Stacks_UserModel
     */
    protected $user_model = null;


    public function __construct() {
        $this->user_model = new Stacks_UserModel();
    }


    public function register_routes() {

        register_rest_route($this->get_api_endpoint(), $this->rest_api, array( // V3
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_user_address'),
                'permission_callback' => array($this, 'get_user_items_permission'),
                'args'                => $this->get_collection_params_get()
            ),
            'schema' => array($this, 'get_public_item_schema'),
        ));

        register_rest_route($this->get_api_endpoint(), $this->rest_api . '/billing', array( // V3
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_user_billing_address'),
                'permission_callback' => array($this, 'get_user_items_permission'),
                'args'                => $this->get_collection_params_get()
            ),
            array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array($this, 'update_user_billing_address'),
                'permission_callback' => array($this, 'get_user_items_permission'),
                'args'                => $this->get_collection_params_billing()
            ),
            'schema' => array($this, 'get_public_item_schema'),
        ));

        register_rest_route($this->get_api_endpoint(), $this->rest_api . '/billing_update', array( // V3
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'update_user_billing_address'),
                'permission_callback' => array($this, 'get_user_items_permission'),
                'args'                => $this->get_collection_params_billing()
            ),
            'schema' => array($this, 'get_public_item_schema'),
        ));

        register_rest_route($this->get_api_endpoint(), $this->rest_api . '/shipping', array( // V3
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_user_shipping_address'),
                'permission_callback' => array($this, 'get_user_items_permission'),
                'args'                => $this->get_collection_params_get()
            ),
            array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array($this, 'update_user_shipping_address'),
                'permission_callback' => array($this, 'get_user_items_permission'),
                'args'                => $this->get_collection_params_shipping()
            ),
            'schema' => array($this, 'get_public_item_schema'),
        ));
        register_rest_route($this->get_api_endpoint(), $this->rest_api . '/shipping_update', array( // V3
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'update_user_shipping_address'),
                'permission_callback' => array($this, 'get_user_items_permission'),
                'args'                => $this->get_collection_params_shipping()
            ),
            'schema' => array($this, 'get_public_item_schema'),
        ));
    }

    /**
     * Get submitted data
     * @return array
     */
    public function get_submitted_data() {
        $submiited_data = array();

        foreach ($this->allowed_params as $param) {
            $submiited_data[$param] = $this->get_request_param($param);
        }

        return $submiited_data;
    }

    /**
     * return prefixed elements
     * @param string $prefix
     * @param array $elements
     * @return array
     */
    public function return_complete_name($prefix, $elements) {
        $complete_name = $this->get_request_param('complete_name');

        if ($complete_name) {
            $array_values = array_values($elements);
            $updated_array_keys = array_map(function ($element) use ($prefix) {
                return $prefix . '_' . $element;
            }, array_keys($elements));
            return array_combine($updated_array_keys, $array_values);
        }

        return $elements;
    }

    /**
     * Get user addresses combined billing + shipping
     * @param object $request
     * @return array
     */
    public function get_user_address($request) {
        $this->map_request_params($request->get_params());

        return $this->return_success_response(
            [
                'billing'    => $this->return_complete_name('billing', $this->user_model->get_address('billing')),
                'shipping'    => $this->return_complete_name('shipping', $this->user_model->get_address('shipping'))
            ]
        );
    }

    /**
     * Main Function Respond to request 
     * @return array
     */
    public function get_user_billing_address($request) {
        $this->map_request_params($request->get_params());

        return $this->return_success_response($this->return_complete_name('billing', $this->user_model->get_address('billing')));
    }

    /**
     * Main Function Respond to request 
     * @return array
     */
    public function get_user_shipping_address($request) {
        $this->map_request_params($request->get_params());

        return $this->return_success_response($this->return_complete_name('shipping', $this->user_model->get_address('shipping')));
    }

    /**
     * update user billing address 
     * @param object $request
     * @return array
     */
    public function update_user_billing_address($request) {
        $this->map_request_params($request->get_params());

        return $this->return_success_response($this->user_model->update_address('billing', $this->get_submitted_data()));
    }

    /**
     * update user shipping address 
     * @param object $request
     * @return array
     */
    public function update_user_shipping_address($request) {
        $this->map_request_params($request->get_params());

        return $this->return_success_response($this->user_model->update_address('shipping', $this->get_submitted_data()));
    }

    /**
     * validate country
     * @param string $country
     * @param object $request
     * @param string $key
     */
    public function validate_country($country, $request, $key) {
        $countries_allowed = array_keys($this->user_model->get_woocommerce_allowed_countries());

        return in_array($country, $countries_allowed);
    }


    /**
     * Options to pass to limit the response 
     * 
     * @return string
     */
    public function get_collection_params_get() {
        $params = array();

        $params['complete_name'] = array(
            'description'       => __('If you would like to have the complete name of the meta by default false.', 'plates'),
            'type'              => 'integer',
            'required'          => false,
            'validate_callback' => array($this, 'validate_integer')
        );

        return $params;
    }

    /**
     * Options to pass to limit the response 
     * 
     * @return string
     */
    public function get_collection_params_shipping() {
        $params = array();

        $params['id'] = array(
            'description'       => __('Get user address by user id.', 'plates'),
            'type'              => 'integer',
            'required'          => true,
            'validate_callback' => array($this, 'validate_integer')
        );
        $params['first_name'] = array(
            'description'       => __('first Name', 'plates'),
            'type'              => 'string',
            'required'          => true,
            'sanitize_callback' => 'sanitize_text_field',
            'validate_callback' => 'rest_validate_request_arg',
        );
        $params['last_name'] = array(
            'description'       => __('lastName', 'plates'),
            'type'              => 'string',
            'required'          => true,
            'sanitize_callback' => 'sanitize_text_field',
            'validate_callback' => 'rest_validate_request_arg',
        );
        $params['company'] = array(
            'description'       => __('company Name', 'plates'),
            'type'              => 'string',
            'required'          => true,
            'sanitize_callback' => 'sanitize_text_field',
            'validate_callback' => 'rest_validate_request_arg',
        );
        $params['country'] = array(
            'description'       => __('country', 'plates'),
            'type'              => 'string',
            'required'          => true,
            'sanitize_callback' => 'sanitize_text_field',
            'validate_callback' => array($this, 'validate_country'),
        );
        $params['city'] = array(
            'description'       => __('city', 'plates'),
            'type'              => 'string',
            'required'          => true,
            'sanitize_callback' => 'sanitize_text_field',
            'validate_callback' => 'rest_validate_request_arg',
        );
        $params['state'] = array(
            'description'       => __('state', 'plates'),
            'type'              => 'string',
            'required'          => true,
            'sanitize_callback' => 'sanitize_text_field',
            'validate_callback' => 'rest_validate_request_arg',
        );
        $params['postcode'] = array(
            'description'       => __('postal code', 'plates'),
            'type'              => 'string',
            'required'          => true,
            'sanitize_callback' => 'sanitize_text_field',
            'validate_callback' => 'rest_validate_request_arg',
        );
        $params['address_1'] = array(
            'description'       => __('address Line 1', 'plates'),
            'type'              => 'string',
            'required'          => true,
            'sanitize_callback' => 'sanitize_text_field',
            'validate_callback' => 'rest_validate_request_arg',
        );
        $params['address_2'] = array(
            'description'       => __('address Line 2', 'plates'),
            'type'              => 'string',
            'required'          => true,
            'sanitize_callback' => 'sanitize_text_field',
            'validate_callback' => 'rest_validate_request_arg',
        );

        return $params;
    }

    /**
     * Options to pass to limit the response  
     * 
     * @return string
     */
    public function get_collection_params_billing() {
        $params = $this->get_collection_params_shipping();

        $params['email'] = array(
            'description'       => __('when submitting shipping email', 'plates'),
            'type'              => 'string',
            'required'          => true,
            'sanitize_callback' => 'sanitize_text_field',
            'validate_callback' => 'rest_validate_request_arg',
        );
        $params['phone'] = array(
            'description'       => __('phone', 'plates'),
            'type'              => 'string',
            'required'          => true,
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
