<?php

class Stacks_PointsAndRewardsPageController extends Stacks_AbstractPagesController {

	protected $rest_api = '/points/background_image';

	public function get_background_image_id() {
		return 'points_rewards_background_image';
	}

	public function register_routes() {
		register_rest_route($this->get_api_endpoint(), $this->rest_api, array( // V3
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array($this, 'get_background_image'),
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
	public function get_background_image($request) {
		return $this->get_page_background_image();
	}
}
