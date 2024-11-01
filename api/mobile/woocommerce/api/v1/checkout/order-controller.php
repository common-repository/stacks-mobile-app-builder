<?php

class Stacks_OrderController extends Stacks_AbstractController {

    /**
     * Route base.
     * @var string
     */
    protected $rest_api = '/orders';

    /**
     * @inherit_doc
     */
    protected $allowed_params = [
        'billing_first_name'        => 'billing_first_name',
        'billing_last_name'        => 'billing_last_name',
        'billing_company'        => 'billing_company',
        'billing_country'        => 'billing_country',
        'billing_address_1'        => 'billing_address_1',
        'billing_address_2'        => 'billing_address_2',
        'billing_city'            => 'billing_city',
        'billing_state'            => 'billing_state',
        'billing_postcode'        => 'billing_postcode',
        'billing_phone'            => 'billing_phone',
        'billing_email'            => 'billing_email',
        'shipping_first_name'        => 'shipping_first_name',
        'shipping_last_name'        => 'shipping_last_name',
        'shipping_company'        => 'shipping_company',
        'shipping_country'        => 'shipping_country',
        'shipping_address_1'        => 'shipping_address_1',
        'shipping_address_2'        => 'shipping_address_2',
        'shipping_city'            => 'shipping_city',
        'shipping_state'        => 'shipping_state',
        'shipping_postcode'        => 'shipping_postcode',
        'order_comments'        => 'order_comments',
        'shipping_method'        => 'shipping_method',
        'payment_method'        => 'payment_method',
        'ship_to_different_address'    => 'ship_to_different_address'
    ];

    /**
     * @var WP_User
     */
    protected $user = null;

    public function register_routes() {
        register_rest_route($this->get_api_endpoint(), $this->rest_api, array( // V3
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array($this, 'create_order'),
                'permission_callback' => array($this, 'get_items_permissions_check'),
                'args'                => $this->get_collection_params()
            ),
            'schema' => array($this, 'get_public_item_schema'),
        ));
        register_rest_route($this->get_api_endpoint(), $this->rest_api, array( // V3
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'create_order'),
                'permission_callback' => array($this, 'get_items_permissions_check'),
                'args'                => $this->get_collection_params()
            ),
            'schema' => array($this, 'get_public_item_schema'),
        ));
        register_rest_route($this->get_api_endpoint(), '/country_states', array( // V3
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_country_states'),
                //                    'permission_callback' => array( $this, 'get_items_permissions_check' ),
                //                    'args'                => $this->get_collection_params()
            ),
            'schema' => array($this, 'get_public_item_schema'),
        ));
    }

    /**
     * creates the order 
     * 
     * @return array
     */
    public function get_country_states($request) {

        $country_code = $request['country'];
        $wc_countries = new WC_Countries();
        $country_states = !empty( $wc_countries->get_states($country_code) ) ? $wc_countries->get_states($country_code) : array();
        return $country_states;
    }

    /**
     * creates the order 
     * 
     * @return array
     */
    public function create_order($request) {
        $request = apply_filters('_api_checkout_before_parameters_mapping', $request);

        $this->map_request_parameters($request);

        // System Valiation to allow it to send any kind of messages
        $proceed = apply_filters('_api_before_process_create_order', true, $this);

        if (is_wp_error($proceed)) {
            return $proceed;
        }

        $stacks_checkout_service = StacksCheckoutService::instance();

        $stacks_checkout_service->set_posted_data($this->get_posted_data());

        do_action('woocommerce_before_checkout_process');

        $errors = $stacks_checkout_service->start_initial_validation();

        if (!empty($errors)) {
            return $this->return_error_response(Stacks_WC_Api_Response_Service::UNEXPECTED_ERROR_CODE, $errors[0], $errors, 500);
        }

        $response = $stacks_checkout_service->start_checkout_order();

        if (!$response['success']) {
            $message = 'No shipping method has been selected. Please double check your address, or contact us if you need any help.';
            $new_message = __('Unfortunately, we don\'t ship to your location yet. Please Contact us if you have any problems', 'plates');


            if (in_array($message, $response['errors'])) {
                $key = array_search($message, $response['errors']);
                $response['errors'][$key] = $new_message;
            }

            return $this->return_error_response(Stacks_WC_Api_Response_Service::UNEXPECTED_ERROR_CODE, $response['errors'][0], $response['errors'], 500);
        } else {
            $order_id = $response['id'];
            $order    = wc_get_order($order_id);

            $data = array(
                'status'        => $order->get_status(),
                'status_name'    => wc_get_order_status_name($order->get_status()),
                'id'        => $order_id
            );

            // Add mobile_app_order meta
            update_post_meta($order_id, 'mobile_app_order', true);

            // add additional parameters to when points plugin is activated 
            if (stacks_is_points_plugin_activated()) {
                $data['points_earned'] = $this->get_earned_points($order_id);
                $data['user_total_points'] = WC_Points_Rewards_Manager::get_users_points(get_current_user_id());
            }


            if (function_exists('pll_set_post_language')) {
                pll_set_post_language($order_id, 'en_US');
            }

            do_action('stacks_api_after_submitting_order', $order_id);
            // Check if WooCommerce Subscriptions plugin is active
            if ( in_array( 'woocommerce-subscriptions/woocommerce-subscriptions.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
                $order = wc_get_order($order_id);

                // Get the products in the order
                $items = $order->get_items();

                foreach ( $items as $item ) {
                    // Check if the product is a simple subscription
                    if ( 'subscription' === $item->get_product()->get_type() ) {
                        // Get the subscription data from the product
                        $product = $item->get_product();
                        $subscription_data = array(
                            'order_id' => $order_id,
                            'status' => 'pending', // Set the subscription status
                            'billing_period' => WC_Subscriptions_Product::get_period( $product ),
                            'billing_interval' => WC_Subscriptions_Product::get_interval( $product ),
                            'start_date' => date("Y-m-d H:i:s"),
                            'price' => WC_Subscriptions_Product::get_price( $product ),
                            'payment_method' => $order->get_payment_method(),
                            'customer_id'      => $order->get_user_id(),
                        );
                    
                        // Create the subscription
                        $subscription = wcs_create_subscription( $subscription_data );
                        $subscription_id = $subscription->get_id();
                        $subscription->add_product( $product, $item->get_quantity());
                        $subscription->calculate_totals();
                        WC_Subscriptions_Manager::activate_subscriptions_for_order( $order );
                    
                        // Add the subscription to the order
                        $item->set_props( array( 'subscription_renewal' => $subscription_id ) );
                        $item->save();
                    }
                  }
            }

            return $this->return_success_response($data);
        }
    }

    /**
     * get points earned for creating order if found
     * 
     * @global WC_Points_Rewards $wc_points_rewards
     * @param int $order_id
     * 
     * @return array
     */
    protected function get_earned_points($order_id) {
        global $wc_points_rewards;

        $points = $this->get_points_earned_for_order_received($order_id);
        $total_points = WC_Points_Rewards_Manager::get_users_points(get_current_user_id());

        $message = get_option('wc_points_rewards_thank_you_message');

        if ($message && $points) {
            $message = str_replace('{points}', number_format_i18n($points), $message);
            $message = str_replace('{points_label}', $wc_points_rewards->get_points_label($points), $message);

            $message = str_replace('{total_points}', number_format_i18n($total_points), $message);
            $message = str_replace('{total_points_label}', $wc_points_rewards->get_points_label($total_points), $message);

            return ['status' => true, 'message' => $message, 'points' => $points, 'total_points' => $total_points];
        }

        return ['status' => false, 'message' => ''];
    }

    public function get_points_earned_for_order_received( $order_id ) {
		global $wc_points_rewards, $wpdb;

		$points = 0;
		$point_log = $wpdb->get_results( $wpdb->prepare( "SELECT points FROM {$wc_points_rewards->user_points_log_db_tablename} WHERE order_id = %d;", $order_id ) );

		if ( ! empty( $point_log ) && $point_log[0]->points > 0 ) {
			$points = $point_log[0]->points;
		} elseif ( ! empty( $point_log ) && isset( $point_log[1]->points ) && $point_log[1]->points > 0 ) {
			$points = $point_log[1]->points;
		}

		return $points;
	}

    /**
     * Gets Posted Data
     * 
     * @return array
     */
    protected function get_posted_data() {
        return apply_filters('sys_api_order_controller_get_posted_data', [
            'billing_address_1'            => $this->get_request_param('billing_address_1'),
            'billing_address_2'            => $this->get_request_param('billing_address_2'),
            'billing_city'                => $this->get_request_param('billing_city'),
            'billing_company'            => $this->get_request_param('billing_company'),
            'billing_country'            => $this->get_request_param('billing_country'),
            'billing_email'                => $this->get_request_param('billing_email'),
            'billing_first_name'            => $this->get_request_param('billing_first_name'),
            'billing_last_name'            => $this->get_request_param('billing_last_name') ? $this->get_request_param('billing_last_name') : null,
            'billing_phone'                => $this->get_request_param('billing_phone'),
            'billing_postcode'            => $this->get_request_param('billing_postcode')? $this->get_request_param('billing_postcode') : null,
            'billing_state'                => $this->get_request_param('billing_state'),
            'createaccount'                => 0,
            'order_comments'            => $this->get_request_param('order_comments'),
            'payment_method'            => $this->get_request_param('payment_method'),
            'ship_to_different_address'        => $this->get_request_param('ship_to_different_address'),
            'shipping_address_1'            => $this->get_request_param('shipping_address_1'),
            'shipping_address_2'            => $this->get_request_param('shipping_address_2'),
            'shipping_city'                => $this->get_request_param('shipping_city'),
            'shipping_company'            => $this->get_request_param('shipping_company'),
            'shipping_country'            => $this->get_request_param('shipping_country'),
            'shipping_first_name'            => $this->get_request_param('shipping_first_name'),
            'shipping_last_name'            => $this->get_request_param('shipping_last_name')? $this->get_request_param('shipping_last_name') : null,
            'shipping_method'            => [$this->get_request_param('shipping_method')],
            'shipping_postcode'            => $this->get_request_param('shipping_postcode')? $this->get_request_param('shipping_postcode') : null,
            'shipping_state'            => $this->get_request_param('shipping_state'),
            'terms'                    => 0,
            'woocommerce_checkout_update_totals'    => false
        ]);
    }

    /**
     * Options to pass to limit the response 
     * 
     * @return array
     */
    public function get_collection_params() {
        $params = array();

        $params['billing_first_name'] = array(
            'description'       => __('billing first name', 'plates'),
            'type'              => 'string',
            'required'          => true,
            'sanitize_callback' => 'sanitize_text_field'
        );

        $params['billing_last_name'] = array(
            'description'       => __('billing last name', 'plates'),
            'type'              => 'string',
            'required'          => false,
            'sanitize_callback' => 'sanitize_text_field'
        );

        $params['billing_company'] = array(
            'description'       => __('billing company', 'plates'),
            'type'              => 'string',
            'required'          => true,
            'sanitize_callback' => 'sanitize_text_field'
        );

        $params['billing_country'] = array(
            'description'       => __('billing country', 'plates'),
            'type'              => 'string',
            'required'          => true,
            'sanitize_callback' => 'sanitize_text_field'
        );

        $params['billing_address_1'] = array(
            'description'       => __('billing address 1', 'plates'),
            'type'              => 'string',
            'required'          => true,
            'sanitize_callback' => 'sanitize_text_field'
        );

        $params['billing_address_2'] = array(
            'description'       => __('billing address 2', 'plates'),
            'type'              => 'string',
            'required'          => false,
            'sanitize_callback' => 'sanitize_text_field'
        );

        $params['billing_city'] = array(
            'description'       => __('billing city', 'plates'),
            'type'              => 'string',
            'required'          => true,
            'sanitize_callback' => 'sanitize_text_field'
        );

        $params['billing_state'] = array(
            'description'       => __('billing state', 'plates'),
            'type'              => 'string',
            'required'          => true,
            'sanitize_callback' => 'sanitize_text_field'
        );

        $params['billing_postcode'] = array(
            'description'       => __('billing postcode', 'plates'),
            'type'              => 'string',
            'required'          => false,
            'sanitize_callback' => 'sanitize_text_field'
        );

        $params['billing_phone'] = array(
            'description'       => __('billing phone', 'plates'),
            'type'              => 'string',
            'required'          => true,
            'sanitize_callback' => 'sanitize_text_field'
        );

        $params['billing_email'] = array(
            'description'       => __('billing email', 'plates'),
            'type'              => 'string',
            'required'          => true,
            'sanitize_callback' => 'sanitize_text_field'
        );

        $params['shipping_first_name'] = array(
            'description'       => __('shipping first name', 'plates'),
            'type'              => 'string',
            'required'          => true,
            'sanitize_callback' => 'sanitize_text_field'
        );

        $params['shipping_last_name'] = array(
            'description'       => __('shipping last name', 'plates'),
            'type'              => 'string',
            'required'          => false,
            'sanitize_callback' => 'sanitize_text_field'
        );

        $params['shipping_company'] = array(
            'description'       => __('shipping company', 'plates'),
            'type'              => 'string',
            'required'          => true,
            'sanitize_callback' => 'sanitize_text_field'
        );

        $params['shipping_country'] = array(
            'description'       => __('shipping country', 'plates'),
            'type'              => 'string',
            'required'          => true,
            'sanitize_callback' => 'sanitize_text_field'
        );

        $params['shipping_address_1'] = array(
            'description'       => __('shipping address 1', 'plates'),
            'type'              => 'string',
            'required'          => true,
            'sanitize_callback' => 'sanitize_text_field'
        );

        $params['shipping_address_2'] = array(
            'description'       => __('shipping address 2', 'plates'),
            'type'              => 'string',
            'required'          => true,
            'sanitize_callback' => 'sanitize_text_field'
        );

        $params['shipping_city'] = array(
            'description'       => __('shipping city', 'plates'),
            'type'              => 'string',
            'required'          => true,
            'sanitize_callback' => 'sanitize_text_field'
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
            'required'          => false,
            'sanitize_callback' => 'sanitize_text_field'
        );

        $params['order_comments'] = array(
            'description'       => __('order comments', 'plates'),
            'type'              => 'string',
            'required'          => true,
            'sanitize_callback' => 'sanitize_text_field'
        );

        $params['shipping_method'] = array(
            'description'       => __('shipping method', 'plates'),
            'type'              => 'string',
            'required'          => true,
            'sanitize_callback' => 'sanitize_text_field'
        );

        $params['payment_method'] = array(
            'description'       => __('payment method', 'plates'),
            'type'              => 'string',
            'required'          => true,
            'sanitize_callback' => 'sanitize_text_field'
        );

        $params['ship_to_different_address'] = array(
            'description'       => __('ship to different address if user requested to add new shipping details', 'plates'),
            'type'              => 'string',
            'required'          => true,
            'sanitize_callback' => 'sanitize_text_field'
        );

        return apply_filters('api_checkout_before_returning_required_fields', $params);
    }
}
