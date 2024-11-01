<?php

/**
 * Stacks_CategoriesModel Class 
 * 
 * Responsible for returning categories structured and send complete object to the controller 
 * and it's the controller mission to customize the return data 
 */
class Stacks_ProductsModel extends Stacks_AbstractModel {

    /**
     * @var integer 
     */
    protected $per_page = null;

    /**
     * @var offset 
     */
    protected $offset = null;

    /**
     * @var string 
     */
    protected $sort = null;

    /**
     * @var string 
     */
    protected $keyword = null;

    /**
     * @var string 
     */
    protected $order = null;

    /**
     * range ['min'=> 2, "max" => 5 ]
     * @var array 
     */
    protected $price_range = null;

    /**
     * categories for the products you want to search about
     * @var type 
     */
    protected $categories = null;

    /**
     * taxonomies for the products you want to search about
     * @var type 
     */
    protected $custom_taxonomies = null;

    /**
     * Calories range 
     * @var array
     */
    protected $calories_range = null;

    /**
     * Featured Image Size
     * @var string
     */
    protected $featured_image_size = 'avaris-iconbox-large';

    /**
     * @var array
     */
    protected $categories_cache = array();

    /**
     * @var boolean
     */
    protected $sale = null;

    /**
     * @var boolean
     */
    protected $sort_rating = null;

    /**
     * set sort by rating to true to activate it
     * 
     * 
     * @return $this
     */
    public function set_sort_by_rating() {
        $this->sort_rating = true;

        return $this;
    }

    /**
     * Set Sale Filter
     * 
     * @param string $sale
     * @return $this
     */
    public function set_sale_filter($sale) {
        $this->sale = $sale;

        return $this;
    }


    /**
     * Set Keyword Filter
     * 
     * @param string $keyword
     * @return $this
     */
    public function set_keyword_filter($keyword) {
        $this->keyword = $keyword;

        return $this;
    }

    /**
     * get Keyword Filter
     * 
     * @return String
     */
    public function get_keyword_filter() {
        return $this->keyword;
    }

    /**
     * Get Sale Filter
     * 
     * @param string $sale
     * @return $this
     */
    public function get_sale_filter() {
        return $this->sale;
    }

    /**
     * Set Featured Image Size
     * 
     * @param string $featured_image_size
     * @return $this
     */
    public function set_featured_image_size($featured_image_size) {
        $this->featured_image_size = $featured_image_size;

        return $this;
    }

    /**
     * Returns Featured Image Size
     * 
     * 
     * @return string
     */
    public function get_featured_image_size() {
        return $this->featured_image_size;
    }

    /**
     * get calories array
     * 
     * 
     * @return array|null
     */
    public function get_calories() {
        return $this->calories_range;
    }

    /**
     * set calories format [ upper: value, lower: value ]
     * 
     * @param array $calories
     * @return $this
     */
    public function set_calories($calories) {
        $this->calories_range = [];
        $this->calories_range['upper'] = absint($calories['upper']);
        $this->calories_range['lower'] = absint($calories['lower']);


        return $this;
    }

    /**
     * Set sorting algorithm
     * 
     * 
     * @param string $sort
     */
    public function set_sort($sort) {
        $this->sort = $sort;
        return $this;
    }

    /**
     * get sorting algorithm applied
     * 
     * 
     * @return string
     */
    public function get_sort() {
        return $this->sort;
    }

    /**
     * Get the order 
     * 
     * 
     * @return string
     */
    public function get_order() {
        return $this->order;
    }

    /**
     * add order method 
     * 
     * @param string $order
     * @return $this
     */
    public function set_order($order) {
        $this->order = $order;
        return $this;
    }

    /**
     * Get the price range 
     * 
     * 
     * @return array
     */
    public function get_price_range() {
        return $this->price_range;
    }

    /**
     * Get Categories
     * 
     * 
     * @return array|null
     */
    public function get_categories() {
        return $this->categories;
    }

    /**
     * Get Taxonomies
     * 
     * @return array|null
     */
    public function get_custom_taxonomies() {
        return $this->custom_taxonomies;
    }

    /**
     * Add the number of products per page 
     * 
     * @param integer $per_page
     * @return $this
     */
    public function set_per_page($per_page) {
        $this->per_page = $per_page;


        return $this;
    }

    /**
     * Get number of products per page 
     * 
     * 
     * @return integer
     */
    public function get_per_page() {
        return $this->per_page;
    }


    /**
     * Add the offset
     * 
     * @param integer $offset
     * @return $this
     */

    public function set_offset($offset) {
        $this->offset = $offset;
        return $this;
    }

    /**
     * Get query offset
     * 
     * 
     * @return integer
     */
    public function get_offset() {
        return $this->offset;
    }

    /**
     * Set Price range 
     * 
     * @param array $price_range
     * @return $this
     */
    public function set_price_range($price_range) {
        $this->price_range = [];
        $this->price_range['upper'] = absint($price_range['upper']);
        $this->price_range['lower'] = absint($price_range['lower']);
        return $this;
    }

    /**
     * Set Categories
     * 
     * @param array $categories
     * @return $this
     */
    public function set_categories($categories) {
        $this->categories = $categories;

        return $this;
    }

    /**
     * Set Custom taxonomies
     * 
     * @param array $custom_taxonomies
     * @return $this
     */
    public function set_custom_taxonomies($custom_taxonomies) {
        $this->custom_taxonomies = $custom_taxonomies;

        return $this;
    }

    /**
     * Checks whether parameter is applied or not 
     * 
     * @param string $parameter
     * @return boolean
     */
    private function is_parameter_applied($parameter) {
        return is_null($this->$parameter) ? false : true;
    }

    /**
     * get popular products 
     * 
     * @return array
     */
    public function get_popular_products($popular_products_num = 5) {
        $products_model = new self();

        return $products_model->set_sort_by_rating()->set_per_page($popular_products_num)->get_products();
    }

    /**
     * Get Products Categories
     * 
     * @param callable $format_callback
     * @param type $children_name
     * @return array
     */
    public function get_products() {

        if (!$this->get_offset()) {
            $posts_per_page = -1;
            $offset = 0;
        } else {
            $offset = $this->get_offset() - 1;
            $posts_per_page = 20;
            $offset = $offset * $posts_per_page;
        }

        $args = [
            'post_type'             => 'product',
            'post_status'           => 'publish',
            'ignore_sticky_posts'   => 1,
            'posts_per_page'        => $posts_per_page,
            'offset'                   => $offset,
            'tax_query'             => [
                'relation' => 'AND'
            ],
            'meta_query'            => [
                'relation' => 'AND'
            ]
        ];

        $this
            ->apply_custom_taxonomies($args)
            ->apply_categories($args)
            ->apply_price_range($args)
            ->apply_calories_range($args)
            ->apply_sorting($args)
            ->apply_per_page($args)
            ->apply_keyword_search($args)

            // must be the last one because it will erase all filters before it  
            ->apply_sale_filter($args)
            ->apply_sorting_by_rating($args)
            ->apply_instock_filter($args);

        $args = apply_filters('api_before_get_products_model', $args);

        $products = new WP_Query($args);

        return array_map(function ($product) {
            return $this->apply_format_callback_item($product);
        }, $products->get_posts());
    }

    /**
     * apply keyword search 
     * 
     * @param array $args
     * @return $this
     */
    private function apply_keyword_search(&$args) {
        if ($this->is_parameter_applied('keyword')) {
            $args['s'] = apply_filters('product_model_before_applying_keyword', $this->get_keyword_filter());
        }
        return $this;
    }

    /**
     * add limit to number of posts per page returned 
     * 
     * @param array $args
     * @return $this
     */
    private function apply_per_page(&$args) {
        if ($this->is_parameter_applied('per_page')) {
            $args['posts_per_page'] = apply_filters('product_model_before_applying_paging', $this->get_per_page());
        }
        return $this;
    }

    /**
     * add filter to get only on sale products 
     * @param array $args
     * @return $this
     */
    private function apply_sale_filter(&$args) {
        if ($this->is_parameter_applied('sale') && $this->get_sale_filter() == 'true') {
            $args['meta_query'] = WC()->query->get_meta_query();
            $args['post__in']   = apply_filters('product_model_before_applying_sale', array_merge(array(0), wc_get_product_ids_on_sale()));
        }
        return $this;
    }

    /**
     * Apply sorting using price and date 
     * @param array $args
     * @return $this
     */
    private function apply_sorting(&$args) {
        if ($this->is_parameter_applied('sort')) {
            if ($this->get_sort() == 'date') {
                $this->apply_date_sorting($args);
            } elseif ($this->get_sort() == 'price') {
                $this->apply_price_sorting($args);
            }

            // this is shared between both price and date 
            $args['order'] = $this->get_order();
        }
        return $this;
    }

    /**
     * apply sorting by rating 
     * @param array $args
     */
    private function apply_sorting_by_rating(&$args) {
        if ($this->is_parameter_applied('sort_rating')) {
            $args['meta_key']   = '_wc_average_rating';
            $args['orderby']    = 'meta_value_num';
            $args['order']      = 'DESC';
            $args['meta_query'] = WC()->query->get_meta_query();
            $args['tax_query']  = WC()->query->get_tax_query();
        }

        return $this;
    }

    /**
     * apply sorting by date 
     * @param array $args
     */
    private function apply_date_sorting(&$args) {
        $args['orderby'] = 'date';
    }

    /**
     * apply sorting by Price 
     * @param array $args
     */
    private function apply_price_sorting(&$args) {
        $args['orderby'] = 'meta_value_num';
        $args['meta_key'] = '_price';
        $args['meta_query'][] = array(
            'key'       => '_price',
            'value'     => '',
            'compare'   => '!='
        );
    }

    /**
     * apply sorting by Price 
     * @param array $args
     */
    private function apply_instock_filter(&$args) {
        $args['meta_query'][] = array(
            'key'       => '_stock_status',
            'value'     => 'instock',
            'compare'   => '='
        );

        return $this;
    }


    /**
     * Add price range if sent to limit products
     * @param array $args
     * @return $this
     */
    private function apply_price_range(&$args) {
        // do not process if ingredients is not activated 
        if ($this->is_parameter_applied('price_range')) {

            $price_range = $this->get_price_range();
            $upper = $price_range['upper'];
            $lower = $price_range['lower'];

            $args['meta_query'][] = array(
                'key' => '_price',
                'value' => $upper,
                'type'    => 'numeric',
                'compare' => '<='
            );

            $args['meta_query'][] = array(
                'key' => '_price',
                'value' => $lower,
                'type'    => 'numeric',
                'compare' => '>='
            );
        }
        return $this;
    }

    /**
     * Add calories range if sent to limit products
     * @param array $args
     * @return $this
     */
    private function apply_calories_range(&$args) {
        // do not process if ingredients is not activated 
        if ($this->is_parameter_applied('calories_range')) {

            $callories_range = $this->get_calories();
            $upper = $callories_range['upper'];
            $lower = $callories_range['lower'];

            $args['meta_query'][] = array(
                'key' => 'calories_value',
                'value' => $upper,
                'type'    => 'numeric',
                'compare' => '<='
            );

            $args['meta_query'][] = array(
                'key' => 'calories_value',
                'value' => $lower,
                'type'    => 'numeric',
                'compare' => '>='
            );
        }
        return $this;
    }


    /**
     * Ensure that each element inside id's list is valid 
     * @param type $ids_list
     * @return type
     */
    private function filter_ids_list($ids_list) {

        if (!empty($ids_list)) {
            return array_filter($ids_list, function ($allergien) {
                if (is_null($allergien) || $allergien === null) {
                    return false;
                }
                return true;
            });
        }
        return array();
    }

    /**
     * Add categories to wp_query arguments 
     * @param array $args
     */
    private function apply_categories(&$args) {
        $param_name     = 'categories';
        $tax_slug       = 'product_cat';
        $cats           = apply_filters('product_model_before_applying_categories', $this->get_categories());

        $result = $this->prepare_taxonomy_arguments($param_name, $tax_slug, $cats);

        if (is_array($result)) {
            $args['tax_query'][] = $result;
        }

        return $this;
    }

    /**
     * Add categories to wp_query arguments 
     * 
     * @param array $args
     */
    private function apply_custom_taxonomies(&$args) {
        $param_name     = 'custom_taxonomies';
        $custom_taxonomies = apply_filters('product_model_before_applying_custom_taxonomies', $this->get_custom_taxonomies());

        if (!empty($custom_taxonomies)) {
            foreach ($custom_taxonomies as $taxonomy_slug => $taxonomy_id) {
                $result = $this->prepare_taxonomy_arguments($param_name, $taxonomy_slug, [$taxonomy_id]);

                if (is_array($result)) {
                    $args['tax_query'][] = $result;
                }
            }
        }

        return $this;
    }

    /**
     * 
     * @param type $param_name
     * @param type $tax_slug
     * @param type $taxonomies
     * @return boolean
     */
    private function prepare_taxonomy_arguments($param_name, $tax_slug, $taxonomies) {
        if ($this->is_parameter_applied($param_name)) {

            $cats = array_values($this->filter_taxonomy($taxonomies, $tax_slug));

            if (!empty($cats)) {
                return array(
                    'taxonomy'  => $tax_slug,
                    'field'     => 'term_id',
                    'terms'     => $cats
                );
            }
        }
        return false;
    }

    /**
     * Filter taxonomies to validate that each id is valid 
     * 
     * @param array $taxonomies
     */
    private function filter_taxonomy($taxonomies, $taxonomy_slug) {

        return array_filter(

            $taxonomies,

            function ($taxonomy) use ($taxonomy_slug) {

                $object = $this->get_taxonomy_from_cache($taxonomy, $taxonomy_slug);

                if ($object instanceof \WP_Error || is_null($object) || !$object) {
                    return false;
                }
                return true;
            }
        );
    }

    /**
     * Implementing cache for categories to reduce calling database 
     * 
     * @param int $id
     * @return object
     */
    private function get_taxonomy_from_cache($id, $type) {
        if (!isset($this->categories_cache[$type][$id])) {
            $this->categories_cache[$type][$id] = get_term_by('term_taxonomy_id', $id, $type);
        }

        return $this->categories_cache[$type][$id];
    }
}
