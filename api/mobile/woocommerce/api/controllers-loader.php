<?php

/**
 * Stacks Controllers loader module 
 * 
 * this module is responsible of loading loader file from requested version and get required details from this loader
 * 
 * @todo receive version from 
 */
class StacksWoocommerceApiControllersLoader {

    private $controllers_location = null;

    /**
     * @var Stacks_ControllersLoader
     */
    public $controllers_loader = null;

    public function __construct() {
        $this->controllers_location = sprintf("%s/v%s", STACKS_WC_API, $this->get_version());

        $this->controllers_loader = $this->get_controllers_loader_from_version();
    }

    /**
     * get version number from headers 
     * 
     * @return string
     */
    public function get_version() {
        return 1;
    }

    /**
     * load controllers loader from controllers version folder 
     * 
     * @return Stacks_ControllersLoader|false
     */
    protected function get_controllers_loader_from_version() {
        if (file_exists($this->controllers_location . '/loader.php')) {
            require_once $this->controllers_location . '/loader.php';

            $this->controllers_loader = new Stacks_ControllersLoader();
        } else {
            $this->controllers_loader = false;
        }

        return $this->controllers_loader;
    }

    /**
     * get controllers location
     * 
     * @return array
     */
    public function include_controllers() {
        if ($this->controllers_loader) {
            return $this->controllers_loader->get_controllers();
        }

        return [];
    }

    /**
     * get controllers names to get instantiated
     * 
     * @return array
     */
    public function get_controllers_class_names() {
        if ($this->controllers_loader) {
            return $this->controllers_loader->get_controllers_class_names();
        }

        return [];
    }
}
