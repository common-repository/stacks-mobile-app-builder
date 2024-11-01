<?php

class Stacks_AddonsFormatting extends Stacks_PlatesFormatter {
	/**
	 * Format Add ons 
	 * 
	 * @return array
	 */
	public function format() {
		if (!empty($this->items)) {
			foreach ($this->items as $index => $addon) {
				$this->items[$index] = $this->format_single_addon($addon);
			}
			return $this->items;
		}

		return array();
	}

	/**
	 * format single add on
	 * 
	 * @param array $addon
	 * @return array
	 */
	protected function format_single_addon($addon) {
		if (!empty($addon['options'])) {
			foreach ($addon['options'] as $key => $option) {
				$addon['options'][$key]['price'] = StacksWoocommerceDataFormating::format_number($option['price']);
			}
		}

		return $addon;
	}
}
