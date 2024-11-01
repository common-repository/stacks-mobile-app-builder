<?php

abstract class Stacks_AbstractPagesController extends Stacks_AbstractController {

    /**
     * Get Page Background Image
     * 
     * @return array
     */
    public function get_page_background_image($return = false) {
        $image = Stacks_ContentSettings::get_setting(static::get_background_image_id());

        if ($return) {
            return $image;
        }

        return $this->return_success_response(['image' => $image]);
    }

    /**
     * get collection parameters for get request
     * 
     * @return array
     */
    protected function get_collection_params_get() {
        return array();
    }

    /**
     * get option from theme options 
     * @param string $option
     * @return string
     */
    public function get_option($option) {
        return Stacks_ContentSettings::get_setting($option);
    }
}
