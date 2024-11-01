<?php


class stacks_views extends WP_REST_Controller {

  public function __construct() {
  }

  public function stacks_register_routes() {
    $namespace = 'v4';

    register_rest_route(
      $namespace,
      '/get-stacks-view/',
      array(
        'methods' => 'GET',
        'callback' => array($this, 'get_view'),
        'args' => array(),
      )
    );

    register_rest_route(
      $namespace,
      '/update-stacks-view/',
      array(
        'methods' => 'POST',
        'callback' => array($this, 'stacks_update_view'),
        'args' => array(),
      )
    );

    register_rest_route(
      $namespace,
      '/delete-all-views/',
      array(
        'methods' => 'POST',
        'callback' => array($this, 'delete_all_views'),
        'args' => array(),
      )
    );
  }

  public function stacks_update_view($request) {
    $response = $GLOBALS['stacks_builder']->update_view( sanitize_text_field( $request['view_id'] ), $request['data'], sanitize_text_field( $request['project_id'] ), sanitize_text_field( $request['view_name'] ), sanitize_text_field( $request['status'] ));
    var_dump(sanitize_text_field( $request['view_id'] ), $request['data'], sanitize_text_field( $request['project_id'] ), sanitize_text_field( $request['view_name'] ), sanitize_text_field( $request['status'] ));
    return wp_send_json_success( $response );
  }

  public function delete_all_views($request) {
    $response = $GLOBALS['stacks_builder']->delete_all_views( $request['project_id'] );
    return wp_send_json_success( $response );
  }
}
add_action('rest_api_init', function () {
  $rest_registeration_controller = new stacks_views();
  $rest_registeration_controller->stacks_register_routes();
});
