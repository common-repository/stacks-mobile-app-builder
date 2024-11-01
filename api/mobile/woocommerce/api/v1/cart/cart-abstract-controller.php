<?php

abstract class Stacks_CartAbstractController extends Stacks_CartWishlistController {

    /**
     * Route base.
     * @var string
     */
    protected $rest_api = '/cart';

    /**
     * @var string 
     */
    protected $type = 'cart';

    /**
     * @var Stacks_CartModel
     */
    public $cart_model = null;

    /**
     * @var Stacks_WC_Points_Service 
     */
    protected $stacks_wc_points_service;

    public function __construct() {
        $this->cart_model = new Stacks_CartModel();
        $this->stacks_wc_points_service = new Stacks_WC_Points_Service();
    }


    /**
     * check if coupon is valid or not 
     * 
     * @param string $coupon_code
     * 
     * @return boolean
     */
    public function is_valid_coupon($coupon_code, $error_message = false) {
        $coupon = new WC_Coupon($coupon_code);

        $coupon_valid = $coupon->is_valid();

        if ($error_message) {
            $result = ['valid' => $coupon_valid];

            if ($coupon_valid) {
                return $result;
            } else {
                return array_merge($result, ['message' => $coupon->get_error_message()]);
            }
        } else {
            return $coupon_valid;
        }
    }

    /**
     * check if coupon is applied on cart or not
     * @param string $coupon_code
     * @return boolean
     */
    public function is_coupon_applied($coupon_code) {
        $applied_coupons = wc()->cart->applied_coupons;

        if (in_array($coupon_code, $applied_coupons)) {
            return true;
        }
        return false;
    }

    /**
     * add points details to returned data 
     * @param array $details
     * @return array
     */
    public function add_points_info($details) {
        $stacks_wc_points_service = new Stacks_WC_Points_Service();

        if ($this->get_user_items_permission()) {
            $details['points_earned']        = WC_Points_Rewards_Manager::get_users_points(get_current_user_id());
            $details['partial_redemption_allowed']    = $this->stacks_wc_points_service->is_partial_redemption_allowed();
            $details['points_rewarded_message']    = $this->stacks_wc_points_service->_stacks_get_points_earned_for_purchase_message();
            $details['points_redeemed_message']    = $this->stacks_wc_points_service->_stacks_get_points_can_be_redeemed();
        }
        return $details;
    }


    /**
     * check if coupons enabled or not 
     * 
     * @return boolean
     */
    public function is_coupons_enabled() {
        return wc_coupons_enabled();
    }


    /**
     * get cart details 
     * @param STRING $hash
     * @return ARRAY
     */
    public function get_cart_details($hash = false) {
        // update Cart Totals
        WC()->cart->calculate_totals();

        // start Adding Required Parameters
        $coupons = $this->filter_valid_coupons(WC()->cart->get_coupon_discount_totals());
        $totals  = wc()->cart->get_totals();

        $details = array(
            'total'                => $totals['total'],
            'subtotal'                => $totals['subtotal'],
            'shipping_total'            => $totals['shipping_total'],
            'applied_coupons'            => $coupons['applied_coupons'],
            'invalid_coupons'            => $coupons['invalid_coupons'],
            'coupons_enabled'            => $this->is_coupons_enabled(),
            'selected_shipping_method'        => $this->get_chosen_shipping_method(),
            'customer_country'            => wc()->customer->get_shipping_country(),
            'enable_shipping_calc'        => 'yes' === get_option('woocommerce_enable_shipping_calc') ? true : false
        );

        $details['tax'] = $this->get_cart_tax_totals();

        if (stacks_is_points_plugin_activated()) {
            $details = $this->add_points_info($details);
        }

        if ($hash) {
            $item = WC()->cart->get_cart_item($hash);

            $details['item_total']     = $item['line_total'];
            $details['item_subtotal']  = $item['line_subtotal'];
        }

        return apply_filters('before_returning_cart_details', $details);
    }

    /**
     * get cart taxes 
     * 
     * @return array
     */
    private function get_cart_tax_totals() {
        $tax_details = [];
        $tax_details['show'] = WC()->cart->display_prices_including_tax();
        $tax_details['status'] = wc_tax_enabled();
        $tax_details['itemized'] = 'itemized' === get_option('woocommerce_tax_total_display');

        $taxes = wc()->cart->get_tax_totals();

        if (!empty($taxes) && $tax_details['status']) {
            $taxes_formatted = [];

            foreach ($taxes as $tax) {
                $taxes_formatted[] = [
                    'amount' => $tax->amount,
                    'label' => $tax->label
                ];
            }
            $taxes = $taxes_formatted;
        } else {
            $taxes = [];
        }

        $tax_details['taxes'] = $taxes;
        $tax_details['total'] = wc()->cart->get_taxes_total();

        return $tax_details;
    }

    /**
     * get zone shipping methods
     * 
     * @param string $country
     * @param string $state
     * @param string $postcode
     */
    public function get_zone_shipping_methods($country, $state, $postcode) {
        $package = [];
        $package['destination']['country']  = $country;
        $package['destination']['state']    = $state;
        $package['destination']['postcode'] = $postcode;

        $zone = WC_Shipping_Zones::get_zone_matching_package($package);

        $shipping_methods = array_values(StacksWoocommerceDataFormating::format_shipping_methods($zone->get_shipping_methods(true)));

        return $shipping_methods;
    }

    /**
     * get chosen shipping method
     * @return array
     */
    public function get_chosen_shipping_method() {
        $shipping_method = WC()->session->get('chosen_shipping_methods');

        if (!empty($shipping_method) && isset($shipping_method[0])) {
            $shipping_method = explode(':', $shipping_method[0]);

            if ($shipping_method[0] == '' || !isset($shipping_method[1])) {
                return array();
            }

            return array(
                'id'            => $shipping_method[0],
                'instance_id'    => $shipping_method[1]
            );
        }

        return array();
    }


    /**
     * filter valid coupons
     * @param array $coupons
     * @return array
     */
    protected function filter_valid_coupons($coupons = array()) {
        $invalid_coupons = array();

        if (!empty($coupons)) {
            foreach ($coupons as $coupon_name => $coupon_value) {

                if (!$this->is_valid_coupon($coupon_name)) {
                    $invalid_coupons[$coupon_name] = $coupon_value;

                    unset($coupons[$coupon_name]);

                    wc()->cart->remove_coupon($coupon_name);
                }
            }
        }

        return array('applied_coupons' => $coupons, 'invalid_coupons' => $invalid_coupons);
    }

    /**
     * Remove item from cart
     * @param string $item_key
     * @return bool
     */
    protected function remove_item_from_cart($item_key) {
        if (wc()->cart->get_cart_item($item_key)) {
            return wc()->cart->remove_cart_item($item_key);
        }
        return false;
    }

    /**
     * Return Error product does not exist in cart
     * @return wp_error
     */
    protected function get_item_not_exists_in_cart_error() {
        return $this->return_error_response(Stacks_WC_Api_Response_Service::UNEXPECTED_ERROR_CODE, $this->unexpected_error_message(), array(__('sorry this product does not exist in cart')), 400);
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
