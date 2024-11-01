<?php

/**
 * Plates Orders Formatter Extension
 */
class Stacks_OrdersFormatting extends WC_REST_Orders_Controller {

    protected $items;

    /**
     * get id from the item if exists 
     * 
     * @param object|int $item
     * @return boolean|int
     */
    protected function get_id($item) {
        if (is_object($item) && isset($item->ID)) {
            return $item->ID;
        } else {
            return (int) $item;
        }

        return false;
    }

    /**
     * Set Items for Formatting 
     * 
     * @param array|int $items
     */
    public function __construct($items) {
        $this->items = $items;

        $this->request['dp'] = false;
    }

    /**
     * validate id is an order 
     * 
     * @param int $id
     * @return boolean
     */
    protected function validate_valid_order($id) {
        if (get_post_status($id) && get_post_type($id) == 'shop_order') {
            return true;
        }
        return false;
    }

    /**
     * Format Group of items
     * 
     * @return array
     */
    public function format() {
        if (is_array($this->items)) {

            foreach ($this->items as $index => $item) {

                $id = $this->get_id($item);

                if (!$id || !$this->validate_valid_order($id)) {
                    unset($this->items[$index]);
                    continue;
                }

                $this->items[$index] = $this->format_single_order($id);
            }

            return array_values($this->items);
        } else {

            $id = $this->get_id($item);

            if (!$id || !$this->validate_valid_order($id)) {
                return false;
            }

            return $this->format_single_order($id);
        }

        return array();
    }

    /**
     * Format Single item and Extract values needed
     * 
     * @param object $id
     * @return array
     */
    protected function format_single_order($id) {
        $object = wc_get_order($id);
        $data = $this->get_formatted_item_data($object);

        $data['needs_payment'] = $this->order_needs_payment($object);
        $data['can_be_canceled'] = $this->order_can_be_canceled($object);

        return apply_filters('avaris-formatting-order', $data);
    }

    /**
     * Check if order can be canceled
     * 
     * @param object $order
     * @return boolean
     */
    public function order_can_be_canceled($order) {
        if (!in_array($order->get_status(), apply_filters('woocommerce_valid_order_statuses_for_cancel', array('pending', 'failed'), $order))) {
            return false;
        }

        return true;
    }

    /**
     * check if order needs payment or not 
     * 
     * @param object $order
     * @return boolean
     */
    public function order_needs_payment($order) {
        return $order->needs_payment();
    }
}
