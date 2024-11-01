<?php


class stacks_connectivity extends WP_REST_Controller {

  public function __construct() {
  }

  public function stacks_register_routes() {
    $namespace = 'v4';

    register_rest_route(
      $namespace,
      '/get-stacks-version/',
      array(
        'methods' => 'GET',
        'callback' => array($this, 'get_stacks_version'),
        'args' => array(),
      )
    );

    register_rest_route(
      $namespace,
      '/get_woocommerce_status/',
      array(
        'methods' => 'GET',
        'callback' => array($this, 'stacks_get_woocommerce_status'),
        'args' => array(),
      )
    );
  }

  public function get_stacks_version() {
    /**
     * Get Version of the plugin
     */
    wp_send_json(array('version' => stacks_version));
  }

  public function stacks_get_woocommerce_status() {
    return is_stacks_woocommerce_active();
  }
}
add_action('rest_api_init', function () {
  $rest_registeration_controller = new stacks_connectivity();
  $rest_registeration_controller->stacks_register_routes();
});
