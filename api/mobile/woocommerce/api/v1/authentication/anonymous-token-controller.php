<?php

/**
 * Controller Responsible for Returning token 
 * @Route("/token")
 * @Method("/GET")
 */
class Stacks_TokenController extends Stacks_AbstractController {

    /**
     * Route base.
     *
     * @var string
     */
    protected $rest_base = '/token';

    /**
     * Type of Taxonomy we are working on 
     * 
     * @var string 
     */
    protected $type = 'auth';

    /**
     * @inherit_doc 
     */
    protected $allowed_params = array();


    public function register_routes() {
        register_rest_route($this->get_api_endpoint(), $this->rest_base, array( // V3
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_token'),
                'args'                => $this->get_collection_params(),
            ),
            'schema' => array($this, 'get_public_item_schema'),
        ));
        register_rest_route('avaris-wc-rest/v1', $this->rest_base, array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_token'),
                'args'                => $this->get_collection_params(),
            ),
            'schema' => array($this, 'get_public_item_schema'),
        ));
    }

    /**
     * Main Function Respond to request 
     * @Route("/token")
     * @Method("GET")
     * @return array
     */
    public function get_token($request) {
        $this->map_request_params($request->get_params());

        $returned_data = apply_filters('_stacks_api_before_sending_anonymous_token', array());

        return $this->return_success_response($returned_data);
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
