<?php

/**
 * Controller Responsible for Returning Array of FoodTypes 
 */
class Stacks_ReviewsController extends Stacks_AbstractController {

    /**
     * Route base.
     *
     * @var string
     */
    protected $rest_base = '/product/(?P<id>[\d]+)/reviews';

    /**
     * Type of Taxonomy we are working on 
     * 
     * @var string 
     */
    protected $type = 'comment';

    /**
     * @inherit_doc 
     */
    protected $allowed_params = array(
        'id'        => 'id',
        'review'    => 'review',
        'rating'    => 'rating',
        'email'     => 'email',
        'name'      => 'name'
    );

    public function register_routes() {
        register_rest_route($this->get_api_endpoint(), $this->rest_base, array( // V3
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_items'),
                'permission_callback' => array($this, 'get_items_permissions_check'),
                'args'                => $this->get_collection_params(),
            ),
            array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array($this, 'submit_review'),
                'permission_callback' => array($this, 'get_items_permissions_check'),
                'args'                => $this->get_collection_params_submit(),
            ),
            'schema' => array($this, 'get_public_item_schema'),
        ));
    }

    /**
     * Review Submission 
     * @param object $request
     * @return array
     */
    public function submit_review($request) {
        $this->map_request_params($request->get_params());

        // validate product has a valid id
        if ('product' !== get_post_type($this->get_request_param('id'))) {
            return $this->return_error_response(Stacks_WC_Api_Response_Service::INVALID_PARAMETER_CODE, $this->invalid_parameter_message(), array(__('Invalid product ID.', 'plates')));
        }

        // here we have 2 behaviors user logged in and user not logged in 
        if (!get_current_user_id()) {

            $errors = $this->validate_name_and_email();

            if (!empty($errors)) {
                return $this->return_error_response(Stacks_WC_Api_Response_Service::INVALID_PARAMETER_CODE, $this->invalid_parameter_message(), $errors);
            }
        }

        $prepared_review = $this->prepare_item_to_be_saved();

        $product_review_id = wp_insert_comment($prepared_review);

        if (!$product_review_id) {
            return $this->return_error_response(Stacks_WC_Api_Response_Service::INVALID_PARAMETER_CODE, $this->invalid_parameter_message(), array(__('Creating product review failed.', 'plates')));
        }

        update_comment_meta($product_review_id, 'rating', $this->get_request_param('rating'));

        return $this->return_success_response(true);
    }

    /**
     * validate name and email are sent 
     * @param array $errors
     */
    protected function validate_name_and_email() {
        $errors = array();

        if (!$this->get_request_param('name')) {
            $errors[] = __('name not sent.', 'plates');
        }

        if (!$this->get_request_param('email')) {
            $errors[] = __('email not sent.', 'plates');
        }

        return $errors;
    }

    /**
     * item preparation to be saved
     * @return array
     */
    protected function prepare_item_to_be_saved() {
        $prepared_review = array('comment_approved' => 1, 'comment_type' => 'review');

        $prepared_review['comment_content'] = $this->get_request_param('review');
        $prepared_review['comment_post_ID'] = (int) $this->get_request_param('id');

        if (get_current_user_id()) {
            $user = new WP_User(get_current_user_id());

            $prepared_review['comment_author'] = $user->display_name;
            $prepared_review['comment_author_email'] = $user->user_email;
        } else {
            $prepared_review['comment_author'] = $this->get_request_param('name');
            $prepared_review['comment_author_email'] = $this->get_request_param('email');
        }

        return $prepared_review;
    }

    /**
     * Main Function Respond to request 
     * @Route("/categories")
     * @Method("GET")
     * @return array
     */
    public function get_items($request) {
        $this->map_request_params($request->get_params());

        $product = wc_get_product($this->get_request_param('id'));

        if (!$product || $product == 'false' || $product === false) {
            return $this->return_error_response(Stacks_WC_Api_Response_Service::INVALID_PARAMETER_CODE, $this->invalid_parameter_message(), array(__('invalid product id', 'plates')), 400);
        }

        $reviews = $this->get_product_reviews($this->get_request_param('id'));

        $data = array(
            'count'     => sizeof($reviews),
            'average'   => $product->get_average_rating(),
            'reviews'   => $reviews
        );

        return $this->return_success_response($data);
    }

    /**
     * Get product reviews 
     * @param int $id
     * @return array
     */
    public function get_product_reviews($id) {
        $comments = array_values(get_approved_comments($id));
        $reviews  = [];

        foreach ($comments as $comment) {
            $reviews[] = [
                'name'              => $comment->comment_author,
                'text'              => $comment->comment_content,
                'rating'            => get_comment_meta($comment->comment_ID, 'rating', true)
            ];
        }
        return $reviews;
    }


    /**
     * Options to pass to limit the response 
     * 
     * @return string
     */
    public function get_collection_params() {
        $params = array();

        $params['id'] = array(
            'description'       => __('Get Product reviews by product id.', 'plates'),
            'type'              => 'integer',
            'validate_callback' => array($this, 'validate_integer')
        );
        return $params;
    }

    /**
     * validate rating 
     * @param integer $param
     * @param object $request
     * @param string $key
     * @return boolean
     */
    public function validate_rating($param, $request, $key) {
        if (!$this->validate_integer($param, $request, $key)) {
            return false;
        }

        if ($param >= 1 && $param <= 5) {
            return true;
        }
        return false;
    }

    /**
     * Options to pass to limit the response 
     * 
     * @return string
     */
    public function get_collection_params_submit() {
        $params = array();

        $params['id'] = array(
            'description'       => __('Get Product reviews by product id.', 'plates'),
            'type'              => 'integer',
            'validate_callback' => array($this, 'validate_integer')
        );

        $params['rating'] = array(
            'description'       => __('Submit rating number between 1 and 5 .', 'plates'),
            'type'              => 'integer',
            'required'          => true,
            'validate_callback' => array($this, 'validate_rating')
        );

        $params['review'] = array(
            'description'       => __('review description .', 'plates'),
            'type'              => 'string',
            'required'          => true
        );

        $params['email'] = array(
            'description'       => __('User email only if user not logged in .', 'plates'),
            'type'              => 'string',
            'required'          => false
        );

        $params['name'] = array(
            'description'       => __('User name only if user not logged in .', 'plates'),
            'type'              => 'string',
            'required'          => false
        );
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
