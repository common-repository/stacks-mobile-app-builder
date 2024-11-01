<?php

class Stacks_AppConfig extends Stacks_AbstractController {
	protected $rest_api = 'config';

	protected $allowed_params = [];

	public function register_routes() {
		register_rest_route($this->get_api_endpoint(), $this->rest_api, [ // V3
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [$this, 'get_app_config'],
				'permission_callback' => [$this, 'get_items_permissions_check'],
				'args'                => $this->get_collection_params()
			],
			'schema' => [$this, 'get_public_item_schema'],
		]);

		register_rest_route($this->get_api_endpoint(), 'receive_builder_image', [ // builder ui
			[
				'methods'             => "POST",
				'callback'            => [$this, 'receive_builder_image'],
				'args'                => $this->get_collection_params()
			],
		]);
	}

	public function get_app_config($request) {
		if ($GLOBALS['builder_api']->stacks_get_multisite_option('app_settings')) {
			$app_settings = $GLOBALS['builder_api']->stacks_get_multisite_option('app_settings');
		}
		if ($GLOBALS['builder_api']->stacks_get_multisite_option('content_settings')) {
			$content_settings = $GLOBALS['builder_api']->stacks_get_multisite_option('content_settings');
		}
		if ($GLOBALS['builder_api']->stacks_get_multisite_option('general_settings')) {
			$general_settings = $GLOBALS['builder_api']->stacks_get_multisite_option('general_settings');
		}
		if ($GLOBALS['builder_api']->stacks_get_multisite_option('global_settings')) {
			$global_settings = $GLOBALS['builder_api']->stacks_get_multisite_option('global_settings');
		}

		$this->map_request_parameters($request);
		$woocommerce_data = [];
		if (is_stacks_woocommerce_active()) {
			$woocommerce_data = [
				'currencySymbol'	=> get_woocommerce_currency_symbol(),
				'wishlistEnabled'	=> stacks_is_wishlist_plugin_activated(),
				'pointsEnabled'		=> stacks_is_points_plugin_activated(),
				'addonsEnabled'		=> stacks_is_addon_plugin_activated(),
				'cartItems'				=> $this->get_user_cart($request),
				'points'					=> $this->get_user_points(),
				'main_cat_enabled'	=> $content_settings->category_sub_page,
				'enable_website_checkout'		=> !empty($content_settings->enable_website_checkout) ? $content_settings->enable_website_checkout : '',
			];
		}
		$data =  [
			'sender_id'				=> $app_settings->android_sender_id,
			'facebook_app_id'	=> $app_settings->facebook_app_id,
			'lang'						=> get_locale(),
			'dir'							=> is_rtl() ? 'rtl' : 'ltr',
			'translations'		=> Stacks_PolylangIntegration::get_existing_languages(),
			'additional_menu_items' => array_values(Stacks_Menu_Model::get_additional_menu_items()),
			'home_layout'		=> 'elementor_builder',
			'mobile_webview_link'		=> $content_settings->mobile_webview_link,
			'header_background'		=> !empty($content_settings->header_background) ? $content_settings->header_background : '',
			'is_woocommerce_disabled' => !is_stacks_woocommerce_active(),
			'global_settings'		=> json_encode($global_settings),
			'disable_guest_user'	=> !empty($content_settings->disable_guest) ? $content_settings->disable_guest : '',
			'browser_type'	=> $content_settings->browser_type
		];
		foreach ($woocommerce_data as $key => $value) {
			$data[$key] = $value;
		}
		return $this->return_success_response(apply_filters('plates_before_config_response', $data));
	}


	/**
	 * Get user cart details 
	 * 
	 * @param object $request
	 * 
	 * @return array
	 */
	public function get_user_cart($request) {
		$cart_controller = new Stacks_CartController();

		$cart_contents = $cart_controller->cart_model->get_cart_contents();

		return [
			'contents' => array_values($cart_controller->organize_cart_contents($cart_contents)),
			'cart_details' => $cart_controller->get_cart_details()
		];
	}

	/**
	 * Get user number of points 
	 * 
	 * @return int
	 */
	public function get_user_points() {
		$user_id = get_current_user_id();

		if ($user_id && stacks_is_points_plugin_activated()) {
			return WC_Points_Rewards_Manager::get_users_points($user_id);
		}

		return 0;
	}

	/**
	 * Get schema
	 * 
	 * @return array
	 */
	public function get_collection_params() {
		return [];
	}

	public function get_public_item_schema() {
		return [];
	}

	public function receive_builder_image() {
		$uploaddir = wp_upload_dir()['basedir'] . '/stacks-uploads/builder';
		$uploadfile = $uploaddir . '/' . basename( sanitize_file_name( $_FILES['file']['name'] ) );
		$uploadurl = wp_upload_dir()['baseurl'] . '/stacks-uploads/builder';
		$uploadfile_url = $uploadurl . '/' . basename( sanitize_file_name( $_FILES['file']['name'] ) );

		if (!file_exists($uploaddir)) {
			mkdir($uploaddir, 0755, true);
		}

		if (move_uploaded_file(sanitize_text_field( $_FILES['file']['tmp_name'] ), $uploadfile)) {
			return str_replace("http://", "https://", $uploadfile_url);
		} else {
			return false;
		}
	}
}
