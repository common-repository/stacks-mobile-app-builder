<?php

class JsonParserService {

    protected $file = null;
    protected $data = null;

    /**
     * pass file to the constructor
     * 
     * @param string $file url to the file 
     * 
     * @return boolean|$this
     */
    public function __construct($file) {
        $this->file = $file;
    }

    /**
     * check if file has required data or not 
     * 
     * @return boolean|$this
     */
    public function is_valid_file() {
        if ($this->file) {
            $this->data = $this->get_data_from_url();

            if ($this->data) {
                return $this;
            }
        }
        return false;
    }

    /**
     * perform request to get json data from file 
     * 
     * @return boolean
     */
    protected function get_data_from_url() {
        $response = $this->file;
        if ($response) {
            $body = json_decode($response, true);

            return $body;
        }

        return false;
    }

    /**
     * search existing data for key 
     * 
     * @param string $needle
     * @param array $haystack
     * @param string $currentKey
     * 
     * @return boolean|string
     */
    protected function search_data($needle, $haystack, $currentKey = '') {
        foreach ($haystack as $key => $value) {
            if (is_array($value)) {
                $nextKey = $this->search_data($needle, $value, $currentKey . '[' . $key . ']');

                if ($nextKey) {
                    return $nextKey;
                }
            } else if ($key == $needle) {
                return $value;
            }
        }
        return false;
    }

    /**
     * get value from data using key
     * 
     * @param string $key
     * 
     * @return string
     */
    public function get_parameter($key) {
        return $this->search_data($key, $this->data);
    }


    /**
     * replace parameter within your array
     * 
     * @param string $exisiting_key
     * @param string $new_value
     * 
     * @return array
     */
    public function replace_key($exisiting_key, $new_value) {
        array_walk_recursive($this->data, function (&$value, $key) use ($exisiting_key, $new_value) {
            if ($key == $exisiting_key) {
                $value = $new_value;
            }
        });

        return $this->data;
    }

    /**
     * loop through a list of json urls and return instance of self 
     * 
     * @param array $urls
     * 
     * @return boolean|\self
     */
    public static function get_valid_service_json_instance_from_urls($urls) {
        if (!empty($urls)) {
            foreach ($urls as $url) {
                $json_parser = new JsonParserService($url);

                if ($json_parser->is_valid_file()) {
                    return $json_parser;
                }
            }
        }

        return false;
    }
}
