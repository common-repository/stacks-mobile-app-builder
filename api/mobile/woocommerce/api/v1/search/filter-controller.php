<?php

/**
 * Controller Responsible for Returning Array of Accepted Filters
 * @Route("/filter")
 * @Method("/GET")
 */
class Stacks_FilterController extends Stacks_AbstractController {
    /**
     * Route base.
     *
     * @var string
     */
    protected $rest_base = 'filter';

    /**
     * Type of Taxonomy we are working on 
     * 
     * @var string 
     */
    protected $type = 'collection';

    /**
     * @inherit_doc 
     */
    protected $allowed_params = [];

    /**
     * @var Stacks_CategoriesModel 
     */
    protected $categories_model;

    public function __construct() {
        add_filter('stacks_rest_api_filteration_parameters', [$this, 'add_custom_taxonomies']);
    }

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
    }

    /**
     * Get instances from models we need to extract data from 
     */
    public function instantiate_models() {
        $this->categories_model = new Stacks_CategoriesModel();
    }

    /**
     * Add custom Taxonomies to the filtration from app setting 
     * 
     * @param array $data
     * @return array
     */
    public function add_custom_taxonomies($data) {
        $taxonomy = Stacks_AppSettings::get_enabled_custom_taxonomies();

        if ($taxonomy) {
            $taxonomy_array = $this->categories_model->set_format_callback(array($this, 'format_categories'))->set_taxonomy_name($taxonomy)->get_product_categories();

            $taxonomy_obj = get_taxonomy($taxonomy);

            $data['custom_taxonomies'][$taxonomy] = [];
            $data['custom_taxonomies'][$taxonomy]['label'] = __($taxonomy_obj->label, '');
            $data['custom_taxonomies'][$taxonomy]['items'] = $taxonomy_array;
        }

        return $data;
    }

    /**
     * Main Function Respond to request 
     * 
     * @Route("/categories")
     * @Method("GET")
     * @return array
     */
    public function get_items($request) {
        $this->map_request_params($request->get_params());

        $this->instantiate_models();

        $data = [
            'categories'    => $this->categories_model->set_format_callback(array($this, 'format_categories'))->get_product_categories(),
            'price_range'   => $this->get_highest_lowest_product_prices()
        ];

        return $this->return_success_response(apply_filters('stacks_rest_api_filteration_parameters', $data));
    }

    /**
     * Get minimum product price and maximum product price 
     * 
     * @global object $wpdb
     * @return array
     */
    public function get_highest_lowest_product_prices() {
        global $wpdb;

        $results = $wpdb->get_results("SELECT min( meta_value + 0 ) as lower ,max( meta_value + 0 ) as upper  FROM {$wpdb->prefix}posts LEFT JOIN {$wpdb->prefix}postmeta ON {$wpdb->prefix}posts.ID = {$wpdb->prefix}postmeta.post_id  WHERE meta_key = '_price'", OBJECT);
        $prices = (array) $results[0];
        return array_map(function ($el) {
            return (int) $el;
        }, $prices);
    }

    /**
     * Getting highest and lowest product calories
     * @global object $wpdb
     * @return type
     */
    public function get_highest_lowest_product_calories() {
        global $wpdb;

        $results = $wpdb->get_results("SELECT min( meta_value + 0 ) as lower ,max( meta_value + 0 ) as upper  FROM {$wpdb->prefix}posts LEFT JOIN {$wpdb->prefix}postmeta ON {$wpdb->prefix}posts.ID = {$wpdb->prefix}postmeta.post_id  WHERE meta_key = 'calories_value'", OBJECT);
        $callories = (array) $results[0];
        return array_map(function ($el) {
            return (int) $el;
        }, $callories);
    }

    /**
     * Options to pass to limit the response 
     * 
     * @return string
     */
    public function get_collection_params() {
        $params = array();

        return $params;
    }

    /**
     * the schema for the request 
     * 
     * @return string
     */
    public function get_public_item_schema() {
        $schema = array(
            '$schema'              => 'http://json-schema.org/draft-04/schema#',
            'title'                => $this->type,
            'type'                 => 'object',
            'properties'           => array(),
        );

        return $schema;
    }
}
