<?php

class Stacks_PlatesFormatter {

    protected $items;

    /**
     * set items you would like to format
     * 
     * @param array $items
     * @return $this
     */
    public function set_items($items) {
        $this->items = $items;

        return $this;
    }

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
}
