<?php

class Stacks_PolylangIntegration {

    /**
     * Create new Group with a bunch of strings to add 
     * 
     * @param array $app_strings
     * @param string $group
     * 
     * @return boolean
     */
    public static function translate_strings($app_strings, $group) {
        if (self::is_installed()) {
            foreach ($app_strings as $string) {
                pll_register_string(sanitize_title($string), $string, $group);
            }

            return true;
        }

        return false;
    }

    /**
     * check if polylang is installed 
     * 
     * @return bool
     */
    public static function is_installed() {
        if (!function_exists('is_plugin_active')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        if (
            (is_plugin_active('polylang/polylang.php') || is_plugin_active('polylang-pro/polylang.php'))
            ||
            (is_plugin_active_for_network('polylang/polylang.php') || is_plugin_active_for_network('polylang-pro/polylang.php'))
        ) {
            if (isset($GLOBALS['polylang'], \PLL()->model, PLL()->links_model)) {
                if (pll_default_language()) {
                    return true;
                }
            }
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

    /**
     * Get string translation from polylang
     * 
     * @param string $string
     * @param string $language
     * 
     * @return string translation
     */
    public static function get_pll_registered_String_translation($string, $language) {
        return pll_translate_string($string, $language);
    }

    /**
     * return Translations Exists 
     * 
     * @return array
     */
    public static function get_existing_languages() {
        $languages = [];

        if (self::is_installed()) {
            $polylang_languages = PLL()->model->get_languages_list();
            
            foreach ($polylang_languages as $language) {
                $languages[] = [
                    "id" => $language->locale,
                    "locale" => $language->name,
                    "dir" => $language->is_rtl ? 'rtl' : 'ltr',
                    "default" => $language->slug == pll_default_language() ? true : false, 
                ];
            }
        }

        if (empty($languages)) {
            $languages = self::get_default_languages();
        }

        return $languages;
    }

    /**
     * Get Default language within the site if POLYLANG is not installed
     * 
     * @return array
     */
    private static function get_default_languages() {
        $locale = get_locale();

        $languages = [
            [
                "id" => $locale,
                "locale" => $locale == 'ar' ? 'العربية' : "English (US)",
                "dir" => $locale == 'ar' ? 'rtl' : "ltr",
            ]
        ];

        return $languages;
    }


    /**
     * Get locale for existing languages 
     * 
     * @return array
     */
    public static function get_locale_for_existing_languages() {
        $existing_translations = self::get_existing_languages();

        $locale = [];

        foreach ($existing_translations as $translation) {
            $locale[] = $translation['id'];
        }

        return $locale;
    }

    /**
     * Check if Arabic is chosen or not 
     * 
     * @return bool
     */
    public static function is_arabic_translation_choosen() {
        if (self::is_installed()) {
            $languages_list = self::get_locale_for_existing_languages();

            return in_array('ar', $languages_list);
        }

        return false;
    }

    /**
     * Get Arabic Term from languages in polylang
     * 
     * @return boolean|WP_Term
     */
    public static function get_arabic_term_from_Langs() {
        // clean cache 
        PLL()->model->clean_languages_cache();

        $languages = PLL()->model->get_languages_list();

        $languages_filter = function ($language) {
            return $language->slug == 'ar';
        };

        $ar_langs = array_values(array_filter($languages, $languages_filter));

        if (!empty($ar_langs)) {
            return $ar_langs[sizeof($ar_langs) - 1];
        } else {
            return false;
        }
    }
}
