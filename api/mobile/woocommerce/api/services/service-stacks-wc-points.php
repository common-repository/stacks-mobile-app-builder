<?php

class Stacks_WC_Points_Service {

	protected $wc_points_rewards_cart_checkout;

	public function __construct() {
		if (!stacks_is_points_plugin_activated()) {
			return false;
		}
		$this->wc_points_rewards_cart_checkout = new WC_Points_Rewards_Cart_Checkout();
	}


	/**
	 * check if partial Redemption is allowed or not
	 * 
	 * @return boolean
	 */
	public function is_partial_redemption_allowed() {
		if ('yes' === get_option('wc_points_rewards_partial_redemption_enabled')) {
			return true;
		}
		return false;
	}

	/**
	 * check if there is a points discount already applied on cart
	 * 
	 * @return boolean
	 */
	public function is_points_discount_already_applied_on_cart() {
		$existing_discount = WC_Points_Rewards_Discount::get_discount_code();

		if ((!empty($existing_discount) && WC()->cart->has_discount($existing_discount))) {
			return true;
		}

		return false;
	}

	/**
	 * get rewarded points when user perform purchase
	 * 
	 * @return int
	 */
	public function _stacks_get_points_earned_for_purchase() {
		$reflector = new ReflectionObject($this->wc_points_rewards_cart_checkout);
		$method = $reflector->getMethod('get_points_earned_for_purchase');
		$method->setAccessible(true);
		return $method->invoke($this->wc_points_rewards_cart_checkout);
	}


	/**
	 * get points earned when user purchase 
	 * @global object $wc_points_rewards
	 * @return array|boolean
	 */
	public function _stacks_get_points_earned_for_purchase_message() {
		global $wc_points_rewards;

		// get the total points earned for this purchase
		$points_earned = $this->_stacks_get_points_earned_for_purchase();

		$message = get_option('wc_points_rewards_earn_points_message');

		// bail if no message set or no points will be earned for purchase
		if (!$message || !$points_earned) {
			return '';
		}

		// points earned
		$message = str_replace('{points}', number_format_i18n($points_earned), $message);

		// points label
		$message = str_replace('{points_label}', $wc_points_rewards->get_points_label($points_earned), $message);

		return array(
			'points'	=> $points_earned,
			'message'	=> $message
		);
	}


	/**
	 * Get discount user can gain after redeeming his points 
	 * 
	 * @return int
	 */
	public function get_total_discount_available_for_redeem() {
		return WC_Points_Rewards_Cart_Checkout::get_discount_for_redeeming_points();
	}

	/**
	 * Wrapper above _stacks_get_points_can_be_redeemed_for_purchase to restrict call to this function before performing validation 
	 * 
	 * @return string|array
	 */
	public function _stacks_get_points_can_be_redeemed() {
		if (wc_coupons_enabled() && !$this->is_points_discount_already_applied_on_cart()) {
			$val = $this->_stacks_get_points_can_be_redeemed_for_purchase();

			return !$val ? '' : $val;
		}

		return '';
	}

	/**
	 * get total points redeemed for purchase 
	 * 
	 * ex:Use 200 Points for a £200 discount on this order!
	 * 
	 * @use validation is_coupons_enabled, is_points_discount_already_applied_on_cart
	 * 
	 * @return type
	 */
	protected function _stacks_get_points_can_be_redeemed_for_purchase() {
		global $wc_points_rewards;

		// get the total discount available for redeeming points
		$discount_available = $this->get_total_discount_available_for_redeem();

		$message = get_option('wc_points_rewards_redeem_points_message');

		// bail if no message set or no points will be earned for purchase
		if (!$message || !$discount_available) {
			return false;
		}

		// points required to redeem for the discount available
		$points  = WC_Points_Rewards_Manager::calculate_points_for_discount($discount_available);

		$message = str_replace('{points}', number_format_i18n($points), $message);

		// the maximum discount available given how many points the customer has
		$message = str_replace('{points_value}', wc_price($discount_available), $message);

		// points label
		$message = str_replace('{points_label}', $wc_points_rewards->get_points_label($points), $message);

		return array(
			'points'		=> number_format_i18n($points),
			'points_value'	=> StacksWoocommerceDataFormating::format_number($discount_available),
			'message'		=> $message
		);
	}
}
