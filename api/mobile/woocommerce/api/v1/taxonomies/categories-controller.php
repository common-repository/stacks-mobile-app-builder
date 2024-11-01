<?php

/**
 * Controller Responsible for Returning Array of categories 
 * @Route("/categories")
 * @Method("/GET")
 */
class Stacks_CategoriesController extends Stacks_AbstractController {
    /**
     * Route base.
     *
     * @var string
     */
    protected $rest_base = 'categories';

    /**
     * Type of Taxonomy we are working on 
     * 
     * @var string 
     */
    protected $type = 'categories';


    /**
     * @inherit_doc 
     */
    protected $allowed_params = ['cat_id' => 'id', 'all' => 'all'];

    /**
     * @var Stacks_CategoriesModel 
     */
    protected $model = null;

    public function register_routes() {
        register_rest_route($this->get_api_endpoint(), $this->rest_base, array( // V3
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_items'),
                'permission_callback' => array($this, 'get_items_permissions_check'),
                'args'                => $this->get_collection_params(),
            ),
            'schema' => array($this, 'get_public_item_schema'),
        ));
        register_rest_route($this->get_api_endpoint(), $this->rest_base . '/sync', array(
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array($this, 'sync_categories'),
                'args'                => $this->get_collection_params(),
            ),
            'schema' => array($this, 'get_public_item_schema'),
        ));
    }

    /**
     * Get categories model instance
     * 
     * @return \Stacks_CategoriesModel
     */
    public function get_model_instance() {
        if (is_null($this->model)) {
            $this->model = new Stacks_CategoriesModel();
        }

        return $this->model;
    }

    /**
     * Main Function Respond to request 
     * @Route("/categories")
     * @Method("GET")
     * @return array
     */
    public function get_items($request) {
        $this->map_request_params($request->get_params());

        $id = $this->get_request_param('id');
        if ($this->get_request_param('all') === 'false') {
            $all = false;
        } else {
            if ($this->get_request_param('all') === 'true') {
                $all = true;
            } else {
                $all = null;
            }
        }
        $items = $this->get_model_instance()
            ->set_all($all)
            ->set_children_name('children')
            ->set_category_id($id)
            ->set_format_callback([$this, 'format_categories'])
            ->get_product_categories();

        return $this->return_success_response(apply_filters('stacks_rest_api_categories_modify', $items));
    }

    public function sync_categories($request) {
        $request = $request->get_params();
        $stacks_builder_api = new stacks_builder_api();
        $signature_validation = $stacks_builder_api->stacks_validate_signature($request['order_id']);

        // if( !$signature_validation ) {
        //     return false;
        // }

        $limit = !empty($request['limit']) ? $request['limit'] : 1;
        $cat_args = array(
            'taxonomy'    => 'product_cat',
            'orderby'    => $request['orderby'],
            'order'      => $request['order'],
            'hide_empty' => $request['hide_empty'],
            'number' => $limit,
            'lang' => get_locale(),
        );

        if ($request['source'] == 'by_id' && !empty($request['categories'])) {
            $cat_args['include'] = $request['categories'];
        }
        if ($request['source'] == 'by_parent' && !empty($request['parent'])) {
            $cat_args['parent'] = $request['parent'];
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
            $category_data['image_url'] = $category_img;
            $categories_data[] = $category_data;
        }
        return $this->return_success_response(apply_filters('stacks_rest_api_categories_modify', $categories_data));
    }

    /**
     * Options to pass to limit the response 
     * 
     * @return string
     */
    public function get_collection_params() {
        $params = [];

        $params['cat_id'] = [
            'description'       => __('Get Single category and sub categories.', 'woocommerce'),
            'type'              => 'int',
            'sanitize_callback' => 'sanitize_text_field',
            'validate_callback' => 'rest_validate_request_arg',
        ];

        return $params;
    }

    /**
     * the schema for the request 
     * 
     * @return string
     */
    public function get_public_item_schema() {
        $schema = [
            '$schema'              => 'http://json-schema.org/draft-04/schema#',
            'title'                => $this->type,
            'type'                 => 'object',
            'properties'           => [
                'id' => [
                    'description'  => esc_html__('Unique identifier for the object.', 'plates'),
                    'type'         => 'integer',
                    'context'      => ['view'],
                    'readonly'     => true,
                ]
            ],
        ];

        return $schema;
    }
}
