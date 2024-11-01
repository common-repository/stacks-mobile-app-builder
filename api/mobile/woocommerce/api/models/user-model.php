<?php

class Stacks_UserModel extends Stacks_AbstractModel {

    /**
     * Shipping Parameters 
     * 
     * @var array 
     */
    protected $shipping_params;

    /**
     * Billing Parameters
     * 
     * @var array 
     */
    protected $billing_params;

    public function __construct() {
        $this->shipping_params = $this->get_shipping_params();

        $this->billing_params = $this->get_billing_params();
    }

    /**
     * Get Shipping Parameters 
     * 
     * @return array
     */
    public function get_shipping_params() {
        return ['first_name', 'last_name', 'company', 'country', 'city', 'state', 'postcode', 'address_1', 'address_2'];
    }

    /**
     * Get Billing parameters 
     * 
     * @return array
     */
    public function get_billing_params() {
        return array_merge($this->get_shipping_params(), ['email', 'phone']);
    }


    /**
     * Get user address 
     * 
     * @param string $prefix
     * @return array
     */
    public function get_address($prefix) {
        $paramters = $prefix == 'shipping' ? $this->get_shipping_params() : $this->get_billing_params();

        $user_id = get_current_user_id();

        $meta = get_user_meta($user_id);

        // remove country key 
        unset($paramters[array_search('country', $paramters)]);

        $user_details = [];

        foreach ($paramters as $element) {
            $meta_key = $this->make_meta_key($prefix, $element);

            $value = isset($meta[$meta_key]) ? $meta[$meta_key][0] : '';

            $user_details[$element] = $value;
        }

        // add country to user_details
        $country = self::get_user_country($user_id, $prefix);

        $user_details['country'] = $country;

        return $user_details;
    }

    /**
     * Get User Country code and name
     * 
     * @param int $userId
     * @param string $prefix shipping or billing country
     * 
     * @return array
     */
    public static function get_user_country($userId, $prefix) {
        $country = get_user_meta($userId, $prefix . '_country', true);

        $countryName = '';

        if ($country) {
            $countryName = wc()->countries->get_allowed_countries()[$country];
        }

        return ['code' => $country, 'name' => $countryName];
    }

    /**
     * get Woo-commerce allowed countries 
     * 
     * @return array
     */
    public function get_woocommerce_allowed_countries() {
        return wc()->countries->get_allowed_countries();
    }

    /**
     * update user address 
     * 
     * @param string $prefix
     * @return boolean
     */
    public function update_address($prefix, $submitted_data) {
        $user_id = get_current_user_id();

        $data = $prefix == 'shipping' ? $this->shipping_params : $this->billing_params;

        foreach ($data as $element) {
            $meta_key = $this->make_meta_key($prefix, $element);

            update_user_meta($user_id, $meta_key, $submitted_data[$element]);
        }

        return true;
    }

    /**
     * create meta key from prefix and element
     * 
     * @param string $prefix
     * @param string $element
     * @return string
     */
    private function make_meta_key($prefix, $element) {
        return sprintf('%s_%s', $prefix, $element);
    }
}
