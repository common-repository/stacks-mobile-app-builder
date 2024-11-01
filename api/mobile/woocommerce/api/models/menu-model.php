<?php

class Stacks_Menu_Model {
	protected static $menu_items = null;

	public static function get_additional_menu_items() {
		$items = [];

		if (self::is_mobile_menu_defined()) {
			$items = self::get_mobile_menu_items();
		}

		$menu_items = apply_filters('stacks_additional_menu_items', $items);

		return array_map([self::class, 'format_menu_nav_item'], $menu_items);
	}

	protected static function is_mobile_menu_defined() {
		$menu_items = self::get_mobile_menu_items();

		if ($menu_items && is_array($menu_items)) {
			return true;
		}

		return false;
	}


	protected static function get_mobile_menu_items() {
		if (is_null(self::$menu_items)) {
			self::$menu_items = wp_get_nav_menu_items('mobile');
		}

		return self::$menu_items;
	}

	protected static function format_menu_nav_item($item) {
		return apply_filters('stacks_mobile_format_menu_nav_item', [
			'title' => $item->title,
			'url' => $item->url
		], $item);
	}
}
