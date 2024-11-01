<?php

class stacks_users extends WP_REST_Controller {

  public function __construct() {
  }

  public function stacks_register_routes() {
    $namespace = 'v4';

    register_rest_route(
      $namespace,
      '/get-stacks-users/',
      array(
        'methods' => 'GET',
        'callback' => array($this, 'get_stacks_users'),
        'args' => array(),
      )
    );
  }

  public function get_stacks_users() {
    /**
     * Get users from the customer website
     */
    wp_send_json(get_users());
  }
}
add_action('rest_api_init', function () {
  $rest_registeration_controller = new stacks_users();
  $rest_registeration_controller->stacks_register_routes();
});
