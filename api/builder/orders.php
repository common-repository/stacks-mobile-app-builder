<?php

class stacks_orders extends WP_REST_Controller {

  public function __construct() {
  }

  public function stacks_register_routes() {
    $namespace = 'v4';

    register_rest_route(
      $namespace,
      '/get-stacks-orders/',
      array(
        'methods' => 'GET',
        'callback' => array($this, 'get_stacks_orders'),
        'args' => array(),
      )
    );
  }

  public function get_stacks_orders() {
    /**
     * Validate the signature
     */
    $signature_validation = $GLOBALS['builder_api']->stacks_validate_signature( sanitize_text_field( $_GET['order_id'] ));
    if (!$signature_validation) {
      return false;
    }
    /**
     * Get orders of WooCommerce that is coming from Stacks Mobile App
     */
    $orders = wc_get_orders(array('numberposts' => -1));
    $mobile_orders = array();
    foreach ($orders as $key => $order) {
      if (get_post_meta($order->get_id(), 'mobile_app_order', true)) {
        array_push($mobile_orders, array(
          'id' => $order->get_id(),
          'user_email' => $order->get_billing_email(),
          'status' => $order->get_status(),
          'date' => date("Y-m-d", strtotime($order->order_date)),
          'total' => $order->get_total()
        ));
      }
    }
    wp_send_json($mobile_orders);
  }
}
add_action('rest_api_init', function () {
  $rest_registeration_controller = new stacks_orders();
  $rest_registeration_controller->stacks_register_routes();
});
