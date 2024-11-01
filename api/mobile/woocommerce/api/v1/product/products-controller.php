<?php

/**
 * Controller Responsible for Returning Array of categories 
 * @Route("/categories")
 * @Method("/GET")
 */
class Stacks_ProductsController extends Stacks_AbstractProductsController {

    /**
     * Route base.
     *
     * @var string
     */
    protected $rest_base = 'products/search';

    /**
     * allowed parameters
     */
    protected $allowed_params = [
        'sort'                  => 'sort',
        'order'                 => 'order',
        'categories'            => 'cats',
        'price_range'           => 'price_range',
        'calories_range'        => 'calories_range',
        'custom_taxonomies'     => 'custom_taxonomies',
        'allergies'             => 'allergies',
        'per_page'              => 'per_page',
        'offset'                => 'offset',
        'sale'                  => 'sale',
        'featured_image_size'   => 'featured_image_size',
        'keyword'        => 'keyword'
    ];

    public function register_routes() {
        register_rest_route($this->get_api_endpoint(), $this->rest_base, [ // V3
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$this, 'get_items'],
                'permission_callback' => [$this, 'get_items_permissions_check'],
                'args'                => $this->get_collection_params(),
            ],
            'schema' => array($this, 'get_public_item_schema'),
        ]);
        register_rest_route($this->get_api_endpoint(), $this->rest_base . '/sync', [ // V3
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'sync_products'],
                'args'                => $this->get_collection_params(),
            ],
            'schema' => array($this, 'get_public_item_schema'),
        ]);
    }

    public function sync_products($request) {

        $limit = !empty($request['limit']) ? $request['limit'] : 1;
        $args = array(
            'limit' => $limit,
            'status' => 'publish',
            'order' => $request['query_order'],
            'orderby' => $request['query_orderby'],
            'lang' => get_locale()
        );

        // Source 
        switch ($request['query_post_type']) {
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
                $categories_ids = $request['products_categories'];
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

        if (!empty($products)) {
            // Orderby price
            if ($request['query_orderby'] == 'price') {
                $products  = wc_products_array_orderby($products, 'price', $request['query_order']);
            }

            // 2nd Orderby for latest products
            if ($request['query_post_type'] == 'product') {
                $products  = wc_products_array_orderby($products, $request['query_orderby'], $request['query_order']);
            }

            $products_data = [
                'currency_symbol' => html_entity_decode(get_woocommerce_currency_symbol()),
                'products' => []

            ];
            foreach ($products as $product) {
                $image_id  = $product->get_image_id();
                $featured_img = wp_get_attachment_image_url($image_id, 'thumbnail');

                $product_data = $product->get_data();
                $product_data['type'] = $product->get_type();
                if ($product_data['type'] == 'variable') {
                    $product_data['min_price'] = $product->get_variation_regular_price();
                    $product_data['min_sale_price'] = $product->get_variation_sale_price();
                    $product_data['max_price'] = $product->get_variation_regular_price('max', true);
                    $product_data['max_sale_price'] = $product->get_variation_sale_price('max', true);
                }
                $product_data['featured_img'] = $featured_img;
                $products_data['products'][] = $product_data;
            }

            return rest_ensure_response($products_data);
        } else { // Empty products
            return "No products exist with specified filters!";
        }
    }

    /**
     * Search for Product
     * 
     * @param WP_Rest_Request $request
     * @return array
     */
    public function get_items($request) {
        $this->map_request_params($request->get_params())->get_model_instance()->validate_and_add_parameters();

        if ($this->has_errors()) {
            return $this->return_error_response(Stacks_WC_Api_Response_Service::INVALID_PARAMETER_CODE, $this->invalid_parameter_message(), $this->get_errors());
        }

        $products = $this->model->get_products();

        $products_formatted = $this->format_products($products, $this->model->get_featured_image_size());

        return $this->return_success_response(apply_filters('stacks_rest_api_categories_modify', $products_formatted));
    }

    /**
     * Format Products 
     * 
     * @param array $products
     * @param string $image_size
     * @return array
     */
    private function format_products($products, $image_size) {
        if (!empty($products)) {
            return StacksWoocommerceDataFormating::format_product($products, $image_size);
        } else {
            return [];
        }
    }
}
