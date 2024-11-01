<?php

class Stacks_ChangeOrderPaymentMethodController extends Stacks_AbstractOrderController {

	/**
	 * Route base.
	 * @var string
	 */
	protected $rest_api = '/orders/(?P<order_id>[\d]+)/payment_method';

	/**
	 * @inherit_doc
	 */
	protected $allowed_params = [
		'order_id'		=> 'order_id',
		'payment_method'	=> 'payment_method'
	];

	public function register_routes() {
		register_rest_route($this->get_api_endpoint(), $this->rest_api, array( // V3
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array($this, 'edit_order_payment_method'),
				'permission_callback' => array($this, 'get_user_items_permission'),
				'args'                => $this->get_collection_params()
			),
			'schema' => array($this, 'get_public_item_schema'),
		));

		register_rest_route($this->get_api_endpoint(), $this->rest_api, array( // V3
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array($this, 'edit_order_payment_method'),
				'permission_callback' => array($this, 'get_user_items_permission'),
				'args'                => $this->get_collection_params()
			),
			'schema' => array($this, 'get_public_item_schema'),
		));
	}

	/**
	 * creates the order 
	 * @return array
	 */
	public function edit_order_payment_method($request) {
		$this->map_request_parameters($request);

		$order_id		= $this->get_request_param('order_id');
		$payment_method	= $this->get_request_param('payment_method');
		$order			= wc_get_order($order_id);

		// if order is not valid
		if (!$order) {
			return $this->return_error_response(Stacks_WC_Api_Response_Service::INVALID_PARAMETER_CODE, $this->invalid_parameter_message(), array($this->invalid_order_id_message()), 500);
		}

		if ($order->needs_payment()) {
			$available_gateways = WC()->payment_gateways->get_available_payment_gateways();

			if (!$payment_method || !isset($available_gateways[$payment_method])) {
				return $this->return_error_response(Stacks_WC_Api_Response_Service::INVALID_PARAMETER_CODE, $this->invalid_parameter_message(), array(__('Invalid payment method', 'plates')), 500);
			}

			// Update meta
			update_post_meta($order_id, '_payment_method', $payment_method);

			if (isset($available_gateways[$payment_method])) {
				$payment_method_title = $available_gateways[$payment_method]->get_title();
			} else {
				$payment_method_title = '';
			}

			update_post_meta($order_id, '_payment_method_title', $payment_method_title);

			// Validate
			$available_gateways[$payment_method]->validate_fields();

			// Process
			$result = $available_gateways[$payment_method]->process_payment($order_id);
		} else {
			// No payment was required for order
			$order->payment_complete();
		}

		return $this->return_success_response([
			'status' => $order->get_status()
		]);
	}


	/**
	 * Options to pass to limit the response 
	 * 
	 * @return array
	 */
	public function get_collection_params() {
		$params = array();

		$params['order_id'] = array(
			'description'       => __('Order Id', 'plates'),
			'type'              => 'integer',
			'required'          => true,
			'sanitize_callback' => 'sanitize_text_field'
		);

		$params['payment_method'] = array(
			'description'       => __('payment method ', 'plates'),
			'type'              => 'string',
			'required'          => true,
			'sanitize_callback' => 'sanitize_text_field'
		);

		return $params;
	}
}
