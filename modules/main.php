<?php
class stacks_mobile_app_builder_modules {

    public function __construct() {
        $this->initialize();
    }

    public function initialize() {
        require_once 'db.php';
        require_once 'push-notifications/main.php';
        require_once 'checkout/checkout.php';
        require_once 'polylang/main.php';
        if(is_plugin_active( 'paid-memberships-pro/paid-memberships-pro.php' )) {
            require_once 'membership/membership.php';
        }
    }
}

new stacks_mobile_app_builder_modules();
