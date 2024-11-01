<?php

/**
 * Plates Product Formatter Extension
 */
class Stacks_ProductFormatting extends Stacks_PlatesFormatter {
    /**
     * The size of the Featured Image
     * 
     * @var string 
     */
    protected $featured_image_size;

    /**
     * set featured image size 
     * 
     * @param string $size
     * @return $this
     */
    public function set_featured_image_size($size) {
        $this->featured_image_size = $size;

        return $this;
    }

    /**
     * check if id provided is for valid product
     * 
     * @param integer $id
     * @return boolean
     */
    protected function is_valid_product($id) {
        if (wc_get_product($id)) {
            return $id;
        }
        return false;
    }

    /**
     * Format Group of products or single product object or just integer value
     * 
     * @param array $products
     * @return array
     */
    public function format() {
        if (!is_array($this->items)) {
            $id = $this->get_id($this->items);

            return !$id ? false : $this->format_single_product($id);
        } else {
            foreach ($this->items as $index => $product) {

                $id = $this->get_id($product);

                if (!$id) {
                    unset($this->items[$index]);
                    continue;
                }
                try {
                    $oProduct = new WC_Product($id);
                    if ($oProduct->exists()) {
                        $this->items[$index] = $this->format_single_product($id);
                    }
                } catch (Exception $ex) {
                }
            }

            return $this->items;
        }
    }

    /**
     * Format product name 
     * 
     * @param string $title
     * 
     * @return string
     */
    protected function format_product_name($title) {
        $ending_dots = Stacks_ContentSettings::get_formatting_title_ending_dots();

        $allowed_length = Stacks_ContentSettings::get_formatting_title_num_chars();

        $allowed_length = $allowed_length == '' ? 18 : (int) $allowed_length;

        return strlen($title) > $allowed_length ? mb_substr($title, 0, $allowed_length) . $ending_dots : $title;
    }


    /**
     * Format Product
     * @param int|object $product
     * @param string $featured_image_size
     * @return boolean
     */
    protected function format_single_product($id) {
        // config 
        $productObj = new Stacks_Woocommerce_Product_Data($id);
        $product    = get_post($id);

        if (empty($productObj->product)) {
            return false;
        }
        // var_dump($productObj->product);
        // price naming is different between simple and variable product
        $regular_price    = $productObj->product->get_type() == 'simple' ? 'price' : 'max_price';
        $sale_price    = $productObj->product->get_type() == 'simple' ? 'sale_price' : 'min_price';
        // build 
        $price = $productObj->get_price();
        $date  = (array) $productObj->product->get_date_created();
        $availability = $productObj->product->get_availability();

        $data = [];
        $data['id'] = $product->ID;
        $data['title'] = $this->format_product_name($product->post_title);
        $data['featured_image'] = $this->get_featured_image($productObj);
        $data['offer'] = $productObj->is_product_on_sale();
        $data['post_date'] = $date['date'];
        $data['post_type'] = 'product';
        $data['type'] = $productObj->product->get_type();
        $data['rating'] = $productObj->get_product_average_rating();
        $data[$regular_price] = $price['regular_price'];
        $data[$sale_price] = $price['sale_price'];
        $data['stock_status'] = $productObj->product->get_stock_status();
        $data['manage_stock'] = $productObj->product->managing_stock();
        $data['stock_quantity'] = $productObj->product->get_stock_quantity();
        $data['in_stock'] = $productObj->product->is_in_stock();
        if ($data['type'] == 'variable') {
            $data['min_price'] = $productObj->product->get_variation_regular_price();
            $data['min_sale_price'] = $productObj->product->get_variation_sale_price();
            $data['max_price'] = $productObj->product->get_variation_regular_price('max', true);
            $data['max_sale_price'] = $productObj->product->get_variation_sale_price('max', true);
        }

        $data['backorders_allowed'] = $productObj->product->backorders_allowed();
        $data['availability_html'] = apply_filters('woocommerce_stock_html', '<p class="stock ' . esc_attr($availability['class']) . '">' . esc_html($availability['availability']) . '</p>', $availability['availability']);
        $data['max_qty'] = $productObj->product->backorders_allowed() ? null : $productObj->product->get_stock_quantity();

        return apply_filters('avaris-formatting-product', $data);
    }

    /**
     * Get Product Featured Image
     * 
     * @param Stacks_Woocommerce_Product_Data
     */
    public function get_featured_image($productObj) {
        $full = "full";
        $stacks_iconbox_large = "avaris-iconbox-large";
        $stacks_blog_spotlight_medium = "avaris-blog-spotlight-medium";

        if (!has_post_thumbnail($productObj->get_product_id())) {
            return false;
        }

        $featured_image = new stdClass();
        $featured_image->$full = $productObj->get_post_thumbnail_image_src(null, $full);
        $featured_image->$stacks_iconbox_large = $productObj->get_post_thumbnail_image_src(null, $stacks_iconbox_large);
        $featured_image->$stacks_blog_spotlight_medium = $productObj->get_post_thumbnail_image_src(null, $stacks_blog_spotlight_medium);

        return $featured_image;
    }

    /**
     * Format Calories 
     * @param array $calories
     * @return string
     */
    public function format_calories($calories) {
        if (
            (!$calories['calories_value'] ||  $calories['calories_value'] == '') &&
            (!$calories['calories_name'] || $calories['calories_name'] == '')
        ) {
            return '';
        }

        return sprintf('%s %s', $calories['calories_value'], $calories['calories_name']);
    }

    /**
     * 
     * @param object $object
     */
    public function format_remove_unused_parmeters(&$object) {
        $remove_items = [
            'post_author',
            'post_content',
            'post_title',
            'post_status',
            'comment_status',
            'post_modified',
            'to_ping',
            'pinged',
            'post_content_filtered',
            'post_modified_gmt',
            'post_parent',
            'menu_order',
            'post_mime_type',
            'guid',
            'filter',
            'comment_count',
            'post_name',
            'post_password',
            'ping_status',
            'post_excerpt',
            'post_date_gmt',
            'ID'
        ];

        foreach ($remove_items as $item) {
            unset($object->$item);
        }
    }
}
