<?php

/**
 * Plates Shipping Method Formatter Extension
 */
class Stacks_ShippingMethodFormatting extends Stacks_PlatesFormatter {

	/**
	 * Set Items for Formatting 
	 * 
	 * @param array|int $items
	 */
	public function __construct($items) {
		$this->set_items($items);
	}

	/**
	 * Format Group of Shipping methods or single shipping method object
	 * 
	 * @return array
	 */
	public function format() {
		if (!is_array($this->items)) {
			return $this->format_single_shipping_method($this->items);
		} else {
			foreach ($this->items as $index => $shipping_method) {
				$this->items[$index] = $this->format_single_shipping_method($shipping_method);
			}

			return $this->items;
		}
	}

	public function format_single_shipping_method(WC_Shipping_Method $shipping_method) {
		$method = array(
			'id'                 => $shipping_method->id,
			'instance_id'        => $shipping_method->instance_id,
			'title'              => $shipping_method->instance_settings['title'],
			'order'              => $shipping_method->method_order,
			'enabled'            => ('yes' === $shipping_method->enabled),
			'method_id'          => $shipping_method->id,
			'method_title'       => $shipping_method->method_title,
			'method_description' => $shipping_method->method_description,
			'cost'		 => $shipping_method->get_option('cost')
		);

		return $method;
	}
}
