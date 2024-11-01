<?php

abstract class Stacks_WishlistAbstractController extends Stacks_CartWishlistController {

    use Stacks_WishlistValidationTrait;

    /**
     * check if wishlists plugin exists and activated
     * @return boolean
     */
    public static function stacks_is_wishlist_plugin_activated() {
        return stacks_is_wishlist_plugin_activated();
    }

    /**
     * return not a valid wishlist id error response 
     * @return array
     */
    public function not_valid_wishlist_id_error_response() {
        $message = __('this is not a valid wishlist id', 'plates');

        return $this->return_error_response('wishlist_id_not_valid', $message, array($message), 401);
    }

    /**
     * return not a valid product id error response 
     * @return array
     */
    public function not_valid_product_id_error_response() {
        $message = __('this is not a valid product id', 'plates');

        return $this->return_error_response('product_id_not_valid', $message, array($message), 401);
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
