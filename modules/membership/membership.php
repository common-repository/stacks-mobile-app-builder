<?php

register_meta('post', 'user_access', [
    'object_subtype' => 'pmpro_course',
    'type' => 'boolean',
    'single' => true,
    'show_in_rest' => true
]);

register_meta('post', 'lesson_completion', [
    'object_subtype' => 'pmpro_lesson',
    'type' => 'string',
    'single' => true,
    'show_in_rest' => true
]);

add_filter( 'rest_pmpro_course_query', 'rest_pmpro_course_query', 10, 2 );

function rest_pmpro_course_query( $args, $request ) {
    $user_id = $request->get_param( 'user_id' );

    if( empty( $user_id ) )
    return $args;

    $posts_array = get_posts( $args );
    foreach($posts_array as $post_array)
    {
        $has_access = pmpro_has_membership_access( $post_array->ID, $user_id );
        update_post_meta($post_array->ID, 'user_access', $has_access);
    }

    return $args;
}

add_action('rest_api_init', function () {
    $rest_registeration_controller = new rest_projects();
    $rest_registeration_controller->register_routes();
});
    

class rest_projects extends WP_REST_Controller {

    public function __construct() {
    }

    public function register_routes() {
    $namespace = 'v2';

    // Get course lessons
    register_rest_route(
        $namespace,
        '/getCourse/(?P<id>\d+)/(?P<user_id>[a-zA-Z0-9-]+)',
        array(
        'methods' => 'GET',
        'callback' => array($this, 'get_course'),
        'args' => array(
            // 'id' => array(
            //   'validate_callback' => function($param, $request, $key) {
            //     return is_numeric( $param );
            //   }
            // ),
        ),
        )
        );

        // Get lesson status
    register_rest_route(
        $namespace,
        '/getLessonStatus/(?P<lesson_id>[\d]+)/(?P<course_id>[\d]+)/(?P<user_id>[\d]+)',
        array(
            'methods' => 'GET',
            'callback' => array($this, 'get_lesson_status'),
            'args' => array(
            'lesson_id' => array(
                'validate_callback' => function($param, $request, $key) {
                return is_numeric( $param );
                }
            ),
            'course_id' => array(
                'validate_callback' => function($param, $request, $key) {
                    return is_numeric( $param );
                }
                ),
                'user_id' => array(
                'validate_callback' => function($param, $request, $key) {
                    return is_numeric( $param );
                }
                ),
            ),
        )
        );

            // Set lesson status
    register_rest_route(
        $namespace,
        '/setLessonStatus/(?P<lesson_id>[\d]+)/(?P<user_id>[\d]+)',
        array(
        'methods' => 'GET',
        'callback' => array($this, 'set_lesson_status'),
        'args' => array(
            'lesson_id' => array(
            'validate_callback' => function($param, $request, $key) {
                return is_numeric( $param );
            }
            ),
            'user_id' => array(
                'validate_callback' => function($param, $request, $key) {
                return is_numeric( $param );
                }
            ),
        ),
        )
        );

        // get membership levels
    register_rest_route(
        $namespace,
        '/getMembershipLevels(?:/(?P<course_id>\d+))?(?:/(?P<user_id>\d+))?',
        array(
        'methods' => 'GET',
        'callback' => array($this, 'get_membership_levels'),
        'args' => array(
        ),
        )
        );

        // is membership active
    register_rest_route(
        $namespace,
        '/isMembership',
        array(
        'methods' => 'GET',
        'callback' => array($this, 'is_membership_active'),
        'args' => array(
        ),
        )
        );

        // Get user memberships
    register_rest_route(
        $namespace,
        '/getUserMemberships/(?P<user_id>[\d]+)',
        array(
        'methods' => 'GET',
        'callback' => array($this, 'get_user_memberships'),
        'args' => array(
            'user_id' => array(
                'validate_callback' => function($param, $request, $key) {
                return is_numeric( $param );
                }
            ),
        ),
        )
        );

        // Cancel user membership
    register_rest_route(
        $namespace,
        '/cancelUserMembership/(?P<level_id>[\d]+)/(?P<user_id>[\d]+)',
        array(
        'methods' => 'GET',
        'callback' => array($this, 'cancel_user_membership'),
        'args' => array(
            'level_id' => array(
            'validate_callback' => function($param, $request, $key) {
                return is_numeric( $param );
            }
            ),
            'user_id' => array(
                'validate_callback' => function($param, $request, $key) {
                return is_numeric( $param );
                }
            ),
        ),
        )
        );

        // update membership level
    register_rest_route(
        $namespace,
        '/updateMembership/(?P<user_id>[a-zA-Z0-9-]+)',
        array(
        'methods' => 'GET',
        'callback' => array($this, 'update_membership'),
        'args' => array(
            // 'id' => array(
            //   'validate_callback' => function($param, $request, $key) {
            //     return is_numeric( $param );
            //   }
            // ),
        ),
        )
        );


    }

public function get_course($data) {
    $lessons = pmpro_courses_get_lessons( $data['id'] );

    if ( empty( $lessons ) ) {
    return null;
    }

    if( $data['user_id'] == 'anonymous') {
    return $lessons;
    }

    foreach($lessons as $lesson)
    {
    $lesson_status = pmpro_courses_get_user_lesson_status( $lesson->ID, $data['id'], $data['user_id'] );
    update_post_meta($lesson->ID, 'lesson_completion', $lesson_status);
    $lesson->lesson_completion = $lesson_status;
    }

    return $lessons;
    }

    public function get_lesson_status($data) {
    $lesson_status = pmpro_courses_get_user_lesson_status( $data['lesson_id'], $data['course_id'], $data['user_id'] );

    
    return $lesson_status;
    }

    public function set_lesson_status($data) {
    return pmpro_courses_toggle_lesson_progress( $data['lesson_id'], $data['user_id']);
    }

    public function get_membership_levels($data) {
    $pmpro_Levels = pmpro_sort_levels_by_order( pmpro_getAllLevels( true, true ));
    $pmprp_course_level = pmpro_has_membership_access($data['course_id'], $data['user_id'], true);

    $all_membership_levels = (object)[
        'all_membership_levels' => $pmpro_Levels,
    ];
    if($data['course_id']){
        $pmpro_result = array();
        foreach ( $pmpro_Levels as $key => $value ) {
        foreach ($pmprp_course_level[1] as $element) {
            if ( $element == $value->id ) {
            $pmpro_result[] = $pmpro_Levels[$key];
            unset($pmpro_Levels[$key]);
            $all_membership_levels = (object)[
            'all_membership_levels' => $pmpro_Levels,
        ];
                $all_membership_levels = (object) array_merge( (array)$all_membership_levels, array( 'course_membership_level' => $pmpro_result ) );
            }
        }
    }
    }

    return $all_membership_levels;
    }

    public function is_membership_active() {
    return is_plugin_active( 'paid-memberships-pro/paid-memberships-pro.php' );
    }

    public function get_user_memberships($data) {
    return pmpro_getMembershipLevelForUser($data['user_id']);
    }

    public function cancel_user_membership($data) {
    return pmpro_cancelMembershipLevel( $data['level_id'], $data['user_id'], 'inactive');
    }

    public function update_membership($data) {

    $posts_array = get_posts(array('post_type' => 'pmpro_course'));
    foreach($posts_array as $post_array)
    {
        $has_access = pmpro_has_membership_access( $post_array->ID, $data['user_id'] );
        update_post_meta($post_array->ID, 'user_access', $has_access);
    }

    return true;
    }
}