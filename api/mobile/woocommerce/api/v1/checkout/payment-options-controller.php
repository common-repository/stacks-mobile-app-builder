<?php

class Stacks_PaymentOptionsController extends Stacks_AbstractController {

    /**
     * Route base.
     * @var string
     */
    protected $rest_api = 'payment_options';

    /**
     * @inherit_doc
     */
    protected $allowed_params = [];

    public function register_routes() {
        register_rest_route($this->get_api_endpoint(), $this->rest_api, array( // V3
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_payment_options'),
                'permission_callback' => array($this, 'get_user_items_permission'),
                'args'                => $this->get_collection_params()
            ),
            'schema' => array($this, 'get_public_item_schema'),
        ));
    }

    /**
     * Get Payment Options
     * 
     * @param object $request
     * 
     * @return array
     */
    public function get_payment_options($request) {
        $this->map_request_params($request->get_params());

        $payment_options = wc()->payment_gateways()->get_available_payment_gateways();
        $allowed_payment_options = ['cod', 'cheque', 'bacs', 'paypal', 'ppcp-gateway'];

        foreach ($payment_options as $payment_option => $settings) {
            // if (!in_array($payment_option, $allowed_payment_options) || $settings->enabled === 'no') {
                // unset($payment_options[$payment_option]);
            // } else {
                $payment_options[$payment_option] =  $this->format_payment_option($this->add_payment_additional_parameters($payment_option, $settings));
            // }
        }

        return $this->return_success_response($payment_options);
    }

    /**
     * Format Payment Gateway arameters
     * 
     * @param WC_Payment_Gateway $payment_option
     */
    public function format_payment_option($payment_option) {
        $settings = [];
        $settings['id'] = $payment_option->id;
        $settings['title'] = __($payment_option->title, 'woocommerce');
        $settings['description'] = __($payment_option->description, 'woocommerce');

        if ($payment_option->id == 'paypal' || $payment_option->id == 'ppcp-gateway') {
            $settings['paypal_app_settings'] = $payment_option->paypal_app_settings;
            $settings['settings']['paymentaction'] = $payment_option->get_option('paymentaction');
        }

        return $settings;
    }

    /**
     * add additional parameters to settings if required 
     * 
     * @param string $payment_option_name
     * @param obj $settings
     * 
     * @return array
     */
    public function add_payment_additional_parameters($payment_option_name, $settings) {
        switch ($payment_option_name) {
            case 'paypal':
            case 'ppcp-gateway':
                $app_settings = [];
                $app_settings['client_id'] = Stacks_AppSettings::get_paypal_client_id();
                $app_settings['env'] = Stacks_AppSettings::get_paypal_env();
                $app_settings['status'] = _stacks_paypal_app_settings_complete();
                $app_settings['currency'] = get_woocommerce_currency();
                $app_settings['currency_symbol'] = get_woocommerce_currency_symbol();
                $settings->paypal_app_settings = $app_settings;

                break;
        }
        return $settings;
    }


    /**
     * Options to pass to limit the response 
     * 
     * @return array
     */
    public function get_collection_params() {
        $params = array();

        return $params;
    }
}
