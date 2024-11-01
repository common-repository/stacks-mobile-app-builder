<?php

class Stacks_PolylangIntegrationFilters {

	public function __construct() {
		if (Stacks_PolylangIntegration::is_installed()) {

			add_filter('api_before_get_products_model', [$this, 'add_lang_parameter']);

			add_filter('api_before_getting_ingredients', [$this, 'add_lang_parameter']);

			add_filter('before_getting_content_setting_list_products', [$this, 'add_default_lang_parameter']);

			add_filter('before_getting_popular_products', [$this, 'get_posts_translation']);

			add_filter('before_getting_new_products', [$this, 'get_posts_translation']);

			add_filter('api_before_getting_taxs', [$this, 'add_lang_parameter'], 10, 1);

			add_filter('api_add_language_parameter', [$this, 'add_lang_parameter'], 10, 1);

			add_filter('api_after_getting_cats', [$this, 'filter_tax'], 10, 1);

			add_filter('api_translation_filter_tax', [$this, 'filter_tax'], 10, 1);

			add_filter('api_after_getting_food_types', [$this, 'filter_tax'], 10, 1);

			add_action('stacks_api_after_submitting_order', [$this, 'assign_default_language_to_post'], 10, 1);

			add_action('stacks_additional_menu_items', [$this, 'filter_mobile_menu_items'], 10, 1);
		}
	}

	/**
	 * Filter mobile menu items to match current page language 
	 * 
	 * @param array $menu_items
	 * @return array
	 */
	public function filter_mobile_menu_items($menu_items) {
		if (empty($menu_items)) {
			return $menu_items;
		}


		// get only mobile menu items which lang is the current page lang
		return array_filter($menu_items, function ($item) {
			$page_id = $item->object_id;

			$lang = pll_get_post_language($page_id);

			if ($lang == Stacks_PolylangIntegrationFilters::get_slug_from_locale(get_locale()) && $item->object == 'page') {
				return true;
			}

			return false;
		});
	}

	public function assign_default_language_to_post($post_id) {
		pll_set_post_language($post_id, $this->get_current_lang_slug());
	}

	/**
	 * Get posts translation 
	 * 
	 * @param array $posts
	 * @return array
	 */
	public function get_posts_translation($posts) {

		if (!empty($posts) && apply_filters('get_posts_translation_per_current_language', true)) {

			foreach ($posts as $index => $post) {

				$post_id = isset($post->ID) ? $post->ID : absint($post);

				$translated_post_id = pll_get_post($post_id, $this->get_current_lang_slug());

				if (!$translated_post_id) {
					unset($posts[$index]);
					continue;
				} else {
					$posts[$index] = $translated_post_id;
				}
			}
		}

		return array_values($posts);
	}

	/**
	 * Filter Taxonomies according to language
	 * 
	 * @param array $taxs
	 * @param string $lang
	 * @return array
	 */
	public function filter_tax($taxs) {
		if (!empty($taxs)) {
			$taxs = array_values($taxs);

			if (pll_is_translated_taxonomy($taxs[0]->taxonomy)) {
				$current_lang_slug = $this->get_current_lang_slug();

				$taxs = array_filter($taxs, function ($tax) use ($current_lang_slug) {
					return pll_get_term_language($tax->term_id) == $current_lang_slug;
				});

				$taxs = array_values($taxs);
			}
		}

		return $taxs;
	}

	/**
	 * add default language parameter
	 * 
	 * @param array $args
	 * @return array
	 */
	public function add_default_lang_parameter($args) {
		$args['lang'] = pll_default_language('slug');

		return $args;
	}

	/**
	 * add language parameter to existing arguments 
	 * 
	 * @param array $args
	 * @return array
	 */
	public function add_lang_parameter($args) {
		$args['lang'] = $this->get_current_lang_slug();

		return $args;
	}

	/**
	 * Get current language slug
	 * 
	 * @return string
	 */
	private function get_current_lang_slug() {
		$locale = get_locale();

		$slug = self::get_slug_from_locale($locale);

		if (!$slug) {
			$slug = pll_default_language('slug');
		}

		return $slug;
	}


	public static function get_slug_from_locale($locale) {
		$langs = PLL()->model->get_languages_list();

		foreach ($langs as $lang) {
			if ($lang->locale == $locale) {
				return $lang->slug;
			}
		}

		return false;
	}
}

new Stacks_PolylangIntegrationFilters();
