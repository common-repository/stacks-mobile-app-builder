<?php

class TranslationService {

    public function __construct() {
        $this->includes();

        if (Stacks_PolylangIntegration::is_installed()) {
            add_action('admin_init', [Stacks_App_Strings_Polylang_Translation::get_instance(), 'set_app_arabic_translation_strings'], 10, 1);

            add_action('admin_init', array($this,'initialize_app_strings_to_polylang'));
        }

        if (Stacks_Woocommerce_Integration_Api::is_request_to_stacks_api()) {
            add_filter('locale', array($this, 'update_wp_locale'));
        }
    }

    /**
     * Call Polylang App Strings to add app strings to polylang String Translation
     */
    public function initialize_app_strings_to_polylang() {
        Stacks_App_Strings_Polylang_Translation::get_instance()->create_polylang_group_for_app_strings();
    }

    /**
     * Include Required files
     * 
     * @return void
     */
    private function includes() {
        $translation_dir = trailingslashit(STACKS_WC_API) . 'services/translation/';

        require_once $translation_dir . 'app-strings-polylang-translation.php';
        require_once $translation_dir . 'polylang-integration.php';
        require_once $translation_dir . 'polylang-integration-filters.php';
        require_once $translation_dir . 'strings.php';
    }

    /**
     * Update Wp current language
     * 
     * @param string $locale
     * 
     * @return string
     */
    public function update_wp_locale($locale) {
        $requested_lang = self::get_requested_language();

        if (Stacks_PolylangIntegration::is_installed()) {
            return Stacks_PolylangIntegration::check_language_supported($requested_lang);
        } elseif ($requested_lang) {
            return $requested_lang;
        } else {
            return $locale;
        }
    }

    /**
     * Get User Requested Language 
     * 
     * @return boolean|string
     */
    private static function get_requested_language() {
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            return sanitize_text_field($_SERVER['HTTP_ACCEPT_LANGUAGE']);
        }

        return false;
    }
}

new TranslationService();
