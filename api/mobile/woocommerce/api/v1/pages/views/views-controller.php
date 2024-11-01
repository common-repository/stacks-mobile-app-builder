<?php

class Stacks_ViewsController extends Stacks_AbstractController {

    /**
     * @var string
     */
    protected $rest_api = '/views';

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
                'callback'            => array($this, 'get_views'),
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
    public function add_data_to_view_widgets($page_elements, $page_widgets = []) {

        if (!empty($page_elements)) {

            foreach ($page_elements as $key => $element) {

                if (!empty($element->elements)) {

                    $page_widgets = $this->add_data_to_view_widgets($element->elements, $page_widgets); // Recursion

                } else { //this element doesn't have elements => this element is a widget

                    // Return products array in mobile-products widget
                    if (is_stacks_woocommerce_active() && !empty($element->type) && $element->type == 'products') {

                        $limit = !empty($element->data->limit) ? $element->data->limit : 1;
                        $args = array(
                            'limit' => $limit,
                            'status' => 'publish',
                            'order' => $element->data->query_order,
                            'orderby' => $element->data->query_orderby,
                        );

                        // Source 
                        switch ($element->data->query_post_type) {
                            case 'product': // Latest Products
                                $args['orderby'] = 'date';
                                $args['order'] = 'DESC';
                                break;
                            case 'sale': // Sale
                                $args['include'] = wc_get_product_ids_on_sale();
                                break;
                            case 'featured': // Featured
                                $args['featured'] = true;
                                break;
                            case 'by_category': // By category
                                $categories_ids = (array) $element->data->products_categories;
                                if (!empty($categories_ids)) {
                                    $categories_slugs = [];

                                    // Get categories slugs to use in products query
                                    foreach ($categories_ids as $id) {
                                        $category = get_term_by('id', $id, 'product_cat', 'ARRAY_A');
                                        $categories_slugs[] = $category['slug'];
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

                    if ( !empty($element->type) && $element->type == 'posts') {

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
                    if (is_stacks_woocommerce_active() && !empty($element->type) && $element->type == 'categories') {

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

                    if (!empty( $element->widgetType) && $element->widgetType == 'mobile-text-editor') {
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
    public function get_views($request) {

        $view_name = sanitize_text_field($_GET['viewName']);
        $project_id = sanitize_text_field($_GET['project_id']);

        if ($GLOBALS['stacks_builder']->get_view($view_name, $project_id)) {
            $home_response_items = [
                'builder_data' => $GLOBALS['stacks_builder']->get_view( $view_name, $project_id )
            ];
            $home_response_items['builder_data'] = $this->add_data_to_view_widgets($home_response_items['builder_data'], $page_widgets = []);

            //            return  'v3::' . $this->v1_v2_function_suffix . '::';
            return $this->return_success_response(apply_filters('home_items_before_success_response', $home_response_items));
        }

        return [];
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
