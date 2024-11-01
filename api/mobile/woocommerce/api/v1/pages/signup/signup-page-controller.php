<?php

class Stacks_SignUpPageController extends Stacks_AbstractPagesController {

	protected $rest_api = '/pages/signup';

	public function register_routes() {
		register_rest_route($this->get_api_endpoint(), $this->rest_api, array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array($this, 'get_signup_data'),
				'permission_callback' => array($this, 'get_items_permissions_check'),
				'args'                => $this->get_collection_params_get()
			),
			'schema' => array($this, 'get_public_item_schema'),
		));
	}

	/**
	 * Get Page Background Image
	 * @param object $request
	 * @return array
	 */
	public function get_signup_data($request) {
		return $this->return_success_response([
			'bg_image' => Stacks_ContentSettings::get_login_signup_background(),
			'site_logo' => Stacks_ContentSettings::get_about_us_site_logo()
		]);
	}
	/**
	 * Get Page Background Image
	 * @param object $request
	 * @return array
	 */
	public function get_signup_data_v1_v2($request) {
		return $this->return_success_response([
			'bg_image' => Stacks_ContentSettings::get_login_signup_background_v1_v2(),
			'site_logo' => Stacks_ContentSettings::get_about_us_site_logo_v1_v2()
		]);
	}
}
