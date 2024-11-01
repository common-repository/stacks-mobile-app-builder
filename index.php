<?php

/*
 * Plugin Name: Stacks Mobile App Builder
 * Author: Stacks
 * Author URI: stacksmarket.co
 * Description: Enjoy the fast and easy experience of building your Ecommerce mobile application
 * Version: 5.2.3
 */
class stacks_app_builder {

    public function __construct() {
        add_action('after_setup_theme', array($this, 'initialize'));

        add_action( 'admin_menu', array( &$this, 'stacks_admin_menu' ) );

        add_action('admin_enqueue_scripts', array( &$this, 'stacks_admin_enqueue_scripts') ); 

        register_activation_hook(__FILE__, array($this, 'stacks_app_activate'));

    }

    public function initialize() {
        require_once 'helper_functions.php';
        require_once 'api/main.php';
        require_once 'modules/main.php';
    }

    /**
     * Make sure that there is no old versions of Stacks Plugin that is active, this should prevent conflicts
     *
     * @param [type] $plugin
     * @return void
     */
    public function stacks_app_activate() {
        $all_plugins = get_plugins();
        foreach ($all_plugins as $key => $value) {
            if($value['Name'] == 'Stacks App') {
                if(is_plugin_active($key)) {
                    deactivate_plugins( $key );
                }
            }
        }

        flush_rewrite_rules();
    }

    // adds stacks menu item to wordpress admin dashboard
    function stacks_admin_menu() {
        add_menu_page( __( 'Stacks Dashboard' ),
        __( 'Stacks' ),
        'manage_options',
        'stacks-welcome',
        array( &$this, 'stacks_admin_menu_page' ), plugin_dir_url( __FILE__ ) . '/assets/images/favicon.png'
        );
        
    }

    public function stacks_admin_menu_page() {
        // Load home page
        require_once untrailingslashit( __DIR__ ) . '/views/stacks-welcome.php'; 
    }

    public function stacks_admin_enqueue_scripts(){
                
        $current_page = get_current_screen()->base;
        
        wp_enqueue_style( 'stacks_main_css', plugins_url('assets/css/stacks-main.css', __FILE__), array());

    }

}

new stacks_app_builder();
