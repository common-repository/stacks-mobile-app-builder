<?php

class Polylang_Stacks_Integration {
    public function __construct() {
        add_filter('pll_model', [$this, 'define_our_replacement_model']);
    }

    public function define_our_replacement_model($name) {
        if ($name == 'PLL_Model') {
            $this->require_model();
            $name = 'Polylang_Stacks_Integration_Model';
        }
        return $name;
    }

    public function require_model() {
        require_once __DIR__ . '/polylang_stacks_integration_model.php';
    }
}

new Polylang_Stacks_Integration();
