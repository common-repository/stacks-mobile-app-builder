<?php

class StacksCheckoutService extends WC_Checkout {

	protected $posted_data;

	/**
	 * The single instance of the class.
	 *
	 * @var StacksCheckoutService|null
	 */
	protected static $instance = null;

	/**
	 * Gets the main StacksCheckoutService instance
	 * @static
	 * @return StacksCheckoutService Main instance
	 */
	public static function instance() {
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * set's posted data from user side 
	 * @param array $posted_data
	 * @return $this
	 */
	public function set_posted_data($posted_data) {
		$this->posted_data = $posted_data;
		return $this;
	}

	/**
	 * start initial validation and return errors array
	 * @return array
	 */
	public function start_initial_validation() {
		$errors = array();

		// check if cart is empty 
		if (WC()->cart->is_empty()) {
			$errors[] = __('sorry your cart is empty', 'plates');
		}

		wc_set_notices([]);

		// coupons validations for restrictions and validation by billing email
		do_action('woocommerce_after_checkout_validation', $this->posted_data, $errors);

		$notices = wc_get_notices();
		if (!empty($notices) && !empty($notices['error'])) {
			$errors = array_merge($errors, $notices['error']);
			wc()->cart->calculate_totals();
		}

		return $errors;
	}

	/**
	 * return error response
	 * @param array $errors
	 * @return array
	 */
	public function return_errors($errors) {
		return array('success' => false, 'errors' => is_array($errors) ? $errors : array($errors));
	}

	/**
	 * Start Order Checkout Process
	 * @return int|array
	 */
	public function start_checkout() {
		do_action('woocommerce_checkout_process');

		$this->update_session($this->posted_data);

		$errors			= new WP_Error();
		$notices_log	= array();

		$this->validate_checkout($this->posted_data, $errors);

		if (!empty($errors->get_error_messages())) {
			foreach ($errors->get_error_messages() as $message) {
				$notices_log[] = $message;
			}

			return $this->return_errors($notices_log);
		}

		wc_set_time_limit(0);

		$this->process_customer($this->posted_data);

		$order_id = $this->create_order($this->posted_data);
		$order    = wc_get_order($order_id);

		if (is_wp_error($order_id)) {
			return $this->return_errors($order_id->get_error_message());
		}

		do_action('woocommerce_checkout_order_processed', $order_id, $this->posted_data, $order);

		if (WC()->cart->needs_payment()) {
			$result = $this->process_order_payment($order_id, $this->posted_data['payment_method']);

			if (!$result) {
				return $this->return_errors(Stacks_WC_Api_Response_Service::get_instance()->unexpected_error_message());
			}
		} else {
			$this->process_order_without_payment($order_id);
		}

		wc_empty_cart();

		wc()->cart->calculate_totals();

		return array('success' => true, 'id' => $order_id);
	}

	/**
	 * Start Order Checkout Process
	 * @return int|array
	 */
	public function start_checkout_order() {
		do_action('woocommerce_checkout_process');

		$this->update_session($this->posted_data);
		$this->posted_data['status'] = 'processing';

		$errors			= new WP_Error();
		$notices_log	= array();

		$this->validate_checkout($this->posted_data, $errors);
		if (!empty($errors->get_error_messages()) && $errors->get_error_data()['id'] !== "billing_email") {
			foreach ($errors->get_error_messages() as $message) {
				$notices_log[] = $message;
			}

			return $this->return_errors($notices_log);
		}

		wc_set_time_limit(0);

		$this->process_customer($this->posted_data);

		$order_id = $this->create_order($this->posted_data);
		// $order    = wc_get_order($order_id);
		return array('success' => true, 'id' => $order_id);
	}

	/**
	 * Process an order that does require payment.
	 *
	 * @since  3.0.0
	 * @param  int $order_id
	 * @param  string $payment_method
	 */
	protected function process_order_payment($order_id, $payment_method) {
		$available_gateways = WC()->payment_gateways->get_available_payment_gateways();

		if (!isset($available_gateways[$payment_method])) {
			return;
		}

		// Store Order ID in session so it can be re-used after payment failure
		WC()->session->set('order_awaiting_payment', $order_id);

		// Process Payment
		$result = $available_gateways[$payment_method]->process_payment($order_id);

		// Redirect to success/confirmation/payment page
		if (isset($result['result']) && 'success' === $result['result']) {
			return true;
		} else {
			return false;
		}
	}
}
