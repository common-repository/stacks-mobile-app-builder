<?php

class Stacks_App_Strings_Polylang_Translation {

	const APP_STRINGS_GROUP_NAME = 'Application text';

	private $app_strings = null;
	private $app_strings_ar = null;

	private static $instance = null;

	/**
	 * Allow one object from this class to exists
	 * 
	 * @return self
	 */
	public static function get_instance() {
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Get Application Strings from File
	 * 
	 * @return array
	 */
	private function include_app_strings() {
		$static_content = include trailingslashit(STACKS_WC_API) . 'services/translation/strings/app-strings.php';
		foreach (self::get_dynamic_strings() as $key => $value) {
			$static_content[$key] = $value;
		}
		return $static_content;
	}

	public function get_dynamic_strings() {
		if(is_multisite()) {
			$option_data = get_network_option(get_current_blog_id(), 'builder_main_strings');
		} else {
			$option_data = get_option('builder_main_strings');
		}
		if(!$option_data) {
			return array();
		}
		foreach ($option_data as $value) {
			$builder_dynamic_strings[$value] = $value;
		}
		return $builder_dynamic_strings;
	}

	/**
	 * Get Arabic Application Strings from File
	 * 
	 * @return array
	 */
	private static function include_ar_app_strings() {
		return include trailingslashit(STACKS_WC_API) . 'services/translation/strings/app-strings-ar.php';
	}

	/**
	 * Get Application strings 
	 * 
	 * @param string $type
	 * 
	 * @return array
	 */
	public function get_app_strings(string $type = 'en') {
		// define the pll_get_strings callback 
		if ($type == 'en') {
			if (is_null($this->app_strings)) {
				$this->app_strings = apply_filters('system_app_strings_en', self::include_app_strings());
			}
			return $this->app_strings;
		} else {
			if (is_null($this->app_strings_ar)) {
				$this->app_strings_ar = apply_filters('system_app_strings_ar', self::include_ar_app_strings());
			}
			return $this->app_strings_ar;
		}
	}

	/**
	 * Create a polylang Group For Application Strings 
	 */
	public function create_polylang_group_for_app_strings() {
		if (Stacks_PolylangIntegration::is_installed()) {
			Stacks_PolylangIntegration::translate_strings($this->get_app_strings(), self::APP_STRINGS_GROUP_NAME);
		}
	}

	/**
	 * Get application Strings after translation
	 * 
	 * @return array
	 */
	public static function get_app_stings_translated() {
		$Stacks_App_Strings_Polylang_Translation = Stacks_App_Strings_Polylang_Translation::get_instance();

		$strings = $Stacks_App_Strings_Polylang_Translation->get_app_strings();

		$translated_strings = [];

		foreach ($strings as $key => $string) {
			$translated_strings[$key] = Stacks_PolylangIntegration::get_pll_registered_String_translation($string, get_locale());
		}

		return $translated_strings;
	}

	/**
	 * Set Arabic Translation first time user add a new Arabic language
	 * 
	 * @param void $args
	 */
	public function set_app_arabic_translation_strings() {
		/**
		 * First Get English Strings 
		 * Loop until you create an array of key ( Sanitized title ) and English translation that user updated
		 * Get Arabic Strings 
		 * Loop throw Arabic Strings with create a match to get translation and create an array for translation
		 */
		if( !get_option('arabic_strings_added') ) {
			// English Strings 
			$en_strings = $this->get_app_strings();

			// Arabic Strings 
			$ar_strings = $this->get_app_strings('ar');

			$arabic_strings_matched_with_en = [];

			foreach ($en_strings as $key => $en) {
				$arabic_strings_matched_with_en[] = [$en, $ar_strings[$key]];
			}

			$arabic_term = Stacks_PolylangIntegration::get_arabic_term_from_Langs();

			if ($arabic_term) {
				update_post_meta($arabic_term->mo_id, '_pll_strings_translations', $arabic_strings_matched_with_en);
				update_option('arabic_strings_added', true);
			}
		}
	}
}
