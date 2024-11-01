<?php
require_once "image.php";
require_once "stacks_users.php";
require_once "connectivity.php";
require_once "posts.php";
require_once "views.php";
require_once "orders.php";
/*
 * Handles the communication with the builder
 */
class stacks_builder_api {

    public function __construct() {
        add_action('wp', array($this, 'stacks_receive_request'));
        add_action('wp', array($this, 'customize_checkout'));
        add_filter('query_vars', array($this, 'stacks_add_query_vars_filter'));
    }

    public function stacks_add_query_vars_filter($vars) {
        $vars[] = 'home_app';
        $vars[] = 'app_settings';
        $vars[] = 'content_settings';
        $vars[] = 'general_settings';
        $vars[] = 'global_settings';
        $vars[] = 'blog_id';
        $vars[] = 'token';
        $vars[] = 'order_id';
        $vars[] = 'project_id';
        $vars[] = 'signature';
        $vars[] = 'get_google_json';
        $vars[] = 'stacks_checkout';
        return $vars;
    }


    public function customize_checkout() {
        if(get_query_var('stacks_checkout')) {
            ?> 
            <style>
                header, footer, #wpadminbar {
                    display: none !important;
                }
            </style>
            <?php
        }
    }

    public function stacks_receive_request() {
        $signature_validation = $this->stacks_validate_signature();
        if (!$signature_validation) {
            return false;
        }

        if (get_query_var('home_app')) {
            $this->stacks_update_multisite_options(get_query_var('blog_id'), 'home_app', get_query_var('home_app'));
            $GLOBALS['stacks_dynamic_translations']->get_data(get_query_var('blog_id'), get_query_var('home_app'));
            if (json_decode(json_encode(get_query_var('home_app')), false) == $this->stacks_get_multisite_option('home_app')) {
                wp_send_json(array('success' => true));
            }
            wp_send_json(array('success' => false));
        }

        if (get_query_var('app_settings')) {
            $this->stacks_update_multisite_options(get_query_var('blog_id'), 'app_settings', get_query_var('app_settings'));
            $GLOBALS['stacks_builder']->update_project_app_settings(get_query_var('project_id'), json_encode(get_query_var('app_settings')));
            if (json_decode(json_encode(get_query_var('app_settings')), false) == $this->stacks_get_multisite_option('app_settings')) {
                wp_send_json(array('success' => true));
            }
            wp_send_json(array('success' => false));
        }

        if (get_query_var('content_settings')) {
            $this->stacks_update_multisite_options(get_query_var('blog_id'), 'content_settings', get_query_var('content_settings'));
            $GLOBALS['stacks_builder']->update_project_content_settings(get_query_var('project_id'), json_encode(get_query_var('content_settings')));
            if (json_decode(json_encode(get_query_var('content_settings')), false) == $this->stacks_get_multisite_option('content_settings')) {
                wp_send_json(array('success' => true));
            }
            wp_send_json(array('success' => false));
        }

        if (get_query_var('general_settings')) {
            $this->stacks_update_multisite_options(get_query_var('blog_id'), 'general_settings', get_query_var('general_settings'));
            $GLOBALS['stacks_builder']->update_project_general_settings(get_query_var('project_id'), json_encode(get_query_var('general_settings')));
            if (json_decode(json_encode(get_query_var('general_settings')), false) == $this->stacks_get_multisite_option('general_settings')) {
                wp_send_json(array('success' => true));
            }
            wp_send_json(array('success' => false));
        }

        if (get_query_var('global_settings')) {
            $this->stacks_update_multisite_options(get_query_var('blog_id'), 'global_settings', get_query_var('global_settings'));
            $GLOBALS['stacks_builder']->update_project_global_settings(get_query_var('project_id'), json_encode(get_query_var('global_settings')));
            if (json_decode(json_encode(get_query_var('global_settings')), false) == $this->stacks_get_multisite_option('global_settings')) {
                wp_send_json(array('success' => true));
            }
            wp_send_json(array('success' => false));
        }

        if (get_query_var('get_google_json')) {
            $uploads_dir    = wp_upload_dir();
            $google_request = wp_remote_get(get_query_var('get_google_json'));
            $google_data = wp_remote_retrieve_body( $google_request );
            file_put_contents($uploads_dir['basedir'] . '/google-services.json', $google_data);
        }
    }
    public function stacks_get_multisite_option($option_name) {
        if (is_multisite()) {
            return json_decode(json_encode(get_network_option(get_current_blog_id(), $option_name)), false);
        } else {
            return json_decode(json_encode(get_option($option_name)), false);
        }
        die;
    }

    public function stacks_validate_signature($order_id = '') {
        $signature = !empty($_SERVER['HTTP_X_SIGNATURE']) ? sanitize_text_field( $_SERVER['HTTP_X_SIGNATURE'] ) : "";
        $pubString = "-----BEGIN PUBLIC KEY-----" . PHP_EOL . "MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA0sCFJ3ddBEycg86FN9xWkBs7a6S3GypjkUB5yqjsMEQDmB8qJ8zLnqKohrRnpieZTx+dQ6fpdGbBZYozO+wVeEan3lZ2W6MeXDCZ6pXvXF2o0YhUElUdKhszV583a0u5ScOahsJlSWwMDzc9V2K9LB3PBy+WGcKnfBqWHbaxEl6JUxjYjyzzDM/l9ZYInz0JxSD0IGIgOpxIHT9VhevGrXlCdiHZoN3x/lt/L2Kf2d+7rkf8hnAtX8sZ3nYd630Dh784KSuu9i2rbk51rc5neITQF6Lw5Cbl8CCS/J8r1gUYVjSVmBxyzGq9aAKwKEOz2NAygaqz9zxRN/EVGXS9zQIDAQAB" . PHP_EOL . "-----END PUBLIC KEY-----";

        $order_id = !empty($order_id) ? $order_id : get_query_var('order_id');
        $res = openssl_verify($order_id, base64_decode($signature), $pubString, OPENSSL_ALGO_SHA256);

        if (!$res) {
            return false;
        } else {
            return true;
        }
    }

    public function stacks_update_multisite_options($blog_id, $option_name, $option_value) {
        if (is_multisite()) {
            update_network_option($blog_id, $option_name, $option_value);
        } else {
            update_option($option_name, $option_value);
        }
    }
}

$GLOBALS['builder_api'] = new stacks_builder_api();
