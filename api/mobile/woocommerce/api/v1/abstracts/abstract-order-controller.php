<?php

abstract class Stacks_AbstractOrderController extends Stacks_AbstractController {

	/**
	 * Check if order can be canceled
	 * @param object $order
	 * @return boolean
	 */
	public function order_can_be_canceled($order) {
		if (!in_array($order->get_status(), apply_filters('woocommerce_valid_order_statuses_for_cancel', array('pending', 'failed'), $order))) {
			return false;
		}
		return true;
	}

	/**
	 * check if order needs payment or not 
	 * @param object $order
	 * @return boolean
	 */
	public function order_needs_payment($order) {
		return $order->needs_payment();
	}
}
