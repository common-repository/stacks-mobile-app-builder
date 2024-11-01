<?php

class Stacks_HomePageController extends Stacks_AbstractController {

    /**
     * @var string
     */
    protected $rest_api = '/home';

    /**
     * @var int
     */
    const POPULAR_PRODUCTS_NUMBER   = 5;

    /**
     * @var int
     */
    const NEW_PRODUCTS_NUMBER        = 5;

    /**
     * Register Routes 
     */
    public function register_routes() {
        register_rest_route($this->get_api_endpoint(), $this->rest_api, [ // V3
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_home_details'),
                'permission_callback' => array($this, 'get_items_permissions_check'),
                'args'                => $this->get_collection_params_get()
            ],
            'schema' => array($this, 'get_public_item_schema'),
        ]);
    }

    /**
     * return new instance from products model instead of having the same instance 
     * 
     * @return \Stacks_ProductsModel
     */
    public function get_product_model_instance() {
        return new Stacks_ProductsModel();
    }

    /**
     * Extracts widgets from home page elements and adds data to them
     * @param type $page_elements
     * @param type $page_widgets
     * @return type $page_elements
     */
    public function add_data_to_home_widgets($page_elements, $page_widgets = []) {

        if (!empty($page_elements)) {

            foreach ($page_elements as $key => $element) {

                if (!empty($element->elements)) {

                    $page_widgets = $this->add_data_to_home_widgets($element->elements, $page_widgets); // Recursion

                } else { //this element doesn't have elements => this element is a widget

                    // Return products array in mobile-products widget
                    if (is_stacks_woocommerce_active() && $element->type == 'products') {

                        $limit = !empty($element->data->limit) ? $element->data->limit : 1;
                        $args = array(
                            'limit' => $limit,
                            'status' => 'publish',
                            'order' => $element->data->query_order,
                            'orderby' => $element->data->query_orderby,
                            'lang' => substr(get_locale(), 0, strpos(get_locale(), "_")) ? substr(get_locale(), 0, strpos(get_locale(), "_")) : get_locale()
                        );

                        // Source 
                        switch ($element->data->query_post_type) {
                            case 'product': // Latest Products
                                break;
                            case 'sale': // Sale
                                $args['include'] = wc_get_product_ids_on_sale();
                                break;
                            case 'featured': // Featured
                                $args['featured'] = true;
                                break;
                            case 'by_category': // By category
                                $categories_ids = $element->data->products_categories;
                                if (!empty($categories_ids)) {
                                    $categories_slugs = [];

                                    // Get categories slugs to use in products query
                                    if (is_string($categories_ids)) {
                                        // Currently the builder is limited to single category id only
                                        if (Stacks_PolylangIntegration::is_installed()) {
                                            $categories_ids = pll_get_term($categories_ids,  get_locale());
                                        }
                                        $category = get_term_by('id', $categories_ids, 'product_cat', 'ARRAY_A');
                                        $categories_slugs[] = $category['slug'];
                                    } else {
                                        foreach ($categories_ids as $id) {
                                            if (Stacks_PolylangIntegration::is_installed()) {
                                                $category = pll_get_term($id, get_locale());
                                            }
                                            $category = get_term_by('id', $category, 'product_cat', 'ARRAY_A');
                                            $categories_slugs[] = $category['slug'];
                                        }
                                    }
                                    $args['category'] = $categories_slugs;
                                }
                                break;
                        }

                        $products = wc_get_products($args);

                        // Orderby price
                        if ($element->data->query_orderby == 'price') {
                            $products  = wc_products_array_orderby($products, 'price', $element->data->query_order);
                        }

                        // 2nd Orderby for latest products
                        if ($element->data->query_post_type == 'product') {
                            $products  = wc_products_array_orderby($products, $element->data->query_orderby, $element->data->query_order);
                        }

                        $products_data = [];
                        foreach ($products as $product) {
                            $image_id  = $product->get_image_id();
                            $featured_image = wp_get_attachment_image_url($image_id, 'thumbnail');

                            $product_data = $product->get_data();
                            $product_data['type'] = $product->get_type();
                            if ($product_data['type'] == 'variable') {
                                $product_data['min_price'] = $product->get_variation_regular_price();
                                $product_data['min_sale_price'] = $product->get_variation_sale_price();
                                $product_data['max_price'] = $product->get_variation_regular_price('max', true);
                                $product_data['max_sale_price'] = $product->get_variation_sale_price('max', true);
                            }
                            $product_data['featured_image'] = $featured_image;
                            $products_data[] = $product_data;
                        }

                        $products = StacksWoocommerceDataFormating::format_product(apply_filters('before_getting_slider_products', $products_data));

                        $element->products = $products;
                    }

                    if ( $element->type == 'posts') {

                        $args = (array) $element->data;
                        $args['lang'] = substr(get_locale(), 0, strpos(get_locale(), "_")) ? substr(get_locale(), 0, strpos(get_locale(), "_")) : get_locale();
                        if($args['post_type'] == 'pmpro_course' && $args['source'] == 'by_category') {
                            $args['tax_query'] = [
                                [
                                'taxonomy' =>  'pmpro_course_category',
                                'field' => 'term_id', 
                                'terms' => $args['category'], /// Where term_id of Term 1 is "1".
                                ]
                            ];
                        }
                        $wp_query= null;
                        $wp_query = new WP_Query();
                        $posts = $wp_query->query($args);
                        $formatted_posts = [];
                        foreach ($posts as $key => $post) {
                            $formatted_posts[$key]['ID'] = $post->ID;
                            $formatted_posts[$key]['guid'] = $post->guid;
                            $formatted_posts[$key]['post_author'] = $post->post_author;
                            $formatted_posts[$key]['post_content'] = $post->post_content;
                            $formatted_posts[$key]['post_date'] = $post->post_date;
                            $formatted_posts[$key]['post_modified'] = $post->post_modified;
                            $formatted_posts[$key]['post_name'] = $post->post_name;
                            $formatted_posts[$key]['post_status'] = $post->post_status;
                            $formatted_posts[$key]['post_title'] = $post->post_title;
                            $formatted_posts[$key]['post_image'] = get_the_post_thumbnail_url($post, 'large');
                        }
                        $element->posts = $formatted_posts;
                    }

                    // Return categories array in mobile-categories widget
                    if (is_stacks_woocommerce_active() && $element->type == 'categories') {
                        $limit = !empty($element->data->limit) ? $element->data->limit : 1;
                        $cat_args = array(
                            'taxonomy'    => 'product_cat',
                            'orderby'    => $element->data->orderby,
                            'order'      => $element->data->order,
                            'hide_empty' => $element->data->hide_empty,
                            'number' => $limit,
                            'lang' => get_locale(),
                        );

                        if ($element->data->source == 'by_id' && !empty($element->data->categories)) {
                            $cat_args['include'] = $element->data->categories;
                        }
                        if ($element->data->source == 'by_parent' && !empty($element->data->parent)) {
                            $cat_args['parent'] = $element->data->parent;
                        }
                        $product_categories = get_terms('product_cat', $cat_args);

                        $categories_data = [];
                        foreach ($product_categories as $category) {
                            $category_img  = get_term_link($category);

                            // Get the thumbnail id using the queried category term_id
                            $thumbnail_id = get_term_meta($category->term_id, 'thumbnail_id', true);

                            // Get the image URL
                            $category_img = wp_get_attachment_url($thumbnail_id);

                            $category_data = (array) $category;
                            $category_data['thumbnail'] = $category_img;
                            $categories_data[] = $category_data;
                        }
                        $element->categories = apply_filters('stacks_rest_api_categories_modify', $categories_data);
                    }

                    if ($element->widgetType == 'mobile-text-editor') {
                        $element->settings->editor = stripslashes($element->settings->editor);
                    }
                }
            }
        }
        return $page_elements;
    }

    /**
     * respond to request and return home details 
     * 
     * @param object $request
     * 
     * @return array
     */
    public function get_home_details($request) {

        if ($GLOBALS['builder_api']->stacks_get_multisite_option('home_app')) {
            $home_response_items = [
                'builder_data' => $GLOBALS['builder_api']->stacks_get_multisite_option('home_app')
            ];

            $home_response_items['builder_data'] = $this->add_data_to_home_widgets($home_response_items['builder_data'], $page_widgets = []);

            //            return  'v3::' . $this->v1_v2_function_suffix . '::';
            return $this->return_success_response(apply_filters('home_items_before_success_response', $home_response_items));
        }
        $home_sections_sorting = Stacks_ContentSettings::get_home_sorting();

        $slider_settings = $popular_products = $recent_products = [];

        foreach ($home_sections_sorting as $section) {
            switch ($section['id']) {
                case 'appSlider':
                    $slider_settings = $section;
                    break;
                case 'appCategories':
                    $categories_settings = $section;
                    break;
                case 'appRecent':
                    $recent_settings = $section;
                    break;
            }
        }
        if ($GLOBALS['builder_api']->stacks_get_multisite_option('content_settings')) {
            $content_settings = $GLOBALS['builder_api']->stacks_get_multisite_option('content_settings');
        }

        $home_response_items = [
            'slider'                => $this->get_slider($slider_settings),
            'popular_categories'        => $this->get_popular_categories($categories_settings),
            'new_products'            => $this->get_new_products($recent_settings),
            'popularProductsLayout'        => $this->get_new_products_style(),
            'popular_categories_title'        => Polylang_Stacks_Strings_translation::get_string_translation(Stacks_ContentSettings::get_popular_categories_title()),
            'new_products_text'            => Polylang_Stacks_Strings_translation::get_string_translation(Stacks_ContentSettings::get_new_products_text()),
            'mobile_webview_link'        => $content_settings->mobile_webview_link
        ];
        //	return  'v3::' . $this->v1_v2_function_suffix . '::';
        return $this->return_success_response(apply_filters('home_items_before_success_response', $home_response_items));
    }

    /**
     * Get slider Products
     *
     * @return array
     */
    public function get_slider($settings) {
        if (!is_stacks_woocommerce_active()) {
            return $settings;
        }
        $products = Stacks_ContentSettings::get_home_slider();

        if (!$products || empty($products)) {
            $args = [
                'post_type' => 'product',
                'meta_key' => 'total_sales',
                'orderby' => 'meta_value_num',
                'posts_per_page' => 10,
            ];

            $products = get_posts($args);
        }

        $products = StacksWoocommerceDataFormating::format_product(apply_filters('before_getting_slider_products', $products));

        $settings['products'] = $products;

        return $settings;
    }

    /**
     * get popular products from it's controller 
     * 
     * @return array
     */
    public function get_popular_categories($settings) {
        $categories = Stacks_ContentSettings::get_home_categories();

        if (!$categories || empty($categories)) {
            $settings['categories'] = [];

            return $settings;
        }

        $mapper = function ($cat_id) {
            return get_term($cat_id);
        };

        $filter = function ($cat_id) {
            return get_term($cat_id);
        };

        $cats = apply_filters('api_after_getting_cats', array_filter(array_map($mapper, $categories), $filter));

        $items = (new Stacks_CategoriesModel())
            ->set_children_name('children')
            ->set_all(true)
            ->set_category_id($cats)
            ->set_format_callback([$this, 'format_categories'])
            ->get_product_categories();

        $settings['categories'] = [];

        if (!empty($items)) {
            foreach ($items as $item) {
                $settings['categories'][] = apply_filters('before_getting_popular_categories', $item);
            }
        }

        return $settings;
    }


    /**
     * get new products
     * 
     * @return array
     */
    public function get_new_products($settings) {
        if (!is_stacks_woocommerce_active()) {
            return $settings;
        }
        $products = Stacks_ContentSettings::get_home_new_products();

        if (!$products || empty($products)) {
            $args = [
                'post_type' => 'product',
                'meta_key' => 'total_sales',
                'orderby' => 'meta_value_num',
                'posts_per_page' => 10,
            ];

            $products = get_posts($args);
        }

        $settings['products'] = StacksWoocommerceDataFormating::format_product(apply_filters('before_getting_new_products', $products));

        return $settings;
    }

    /**
     * get new products style
     * 
     * @return string
     */
    public function get_new_products_style() {
        $products = Stacks_ContentSettings::get_home_new_products_style();

        if (!$products || empty($products)) {
            return 'slider';
        }

        return apply_filters('before_getting_new_products_style', $products);
    }

    /** 
     * get collection parameters for get request
     * @return array
     */
    public function get_collection_params_get() {
        return array();
    }

    /**
     * the schema for the request 
     * 
     * @return string
     */
    protected function get_public_item_schema() {
        $schema = array(
            '$schema'              => 'http://json-schema.org/draft-04/schema#',
            'title'                => 'home_page',
            'type'                 => 'object',
            'properties'           => []
        );

        return $schema;
    }
}
