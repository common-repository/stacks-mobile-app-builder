<?php

class Stacks_UpdateProductQuantityController extends Stacks_CartAbstractController {

    /**
     * @inherit_doc
     */
    protected $allowed_params = [
        'hash_id'   => 'hash_id',
        'quantity'  => 'quantity'
    ];


    public function register_routes() {
        register_rest_route($this->get_api_endpoint(), $this->rest_api . '/quantity', array( // V3
            array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array($this, 'update_product_quantity'),
                'permission_callback' => array($this, 'get_items_permissions_check'),
                'args'                => $this->get_collection_params()
            ),
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'update_product_quantity'),
                'permission_callback' => array($this, 'get_items_permissions_check'),
                'args'                => $this->get_collection_params()
            ),
            'schema' => array($this, 'get_public_item_schema'),
        ));
    }

    /**
     * Update Product Quantity Request Handler
     * @param object $request
     * @return array
     */
    public function update_product_quantity($request) {
        $this->map_request_params($request->get_params());

        $hash           = $this->get_request_param('hash_id');
        $quantity       = (int) $this->get_request_param('quantity');
        $item_updated   = false;
        $errors         = array();

        // if cart not empty 
        if ($item = WC()->cart->get_cart_item($hash)) {

            $_product       = $item['data'];
            $prev_quantity  = (int) $item['quantity'];

            // Update cart validation
            $passed_validation     = apply_filters('woocommerce_update_cart_validation', true, $hash, $item, $quantity);

            // is_sold_individually
            if ($_product->is_sold_individually() && $quantity > 1) {
                $errors[] = sprintf(__('You can only have 1 %s in your cart.', 'plates'), $_product->get_name());
                $passed_validation = false;
            }

            if ($prev_quantity == $quantity) {
                $errors[] = __('Quantity did not updated', 'plates');
                $passed_validation = false;
            }

            if ($passed_validation) {
                WC()->cart->set_quantity($hash, $quantity, false);
                WC()->cart->calculate_totals();
                $item_updated = true;
            }
        } else {
            $errors[] = __('requested product not found in your cart', 'plates');

            return $this->return_error_response(Stacks_WC_Api_Response_Service::INVALID_PARAMETER_CODE, $this->invalid_parameter_message(), $errors, 400);
        }

        if ($item_updated) {
            $return = $this->get_cart_details($hash);

            return $this->return_success_response($return);
        } else {
            return $this->return_error_response(Stacks_WC_Api_Response_Service::INVALID_PARAMETER_CODE, $this->invalid_parameter_message(), $errors, 400);
        }
    }

    /**
     * Options to pass to limit the response 
     * 
     * @return string
     */
    public function get_collection_params() {
        $params = array();

        $params['hash_id'] = array(
            'description'       => __('Hash id of the product you would like to add.', 'woocommerce'),
            'type'              => 'string',
            'required'          => true,
            'sanitize_callback' => 'sanitize_text_field',
            'validate_callback' => 'rest_validate_request_arg'
        );

        $params['quantity'] = array(
            'description'       => __('Hash id of the product you would like to add.', 'woocommerce'),
            // 'type'              => 'int',
            'required'          => true,
            'sanitize_callback' => 'sanitize_text_field',
            'validate_callback' => array($this, 'validate_integer')
        );

        return $params;
    }
}
