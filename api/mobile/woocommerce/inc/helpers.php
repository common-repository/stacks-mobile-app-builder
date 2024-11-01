<?php

/**
 * This File is intended to hold all functions that exists in woo-commerce inc folder
 */

/**
 * Check if wordpress plugins file loaded if not load it
 */
if (!function_exists('_stacks_check_plugins_file_loaded')) {
    function _stacks_check_plugins_file_loaded() {
        if (!function_exists("is_plugin_active")) {
            include_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }
    }
}


/**
 * Get Product Data Class Instance
 *
 * @param $id
 * @return Stacks_Woocommerce_Product_Data
 */
if (!function_exists('_stacks_woocommerce_product_data')) {
    function _stacks_woocommerce_product_data($id) {
        return new Stacks_Woocommerce_Product_Data($id);
    }
}


if (!function_exists('_stacks_product_category_additional_fields_is_active')) {
    function _stacks_product_category_additional_fields_is_active() {
        return class_exists('Stacks_ProductCategoryAdditionalFields');
    }
}

/**
 * get term product category image id 
 * 
 * @param int $term_id
 * @return int|boolean
 */
if (!function_exists('_stacks_get_product_category_image_id')) {
    function _stacks_get_product_category_image_id($term_id) {
        if (_stacks_product_category_additional_fields_is_active()) {
            return Stacks_ProductCategoryAdditionalFields::get_term_background_image_id($term_id);
        }
        return false;
    }
}

/**
 * get product category image source
 * 
 * @param int $term_id
 * @param string $size
 * @return string
 */
if (!function_exists('_stacks_product_category_image_src')) {
    function _stacks_product_category_image_src($term_id, $size) {
        if (_stacks_product_category_additional_fields_is_active()) {
            $image_id = _stacks_get_product_category_image_id($term_id);

            return Stacks_ProductCategoryAdditionalFields::get_image_src_by_id($image_id, $size);
        }
        return false;
    }
}

/**
 * OVERRIDING THE WOOCOMMERCE wc_get_gallery_image_html FUNCTION
 * Get HTML for a gallery image.
 *
 * Woocommerce_gallery_thumbnail_size, woocommerce_gallery_image_size and woocommerce_gallery_full_size accept name based image sizes, or an array of width/height values.
 *
 * @since 3.3.2
 * @param int  $attachment_id Attachment ID.
 * @param bool $main_image Is this the main image or a thumbnail?.
 * @return string
 */
if (!function_exists('_stacks_wc_get_gallery_image_html')) {
    function _stacks_wc_get_gallery_image_html($attachment_id, $main_image = false) {
        $flexslider        = (bool) apply_filters('woocommerce_single_product_flexslider_enabled', get_theme_support('wc-product-gallery-slider'));
        // $gallery_thumbnail = wc_get_image_size( 'gallery_thumbnail' );
        // $thumbnail_size    = apply_filters( 'woocommerce_gallery_thumbnail_size', array( $gallery_thumbnail['width'], $gallery_thumbnail['height'] ) );
        $thumbnail_size    = 'shop_single';
        $image_size        = apply_filters('woocommerce_gallery_image_size', $flexslider || $main_image ? 'woocommerce_single' : $thumbnail_size);
        $full_size         = apply_filters('woocommerce_gallery_full_size', apply_filters('woocommerce_product_thumbnails_large_size', 'full'));
        $thumbnail_src     = wp_get_attachment_image_src($attachment_id, $thumbnail_size);
        $full_src          = wp_get_attachment_image_src($attachment_id, $full_size);
        $alt_text          = trim(wp_strip_all_tags(get_post_meta($attachment_id, '_wp_attachment_image_alt', true)));
        $image             = wp_get_attachment_image(
            $attachment_id,
            $image_size,
            false,
            apply_filters(
                'woocommerce_gallery_image_html_attachment_image_params',
                array(
                    'title'                   => _wp_specialchars(get_post_field('post_title', $attachment_id), ENT_QUOTES, 'UTF-8', true),
                    'data-caption'            => _wp_specialchars(get_post_field('post_excerpt', $attachment_id), ENT_QUOTES, 'UTF-8', true),
                    'data-src'                => esc_url($full_src[0]),
                    'data-large_image'        => esc_url($full_src[0]),
                    'data-large_image_width'  => esc_attr($full_src[1]),
                    'data-large_image_height' => esc_attr($full_src[2]),
                    'class'                   => esc_attr($main_image ? 'wp-post-image' : ''),
                ),
                $attachment_id,
                $image_size,
                $main_image
            )
        );

        return '<div data-thumb="' . esc_url($thumbnail_src[0]) . '" data-thumb-alt="' . esc_attr($alt_text) . '" class="woocommerce-product-gallery__image"><a>' . $image . '</a></div>';
    }
}
