<?php

class Stacks_Woocommerce_Product_Data {

	/**
	 * Product Id
	 * @var integer
	 */
	protected $product_id;

	/**
	 * @var WC_Product
	 */
	public $product;

	/**
	 * Product Image
	 * @var null|int|false
	 */
	protected $product_image_id = null;

	public function __construct($id) {
		$this->product = wc_get_product($id);

		if (!$this->product) {
			return false;
		}

		$this->product_id = $id;
	}

	/**
	 * Return Current Instance Product ID
	 * @return mixed
	 */
	public function get_product_id() {
		return $this->product_id;
	}

	/**
	 * Respond to direct calls
	 *
	 * @param $id
	 * @return Stacks_Woocommerce_product_data
	 */
	public function __invoke($id) {
		return new self($id);
	}

	/**
	 * Get Post Featured Image Id
	 *
	 * @return int|string
	 */
	public function get_post_thumbnail_id() {
		if (is_null($this->product_image_id)) {
			$this->product_image_id = get_post_thumbnail_id($this->product_id);
		}
		return $this->product_image_id;
	}

	/**
	 * @return bool
	 */
	public function product_has_image() {
		$image_thumbnail = $this->get_post_thumbnail_id();
		return $image_thumbnail === false ? false : true;
	}

	/**
	 * Get Post thumbnail src
	 *
	 * @param null $requested_image_thumbnail_id
	 * @param string $image_size
	 * @return array|false
	 */
	public function get_post_thumbnail_image_src($requested_image_thumbnail_id = null, $image_size = 'full') {
		$image_thumbnail_id = is_null($requested_image_thumbnail_id) ? $this->get_post_thumbnail_id() : $requested_image_thumbnail_id;

		$image = wp_get_attachment_image_src($image_thumbnail_id, $image_size);

		return $image ? $image[0] : false;
	}

	/**
	 * Return Product Gallery Image Ids
	 * @return mixed
	 */
	public function get_product_gallery_image_ids() {
		return $this->product->get_gallery_image_ids();
	}

	/**
	 * Render Product Short Description
	 * Used within Templates to load Parts of Product Data
	 */
	public function render_product_short_description() {
		woocommerce_template_single_excerpt();
	}

	/**
	 * Render Product Add to Cart Button
	 * Used within Templates to load Parts of Product Data
	 */
	public function render_add_to_cart() {
		woocommerce_template_single_add_to_cart();
	}

	/**
	 * Display Short Description Template
	 */
	public function get_product_short_description_template() {
		woocommerce_template_single_excerpt();
	}

	/**
	 * Just Gets Product Short Description
	 * 
	 * @return string
	 */
	public function get_product_short_description_value() {
		return apply_filters('woocommerce_short_description', $this->product->get_short_description());
	}

	/**
	 * Return Product Price in Html Format
	 * 
	 * @return string
	 */
	public function get_product_price_html() {
		return $this->product->get_price_html();
	}

	/**
	 * Get Product price 
	 * @return array
	 */
	public function get_price() {
		$type = $this->product->get_type();

		if ($type == 'variable') {
			return $this->get_variable_price();
		} elseif ($type == 'simple') {
			return $this->get_simple_price();
		} else {
			return ['regular_price' => 0, 'sale_price' => 0];
		}
	}

	/**
	 * Get variable Product Price 
	 * 
	 * @return array
	 */
	private function get_variable_price() {

		return [
			'regular_price' =>  StacksWoocommerceDataFormating::format_number($this->product->get_variation_price('max')),
			'sale_price'    =>  StacksWoocommerceDataFormating::format_number($this->product->get_variation_price('min')),
		];
	}

	/**
	 * Get Simple Product Price 
	 * 
	 * @return array
	 */
	private function get_simple_price() {
		return [
			'regular_price' =>  StacksWoocommerceDataFormating::format_number($this->get_product_regular_price()),
			'sale_price'    =>  StacksWoocommerceDataFormating::format_number($this->get_product_sale_price())
		];
	}

	/**
	 * get product regular price 
	 * 
	 * @return float
	 */
	public function get_product_regular_price() {
		return $this->product->get_regular_price();
	}

	/**
	 * get product sale price 
	 * 
	 * @return float
	 */
	public function get_product_sale_price() {
		return $this->product->get_sale_price();
	}

	/**
	 * Check if product is on sale
	 * 
	 * @return boolean
	 */
	public function is_product_on_sale() {
		return $this->product->is_on_sale();
	}

	/**
	 * Get product average rating 
	 * 
	 * @return int
	 */
	public function get_product_average_rating() {
		return (int) $this->product->get_average_rating();
	}
}
