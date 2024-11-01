<?php


/**
 * All Api's should either plugins or theme additional integrations should implement this interface
 */
interface Stacks_Api_Interface {
    /**
     * Api Required files 
     */
    public function includes();

    /**
     * Api controllers class names
     */
    public function get_controllers_class_names();

    /**
     * Api endppoint
     */
    public function get_endpoint();

    /**
     * Api Version
     */
    public function get_version();
}
