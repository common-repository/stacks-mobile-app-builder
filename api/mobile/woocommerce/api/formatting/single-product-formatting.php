<?php

/**
 * Plates Orders Formatter Extension
 */
class Stacks_SingleProductFormatting extends Stacks_PlatesFormatter {

	protected $product;
	protected $default_attributes;
	protected $featured_image_size = 'full';

	/**
	 * set featured image size 
	 * @param string $size
	 */
	public function set_featured_image_size($size) {
		$this->featured_image_size = $size;
		return $this;
	}

	/**
	 * check if id provided is for valid product
	 * @param integer $id
	 * @return boolean
	 */
	protected function is_valid_product($id) {
		$this->product = wc_get_product($id);

		if ($this->product) {
			return true;
		}
		return false;
	}

	/**
	 * Format Group of products or single product object or just integer value
	 * @param array $products
	 * @return array
	 */
	public function format() {
		$id = $this->get_id($this->items);

		if (!$this->is_valid_product($id)) {
			return false;
		}

		return !$id ? false : $this->format_single_product();
	}

	/**
	 * Get product data
	 * @return array
	 */
	protected function format_single_product() {
		$context = 'view';

		$product_object = new Stacks_Woocommerce_Product_Data($this->product->get_id());

		$price = $product_object->get_price();

		$availability = $this->product->get_availability();

		$data = array(
			'id' => $this->product->get_id(),
			'name' => $this->product->get_name($context),
			'slug' => $this->product->get_slug($context),
			'permalink' => $this->product->get_permalink(),
			'date_created' => wc_rest_prepare_date_response($this->product->get_date_created($context), false),
			'date_created_gmt' => wc_rest_prepare_date_response($this->product->get_date_created($context)),
			'date_modified' => wc_rest_prepare_date_response($this->product->get_date_modified($context), false),
			'date_modified_gmt' => wc_rest_prepare_date_response($this->product->get_date_modified($context)),
			'type' => $this->product->get_type(),
			'status' => $this->product->get_status($context),
			'featured' => $this->product->is_featured(),
			'catalog_visibility' => $this->product->get_catalog_visibility($context),
			'description' => 'view' === $context ? wpautop(do_shortcode($this->product->get_description())) : $this->product->get_description($context),
			'short_description' => 'view' === $context ? apply_filters('woocommerce_short_description', $this->product->get_short_description()) : $this->product->get_short_description($context),
			'sku' => $this->product->get_sku($context),
			'date_on_sale_from' => wc_rest_prepare_date_response($this->product->get_date_on_sale_from($context), false),
			'date_on_sale_from_gmt' => wc_rest_prepare_date_response($this->product->get_date_on_sale_from($context)),
			'date_on_sale_to' => wc_rest_prepare_date_response($this->product->get_date_on_sale_to($context), false),
			'date_on_sale_to_gmt' => wc_rest_prepare_date_response($this->product->get_date_on_sale_to($context)),
			'price_html' => $this->product->get_price_html(),
			'on_sale' => $this->product->is_on_sale($context),
			'purchasable' => $this->product->is_purchasable(),
			'total_sales' => $this->product->get_total_sales($context),
			'virtual' => $this->product->is_virtual(),
			'external_url' => $this->product->is_type('external') ? $this->product->get_product_url($context) : '',
			'button_text' => $this->product->is_type('external') ? $this->product->get_button_text($context) : '',
			'tax_status' => $this->product->get_tax_status($context),
			'tax_class' => $this->product->get_tax_class($context),
			'manage_stock' => $this->product->managing_stock(),
			'stock_quantity' => $this->product->get_stock_quantity($context),
			'in_stock' => $this->product->is_in_stock(),
			'shipping_required' => $this->product->needs_shipping(),
			'reviews_allowed' => $this->product->get_reviews_allowed($context),
			'average_rating' => 'view' === $context ? wc_format_decimal($this->product->get_average_rating(), 2) : $this->product->get_average_rating($context),
			'rating_count' => $this->product->get_rating_count(),
			'related_ids' => array_map('absint', array_values(wc_get_related_products($this->product->get_id()))),
			'upsell_ids' => array_map('absint', $this->product->get_upsell_ids($context)),
			'cross_sell_ids' => array_map('absint', $this->product->get_cross_sell_ids($context)),
			'parent_id' => $this->product->get_parent_id($context),
			'purchase_note' => 'view' === $context ? wpautop(do_shortcode(wp_kses_post($this->product->get_purchase_note()))) : $this->product->get_purchase_note($context),
			'images' => $this->get_gallery(),
			'featured_image' => $this->get_featured_image(),
			'attributes' => $this->get_attributes(),
			'variations' => $this->product->get_type() !== 'variable' ? array() : StacksWoocommerceDataFormating::format_product_variations($this->product->get_available_variations()),
			'menu_order' => $this->product->get_menu_order($context),
			'categories' => $this->get_product_cateogries($product_object),
			// 'addons' => stacks_is_addon_plugin_activated() ? StacksWoocommerceDataFormating::format_addons(get_product_addons($this->product->get_id())) : array(),
			'addons' => array(),
			'backorders_allowed' => $this->product->backorders_allowed(),
			'availability_html' => apply_filters('woocommerce_stock_html', '<p class="stock ' . esc_attr($availability['class']) . '">' . esc_html($availability['availability']) . '</p>', $availability['availability']),
			'max_qty' => $this->product->backorders_allowed() ? null : $this->product->get_stock_quantity($context)
		);

		$regular_price = $this->product->get_type() == 'simple' ? 'regular_price' : 'max_price';
		$sale_price = $this->product->get_type() == 'simple' ? 'sale_price' : 'min_price';

		$data[$regular_price] = $price['regular_price'];
		$data[$sale_price] = $price['sale_price'];

		return apply_filters('avaris-formatting-single-product', $data);
	}

	/**
	 * Get product categories Formatted 
	 * 
	 * @param Stacks_Woocommerce_Product_Data $product_object
	 */
	public function get_product_cateogries(&$product_object) {

		$product_categories = $product_object->product->get_category_ids();

		if (!empty($product_categories)) {
			$categoreis = array_map(
				function ($term_id) {
					return get_term($term_id);
				},
				$product_categories
			);

			return StacksWoocommerceDataFormating::format_categories($categoreis);
		}

		return [];
	}


	/**
	 * Get Product Featured Image
	 * @return boolean|array
	 */
	protected function get_featured_image() {

		// get featured image.
		if (has_post_thumbnail($this->product->get_id())) {
			$featured_image_id = $this->product->get_image_id();

			return $this->get_image_response($featured_image_id);
		}

		return false;
	}

	/**
	 * Get the images for a product.
	 *
	 * @return array
	 */
	protected function get_gallery() {
		// Get gallery images.
		$attachment_ids = $this->product->get_gallery_image_ids();

		if (!empty($attachment_ids)) {
			return array_map([$this, 'get_image_response'], $attachment_ids);
		}

		return array();
	}

	/**
	 * Get Image Response 
	 * @param int $attachment_id
	 * @return boolean|array
	 */
	protected function get_image_response($attachment_id) {
		$attachment_post = get_post($attachment_id);

		if (is_null($attachment_post)) {
			return false;
		}

		// get attachment in the required size
		$attachment = wp_get_attachment_image_src($attachment_id, $this->featured_image_size);

		if (!is_array($attachment)) {
			return false;
		}

		return array(
			'id' => (int) $attachment_id,
			'date_created' => wc_rest_prepare_date_response($attachment_post->post_date, false),
			'src' => current($attachment),
			'name' => get_the_title($attachment_id),
			'alt' => get_post_meta($attachment_id, '_wp_attachment_image_alt', true)
		);
	}

	/**
	 * Get product attribute options.
	 *
	 * @param int   $product_id Product ID.
	 * @param array $attribute  Attribute data.
	 * @return array
	 */
	protected function get_product_attribute_options($product_id, $attribute) {
		if (isset($attribute['is_taxonomy']) && $attribute['is_taxonomy']) {
			$mapper = function ($term) {
				return [
					'name' => $term->name,
					'slug' => $term->slug
				];
			};

			return array_map($mapper, wc_get_product_terms($product_id, $attribute['name'], array('fields' => 'all')));
		} elseif (isset($attribute['value'])) {
			return array_map(function ($option) {
				return [
					'name' => trim($option),
					'slug' => trim($option)
				];
			}, explode('|', $attribute['value']));
		}

		return array();
	}

	/**
	 * Get the attributes for a product or product variation.
	 *
	 * @return array
	 */
	protected function get_attributes() {
		$attributes = array();

		if ($this->product->is_type('variation')) {
			$_product = wc_get_product($this->product->get_parent_id());
			foreach ($this->product->get_variation_attributes() as $attribute_name => $attribute) {
				$name = str_replace('attribute_', '', $attribute_name);

				if (!$attribute) {
					continue;
				}

				// Taxonomy-based attributes are prefixed with `pa_`, otherwise simply `attribute_`.
				if (0 === strpos($attribute_name, 'attribute_pa_')) {
					$option_term = get_term_by('slug', $attribute, $name);
					$attributes[] = array(
						'id' => wc_attribute_taxonomy_id_by_name($name),
						'name' => $this->get_attribute_taxonomy_name($name, $_product),
						'option' => $option_term && !is_wp_error($option_term) ? $option_term->name : $attribute,
					);
				} else {
					$attributes[] = array(
						'id' => 0,
						'name' => $this->get_attribute_taxonomy_name($name, $_product),
						'option' => $attribute,
					);
				}
			}
		} else {
			foreach ($this->product->get_attributes() as $attribute) {
				$id = $attribute['is_taxonomy'] ? wc_attribute_taxonomy_id_by_name($attribute['name']) : 0;

				$attributes[] = array(
					'id' => $id,
					'name' => $this->get_attribute_taxonomy_name($attribute['name'], $this->product),
					'original_name' => $attribute['name'],
					'position' => (int) $attribute['position'],
					'visible' => (bool) $attribute['is_visible'],
					'default' => $this->get_attribute_default_value($attribute),
					'variation' => (bool) $attribute['is_variation'],
					'options' => $this->get_product_attribute_options($this->product->get_id(), $attribute),
				);
			}
		}

		return $attributes;
	}

	/**
	 * get attribute default value 
	 * @param int $attribute
	 * @return string
	 */
	public function get_attribute_default_value($attribute) {
		$defaults = $this->get_default_attributes();

		if (!empty($defaults)) {

			foreach ($defaults as $default) {

				if ($attribute['id'] === 0) {
					if ($default['name'] == $attribute['name']) {
						return $default['option'];
					}
				} else {
					if ($default['id'] == $attribute['id']) {
						return $default['option'];
					}
				}
			}
		}

		return '';
	}

	/**
	 * Get default attributes.
	 *
	 * @param WC_Product $product Product instance.
	 * @return array
	 */
	protected function get_default_attributes() {
		if (is_null($this->default_attributes)) {

			if ($this->product->is_type('variable')) {
				foreach (array_filter((array) $this->product->get_default_attributes(), 'strlen') as $key => $value) {
					if (0 === strpos($key, 'pa_')) {
						$this->default_attributes[] = array(
							'id' => wc_attribute_taxonomy_id_by_name($key),
							'name' => $this->get_attribute_taxonomy_name($key),
							'option' => $value,
						);
					} else {
						$this->default_attributes[] = array(
							'id' => 0,
							'name' => $this->get_attribute_taxonomy_name($key),
							'option' => $value,
						);
					}
				}
			} else {
				$this->default_attributes = array();
			}
		}

		return $this->default_attributes;
	}

	/**
	 * Get product attribute taxonomy name.
	 *
	 * @since  3.0.0
	 * @param  string     $slug    Taxonomy name.
	 * @return string
	 */
	protected function get_attribute_taxonomy_name($slug) {
		return wc_attribute_label($slug);
	}
}
