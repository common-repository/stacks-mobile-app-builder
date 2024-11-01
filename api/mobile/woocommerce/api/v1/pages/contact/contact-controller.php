<?php

class Stacks_ContactController extends Stacks_AbstractController {

    /**
     * @inherit_doc
     */
    protected $allowed_params = [
        'name'        => 'name',
        'mail'        => 'mail',
        'message'    => 'message'
    ];

    public $rest_api = 'contact';
    public $rest_api_submit = 'contact_submit';

    public function register_routes() {
        register_rest_route($this->get_api_endpoint(), $this->rest_api, [ // V3
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_contact_social_icons'),
                'permission_callback' => array($this, 'get_items_permissions_check'),
                'args'                => $this->get_collection_params_get()
            ],
            'schema' => array($this, 'get_public_item_schema'),
        ]);

        register_rest_route($this->get_api_endpoint(), $this->rest_api_submit, [ // V3
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array($this, 'submit_contact_form'),
                'permission_callback' => array($this, 'get_items_permissions_check'),
                'args'                => $this->get_collection_params_post()
            ],
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'submit_contact_form'),
                'permission_callback' => array($this, 'get_items_permissions_check'),
                'args'                => $this->get_collection_params_post()
            ],
            'schema' => array($this, 'get_public_item_schema'),
        ]);
    }


    /**
     * remove empty values from icons 
     * 
     * @param array $icons
     * 
     * @return array
     */
    public function filter_social_icons($icons) {
        foreach ($icons as $index => $icon) {
            if ($icon['value'] === '' || $icon['value'] === false) {
                unset($icons[$index]);
            }
        }

        return array_values($icons);
    }

    /**
     * get contact social icons 
     *  
     * @param object $request
     * 
     * @return array
     */
    public function get_contact_social_icons($request) {
        return $this->return_success_response(['social' => [], 'phone' => Stacks_ContentSettings::get_contact_us_phone()]);
    }


    /**
     * get amin contact email 
     * 
     * @return string
     */
    public function get_admin_contact_email() {
        $theme_options_contact_email = Stacks_ContentSettings::get_contact_us_email();

        if (!$theme_options_contact_email || '' == $theme_options_contact_email) {
            $email_service = new MailingService();
            return $email_service->get_admin_email();
        }

        return $theme_options_contact_email;
    }

    /**
     * return the contact us subject 
     * 
     * @return string
     */
    public function get_contact_us_subject() {
        return __('New Contact Form Submission', 'plates');
    }


    /**
     * submit contact form 
     * 
     * @param object $request
     * 
     * @return array
     */
    public function submit_contact_form($request) {
        $this->map_request_parameters($request);

        $user_details = [
            'name'        => $this->get_request_param('name'),
            'email'        => $this->get_request_param('mail'),
            'message'    => $this->get_request_param('message')
        ];

        $email_service = new MailingService();

        $res = $email_service
            ->set_to($this->get_admin_contact_email())
            ->set_subject($this->get_contact_us_subject())
            ->set_message($email_service->get_contact_us_message_template($user_details))
            ->sendMailToEmail();

        return $this->return_success_response($res);
    }


    /**
     * collection of parameters required before accepting request 
     * @return array
     */
    public function get_collection_params_post() {
        $params = array();

        $params['name'] = array(
            'description'       => __('User name', 'plates'),
            'type'              => 'string',
            'required'          => true,
            'sanitize_callback' => 'sanitize_text_field'
        );

        $params['mail'] = array(
            'description'       => __('User Email', 'plates'),
            'type'              => 'string',
            'required'          => true,
            'sanitize_callback' => 'sanitize_text_field'
        );

        $params['message'] = array(
            'description'       => __('Contact Us message', 'plates'),
            'type'              => 'string',
            'required'          => true,
            'sanitize_callback' => 'sanitize_text_field'
        );

        return $params;
    }

    public function get_collection_params_get() {
        return [];
    }
}
