<?php

/**
 * Plates Log Formatter Extension
 */
class Stacks_LogFormatting extends Stacks_PlatesFormatter {
    /**
     * Format Group of items | just a single item
     * 
     * @return array
     */
    public function format() {
        if (!empty($this->items)) {

            if (is_array($this->items)) {

                return array_map([$this, 'format_log'], array_values(array_filter($this->items, [$this, 'filter_log'])));
            } else {

                return self::format_log($this->items);
            }
        } else {

            return array();
        }
    }

    /**
     * Format Single item and Extract values needed
     * 
     * @param object $item
     * @return array
     */
    protected function format_log($item) {
        return apply_filters('avaris-formatting-log', [
            'id' => $item->id,
            'type' => $item->type,
            'date' => strtotime($item->date),
            'points' => $item->points,
            'description' => str_replace('"', '', $item->description),
            'date_display_human' => $item->date_display_human,
        ]);
    }

    /**
     * Log Filtration
     * 
     * @param object $item
     * @return boolean
     */
    protected function filter_log($item) {
        $points = (int) $item->points;

        if ($points == 0) {
            return false;
        }

        return true;
    }
}
