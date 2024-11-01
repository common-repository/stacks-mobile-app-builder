<?php

abstract class Stacks_AbstractModel {
    /**
     * format callback
     * @var callback 
     */
    protected $format_callback = null;

    /**
     * set format callback
     * @param callback $function
     * @return $this
     */
    public function set_format_callback($function) {
        $this->format_callback = $function;

        return $this;
    }

    /**
     * Check if current model has callback format function 
     * @return boolean
     */
    public function has_callback_function() {
        return is_null($this->format_callback) ? false : true;
    }

    /**
     * Apply format callback function in multiple elements 
     * @param array $items
     * @return array
     */
    public function apply_format_callback_items($items) {
        if ($this->has_callback_function()) {
            if (is_array($items)) {
                return array_map($this->format_callback, $items);
            }

            return $this->apply_format_callback_item($items);
        }

        return $items;
    }

    /**
     * Apply format callback function in single element 
     * @param object $item
     * @return array
     */
    public function apply_format_callback_item($item) {
        if ($this->has_callback_function()) {
            return call_user_func($this->format_callback, $item);
        }
        return $item;
    }
}
