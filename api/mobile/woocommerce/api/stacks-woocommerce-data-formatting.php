<?php

/**
 * Plates Woo-commerce API Main Formatting Class 
 */
class StacksWoocommerceDataFormating {
    /**
     * format log 
     * 
     * @param array $items
     * @return array
     */
    public static function format_log($items) {
        return (new Stacks_LogFormatting())->set_items($items)->format();
    }

    /**
     * Format Product|products 
     * 
     * @param int|array|object $items
     * @param string $featured_image_size
     * @return array
     */
    public static function format_product($items, $featured_image_size = 'avaris-iconbox-large') {
        return (new Stacks_ProductFormatting())->set_featured_image_size($featured_image_size)->set_items($items)->format();
    }

    /**
     * Format Single Product get complete details about single product
     * 
     * @param int|array|object $product_id
     * @return array
     */
    public static function format_single_product($product_id, $featured_image_size = 'full') {
        return (new Stacks_SingleProductFormatting())->set_featured_image_size($featured_image_size)->set_items($product_id)->format();
    }

    /**
     * Format Add ons
     * 
     * @param array $items
     * @return array
     */
    public static function format_addons($items) {
        return (new Stacks_AddonsFormatting())->set_items($items)->format();
    }

    /**
     * Format array of categories or one category 
     * 
     * @param array $items
     * @return array
     */
    public static function format_categories($items) {
        if (is_array($items)) {

            if (!empty($items)) {

                return array_map([self::class, 'format_category'], $items);
            }

            return [];
        } else {

            return self::format_category($items);
        }
    }

    /**
     * Format Single item and Extract values needed
     * 
     * @param WP_Term $item
     * @return array
     */
    private static function format_category($item) {
        $thumbnail_id = get_term_meta($item->term_id, 'thumbnail_id', true);

        $image_parameters = wp_get_attachment_image_src($thumbnail_id, 'large');

        // just return the source
        if (is_array($image_parameters)) {
            $image_parameters = $image_parameters[0];
        }

        // Get subcategories
        $args = array('parent' => $item->term_id);
        $subcategories = get_terms('product_cat', $args);

        return [
            'key'    => $item->term_id,
            'name'    => $item->name,
            'count'    => $item->count,
            'bg'    => _stacks_product_category_image_src($item->term_id, 'avaris-blog-spotlight-medium'),
            'thumbnail' => $image_parameters,
            'children' => $subcategories
        ];
    }

    /**
     * Format Product Variations
     * 
     * @param array $items
     * @return array
     */
    public static function format_product_variations($items) {
        return (new Stacks_VariationsFormatting())->set_items($items)->format();
    }

    /**
     * format orders 
     * 
     * @param array $items
     * @return array
     */
    public static function format_orders($items) {
        return (new Stacks_OrdersFormatting($items))->format();
    }

    /**
     * format Shipping Methods 
     * 
     * @param array $items
     * @return array
     */
    public static function format_shipping_methods($items) {
        return (new Stacks_ShippingMethodFormatting($items))->format();
    }

    /**
     * Format Number in decimals 
     * 
     * @param integer|double $number
     * @return double
     */
    public static function format_number($number) {
        $float_number = (float) $number;

        return number_format($float_number, wc_get_price_decimals());
    }
}
