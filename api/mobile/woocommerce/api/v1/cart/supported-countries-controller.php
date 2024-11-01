<?php

/**
 * Controller Responsible for All operations related to supported countries
 * @Route("/countries")
 * @Method("/GET")
 */
class Stacks_SupportedCountriesController extends Stacks_AbstractController {
    /**
     * Route base.
     * @var string
     */
    protected $rest_base    = '/countries';

    /**
     * @inherit_doc 
     */
    protected $allowed_params = array();

    public function register_routes() {
        register_rest_route($this->get_api_endpoint(), $this->rest_base, array( // V3
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_allowed_countries'),
                'permission_callback' => array($this, 'get_items_permissions_check'),
                'args'                => array()
            ),
            'schema' => array($this, 'get_public_item_schema'),
        ));
    }

    /**
     * get Woocommerce allowed countries 
     * 
     * @return array
     */
    private function get_woocommerce_allowed_countries() {
        $wc_countries = new WC_Countries();

        return $wc_countries->get_allowed_countries();
    }

    /**
     * get allowed countries 
     * @param object $request
     * @return array
     */
    public function get_allowed_countries($request) {
        $countries = $this->get_woocommerce_allowed_countries();

        return $this->return_success_response($countries);
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
            'properties'           => array(
                'id' => array(
                    'description'  => esc_html__('Unique identifier for the object.', 'plates'),
                    'type'         => 'integer',
                    'context'      => array('view'),
                    'readonly'     => true,
                )
            ),
        );

        return $schema;
    }
}
