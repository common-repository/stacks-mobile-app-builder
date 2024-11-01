<?php

/**
 * Controller Responsible for Registering User
 */
class Stacks_ForgotPasswordController extends Stacks_AbstractController {

    /**
     * Route base.
     * 
     * @var string 
     */
    protected $rest_base = 'forgot-password';

    /**
     * Route for activate new password
     * 
     * @var string 
     */
    protected $rest_activation = 'activate_new_password';

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
        'email' => 'email',
        'new_password' => 'new_password',
        'secret' => 'secret'
    ];

    /**
     * @var string
     */
    protected $active_validation_type = null;

    public function register_routes() {
        register_rest_route($this->get_api_endpoint(), $this->rest_base, [ // V3
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'retrieve_user_password'],
                'permission_callback' => [$this, 'get_items_permissions_check'],
                'args' => $this->get_collection_params(),
            ],
            'schema' => [$this, 'get_public_item_schema'],
        ]);

        register_rest_route($this->get_api_endpoint(), $this->rest_base, [ // V3
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'retrieve_user_password'],
                'permission_callback' => [$this, 'get_items_permissions_check'],
                'args' => $this->get_collection_params(),
            ],
            'schema' => [$this, 'get_public_item_schema'],
        ]);

        register_rest_route($this->get_api_endpoint(), $this->rest_activation, [ // V3
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'activate_user_new_password'],
                'args' => $this->get_activate_new_password_params(),
            ],
            'schema' => [$this, 'get_public_item_schema'],
        ]);
    }

    /**
     * activate new password 
     * 
     * @param WP_Rest_Request $request
     * @return array
     */
    public function activate_user_new_password($request) {
        $this->map_request_params($request->get_params());

        $hash = $this->get_request_param('secret');
        $record = NewPasswordsService::is_hash_exists($hash);
        $success = 'fail';

        if ($record && is_array($record)) {
            $expired = NewPasswordsService::is_hash_expired($hash);

            if (!$expired) {
                NewPasswordsService::acrivate_record($record);

                $success = 'success';
            }
        }

        $url = add_query_arg('change_pass_res', $success, get_permalink(get_option('woocommerce_myaccount_page_id')));

        wp_safe_redirect($url);

        die;
    }

    /**
     * Main Function Respond to request 
     * 
     * @param WP_Rest_Request $request
     * @return array
     */
    public function retrieve_user_password($request) {
        $this->map_request_params($request->get_params());

        // determine the type of validation 
        $validation_parameter = $this->get_request_param('email');

        $user = $this->is_mail_or_phone($validation_parameter)->is_user_exists_with_validation_type($validation_parameter);

        if (!$user) {
            return $this->return_forgot_password_no_users_found();
        }

        $new_password = NewPasswordsService::create_new_password_record($user->ID, $this->get_request_param('new_password'));

        $mail_params = [
            "link" => $this->get_activation_link($new_password['hash']),
            "email" => $user->user_email,
            "activate_new_pass_data" => $new_password,
            "user" => $user
        ];

        $Mailing_Service = new MailingService();

        $message = $Mailing_Service->get_forgot_password_message_template($mail_params);

        $res = $Mailing_Service->set_subject($this->get_change_password_subject())->set_message($message)->set_to($user->user_email)->sendMailToEmail();

        if (!$res) {
            return $this->return_forgot_password_couldnot_send_email();
        }

        return $this->return_success_response($res, $this->get_message('forgot_password_activate_mail_sent'));
    }

    /**
     * request change password subject
     * 
     * @return string
     */
    protected function get_change_password_subject() {
        return $this->get_message('change_password_subject');
    }

    /**
     * get activation URL
     * 
     * @param string $secret
     * @return string
     */
    public function get_activation_link($secret) {
        $url = trailingslashit(get_site_url()) . 'wp-json/' . trailingslashit($this->get_api_endpoint()) . $this->rest_activation;

        return add_query_arg('secret', $secret, $url);
    }

    /**
     * check if validation parameter is phone or email and get user depending on that 
     * 
     * @param sting $validation_parameter
     * @return object|false
     */
    public function is_user_exists_with_validation_type($validation_parameter) {
        switch ($this->active_validation_type) {
            case 'phone':
                return $this->is_user_exists_with_this_phone($validation_parameter);
            case 'email':
                return $this->is_user_exists_with_this_email($validation_parameter);
        }
    }

    /**
     * check if user exists with this phone 
     * 
     * @param string $validation_parameter
     * @return object|boolean
     */
    private function is_user_exists_with_this_phone($validation_parameter) {
        $users = get_users([
            'meta_key' => 'phone',
            'meta_value' => $validation_parameter,
            'meta_compare' => '='
        ]);

        return !empty($users) ? $users[0] : false;
    }

    /**
     * check if user exists with this email
     * 
     * @param string $validation_parameter
     * @return array
     */
    private function is_user_exists_with_this_email($validation_parameter) {
        $users = get_user_by('email', $validation_parameter);

        return $users;
    }

    /**
     * check if validation type is phone or email 
     * 
     * @param string $email
     * @return $this
     */
    public function is_mail_or_phone($email) {
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->active_validation_type = 'email';
        } else {
            $this->active_validation_type = 'phone';
        }
        return $this;
    }

    /**
     * Options to Guide api user 
     * 
     * @return string
     */
    public function get_activate_new_password_params() {
        $params = array();

        $params['secret'] = array(
            'description' => 'The secret parameter should be passed',
            'type' => 'string',
            'required' => true,
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
    public function get_collection_params() {
        $params = array();

        $params['email'] = array(
            'description' => 'This parameter called email but it can receive email or phone',
            'type' => 'string',
            'required' => true,
            'sanitize_callback' => 'sanitize_text_field',
            'validate_callback' => 'rest_validate_request_arg',
        );
        $params['new_password'] = array(
            'description' => __('The new password you would like to activate once the user hit the link', 'plates'),
            'type' => 'string',
            'required' => true,
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
            '$schema' => 'http://json-schema.org/draft-04/schema#',
            'title' => $this->type,
            'type' => 'object',
            'properties' => array(
                'id' => array(
                    'description' => esc_html__('Unique identifier for the object.', 'plates'),
                    'type' => 'integer',
                    'context' => array('view'),
                    'readonly' => true,
                )
            ),
        );

        return $schema;
    }
}
