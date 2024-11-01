<?php

class Stacks_API {

    /**
     * Api Client
     * 
     * @var Stacks_Api_Interface
     */
    private static $api_registerars = [];

    public function __construct() {

        add_action('rest_api_init', array($this, 'register_rest_routes'), 10);
    }

    /**
     * Register new Registrar client
     * 
     * @param Stacks_Api_Interface $api_registerar
     */
    public static function add_registrar(Stacks_Api_Interface $api_registerar) {
        $api_registerar->includes();

        self::$api_registerars[] = $api_registerar;
    }

    /**
     * Run controllers to instantiate rest api 
     */
    public function register_rest_routes() {
        do_action('stacks_api_before_loading_endpoints');

        if (!empty(self::$api_registerars)) {

            foreach (self::$api_registerars as $client) {
                $client_controllers = $client->get_controllers_class_names();

                $this->register_controllers($client_controllers);
            }
        }
    }

    /**
     * register Controllers route 
     * 
     * @param [] $controllers
     */
    protected function register_controllers($controllers) {
        foreach ($controllers as $controller) {
            $this->controller = new $controller();

            $this->controller->register_routes();
        }
    }
}
new Stacks_API();
