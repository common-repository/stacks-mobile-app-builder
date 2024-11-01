<?php

class Stacks_Woocommerce_Integration_Api implements Stacks_Api_Interface {
    const ENDPOINT = 'avaris-wc-rest';
    const VERSION = 'v3';
    const VERSION_1 = 'v1';
    const VERSION_2 = 'v2';

    /**
     * Woocommerce Api Controllers module
     * 
     * @var StacksWoocommerceApiControllersLoader 
     */
    protected $controllers_loader = null;

    /**
     * get Stacks_Api_Auth
     * 
     * @var Stacks_Api_Auth
     */
    protected $authentication_service = null;

    /**
     * get instance 
     * 
     * @var Stacks_Woocommerce_Integration_Api 
     */
    protected static $instance = null;

    /**
     * get Current Object
     * 
     * @return Stacks_Woocommerce_Integration_Api
     */
    public static function get_instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * validate api requirements are satisfied 
     * 
     * @return boolean
     */
    public function validate_api_requirments_are_satisfied() {
        if (class_exists('WooCommerce')) {
            if (version_compare(wc()->version, '3.3.3', ">=")) {
                return true;
            }
        }
        return false;
    }

    /**
     * get woocommerce loader Instance 
     * 
     * @return StacksWoocommerceApiControllersLoader
     */
    private function get_controller_loader_instance() {
        if (is_null($this->controllers_loader)) {
            $this->controllers_loader = new StacksWoocommerceApiControllersLoader();
        }

        return $this->controllers_loader;
    }

    /**
     * Controllers class names
     * 
     * @return array
     */
    public function get_controllers_class_names() {
        return apply_filters('stacks_woocommerce_api_controllers_class_names', $this->get_controller_loader_instance()->get_controllers_class_names());
    }

    /**
     * Endpoint
     * 
     * @return string
     */
    public function get_endpoint() {
        return apply_filters('stacks_woocommerce_api_endpoint', self::ENDPOINT);
    }

    /**
     * Version
     * 
     * @return string
     */
    public function get_version() {
        return apply_filters('stacks_woocommerce_api_version', self::VERSION);
    }

    /**
     * load session module and run it 
     */
    public function load_stacks_woo_session_module() {
        if (apply_filters('stacks_woocommerce_allow_session_handler', true) && self::is_request_to_stacks_api()) {
            // launch filters
            Stacks_Session_Handler::add_filters();
        }

        do_action('stacks_woocommerce_after_session_handler');
    }

    public function get_authentication_service() {
        if (is_null($this->authentication_service)) {
            $this->authentication_service = Stacks_Api_Auth::get_instance();
        }
        return $this->authentication_service;
    }

    public function includes() {
        // load stacks api helper functions 
        require_once  STACKS_WC_API . '/stacks-api-helper-functions.php';

        // load controllers loader 
        require_once STACKS_WC_API . '/controllers-loader.php';

        // load auth modules 
        require_once STACKS_WC_API . '/authentication/stacks-api-auth.php';
        $this->get_authentication_service();

        // - Base controller 
        require_once STACKS_WC_API . '/abstract-controller.php';

        if (is_stacks_woocommerce_active()) {
            // require stacks session handler file 
            require_once STACKS_WC_API . "/stacks-session-handler.php";
        }


        $models            = $this->get_models();
        $services        = $this->get_services();
        $formatting_modules    = $this->get_formatters();
        $controllers        = $this->get_controller_loader_instance()->include_controllers();

        stacks_require_once_files($services);
        stacks_require_once_files($models);
        stacks_require_once_files($formatting_modules);

        // Controllers
        stacks_require_once_files($controllers);
    }

    /**
     * return array of models 
     * 
     * @return array
     */
    public function get_models() {
        // include Controllers and models 
        // Models 
        $models_location = sprintf("%s/models", STACKS_WC_API);

        return apply_filters('stacks_woocommerce_api_models', [
            // traits 
            $models_location . '/traits/facebook.php',

            // Abstract Model
            $models_location . '/abstract-model.php',

            // models
            $models_location . '/categories-model.php',
            $models_location . '/products-model.php',
            $models_location . '/cart-model.php',
            $models_location . '/user-model.php',

            // registration providers and model
            $models_location . '/registration-providers/registration-provider-interface.php',
            $models_location . '/registration-providers/manual.php',
            $models_location . '/registration-providers/facebook.php',
            $models_location . '/registration-model.php',

            // login providers and model
            $models_location . '/login-providers/login-providers-interface.php',
            $models_location . '/login-providers/login-provider.php',
            $models_location . '/login-model.php',
            $models_location . '/menu-model.php',
            $models_location . '/login-providers/facebook-login-provider.php',
            $models_location . '/login-providers/manual-login-provider.php',
        ]);
    }

    /**
     * return list of services 
     * 
     * @return array
     */
    public function get_services() {
        $services_location = sprintf(STACKS_WC_API . '/services/');
        $woocommerce_data = [];
        $data = [
            $services_location . 'service-translation.php',
            $services_location . 'service-response.php',
            $services_location . 'service-request-data.php',
            $services_location . 'service-mailing.php',
            $services_location . 'service-new-passwords.php',
            $services_location . 'service-validate-variation.php',
        ];
        if (is_stacks_woocommerce_active()) {
            $woocommerce_data = [
                $services_location . 'service-stacks-wc-checkout.php',
                $services_location . 'service-stacks-wc-points.php'
            ];
        }
        foreach ($woocommerce_data as $value) {
            $data[] = $value;
        }
        return apply_filters('stacks_woocommerce_api_services', $data);
    }

    /**
     * get formatters 
     * 
     * @return array
     */
    public function get_formatters() {
        $woocommerce_data = [];
        $data = [
            STACKS_WC_API . '/formatting/plates-formatter.php',
            STACKS_WC_API . '/formatting/addons-formatting.php',
            STACKS_WC_API . '/formatting/log-formatting.php',
        ];
        if (is_stacks_woocommerce_active()) {
            $woocommerce_data = [
                STACKS_WC_API . '/formatting/orders-formatting.php',
                STACKS_WC_API . '/formatting/product-formatting.php',
                STACKS_WC_API . '/formatting/single-product-formatting.php',
                STACKS_WC_API . '/formatting/variations-formatting.php',
                STACKS_WC_API . '/formatting/shipping-method-formatting.php',
                STACKS_WC_API . '/stacks-woocommerce-data-formatting.php'
            ];
        }
        foreach ($woocommerce_data as $value) {
            $data[] = $value;
        }
        return apply_filters('stacks_woocommerce_api_formatters', $data);
    }

    /**
     * Check if is request to our REST API.
     *
     * @return bool
     */
    public static function is_request_to_stacks_api() {
        $request_uri = sanitize_url($_SERVER['REQUEST_URI']);

        if (empty($request_uri)) {
            return false;
        }

        $rest_prefix = trailingslashit(rest_get_url_prefix());

        // Check if our endpoint.
        $stacks_rest_api = (false !== strpos($request_uri, $rest_prefix . self::ENDPOINT));

        return apply_filters('stacks_woocommerce_rest_is_request_to_rest_api', $stacks_rest_api);
    }
}

Stacks_API::add_registrar(Stacks_Woocommerce_Integration_Api::get_instance());
