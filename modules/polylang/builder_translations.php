<?php

/**
 * Retrieves the content from the builder and saves the text elements to be translatable
 */
class stacks_dynamic_translations {

    public $texts = array();
    public function __construct() {
        
    }
    /**
     * Data Getter
     * @return void
     */
    public function get_data($blog, $data) {
        foreach ($data as $key => $value) {
            // Get the Text Block
            switch ($value['type']) {
                case 'text':
                    $this->texts[] = $this->filter_text_block($value);
                    break;
                case 'footer':
                    $this->filter_footer_block($value['data']['menuItems']);
                    break;
                case 'section':
                    $this->get_data($blog, $value['elements']);
                    break;
                case 'button':
                    $this->texts[] = $this->filter_button_block($value);
                    break;
                default:
                    # code...
                    break;
            }
            switch ($value['elType']) {
                case 'column':
                    $this->get_data($blog, $value['elements']);
                    break;
                default:
                    # code...
                    break;
            }
        }
        $this->save_builder_texts($blog, $this->texts);
    }

    private function filter_text_block($data) {
        return stripslashes($data['data']['value']);
    }

    private function filter_footer_block($data) {
        foreach ($data as $value) {
            $this->texts[] = $value['menuText'];
        }
    }

    private function filter_button_block($data) {
        return stripslashes($data['data']['btnText']);
    }

    private function save_builder_texts($blog_id, $value) {
        $GLOBALS['builder_api']->stacks_update_multisite_options($blog_id, 'builder_main_strings', $value);
    }
}

$GLOBALS['stacks_dynamic_translations'] = new stacks_dynamic_translations();