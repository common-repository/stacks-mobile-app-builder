<?php

class stacks_posts extends WP_REST_Controller {

  public function __construct() {
  }

  public function stacks_register_routes() {
    $namespace = 'v4';

    register_rest_route(
      $namespace,
      '/get_stacks_posts/',
      array(
        'methods' => 'POST',
        'callback' => array($this, 'get_stacks_posts'),
        'args' => array(),
      )
    );
  }

  public function get_stacks_posts($request) {
    $args = stacks_recursive_sanitize_text_field($request['args']);
    $args['posts_per_page'] = $args['numberposts'];
    $args['lang'] = substr(get_locale(), 0, strpos(get_locale(), "_")) ? substr(get_locale(), 0, strpos(get_locale(), "_")) : get_locale();
    if($args['post_type'] == 'pmpro_course' && $args['source'] == 'by_category') {
      $args['tax_query'] = [
        [
          'taxonomy' =>  'pmpro_course_category',
          'field' => 'term_id', 
          'terms' => $args['category'], /// Where term_id of Term 1 is "1".
        ]
      ];
    } else if( $args['source'] == 'by_category' ) {
      $args['tax_query'] = [
        [
          'taxonomy' =>  'category',
          'field' => 'term_id', 
          'terms' => $args['category'], /// Where term_id of Term 1 is "1".
        ]
      ];
    }
    
    $wp_query= null;
    $wp_query = new WP_Query();
    $posts = $wp_query->query($args);
    $formatted_posts = [];
    foreach ($posts as $key => $post) {
      $formatted_posts[$key]['ID'] = $post->ID;
      $formatted_posts[$key]['guid'] = $post->guid;
      $formatted_posts[$key]['post_author'] = $post->post_author;
      $formatted_posts[$key]['post_content'] = $post->post_content;
      $formatted_posts[$key]['post_date'] = $post->post_date;
      $formatted_posts[$key]['post_modified'] = $post->post_modified;
      $formatted_posts[$key]['post_name'] = $post->post_name;
      $formatted_posts[$key]['post_status'] = $post->post_status;
      $formatted_posts[$key]['post_title'] = $post->post_title;
      $formatted_posts[$key]['post_image'] = get_the_post_thumbnail_url($post, 'large');
    }

    wp_send_json($formatted_posts);
  }
}
add_action('rest_api_init', function () {
  $rest_registeration_controller = new stacks_posts();
  $rest_registeration_controller->stacks_register_routes();
});
