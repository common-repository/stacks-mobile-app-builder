<?php

/**
 * check if authentication service is JWT
 * @global object $Stacks_REST_Authentication
 * @return boolean
 */
function _stacks_api_is_auth_service_jwt() {
    return Stacks_Woocommerce_Integration_Api::get_instance()->get_authentication_service()->authentication_service->get_name() == 'JWT';
}

/**
 * check if there is any anonymous token submitted
 * @global object $Stacks_REST_Authentication
 * @return boolean
 */
function _stacks_api_is_user() {
    return Stacks_Woocommerce_Integration_Api::get_instance()->get_authentication_service()->authentication_service->is_user();
}

/**
 * check if there is any user token submitted
 * 
 * @global object $Stacks_REST_Authentication
 * @return boolean
 */
function _stacks_api_is_guest() {
    return Stacks_Woocommerce_Integration_Api::get_instance()->get_authentication_service()->authentication_service->is_guest();
}


/**
 * check if plugin is active
 * 
 * @return boolean
 */
function stacks_is_addon_plugin_activated() {
    return apply_filters('stacks_is_addon_plugin_activated', class_exists('WC_Product_Addons'));
}

/**
 * check if Plugin is active
 * 
 * @return boolean
 */
function stacks_is_wishlist_plugin_activated() {
    return apply_filters('stacks_is_wishlist_plugin_activated', class_exists('WC_Wishlists_Plugin'));
}

/**
 * check if plugin is active
 * 
 * @return boolean
 */
function stacks_is_points_plugin_activated() {
    return class_exists('WC_Points_Rewards');
}

/**
 * Return whether user has entered all required fields 
 */
function _stacks_paypal_app_settings_complete() {
    if (
        (Stacks_AppSettings::get_paypal_client_id() && Stacks_AppSettings::get_paypal_client_id() !== '') &&
        (Stacks_AppSettings::get_paypal_env() && Stacks_AppSettings::get_paypal_env() !== '')
    ) {
        return true;
    } else {
        return false;
    }
}
