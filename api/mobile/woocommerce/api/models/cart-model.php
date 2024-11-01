<?php

class Stacks_CartModel extends Stacks_AbstractModel {

    /**
     * get cart contents 
     * @return object
     */
    public static function get_cart_contents() {
        return WC()->cart->get_cart();
    }
}
