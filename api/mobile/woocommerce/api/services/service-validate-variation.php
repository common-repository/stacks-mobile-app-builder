<?php

/**
 * Description of Stacks_ValidateVariationsService
 *
 * @author creiden
 */
class Stacks_ValidateVariationsService {
	/**
	 * @var int
	 */
	protected $variation_id;

	/**
	 * @var array
	 */
	protected $submitted_variations;

	/**
	 * return variation id
	 * @return int|null
	 */
	public function get_variation_id() {
		return $this->variation_id;
	}

	/**
	 * return submitted variations values
	 * @return array|null
	 */
	public function get_submitted_variations_values() {
		return $this->submitted_variations;
	}

	/**
	 * validate submitted attribute values 
	 * @param int $variation_id
	 * @return array
	 */
	public function validate_submitted_attribute_values($product, $submitted_variation_values, $variation_id) {
		$missing_attributes = array();
		$invalid_attributes = array();
		$variations         = array();
		$variation_values   = $this->get_variation_attributes($variation_id);

		foreach ($product->get_attributes() as $attribute) {
			if (!$attribute['is_variation']) {
				continue;
			}

			// Get valid value from variation data.
			$taxonomy    = $this->get_local_attribute_name($attribute['name']);
			$valid_value = isset($variation_values[$taxonomy]) ? $variation_values[$taxonomy] : '';

			/**
			 * If the attribute value was posted, check if it's valid.
			 *
			 * If no attribute was posted, only error if the variation has an 'any' attribute which requires a value.
			 */
			if (isset($submitted_variation_values[$taxonomy])) {
				if ($attribute['is_taxonomy']) {
					// Don't use wc_clean as it destroys sanitized characters.
					$value = sanitize_title(wp_unslash($submitted_variation_values[$taxonomy]));
				} else {
					$value = wc_clean(wp_unslash($submitted_variation_values[$taxonomy])); // WPCS: sanitization ok.
				}

				// Allow if valid or show error.
				if ($valid_value === $value) {
					$variations[$taxonomy] = $value;
				} elseif ('' === $valid_value && in_array($value, $attribute->get_slugs())) {
					// If valid values are empty, this is an 'any' variation so get all possible values.
					$variations[$taxonomy] = $value;
				} else {
					$invalid_attributes[] = sprintf(__('Invalid value posted for %s', 'plates'), wc_attribute_label($attribute['name']));
				}
			} else {
				$missing_attributes[] = sprintf(__('Attribute %s is missing', 'plates'), wc_attribute_label($attribute['name']));
			}
		}

		return array(
			'missing_attributes'    => $missing_attributes,
			'invalid_attributes'    => $invalid_attributes,
			'variations'            => $variations
		);
	}

	/**
	 * check if attributes values are valid or not and return variation id 
	 * @param object $product 
	 * @param array $attributes_values
	 * @param int $variation_id
	 * @return int|boolean
	 */
	public function get_variation_id_for_submitted_attributes($product, $attributes_values, $submitted_variations, $variation_id = 0) {
		// try to predict id from attributes if no variation id is provided

		if (!$variation_id || is_null($variation_id)) {
			if (!empty($submitted_variations)) {
				$data_store   = WC_Data_Store::load('product');
				$variation_id = $data_store->find_matching_product_variation($product, $submitted_variations);
			}
		} else {
			// check if this variation exists
			$variation_ids = $this->get_product_available_variations($product);

			// check if this variation id exists on product variations
			if (!in_array($variation_id, $variation_ids)) {
				$variation_id = false;
			}
		}

		$this->variation_id	= $variation_id;

		return $this->variation_id;
	}

	/**
	 * get product available variations 
	 * @param object $product
	 * @return array
	 */
	protected function get_product_available_variations($product) {
		if ($product) {
			$attributes = $product->get_available_variations();

			if (!empty($attributes)) {
				return array_map(function ($variation) {
					return $variation['variation_id'];
				}, $attributes);
			}
		}

		return array();
	}

	/**
	 * Get submitted attributes, no validation happens on variations
	 * @return array
	 */
	public function get_submitted_variations($product, $attributes = array()) {
		if ($attributes && !empty($attributes)) {

			$product_attributes = array_keys($product->get_attributes());

			$this->submitted_variations = $this->name_attributes_values_according_to_type($product_attributes, $attributes);

			return $this->submitted_variations;
		} else {
			return array();
		}
	}

	/**
	 * Name attributes values according to type ex:size -> attribute_pa_size
	 * @param array $product_attributes
	 * @param array $attributes
	 * @return array
	 */
	protected function name_attributes_values_according_to_type($product_attributes, $attributes) {
		$submitted_attributes = array();

		foreach ($attributes as $attribute => $value) {
			$attribute_trimed = str_replace(' ', '-', strtolower($attribute));
			// local attribute
			if (in_array($attribute_trimed, $product_attributes)) {
				$name = $this->get_local_attribute_name($attribute);
			}
			// global attribute
			elseif (in_array('pa_' . $attribute_trimed, $product_attributes)) {
				$name	= $this->get_global_attribute_name($attribute_trimed);
				$value	= $this->santitize_attribute_name($value);
			}
			// neither global nor local
			else {
				$product_attributes = urldecode_deep($product_attributes);
				if (in_array($attribute_trimed, $product_attributes)) {
					$name = $this->get_local_attribute_name($attribute);
				}
				// global attribute
				elseif (in_array('pa_' . $attribute_trimed, $product_attributes)) {
					$name	= $this->get_global_attribute_name($attribute_trimed);
					$value	= $this->santitize_attribute_name($value);
				} else {
					// if type not determined continue
					continue;
				}
				
			}
			$submitted_attributes[$name] = $value;
		}
		return $submitted_attributes;
	}

	/**
	 * get attribute name according to type global or local
	 * @param int $variation_id
	 * @param string $attribute
	 * @return string|bool
	 */
	protected function get_attribute_name_according_type($variation_id, $attribute) {
		$type = $this->get_attribute_type($variation_id, $attribute);

		switch ($type) {
			case 'global':
				return $this->get_global_attribute_name($attribute);

			case 'local':
				return $this->get_local_attribute_name($attribute);

			default:
				return false;
		}
	}

	/**
	 * check if attribute is global or local attribute for the product 
	 * @param integer	$variation_id
	 * @param string	$attribute_name
	 * @return boolean
	 */
	protected function get_attribute_type($variation_id, $attribute_name) {
		$variation_attributes   = $this->get_variation_attributes($variation_id);
		$global_name            = $this->get_global_attribute_name($attribute_name);
		$local_name				= $this->get_local_attribute_name($attribute_name);

		if (array_key_exists($global_name, $variation_attributes)) {
			return 'global';
		} elseif (array_key_exists($local_name, $variation_attributes)) {
			return 'local';
		} else {
			return false;
		}
	}

	/**
	 * get variation attributes 
	 * @param int $variation_id
	 * @return array
	 */
	protected function get_variation_attributes($variation_id) {
		return wc_get_product_variation_attributes($variation_id);
	}

	/**
	 * get global attribute name 
	 * @param string $name
	 * @return string
	 */
	protected function get_global_attribute_name($name) {
		return sprintf('attribute_pa_%s', $this->santitize_attribute_name($name));
	}

	/**
	 * get local attribute name 
	 * @param string $name
	 * @return string
	 */
	protected function get_local_attribute_name($name) {
		return sprintf('attribute_%s', $this->santitize_attribute_name($name));
	}

	/**
	 * Sanitize Attribute name 
	 * @param string $name
	 * @return string
	 */
	protected function santitize_attribute_name($name) {
		return sanitize_title($name);
	}
}
