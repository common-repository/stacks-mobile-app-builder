<?php

/**
 * Plates Variations Formatter Extension
 */
class Stacks_VariationsFormatting extends Stacks_PlatesFormatter {

	protected $default_attributes;

	protected function get_variation_id_sale_status($variation_id) {
		$variation = wc_get_product($variation_id);

		return $variation->is_on_sale();
	}


	/**
	 * format variations
	 * 
	 * @param integer $variations
	 * @return array
	 */
	public function format() {
		if (!empty($this->items)) {
			$new_variations = array();

			foreach ($this->items as $variation) {
				//get variation id 
				$variation_id = $variation['variation_id'];

				// get on sale status 
				$variation['on_sale'] = $this->get_variation_id_sale_status($variation_id);

				// add result to new array
				$new_variations[$variation_id] = $variation;
			}

			return $new_variations;
		}

		return array();
	}
}
