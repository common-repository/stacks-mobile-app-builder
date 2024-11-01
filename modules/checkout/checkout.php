<?php

class stacks_checkout {
    public function __construct() {
        add_action('admin_init', array($this, 'create_woo_checkout'));
        add_filter('theme_page_templates', array($this, 'mob_co_add_page_template'));
        add_filter('page_template', array($this, 'mob_co_redirect_page_template'));
        add_action('wp_loaded', array($this, 'maybe_load_cart'), 5);
        add_filter('query_vars', array($this, 'mobile_co_add_query_vars_filter'));
        add_action('wp', array($this, 'receive_request_checkout'));
    }
    /**
     * Add checkout page automatically after plugin registration
     */
    public function create_woo_checkout() {

        $woocommerce_active = is_stacks_woocommerce_active();

        if (!$woocommerce_active) {
            return false;
        }

        $page = get_page_by_title('Mobile Checkout');
        if (!$page) {
            $post_details = array(
                'post_title'   => _x('Mobile Checkout', 'Page title', 'woocommerce'),
                'post_name'    => _x('mobile-checkout', 'Page slug', 'woocommerce'),
                'post_status'  => 'publish',
                'post_author'  => 1,
                'post_type'    => 'page',
                'page_template'  => 'mobile_co.php'
            );
            wp_insert_post($post_details);
        }
    }

    public function mob_co_add_page_template($templates) {
        $templates['mobile_co.php'] = 'Mobile Checkout';
        return $templates;
    }

    public function mob_co_redirect_page_template($template) {
        if (is_page('mobile-checkout')) {
            $template = dirname(__FILE__) . '/template.php';
            add_filter( 'woocommerce_is_checkout', '__return_true');
        }
        return $template;
    }

    /**
     * Loads the cart, session and notices should it be required.
     *
     * Note: Only needed should the site be running WooCommerce 3.6
     * or higher as they are not included during a REST request.
     *
     * @see https://plugins.trac.wordpress.org/browser/cart-rest-api-for-woocommerce/trunk/includes/class-cocart-init.php#L145
     * @since   2.0.0
     * @version 2.0.3
     */
    public function maybe_load_cart() {
        if (!is_stacks_woocommerce_active()) {
            return;
        }

        if (version_compare(WC_VERSION, '3.6.0', '>=') && WC()->is_rest_api_request()) {
            if (empty(sanitize_url($_SERVER['REQUEST_URI']))) {
                return;
            }
            $rest_prefix = 'avaris-wc-rest';
            $req_uri     = esc_url_raw(wp_unslash($_SERVER['REQUEST_URI']));
            $is_my_endpoint = (false !== strpos($req_uri, $rest_prefix));
            if (!$is_my_endpoint) {
                return;
            }
            require_once WC_ABSPATH . 'includes/wc-cart-functions.php';
            require_once WC_ABSPATH . 'includes/wc-notice-functions.php';
            if (null === WC()->session) {
                $session_class = apply_filters('woocommerce_session_handler', 'WC_Session_Handler'); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
                if (false === strpos($session_class, '\\')) {
                    $session_class = '\\' . $session_class;
                }
                WC()->session = new $session_class();
                WC()->session->init();
            }
            if (is_null(WC()->customer)) {
                if (is_user_logged_in()) {
                    WC()->customer = new WC_Customer(get_current_user_id());
                } else {
                    WC()->customer = new WC_Customer(get_current_user_id(), true);
                }
                add_action('shutdown', array(WC()->customer, 'save'), 10);
            }
            if (null === WC()->cart) {
                WC()->cart = new WC_Cart();
            }
            // var_dump(WC()->cart);
            // die;
        }
    }

    public function mobile_co_add_query_vars_filter($vars) {
        $vars[] = "mobile_co";
        $vars[] = "stacks_checkout";
        $vars[] = "stacks_order_id";
        $vars[] = "uid";
        return $vars;
    }

    public function receive_request_checkout() {
        global $wp;
        if (get_query_var('mobile_co') == 1) {
            add_filter( 'woocommerce_is_checkout', '__return_true');
            $uid = get_query_var('uid');
            $user = get_user_by('id', $uid);
            if ($user) {
                wp_set_current_user($uid, $user->user_login);
                wp_set_auth_cookie($uid);
                do_action('wp_login', $user->user_login, $user);
                /*
                 * Get Cart Items of Specific User
                 */
                setcookie('mobile_co', 'true', time() + (86400 * 30), "/"); // 86400 = 1 day
                setcookie('mobile_co_id', $uid, time() + (86400 * 30), "/");
                if(!get_query_var('stacks_checkout')) {
                    if (get_site_url() . '/mobile-checkout/' !== get_permalink()) {
                        wp_redirect(home_url(add_query_arg(array(), $wp->request)));
                        exit;
                    } else {
                        wp_redirect(home_url($wp->request));
                        exit;
                    }
                } else {
                    $order_id = get_query_var('stacks_order_id');
                    $order = new WC_Order($order_id);
                    wp_redirect($order->get_checkout_payment_url()."&stacks_checkout=true");
                    // wp_redirect(home_url(add_query_arg(array(), $wp->request)));
                }
            }
        } else {
            if (!empty($_COOKIE['mobile_co']) && sanitize_text_field($_COOKIE['mobile_co']) == 'true') {
                $uid = sanitize_text_field($_COOKIE['mobile_co_id']);
                unset($_COOKIE['mobile_co']);
                unset($_COOKIE['mobile_co_id']);
                setcookie('mobile_co', "", time() - 3600);
                setcookie('mobile_co_id', "", time() - 3600);
                if (get_permalink() == get_site_url() . '/cart/') {
                    wp_redirect(home_url($wp->request));
                    exit;
                }
            }
        }
    }

}

new stacks_checkout();
