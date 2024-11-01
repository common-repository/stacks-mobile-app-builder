<?php

class Stacks_AboutPageController extends Stacks_AbstractPagesController {

	protected $rest_api = '/about';

	public function register_routes() {
		register_rest_route($this->get_api_endpoint(), $this->rest_api, array( // V3
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array($this, 'get_about_us_page_settings'),
				'permission_callback' => array($this, 'get_items_permissions_check'),
				'args'                => $this->get_collection_params_get()
			),
			'schema' => array($this, 'get_public_item_schema'),
		));
	}

	/**
	 * Get Page Background Image
	 * 
	 * @param object $request
	 * 
	 * @return array
	 */
	public function get_about_us_page_settings($request) {
		return $this->return_success_response([
			'background'	=> Stacks_ContentSettings::get_about_us_background(),
			'logo'		=> Stacks_ContentSettings::get_about_us_site_logo(),
			'text'		=> Stacks_ContentSettings::get_about_text(),
			'social'	=> [],
		]);
	}
	/**
	 * Get Page Background Image
	 * 
	 * @param object $request
	 * 
	 * @return array
	 */
	public function get_about_us_page_settings_v1_v2($request) {
		return $this->return_success_response([
			'background'	=> Stacks_ContentSettings::get_about_us_background_v1_v2(),
			'logo'		=> Stacks_ContentSettings::get_about_us_site_logo_v1_v2(),
			'text'		=> Stacks_ContentSettings::get_about_text_v1_v2(),
			'social'	=> [],
		]);
	}
}
