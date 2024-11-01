<?php

class Polylang_Stacks_Integration_Model extends PLL_Model {

    public function __construct(&$options) {
        parent::__construct($options);

        $this->options = apply_filters('edit_polylang_settings', $options);

        $this->update_language_if_needed();
    }

    /**
     * Filter Polylang languages and update if the requested language exists 
     * 
     * update options directly
     */
    public function update_language_if_needed() {
        $req_lang = $this->get_requested_language();

        if ($req_lang) {
            $locales = $this->get_languages_list(['fields' => 'locale']);

            if (!empty($locales)) {
                foreach ($locales as $locale) {
                    if ($req_lang == $locale) {
                        $this->change_language($locale);
                        return;
                    }
                }
            }
        }
    }

    /**
     * change options default language directly 
     * 
     * @param string $lang
     */
    public function change_language($lang) {
        $this->options['default_lang'] = $lang;
    }

    /**
     * Get User Requested Language 
     * 
     * @return boolean|string
     */
    public static function get_requested_language() {
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            return sanitize_text_field($_SERVER['HTTP_ACCEPT_LANGUAGE']);
        }

        return false;
    }

    /**
     * Check if language is supported or not if supported return it if not then return the default language
     * 
     * @return array
     */
    public static function check_language_supported($requested_lang) {
        $languages_list = self::get_locale_for_existing_languages();

        if ($requested_lang && in_array($requested_lang, $languages_list)) {
            return $requested_lang;
        } else {
            return pll_default_language();
        }
    }
}
