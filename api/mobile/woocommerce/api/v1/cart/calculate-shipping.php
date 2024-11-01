<?php

/**
 * Controller Responsible for Calculating Shipping
 * @Route("/calculate_shipping")
 * @Method("/GET")
 */
class Stacks_CalculateShipping extends Stacks_CartAbstractController {
    /**
     * Route base.
     *
     * @var string
     */
    protected $rest_base    = '/calculate_shipping';

    /**
     * @inherit_doc 
     */
    protected $allowed_params = array(
        'shipping_country'  => 'shipping_country',
        'shipping_state'    => 'shipping_state',
        'shipping_postcode' => 'shipping_postcode'
    );

    protected $allowed_countries = null;

    public function register_routes() {
        register_rest_route($this->get_api_endpoint(), $this->rest_base, array( // V3
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'calculate_shipping'),
                'permission_callback' => array($this, 'get_items_permissions_check'),
                'args'                => $this->get_collection_params()
            ),
            'schema' => array($this, 'get_public_item_schema')
        ));
    }

    /**
     * calculate shipping
     * @param object $request
     * @return array
     */
    public function calculate_shipping($request) {
        $this->map_request_params($request->get_params());

        $country   = wc_clean(wp_unslash($this->get_request_param('shipping_country')));
        $state     = wc_clean(wp_unslash($this->get_request_param('shipping_state')));
        $postcode  = apply_filters('woocommerce_shipping_calculator_enable_postcode', true) ? wc_clean(wp_unslash($this->get_request_param('shipping_postcode'))) : '';

        WC()->shipping->reset_shipping();

        // if postcode is not valid
        if ($postcode && !WC_Validation::is_postcode($postcode, $country)) {
            return $this->return_error_response(Stacks_WC_Api_Response_Service::INVALID_PARAMETER_CODE, $this->invalid_parameter_message(),  array($this->get_message('invalid_postal_code')),  Stacks_WC_Api_Response_Service::INVALID_PARAMETER_STATUS_CODE);
        } elseif ($postcode) {
            $postcode = wc_format_postcode($postcode, $country);
        }

        $shipping_methods = $this->get_zone_shipping_methods($country, $state, $postcode);

        // is empty shipping methods you need to mention that 
        if (empty($shipping_methods)) {
            $this->update_success_return_message($this->get_message('no_shipping_methods_associated'));
        } else {
            // if country is valid 
            if ($country) {
                WC()->customer->set_location($country, $state, $postcode, false);
                WC()->customer->set_shipping_location($country, $state, $postcode, false);
            } else {
                WC()->customer->set_billing_address_to_base();
                WC()->customer->set_shipping_address_to_base();
            }

            WC()->customer->set_calculated_shipping(true);
            WC()->customer->save();
            WC()->cart->calculate_totals();

            $this->update_success_return_message($this->get_message('success_retireve_shipping_methods'));
        }

        return $this->return_success_response([
            'data' => [
                'cart_details'      => $this->get_cart_details(),
                'shipping_methods'  => $shipping_methods
            ]
        ]);
    }


    /**
     * get allowed countries 
     * @return array
     */
    public function get_allowed_countries() {
        if (is_null($this->allowed_countries)) {
            $wc_countries = new WC_Countries();
            $this->allowed_countries = $wc_countries->get_allowed_countries();
        }
        return $this->allowed_countries;
    }

    /**
     * validate country 
     * @param string $value
     * @param object $request
     * @param string $name
     * @return boolean
     */
    public function validate_country($value, $request, $name) {
        if (!in_array($value, array_keys($this->get_allowed_countries()))) {
            return false;
        }
        return true;
    }

    /**
     * Options to pass to limit the response 
     * 
     * @return string
     */
    public function get_collection_params() {
        $params = array();

        $params['shipping_country'] = array(
            'description'       => __('shipping country', 'plates'),
            'type'              => 'string',
            'required'          => true,
            'sanitize_callback' => 'sanitize_text_field',
            'validate_callback' => array($this, 'validate_country')
        );

        $params['shipping_state'] = array(
            'description'       => __('shipping state', 'plates'),
            'type'              => 'string',
            'required'          => true,
            'sanitize_callback' => 'sanitize_text_field'
        );

        $params['shipping_postcode'] = array(
            'description'       => __('shipping postcode', 'plates'),
            'type'              => 'string',
            'required'          => true,
            'sanitize_callback' => 'sanitize_text_field'
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
