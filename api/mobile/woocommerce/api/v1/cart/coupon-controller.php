<?php

class Stacks_CouponController extends Stacks_CartAbstractController {


	/**
	 * @inherit_doc
	 */
	protected $allowed_params = [
		'coupon_id' => 'coupon_id',
		'points_num' => 'points_num',
	];

	protected $product;

	public function register_routes() {
		register_rest_route($this->get_api_endpoint(), $this->rest_api . '/points_discount', array( // V3
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array($this, 'apply_points_discount'),
				'permission_callback' => array($this, 'get_user_items_permission'),
				'args'                => $this->get_collection_params_get()
			),
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array($this, 'apply_points_discount'),
				'permission_callback' => array($this, 'get_user_items_permission'),
				'args'                => $this->get_collection_params_get()
			),
		));

		register_rest_route($this->get_api_endpoint(), $this->rest_api . '/coupons', array( // V3
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array($this, 'apply_coupon'),
				'permission_callback' => array($this, 'get_items_permissions_check'),
				'args'                => $this->get_collection_params_submit_coupon()
			),
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array($this, 'apply_coupon'),
				'permission_callback' => array($this, 'get_items_permissions_check'),
				'args'                => $this->get_collection_params_submit_coupon()
			),
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array($this, 'remove_coupon'),
				'permission_callback' => array($this, 'get_items_permissions_check'),
				'args'                => $this->get_collection_params_submit_coupon()
			),
			'schema' => array($this, 'get_public_item_schema'),
		));
		register_rest_route($this->get_api_endpoint(), $this->rest_api . '/coupons/remove_coupon', array( // V3
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array($this, 'remove_coupon'),
				'permission_callback' => array($this, 'get_items_permissions_check'),
				'args'                => $this->get_collection_params_submit_coupon()
			),
			'schema' => array($this, 'get_public_item_schema'),
		));
	}

	///////////////////////////
	// Validation Functions //
	/////////////////////////


	/**
	 * validates user email is permitted to add this coupon
	 * 
	 * @param int $coupon_id
	 * 
	 * @return boolean
	 */
	public function validate_coupon_restrictions($coupon_id) {
		$coupon = new WC_Coupon($coupon_id);

		$restrictions = $coupon->get_email_restrictions();

		$customer_billing_emails = WC()->customer->get_billing_email() === '' ? [] : [WC()->customer->get_billing_email()];

		if (is_array($restrictions) && 0 < count($restrictions) && 0 === count(array_intersect($customer_billing_emails, $restrictions))) {
			return $this->return_error_response(
				Stacks_WC_Api_Response_Service::CAN_NOT_aPPLY_COUPON,
				$this->get_message('apply_coupon_not_yours'),
				array($this->get_message('apply_coupon_not_yours')),
				Stacks_WC_Api_Response_Service::CLIENT_ERROR_STATUS_CODE
			);
		}

		return true;
	}

	/**
	 * validate coupons is enabled in woo-commerce 
	 * @return boolean
	 */
	private function validate_coupons_is_enabled() {
		if (!$this->is_coupons_enabled()) {
			return $this->return_error_response(
				Stacks_WC_Api_Response_Service::COUPONS_NOT_ACTIVE_CODE,
				$this->get_message('coupons_is_not_active'),
				$this->get_errors(),
				Stacks_WC_Api_Response_Service::CLIENT_ERROR_STATUS_CODE
			);
		}
	}

	/**
	 * coupons validation wrapper 
	 * @param int $coupon_id
	 * @param boolean $coupon_applied check if coupon is applied on cart or not
	 * @return boolean
	 */
	private function perform_coupons_validation($coupon_id = null, $coupon_applied = false) {
		// check if coupons enabled 
		$coupons_enabled_error = $this->validate_coupons_is_enabled();

		if (is_wp_error($coupons_enabled_error)) {
			return $coupons_enabled_error;
		}

		if (!is_null($coupon_id)) {
			// check if coupon is valid and not applied in the cart
			$coupon_valid = $this->is_valid_coupon($coupon_id, true);

			if (!$coupon_valid['valid']) {
				return $this->return_invalid_coupon_response($coupon_valid['message']);
			}

			if ($this->is_coupon_applied($coupon_id) && $coupon_applied) {
				return $this->return_error_response(
					Stacks_WC_Api_Response_Service::INVALID_PARAMETER_CODE,
					$this->get_message('coupon_already_applied_message'),
					array($this->get_message('coupon_already_applied_message')),
					Stacks_WC_Api_Response_Service::CLIENT_ERROR_STATUS_CODE
				);
			}
		}

		return true;
	}

	/**
	 * validate partial redemption user requested
	 * @param int $points
	 * @return wp_error|int
	 */
	private function validate_partial_redemption_points_max_amount($points) {
		$max_discount = (int) get_option('wc_points_rewards_cart_max_discount');

		if (!$max_discount || $max_discount == '') {
			return $points;
		} else {
			if ($points > $max_discount) {
				return $this->return_error_response(
					Stacks_WC_Api_Response_Service::INVALID_PARAMETER_CODE,
					$this->invalid_parameter_message(),
					array(sprintf(__('you are only allowed to redeem more than %s points', 'plates'), $max_discount))
				);
			} else {
				return $points;
			}
		}
	}

	/**
	 * Validate that user has this amount of points he request 
	 * 
	 * @param int $points_requested
	 */
	private function validate_partial_redemption_user_has_this_amount($points_requested) {
		$user_points	= (int) WC_Points_Rewards_Manager::get_users_points(get_current_user_id());

		if ($points_requested && $points_requested <= $user_points) {
			return true;
		} else {
			return $this->return_error_response(
				Stacks_WC_Api_Response_Service::INVALID_PARAMETER_CODE,
				$this->invalid_parameter_message(),
				array(sprintf(__('Number of points requested"%s" are more than what you have""', 'plates'), $points_requested, $user_points))
			);
		}
	}


	/**
	 * Remove coupon on cart
	 * 
	 * @param WP_REST_Request $request
	 */
	public function remove_coupon($request) {
		$this->map_request_parameters($request);

		$coupon_id = $this->get_request_param('coupon_id');

		$result = $this->perform_coupons_validation($coupon_id, false);
		if (is_wp_error($result)) {
			return $result;
		}

		if (!$this->is_valid_coupon($coupon_id)) {
			return $this->get_response_service()->invalid_parameter_response($this->get_response_service()->get_message('coupon_does_not_exist'));
		}

		if (!$this->is_coupon_applied($coupon_id)) {
			return $this->get_response_service()->invalid_parameter_response($this->get_response_service()->get_message('coupon_does_not_applied'));
		}

		unset(WC()->session->wc_points_rewards_discount_code);
		unset(WC()->session->wc_points_rewards_discount_amount);

		$remove_action = wc()->cart->remove_coupon($coupon_id);

		if (!$remove_action) {
			return $this->get_response_service()->invalid_parameter_response($this->get_response_service()->get_message('coupon_might_be_expired'));
		}

		wc()->cart->calculate_totals();

		return $this->return_success_response($this->get_cart_details());
	}


	/**
	 * Apply coupon on cart
	 * 
	 * @param object $request
	 * @return array
	 */
	public function apply_coupon($request) {
		$this->map_request_parameters($request);

		$coupon_id = $this->get_request_param('coupon_id');

		$result = $this->perform_coupons_validation($coupon_id, true);

		if (is_wp_error($result)) {
			return $result;
		}

		// validate coupon can be added to this user 
		$result = $this->validate_coupon_restrictions($coupon_id);

		if (is_wp_error($result)) {
			return $result;
		}


		$result = wc()->cart->add_discount($coupon_id);

		if (!$result) {
			$notices = wc_get_notices();

			if (!empty($notices['error'])) {
				$message = array_unique($notices['error']);
			} else {
				$message = array(__('this coupon might be expired', 'plates'));
			}

			return $this->return_error_response(Stacks_WC_Api_Response_Service::INVALID_PARAMETER_CODE, $message[0], $message, 400);
		}

		wc()->cart->calculate_totals();

		return $this->return_success_response($this->get_cart_details());
	}

	/**
	 * Apply Points Discount 
	 * @param object $request
	 * @return array
	 */
	public function apply_points_discount($request) {
		$this->map_request_parameters($request);

		$user_id			= get_current_user_id();
		$requested_points	= $this->get_request_param_int('points_num');

		// validate points plugin is activated 
		if (!stacks_is_points_plugin_activated()) {
			return $this->return_error_response($this->addon_not_activated_code(), $this->addon_not_activated_message(), array($this->addon_not_activated_message()), 400);
		}

		$points = (int) WC_Points_Rewards_Manager::get_users_points($user_id);

		// -1 // must happened validations are coupons enabled ? 
		// -2 // does user have any points at all ?
		// -3 // is there already applied discount ?

		// -1 // check if coupons enabled 
		$coupons_enabled_error = $this->validate_coupons_is_enabled();
		if (is_wp_error($coupons_enabled_error)) {
			return $coupons_enabled_error;
		}

		// -2 // check user has points at all 
		if ($points <= 0) {
			return $this->return_error_response(Stacks_WC_Api_Response_Service::INVALID_PARAMETER_CODE, $this->invalid_parameter_message(), $this->no_points_exists_message(), 400);
		}

		// -3 // bail if the discount has already been applied
		$existing_discount = WC_Points_Rewards_Discount::get_discount_code();

		if (!empty($existing_discount) && WC()->cart->has_discount($existing_discount)) {
			return $this->return_error_response(Stacks_WC_Api_Response_Service::INVALID_PARAMETER_CODE, $this->invalid_parameter_message(), $this->points_discount_already_applied_message(), 400);
		}

		// if user is requesting partial redemption then validate partial redemption value 
		if ($requested_points) {

			// is partial Redemption allowed or not
			$partial_redemption_enabled = $this->stacks_wc_points_service->is_partial_redemption_allowed();

			if (!$partial_redemption_enabled) {
				return $this->return_error_response(Stacks_WC_Api_Response_Service::INVALID_PARAMETER_CODE, $this->invalid_parameter_message(), array($this->get_message('partial_redemption_not_enabled')), 400);
			}

			// does user has the amount of points requested 
			$user_has_this_points_validation = $this->validate_partial_redemption_user_has_this_amount($requested_points);

			if (is_wp_error($user_has_this_points_validation)) {
				return $user_has_this_points_validation;
			}

			// does user passed max amount of points 
			$max_amount_points_can_be_redeemed = $this->validate_partial_redemption_points_max_amount($requested_points);

			if (is_wp_error($max_amount_points_can_be_redeemed)) {
				return $max_amount_points_can_be_redeemed;
			}

			$points = $requested_points;
		}

		// generate and set unique discount code
		$discount_code = WC_Points_Rewards_Discount::generate_discount_code();

		// apply the discount
		$code = WC()->cart->add_discount($discount_code);

		if ($code) {
			return $this->return_success_response(['cart_details' => $this->get_cart_details(), 'message' => $this->get_message('points_redeemed_successfully')]);
		} else {
			return $this->return_error_response(Stacks_WC_Api_Response_Service::UNEXPECTED_ERROR_CODE, Stacks_WC_Api_Response_Service::UNEXPECTED_ERROR_CODE, $this->points_discount_already_applied_message(), 400);
		}
	}


	/**
	 * Return Collection of Parameters required for submitting coupon
	 * @return array
	 */
	public function get_collection_params_submit_coupon() {
		$params = array();

		$params['coupon_id'] = array(
			'description'       => __('Coupon id you would like to apply to cart.', 'plates'),
			'type'              => 'string',
			'required'          => true,
			'sanitize_callback' => 'sanitize_text_field'
		);

		return $params;
	}


	/**
	 * get collection parameters for get request
	 * @return array
	 */
	public function get_collection_params_get() {
		$params = array();

		$params['points_num'] = array(
			'description'       => __('Number of Points you would like to Redeem from your points for this order.', 'plates'),
			'type'              => 'int',
			'sanitize_callback' => 'sanitize_text_field'
		);

		return $params;
	}
}
