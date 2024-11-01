<?php

/**
 * This class holds shared functionality between cart and wishlist with high level of abstraction 
 */
abstract class Stacks_CartWishlistController extends Stacks_AbstractController {

    /**
     * return out of stock error response
     * 
     * @return array
     */
    protected function return_error_out_of_stock() {
        return [
            'success' => false,
            'errors' => [$this->get_out_of_stock_message()]
        ];
    }

    /**
     * organize cart content 
     * 
     * @param array $contents
     * 
     * @return array
     */
    public function organize_cart_contents($contents) {
        if (!empty($contents)) {
            $cart_items = [];

            foreach ($contents as $key => $item) {
                $item = $this->organize_cart_single_content($item);
                $item['hash_key'] = $key;
                $cart_items[] = $item;
            }

            return $cart_items;
        }

        return array();
    }

    /**
     * Get single item price 
     * 
     * ex: assume we have variable product with quantity 2 and many add-ons now we want to get single item price 
     * 
     * @param array $content
     * 
     * @return double|int
     */
    protected function get_single_cart_item_price_with_addons($content) {
        $product = apply_filters('woocommerce_cart_item_product', $content['data'], $content);

        if ('excl' === wc()->cart->get_tax_price_display_mode()) {
            $product_price = wc_get_price_excluding_tax($product);
        } else {
            $product_price = wc_get_price_including_tax($product);
        }

        return apply_filters('woocommerce_cart_product_price', StacksWoocommerceDataFormating::format_number($product_price), $product);
    }

    /**
     * Get Single Product Price without add-ons
     * @param array $content
     * @param Stacks_Woocommerce_Product_Data $product_obj
     * @return double|int
     */
    protected function get_single_cart_item_price_without_addons($content, $product_obj) {
        $product_id = $content['product_id'];

        if ($content['product_type'] == 'simple') {
            $product = new WC_Product_Simple($product_id);
        } else {
            $product = new WC_Product_Variation($content['variation_id']);
        }

        return StacksWoocommerceDataFormating::format_number($product->get_price());
    }

    /**
     * Organize single cart Item
     * 
     * @param array $content
     */
    protected function organize_cart_single_content($content) {
        $product_obj = new Stacks_Woocommerce_Product_Data($content['product_id']);

        $content['product']                = StacksWoocommerceDataFormating::format_product($content['product_id']);
        $content['product_type']            = $product_obj->product->get_type();
        $content['single_item_price_with_addons']    = $this->get_single_cart_item_price_with_addons($content);
        $content['single_item_price_without_addons']    = $this->get_single_cart_item_price_without_addons($content, $product_obj);

        if (!empty($content['variation'])) {
            $variations = array();

            foreach ($content['variation'] as $attr_name => $attr_value) {
                $cleaner_attr_name = str_replace('pa_', '', str_replace('attribute_', '', $attr_name));

                $variations[$cleaner_attr_name] = $this->get_attribute_name_by_slug($attr_name, $content['variation_id']);
            }

            unset($content['variation']);

            $content['variation'] = $variations;
        }

        return $content;
    }

    private function get_attribute_name_by_slug($attribute_name, $variation_id) {
        $variation = new WC_Product_Variation($variation_id);

        return $variation->get_attribute(str_replace('attribute_', '', $attribute_name));
    }

    //////////////////////////
    // Addons Manipulation //
    ////////////////////////
    /**
     * validate addon values do not have spaces and for further improvements 
     * @param array $values
     * @return array
     */
    public function validate_addon_values($values) {
        $updated_values = array();

        if (!empty($values)) {

            foreach ($values as $value) {
                $updated_values[] = str_replace(' ', '-', $value);
            }
        }

        return $updated_values;
    }

    /**
     * validate and sanitize add on values for all select fields
     * @param string|array $value
     * @param array $possible_values
     * @return string
     */
    public function validate_addons_values_Select($value, $possible_values) {
        $recorder = 0;

        $value = is_array($value) ? $value[0] : $value;

        if (!empty($possible_values)) {
            foreach ($possible_values as $p_value) {
                $recorder++;

                if (sanitize_title($p_value['label']) == sanitize_title($value)) {
                    return sanitize_title($value . '-' . $recorder);
                }
            }
        }

        return '';
    }


    /**
     * check if we have  add on submitted from api
     * @return void
     */
    public function check_addons($product_id) {
        if (!stacks_is_addon_plugin_activated()) {
            return;
        }

        $original_product_addons = get_product_addons($product_id);

        if (!empty($original_product_addons)) {

            foreach ($original_product_addons as $addon) {
                $field_submitted    = $addon['field-name'];
                $field_key          = 'addon-' . $addon['field-name'];

                if (!isset($_REQUEST[$field_submitted])) {
                    continue;
                }

                if ($addon['type'] == 'select') {
                    $values = $this->validate_addons_values_Select($_REQUEST[$field_submitted], $addon['options']);
                } else {
                    $values = $this->validate_addon_values($_REQUEST[$field_submitted], $addon['type']);
                }

                $_POST[$field_key] = $values;
            }
        }
    }

    //////////////////////////////
    // Variations Manipulation //
    ////////////////////////////
    /**
     * - get submitted attribute values and check if variation id exists just return it 
     *	 or try to find the variation has this attribute values
     * @param int $variation_id
     * @return boolean
     */
    public function validate_variation($product, $attributes, $variation_id) {
        $Stacks_ValidateVariationsService        = new Stacks_ValidateVariationsService();
        $submitted_attributes_values    = $Stacks_ValidateVariationsService->get_submitted_variations($product, $attributes);
        $variation_id = $Stacks_ValidateVariationsService->get_variation_id_for_submitted_attributes($product, $attributes, $submitted_attributes_values, $variation_id);
        return array('variation_id' => $variation_id, 'submitted_variations' => $submitted_attributes_values);
    }

    /**
     * validate submitted attributes
     * @param object $product
     * @param array $submitted_variation_values
     * @param array $variation_id
     */
    public function validate_submitted_variations($product, $submitted_variation_values, $variation_id) {
        $Stacks_ValidateVariationsService = new Stacks_ValidateVariationsService();
        return $Stacks_ValidateVariationsService->validate_submitted_attribute_values($product, $submitted_variation_values, $variation_id);
    }

    /**
     * when adding to cart or wishlist we must validate we have no errors
     * 
     * @return array
     */
    public function get_errors_variations_validation() {
        $error_notices = wc_get_notices('error');

        if (!empty($error_notices)) {
            wc_clear_notices();

            return array_map(
                function ($el) {
                    return str_replace('-', ' ', sanitize_title($el));
                },
                array_unique($error_notices)
            );
        }
        return array();
    }
}
